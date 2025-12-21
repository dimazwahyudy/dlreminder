<?php
class EventAPI {
    private $conn;
    private $user;

    public function __construct($conn, $user) {
        $this->conn = $conn;
        $this->user = $user;
    }

    private function ensure_event_schema() {
        $conn = $this->conn;
        $res = $conn->query("SHOW COLUMNS FROM event LIKE 'google_event_id'");
        if ($res && $res->num_rows === 0) {
            $conn->query("ALTER TABLE event ADD COLUMN google_event_id VARCHAR(255) DEFAULT NULL");
        }
        $res2 = $conn->query("SHOW COLUMNS FROM event LIKE 'visibility'");
        if ($res2 && $res2->num_rows === 0) {
            $conn->query("ALTER TABLE event ADD COLUMN visibility VARCHAR(32) NOT NULL DEFAULT 'self'");
        }
        $conn->query("CREATE TABLE IF NOT EXISTS event_google_map (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            google_event_id VARCHAR(255) DEFAULT NULL,
            pending_action VARCHAR(16) DEFAULT '',
            pending_payload TEXT DEFAULT NULL,
            UNIQUE KEY ux_event_user (event_id,user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Ensure columns exist for older schemas
        $respa = $conn->query("SHOW COLUMNS FROM event_google_map LIKE 'pending_action'");
        if ($respa && $respa->num_rows === 0) {
            $conn->query("ALTER TABLE event_google_map ADD COLUMN pending_action VARCHAR(16) DEFAULT ''");
        }
        $respb = $conn->query("SHOW COLUMNS FROM event_google_map LIKE 'pending_payload'");
        if ($respb && $respb->num_rows === 0) {
            $conn->query("ALTER TABLE event_google_map ADD COLUMN pending_payload TEXT DEFAULT NULL");
        }
    }

    private function get_google_token_row($uid) {
        $stmt = $this->conn->prepare("SELECT * FROM google_token WHERE user_id = ?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    private function refresh_access_token_for_user($uid, $refresh_token) {
        $post = http_build_query([
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        ]);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_POSTFIELDS=>$post]);
        $resp_raw = curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        if ($err) return null;
        $resp = json_decode($resp_raw, true);
        if (isset($resp['access_token'])) {
            $access = $resp['access_token'];
            $expires_in = $resp['expires_in'] ?? 3600;
            $refresh = $resp['refresh_token'] ?? $refresh_token;
            $stmt = $this->conn->prepare("UPDATE google_token SET access_token = ?, refresh_token = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE user_id = ?");
            $stmt->bind_param('ssii', $access, $refresh, $expires_in, $uid);
            $stmt->execute();
            return $access;
        }
        return null;
    }

    private function get_valid_access_token($uid) {
        $row = $this->get_google_token_row($uid);
        if (!$row) return null;
        if (!empty($row['expires_at']) && strtotime($row['expires_at']) > time() + 30) {
            return $row['access_token'];
        }
        if (!empty($row['refresh_token'])) {
            return $this->refresh_access_token_for_user($uid, $row['refresh_token']);
        }
        return null;
    }

    // helper: check whether a given user_id is registered as a dosen
    private function isUserDosen($uid) {
        $chk = $this->conn->prepare("SELECT 1 FROM dosen WHERE user_id = ?");
        $chk->bind_param('i', $uid);
        $chk->execute();
        $res = $chk->get_result();
        return ($res && $res->num_rows>0);
    }

    private function push_event_to_google_for_user($uid, $eventData, $existingId = null) {
        $access = $this->get_valid_access_token($uid);
        if (!$access) return ['ok'=>false, 'error'=>'no_access'];

        $payload = [
            'summary' => $eventData['title'],
            'description' => $eventData['description'] ?? '',
            'start' => ['dateTime' => $eventData['start'], 'timeZone' => 'Asia/Jakarta'],
            'end' => ['dateTime' => $eventData['end'], 'timeZone' => 'Asia/Jakarta']
        ];

        if ($existingId) { 
            $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events/' . urlencode($existingId);
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST=>'PUT', CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$access,'Content-Type: application/json'], CURLOPT_POSTFIELDS=>json_encode($payload)]);
        } else {
            $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$access,'Content-Type: application/json'], CURLOPT_POSTFIELDS=>json_encode($payload)]);
        }
        $raw = curl_exec($ch);
        $err = curl_errno($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = $raw ? json_decode($raw, true) : null;
        if ($err) return ['ok'=>false, 'errno'=>$err];
        if (in_array($http, [200,201,204])) {
            return ['ok'=>true, 'http'=>$http, 'id'=>$decoded['id'] ?? $existingId, 'raw'=>$decoded];
        }
        // If authentication error (401 / authError), remove stored token so user must re-authorize
        $authError = false;
        if ($http == 401) $authError = true;
        if (is_array($decoded) && isset($decoded['error']['errors']) && is_array($decoded['error']['errors'])) {
            foreach ($decoded['error']['errors'] as $e) {
                if (!empty($e['reason']) && in_array($e['reason'], ['authError','invalidCredentials'])) { $authError = true; break; }
            }
        }
        if ($authError) {
            try {
                $d = $this->conn->prepare("DELETE FROM google_token WHERE user_id = ?");
                $d->bind_param('i', $uid);
                $d->execute();
                $logEntry = date('c') . " DELETE_TOKEN user={$uid} reason=auth_error http={$http} resp=" . json_encode($decoded) . PHP_EOL;
            } catch (\Exception $ex) {
                // ignore
            }
            return ['ok'=>false, 'http'=>$http, 'raw'=>$decoded, 'deleted_token'=>true];
        }
        return ['ok'=>false, 'http'=>$http, 'raw'=>$decoded];
    }

    // Helper: delete event across mappings and Google calendars. Returns array result.
    private function perform_delete_with_google(int $event_id, array $ev) {
        $recipients = [];
        $vis = $ev['visibility'];
                if ($vis === 'dosen_mahasiswa') {
            // If the original creator is a dosen, target their students; otherwise (admin or unknown) target all mahasiswa
            $creator = (int)$ev['created_by'];
            $isDosen = $this->isUserDosen($creator);
            if ($isDosen) {
                $q = $this->conn->prepare("SELECT m.user_id FROM kelas_dosen kd JOIN mahasiswa m ON m.kelas_id = kd.kelas_id WHERE kd.dosen_user_id = ?");
                $q->bind_param('i', $creator); $q->execute(); $r = $q->get_result(); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
            } else {
                $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
            }
        } elseif ($vis === 'mahasiswa') { $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; }
        elseif ($vis === 'dosen') { $r = $this->conn->query("SELECT user_id FROM dosen"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; }
        elseif ($vis === 'all') { $r = $this->conn->query("SELECT user_id FROM dosen"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; }

        $recipients[] = (int)$ev['created_by'];

        $push_info = [];
        $ok_delete_user_ids = [];
        $failed = false;
        foreach (array_unique($recipients) as $target) {
            $m = $this->conn->prepare("SELECT google_event_id FROM event_google_map WHERE event_id = ? AND user_id = ?");
            $m->bind_param('ii', $event_id, $target); $m->execute(); $mr = $m->get_result(); $map = $mr->fetch_assoc(); $gid = $map['google_event_id'] ?? null;
            if ($gid) {
                $access = $this->get_valid_access_token($target);
                if ($access) {
                    $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . urlencode($gid));
                    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST=>'DELETE', CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$access]]);
                    $raw = curl_exec($ch); $err = curl_errno($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
                    $push_info[$target] = ['http'=>$http,'errno'=>$err,'raw'=> $raw];
                    if (($http >= 200 && $http < 300) || $http == 404) {
                        $ok_delete_user_ids[] = $target;
                    } else {
                        $failed = true;
                    }
                } else {
                    $push_info[$target] = ['error'=>'no_access'];
                    $failed = true;
                }
            } else {
                $push_info[$target] = ['skipped'=>'no_mapping'];
                $ok_delete_user_ids[] = $target;
            }
        }

        if ($failed) {
            $logDir = __DIR__ . '/../logs'; if (!is_dir($logDir)) @mkdir($logDir,0755,true);
            $logEntry = date('c') . " DELETE_PUSH_PARTIAL event_id={$event_id} res=" . json_encode($push_info) . PHP_EOL;
            @file_put_contents($logDir . '/google_push.log', $logEntry, FILE_APPEND);
            return ['status'=>false,'message'=>'Partial failure: some targets could not be deleted on Google. DB not modified.','push'=>$push_info];
        }

        // All deletions succeeded or were skipped -> safe to remove mappings and event from DB
        if (!empty($ok_delete_user_ids)) {
            $placeholders = implode(',', array_fill(0, count($ok_delete_user_ids), '?'));
            $types = str_repeat('i', count($ok_delete_user_ids));
            $params = $ok_delete_user_ids;
            $sql = "DELETE FROM event_google_map WHERE event_id = ? AND user_id IN ($placeholders)";
            $stmtDel = $this->conn->prepare($sql);
            $bind_types = 'i' . $types;
            $bind_values = array_merge([$bind_types], array_merge([$event_id], $params));
            $refs = [];
            foreach ($bind_values as $i => $v) { $refs[$i] = &$bind_values[$i]; }
            call_user_func_array([$stmtDel, 'bind_param'], $refs);
            $stmtDel->execute();
        }

        $d2 = $this->conn->prepare("DELETE FROM event WHERE id = ?"); $d2->bind_param('i',$event_id); $d2->execute();
        $logDir = __DIR__ . '/../logs'; if (!is_dir($logDir)) @mkdir($logDir,0755,true);
        $logEntry = date('c') . " DELETE_PUSH event_id={$event_id} res=" . json_encode($push_info) . PHP_EOL;
        @file_put_contents($logDir . '/google_push.log', $logEntry, FILE_APPEND);
        return ['status'=>true,'message'=>'Deleted','push'=>$push_info];
    }

    public function handle() {
        header('Content-Type: application/json');
        $this->ensure_event_schema();
        $method = $_SERVER['REQUEST_METHOD'];
        $user = $this->user;
        $uid = (int)$user['id'];
        $role = $user['role'];

        // GET
        if ($method === 'GET') {
            if ($role === 'admin') {
                // Admin: return all events. Use query() directly to avoid prepared-statement edge cases for simple static SQL
                $sql = "SELECT e.*, u.name as creator_name FROM event e JOIN user u ON u.id = e.created_by";
                $res = $this->conn->query($sql);
                $events = [];
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $events[] = [
                            'id' => (int)$row['id'],
                            'title' => $row['title'],
                            'description' => $row['description'],
                            'start' => $row['start_time'],
                            'end' => $row['end_time'],
                            'created_by' => (int)$row['created_by'],
                            'creator_name' => $row['creator_name'],
                            'visibility' => $row['visibility']
                        ];
                    }
                }
                $out = ["status" => true, "events" => $events];
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    $tot = $this->conn->query("SELECT COUNT(*) as c FROM event")->fetch_assoc();
                    $samples = [];
                    $r2 = $this->conn->query("SELECT * FROM event ORDER BY id DESC LIMIT 20");
                    if ($r2) while ($rr = $r2->fetch_assoc()) $samples[] = $rr;
                    $out['debug'] = ['total_events' => (int)$tot['c'], 'sample' => $samples, 'query_role' => $role];
                }
                echo json_encode($out);
                exit;
            } elseif ($role === 'dosen') {
                $sql = "SELECT e.*, u.name as creator_name FROM event e JOIN user u ON u.id = e.created_by WHERE e.created_by = ? OR e.visibility = 'all'";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $uid);
            } else {
                $sql = "SELECT e.*, u.name as creator_name
                     FROM event e
                     JOIN user u ON u.id = e.created_by
                     LEFT JOIN kelas_dosen kd ON kd.dosen_user_id = e.created_by
                     LEFT JOIN mahasiswa m ON m.user_id = ? AND m.kelas_id = kd.kelas_id
                     WHERE (e.created_by = ?)
                       OR (e.visibility IN ('all','mahasiswa'))
                       OR (e.visibility = 'dosen_mahasiswa' AND m.user_id IS NOT NULL)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('ii', $uid, $uid);
            }

            if (isset($stmt)) {
                $stmt->execute();
                $res = $stmt->get_result();
                $events = [];
                while ($row = $res->fetch_assoc()) {
                    $events[] = [
                        'id' => (int)$row['id'],
                        'title' => $row['title'],
                        'description' => $row['description'],
                        'start' => $row['start_time'],
                        'end' => $row['end_time'],
                        'created_by' => (int)$row['created_by'],
                        'creator_name' => $row['creator_name'],
                        'visibility' => $row['visibility']
                    ];
                }
                $out = ["status" => true, "events" => $events];
                if (isset($_GET['debug']) && $_GET['debug'] == '1' && $role === 'admin') {
                    $tot = $this->conn->query("SELECT COUNT(*) as c FROM event")->fetch_assoc();
                    $samples = [];
                    $r2 = $this->conn->query("SELECT * FROM event ORDER BY id DESC LIMIT 20");
                    if ($r2) while ($rr = $r2->fetch_assoc()) $samples[] = $rr;
                    $out['debug'] = ['total_events' => (int)$tot['c'], 'sample' => $samples, 'query_role' => $role];
                }
                echo json_encode($out);
            }
            exit;
        }

        // POST
        if ($method === 'POST') {
            $action = $_POST['action'] ?? 'create';

            if ($action === 'sync_me') {
                $push_results = [];
                $q = $this->conn->prepare("SELECT * FROM event WHERE created_by = ?");
                $q->bind_param('i', $uid); $q->execute(); $r = $q->get_result();
                while ($ev = $r->fetch_assoc()) {
                    $event_id = (int)$ev['id'];
                    $m = $this->conn->prepare("SELECT google_event_id FROM event_google_map WHERE event_id = ? AND user_id = ?");
                    $m->bind_param('ii', $event_id, $uid); $m->execute(); $mr = $m->get_result();
                    $mapped = $mr->fetch_assoc();
                    $existing = $mapped['google_event_id'] ?? null;
                    $eventData = ['title'=>$ev['title'],'description'=>$ev['description'],'start'=>date('c',strtotime($ev['start_time'])),'end'=>date('c',strtotime($ev['end_time']))];
                    if (!$existing) {
                        $res_push = $this->push_event_to_google_for_user($uid, $eventData, null);
                        $push_results[$event_id] = $res_push;
                        if ($res_push['ok'] && !empty($res_push['id'])) {
                            $up = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id)");
                            $up->bind_param('iis', $event_id, $uid, $res_push['id']); $up->execute();
                            $u = $this->conn->prepare("UPDATE event SET google_event_id = ? WHERE id = ?"); $u->bind_param('si', $res_push['id'], $event_id); $u->execute();
                        }
                    } else {
                        $push_results[$event_id] = ['ok'=>true,'note'=>'already_mapped','id'=>$existing];
                    }
                }
                echo json_encode(["status"=>true,"message"=>"Sync completed","push"=>$push_results]);
                exit;
            }

                if ($action === 'update') {
                $this->ensure_event_schema();
                $event_id = (int)($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $desc = trim($_POST['description'] ?? '');

                if (empty($event_id) || empty($title)) {
                    echo json_encode(["status" => false, "message" => "Data tidak lengkap"]);
                    exit;
                }

                $q = $this->conn->prepare("SELECT * FROM event WHERE id = ?");
                $q->bind_param('i', $event_id); $q->execute(); $res = $q->get_result();
                if (!$res->num_rows) { http_response_code(404); echo json_encode(["status"=>false,"message"=>"Not found","id"=>$event_id]); exit; }
                $ev = $res->fetch_assoc();

                if ($ev['created_by'] != $this->user['id'] && $this->user['role'] !== 'admin') {
                    http_response_code(403); echo json_encode(["status"=>false,"message"=>"Forbidden"]); exit;
                }

                $start = $_POST['start'] ?? $ev['start_time'];
                $end = $_POST['end'] ?? $ev['end_time'];

                $role_local = $this->user['role'];
                $visibility = $ev['visibility'];
                if ($role_local === 'mahasiswa') { $visibility = 'self'; }
                elseif ($role_local === 'dosen') {
                    $chk_dosen = isset($_POST['opt_dosen']);
                    $chk_mahasiswa = isset($_POST['opt_mahasiswa']);
                    if ($chk_dosen && $chk_mahasiswa) $visibility = 'dosen_mahasiswa';
                    elseif ($chk_dosen) $visibility = 'dosen';
                    elseif ($chk_mahasiswa) $visibility = 'mahasiswa';
                } elseif ($role_local === 'admin') {
                    $chk_admin = isset($_POST['chk_admin']); $chk_dosen = isset($_POST['chk_dosen']); $chk_mahasiswa = isset($_POST['chk_mahasiswa']);
                    if ($chk_dosen && $chk_mahasiswa) $visibility = 'all';
                    elseif ($chk_dosen) $visibility = 'dosen';
                    elseif ($chk_mahasiswa) $visibility = 'mahasiswa';
                    else $visibility = $ev['visibility'];
                }

                $stmt = $this->conn->prepare("UPDATE event SET title=?, description=?, start_time=?, end_time=?, visibility=? WHERE id=?");
                $stmt->bind_param('sssssi', $title, $desc, $start, $end, $visibility, $event_id);
                $ok = $stmt->execute();
                if (!$ok) { http_response_code(500); echo json_encode(["status"=>false,"message"=>$this->conn->error]); exit; }

                $push_results = [];
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $recipients = [];
                if ($visibility === 'dosen_mahasiswa') {
                    // if original creator is dosen -> target their students, otherwise target all mahasiswa (admin-created)
                    $creator = (int)$ev['created_by'];
                    if ($this->isUserDosen($creator)) {
                        $q = $this->conn->prepare("SELECT m.user_id FROM kelas_dosen kd JOIN mahasiswa m ON m.kelas_id = kd.kelas_id WHERE kd.dosen_user_id = ?");
                        $q->bind_param('i', $creator); $q->execute(); $r = $q->get_result();
                        while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                    } else {
                        $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                    }
                } elseif ($visibility === 'mahasiswa') {
                    if ($role_local === 'dosen') {
                        // when a dosen performs this action, target students of the event creator if they are a dosen; otherwise target students of current user
                        $creator = (int)$ev['created_by'];
                        if ($this->isUserDosen($creator)) {
                            $q = $this->conn->prepare("SELECT m.user_id FROM kelas_dosen kd JOIN mahasiswa m ON m.kelas_id = kd.kelas_id WHERE kd.dosen_user_id = ?");
                            $q->bind_param('i', $creator); $q->execute(); $r = $q->get_result();
                            while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                        } else {
                            $q = $this->conn->prepare("SELECT m.user_id FROM kelas_dosen kd JOIN mahasiswa m ON m.kelas_id = kd.kelas_id WHERE kd.dosen_user_id = ?");
                            $q->bind_param('i', $uid); $q->execute(); $r = $q->get_result();
                            while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                        }
                    } elseif ($role_local === 'admin') {
                        $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                    }
                } elseif ($visibility === 'dosen') {
                    if ($role_local === 'admin') {
                        $r = $this->conn->query("SELECT user_id FROM dosen"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                    }
                } elseif ($visibility === 'all') {
                    $r = $this->conn->query("SELECT user_id FROM dosen"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                    $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                }

                $recipients[] = (int)$ev['created_by'];

                foreach (array_unique($recipients) as $uid_target) {
                    $m = $this->conn->prepare("SELECT google_event_id FROM event_google_map WHERE event_id = ? AND user_id = ?");
                    $m->bind_param('ii', $event_id, $uid_target); $m->execute(); $mr = $m->get_result();
                    $map = $mr->fetch_assoc();
                    $existing = $map['google_event_id'] ?? null;
                    $eventData = ['title'=>$title,'description'=>$desc,'start'=>date('c',strtotime($start)),'end'=>date('c',strtotime($end))];

                    if ($existing) {
                        $res_push = $this->push_event_to_google_for_user($uid_target, $eventData, $existing);
                        if (!$res_push['ok'] && isset($res_push['http']) && $res_push['http'] == 404) {
                            $res_push_create = $this->push_event_to_google_for_user($uid_target, $eventData, null);
                            if ($res_push_create['ok'] && !empty($res_push_create['id'])) {
                                $up = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id)");
                                $up->bind_param('iis', $event_id, $uid_target, $res_push_create['id']); $up->execute();
                            }
                            $res_push = $res_push_create;
                        } elseif (!$res_push['ok'] && isset($res_push['error']) && $res_push['error'] === 'no_access') {
                            // recipient has no valid token; ensure mapping exists so we can push later when they connect
                            $ins = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,NULL) ON DUPLICATE KEY UPDATE google_event_id = google_event_id");
                            $ins->bind_param('ii', $event_id, $uid_target); $ins->execute();
                            // skip
                            $logEntry = date('c') . " UPDATE_PUSH target={$uid_target} event_id={$event_id} res=" . json_encode($res_push) . PHP_EOL;
                            @file_put_contents($logDir . '/google_push.log', $logEntry, FILE_APPEND);
                        }
                    } else {
                        $token_row = $this->get_google_token_row($uid_target);
                            if ($token_row) {
                                $res_push = $this->push_event_to_google_for_user($uid_target, $eventData, null);
                                if ($res_push['ok'] && !empty($res_push['id'])) {
                                    $ins = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id)");
                                    $ins->bind_param('iis', $event_id, $uid_target, $res_push['id']); $ins->execute();
                                }
                                $logEntry = date('c') . " UPDATE_PUSH target={$uid_target} event_id={$event_id} res=" . json_encode($res_push) . PHP_EOL;
                                @file_put_contents($logDir . '/google_push.log', $logEntry, FILE_APPEND);
                            } else {
                            // create a placeholder mapping so we know this user should receive this event once they connect Google
                            $ins = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,NULL) ON DUPLICATE KEY UPDATE google_event_id = google_event_id");
                            $ins->bind_param('ii', $event_id, $uid_target); $ins->execute();
                            $res_push = ['ok'=>false,'error'=>'no_access'];
                        }
                    }
                    $push_results[$uid_target] = $res_push ?? ['skipped'=>true];
                }

                echo json_encode(["status"=>true,"message"=>"Updated","push"=>$push_results]);
                exit;
            }

            // --- USER: push pending mappings to current user's Google calendar ---
            if ($action === 'sync_pending') {
                // push all events mapped to this user that have no google_event_id yet
                $rows = $this->conn->prepare("SELECT eg.event_id, e.title, e.description, e.start_time, e.end_time FROM event_google_map eg JOIN event e ON e.id = eg.event_id WHERE eg.user_id = ? AND (eg.google_event_id IS NULL OR eg.google_event_id = '')");
                $rows->bind_param('i', $uid); $rows->execute(); $r = $rows->get_result();
                $summary = [];
                while ($rec = $r->fetch_assoc()) {
                    $eid = (int)$rec['event_id'];
                    $edata = ['title'=>$rec['title'],'description'=>$rec['description'],'start'=>date('c',strtotime($rec['start_time'])),'end'=>date('c',strtotime($rec['end_time']))];
                    $res_push = $this->push_event_to_google_for_user($uid, $edata, null);
                    if ($res_push['ok'] && !empty($res_push['id'])) {
                        $u = $this->conn->prepare("UPDATE event_google_map SET google_event_id = ? WHERE event_id = ? AND user_id = ?");
                        $u->bind_param('sii', $res_push['id'], $eid, $uid); $u->execute();
                        // also update legacy event.google_event_id for this event (optional, only if created_by == user)
                        $this->conn->query("UPDATE event SET google_event_id = '".$this->conn->real_escape_string($res_push['id'])."' WHERE id = ".$eid." AND created_by = ".$uid);
                    }
                    $summary[$eid] = $res_push;
                }
                echo json_encode(['status'=>true,'message'=>'Sync pending completed','push'=>$summary]);
                exit;
            }

            // --- USER: import events from Google Calendar into system (visibility = self) ---
            if ($action === 'import_google') {
                $res = $this->importGoogleForUser($uid);
                echo json_encode($res);
                exit;
            }

            // delete via POST
            if ($action === 'delete') {
                $this->ensure_event_schema();
                $event_id = (int)($_POST['id'] ?? 0);
                if (!$event_id) { http_response_code(422); echo json_encode(["status"=>false,"message"=>"Missing id"]); exit; }

                $q = $this->conn->prepare("SELECT * FROM event WHERE id = ?");
                $q->bind_param('i', $event_id); $q->execute(); $res = $q->get_result();
                if (!$res->num_rows) { http_response_code(404); echo json_encode(["status"=>false,"message"=>"Not found","id"=>$event_id]); exit; }
                $ev = $res->fetch_assoc();
                if ($ev['created_by'] != $this->user['id'] && $this->user['role'] !== 'admin') { http_response_code(403); echo json_encode(["status"=>false,"message"=>"Forbidden"]); exit; }

                $result = $this->perform_delete_with_google($event_id, $ev);
                echo json_encode($result);
                exit;
            }

            // CREATE
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $start_input = $_POST['start'] ?? null;
            $end_input = $_POST['end'] ?? null;

            if (!$title || !$start_input || !$end_input) { http_response_code(422); echo json_encode(["status" => false, "message" => "Data wajib diisi (judul, mulai & selesai)"]); exit; }

            // Parse start
            if (strpos($start_input, 'T') !== false || strpos($start_input, ' ') !== false) {
                $start_dt = date('Y-m-d H:i:s', strtotime($start_input));
            } else {
                $start_dt = date('Y-m-d', strtotime($start_input)) . ' 00:00:00';
            }

            // Parse end
            if (strpos($end_input, 'T') !== false || strpos($end_input, ' ') !== false) {
                $end_dt = date('Y-m-d H:i:s', strtotime($end_input));
            } else {
                $end_dt = date('Y-m-d', strtotime($end_input)) . ' 23:59:59';
            }

            // Ensure end is not earlier than start
            if (strtotime($end_dt) < strtotime($start_dt)) {
                // adjust end to be start + 1 hour
                $end_dt = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($start_dt)));
            }

            $visibility = 'self';
            if ($role === 'dosen') {
                $chk_dosen = isset($_POST['opt_dosen']);
                $chk_mahasiswa = isset($_POST['opt_mahasiswa']);
                if ($chk_dosen && $chk_mahasiswa) $visibility = 'dosen_mahasiswa';
                elseif ($chk_mahasiswa) $visibility = 'mahasiswa';
                else $visibility = 'dosen';
            } elseif ($role === 'admin') {
                $chk_dosen = isset($_POST['chk_dosen']);
                $chk_mahasiswa = isset($_POST['chk_mahasiswa']);
                if ($chk_dosen && $chk_mahasiswa) $visibility = 'all';
                elseif ($chk_dosen) $visibility = 'dosen';
                elseif ($chk_mahasiswa) $visibility = 'mahasiswa';
            }

            $this->ensure_event_schema();
            $stmt = $this->conn->prepare("INSERT INTO event (title,description,start_time,end_time,created_by,visibility) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssssis', $title, $description, $start_dt, $end_dt, $uid, $visibility);
            if (!$stmt->execute()) { echo json_encode(["status" => false, "message" => "Gagal: " . $this->conn->error]); exit; }
            $id = $stmt->insert_id;

            $eventData = ['title'=>$title,'description'=>$description,'start'=>date('c',strtotime($start_dt)),'end'=>date('c',strtotime($end_dt))];
            $push_results = [];

            // Check creator token before attempting push. get_valid_access_token will try to refresh when possible.
            $access_creator = $this->get_valid_access_token($uid);

            if ($access_creator) {
                $res_creator = $this->push_event_to_google_for_user($uid, $eventData, null);
                $logEntry = date('c') . " CREATE_PUSH creator={$uid} event_id={$id} res=" . json_encode($res_creator) . PHP_EOL;
                $push_results[] = ['user'=>$uid,'res'=>$res_creator];
                if ($res_creator['ok'] && !empty($res_creator['id'])) {
                    $p = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id)");
                    $p->bind_param('iis', $id, $uid, $res_creator['id']); $p->execute();
                    $u = $this->conn->prepare("UPDATE event SET google_event_id = ? WHERE id = ?"); $u->bind_param('si', $res_creator['id'], $id); $u->execute();
                }
            } else {
                // No valid token: save placeholder mapping for creator and instruct frontend to re-auth
                $insc = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,NULL) ON DUPLICATE KEY UPDATE google_event_id = google_event_id");
                $insc->bind_param('ii', $id, $uid); $insc->execute();
                $res_creator = ['ok'=>false,'note'=>'no_access','reauth_required'=>true,'reauth_url'=>'google_login.php'];
                $logEntry = date('c') . " CREATE_PUSH creator={$uid} event_id={$id} res=" . json_encode($res_creator) . PHP_EOL;
                $push_results[] = ['user'=>$uid,'res'=>$res_creator];
            }

            $recipients = [];
            if ($visibility === 'dosen_mahasiswa') {
                // if creator is a dosen, target their students; otherwise (admin-created) target all mahasiswa
                if ($this->isUserDosen($uid)) {
                    $q = $this->conn->prepare("SELECT m.user_id FROM kelas_dosen kd JOIN mahasiswa m ON m.kelas_id = kd.kelas_id WHERE kd.dosen_user_id = ?");
                    $q->bind_param('i', $uid); $q->execute(); $r = $q->get_result(); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                } else {
                    $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id'];
                }
            } elseif ($visibility === 'mahasiswa') {
                if ($role === 'dosen') { $q = $this->conn->prepare("SELECT m.user_id FROM kelas_dosen kd JOIN mahasiswa m ON m.kelas_id = kd.kelas_id WHERE kd.dosen_user_id = ?"); $q->bind_param('i', $uid); $q->execute(); $r = $q->get_result(); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; }
                elseif ($role === 'admin') { $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; }
            } elseif ($visibility === 'dosen') {
                if ($role === 'admin') { $r = $this->conn->query("SELECT user_id FROM dosen"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; }
            } elseif ($visibility === 'all') { $r = $this->conn->query("SELECT user_id FROM dosen"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; $r = $this->conn->query("SELECT user_id FROM mahasiswa"); while ($row = $r->fetch_assoc()) $recipients[] = (int)$row['user_id']; }

            // For creation: push to recipients only if they have a valid Google token; otherwise create placeholder mappings
            foreach (array_unique($recipients) as $target) {
                if ($target === $uid) continue; // creator already pushed above
                $access = $this->get_valid_access_token($target);
                if ($access) {
                    $res_push = $this->push_event_to_google_for_user($target, $eventData, null);
                    // log recipient push attempt
                    $logEntry = date('c') . " CREATE_PUSH target={$target} event_id={$id} res=" . json_encode($res_push) . PHP_EOL;
                    $push_results[] = ['user'=>$target,'res'=>$res_push];
                    if ($res_push['ok'] && !empty($res_push['id'])) {
                        $ins = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id)");
                        $ins->bind_param('iis', $id, $target, $res_push['id']); $ins->execute();
                    } else {
                        // couldn't push (maybe token refresh failed) -> ensure placeholder mapping exists
                        $ins = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,NULL) ON DUPLICATE KEY UPDATE google_event_id = google_event_id");
                        $ins->bind_param('ii', $id, $target); $ins->execute();
                    }
                } else {
                    // no token -> placeholder mapping, will be pushed when user connects
                    $ins = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,NULL) ON DUPLICATE KEY UPDATE google_event_id = google_event_id");
                    $ins->bind_param('ii', $id, $target); $ins->execute();
                    $res_push = ['ok'=>false,'note'=>'no_token_placeholder_created'];
                    $logEntry = date('c') . " CREATE_PUSH target={$target} event_id={$id} res=" . json_encode($res_push) . PHP_EOL;
                    $push_results[] = ['user'=>$target,'res'=>$res_push];
                }
            }

            if (isset($_POST['repeat_monthly'])) {
                for ($m = 1; $m <= 11; $m++) {
                    $next_end = date('Y-m-d H:i:s', strtotime("+{$m} month", strtotime($end_dt)));
                    $next_start = date('Y-m-d', strtotime($next_end)) . ' 00:00:00';
                    $ins = $this->conn->prepare("INSERT INTO event (title,description,start_time,end_time,created_by,visibility) VALUES (?,?,?,?,?,?)");
                    $ins->bind_param('ssssis', $title, $description, $next_start, $next_end, $uid, $visibility);
                    if ($ins->execute()) {
                        $nid = $ins->insert_id;
                        $eventData2 = ['title'=>$title,'description'=>$description,'start'=>date('c',strtotime($next_start)),'end'=>date('c',strtotime($next_end))];
                        $res_creator2 = $this->push_event_to_google_for_user($uid, $eventData2, null);
                        if ($res_creator2['ok'] && !empty($res_creator2['id'])) {
                            $p2 = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id)");
                            $p2->bind_param('iis', $nid, $uid, $res_creator2['id']); $p2->execute();
                        }
                        // For repeat occurrences: push to recipients who have tokens, otherwise create placeholder mappings
                        foreach (array_unique($recipients) as $target2) {
                            if ($target2 === $uid) continue;
                            $access2 = $this->get_valid_access_token($target2);
                            if ($access2) {
                                $rp = $this->push_event_to_google_for_user($target2, $eventData2, null);
                                if ($rp['ok'] && !empty($rp['id'])) {
                                    $insmap = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id)");
                                    $insmap->bind_param('iis', $nid, $target2, $rp['id']); $insmap->execute();
                                } else {
                                    $insmap = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,NULL) ON DUPLICATE KEY UPDATE google_event_id = google_event_id");
                                    $insmap->bind_param('ii', $nid, $target2); $insmap->execute();
                                }
                            } else {
                                $insmap = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,NULL) ON DUPLICATE KEY UPDATE google_event_id = google_event_id");
                                $insmap->bind_param('ii', $nid, $target2); $insmap->execute();
                            }
                        }
                    }
                }
            }

            echo json_encode(["status" => true, "message" => "Event created", "id" => $id, "push" => $push_results]);
            exit;
        }

        // DELETE method
        if ($method === 'DELETE') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $this->ensure_event_schema();
                $event_id = (int)$id;
                $q = $this->conn->prepare("SELECT * FROM event WHERE id = ?");
                $q->bind_param('i', $event_id); $q->execute(); $res = $q->get_result();
                if (!$res->num_rows) { http_response_code(404); echo json_encode(["status"=>false,"message"=>"Not found","id"=>$event_id]); exit; }
                $ev = $res->fetch_assoc();
                if ($ev['created_by'] != $this->user['id'] && $this->user['role'] !== 'admin') { http_response_code(403); echo json_encode(["status"=>false,"message"=>"Forbidden"]); exit; }

                $result = $this->perform_delete_with_google($event_id, $ev);
                echo json_encode($result);
            } else {
                echo json_encode(["status" => false, "message" => "ID tidak ditemukan"]);
            }
            exit;
        }
    }

    // Public helper to run sync_pending for a specific user (used by OAuth callback)
    public function syncPendingForUser($userId) {
        $uid = (int)$userId;
        $rows = $this->conn->prepare("SELECT eg.event_id, eg.pending_action, eg.pending_payload, eg.google_event_id, e.title, e.description, e.start_time, e.end_time FROM event_google_map eg JOIN event e ON e.id = eg.event_id WHERE eg.user_id = ? AND (eg.google_event_id IS NULL OR eg.google_event_id = '' OR (eg.pending_action IS NOT NULL AND eg.pending_action <> ''))");
        $rows->bind_param('i', $uid); $rows->execute(); $r = $rows->get_result();
        $summary = [];
        $logDir = __DIR__ . '/../logs'; if (!is_dir($logDir)) @mkdir($logDir,0755,true);
        while ($rec = $r->fetch_assoc()) {
            $eid = (int)$rec['event_id'];
            $pending = $rec['pending_action'] ?? null;
            $payload_raw = $rec['pending_payload'] ?? null;
            $existing_gid = $rec['google_event_id'] ?? null;
            $defaultData = ['title'=>$rec['title'],'description'=>$rec['description'],'start'=>date('c',strtotime($rec['start_time'])),'end'=>date('c',strtotime($rec['end_time']))];

            // Determine data to push: prefer pending_payload if present
            $edata = $defaultData;
            if ($payload_raw) {
                $decoded = json_decode($payload_raw, true);
                if (is_array($decoded)) $edata = array_merge($edata, $decoded);
            }

            $result = null;
            if (!$pending) {
                // normal create (no google_event_id yet)
                $result = $this->push_event_to_google_for_user($uid, $edata, null);
                if ($result['ok'] && !empty($result['id'])) {
                    $u = $this->conn->prepare("UPDATE event_google_map SET google_event_id = ?, pending_action = NULL, pending_payload = NULL WHERE event_id = ? AND user_id = ?");
                    $u->bind_param('sii', $result['id'], $eid, $uid); $u->execute();
                    // update legacy event.google_event_id for events created by this user
                    $this->conn->query("UPDATE event SET google_event_id = '".$this->conn->real_escape_string($result['id'])."' WHERE id = ".$eid." AND created_by = ".$uid);
                }
            } else {
                if ($pending === 'create' || $pending === 'update') {
                    // if we have an existing gid, try update, otherwise try create
                    $existingId = $existing_gid ? $existing_gid : null;
                    $result = $this->push_event_to_google_for_user($uid, $edata, $existingId);
                    if ($result['ok'] && !empty($result['id'])) {
                        $u = $this->conn->prepare("UPDATE event_google_map SET google_event_id = ?, pending_action = NULL, pending_payload = NULL WHERE event_id = ? AND user_id = ?");
                        $u->bind_param('sii', $result['id'], $eid, $uid); $u->execute();
                        $this->conn->query("UPDATE event SET google_event_id = '".$this->conn->real_escape_string($result['id'])."' WHERE id = ".$eid." AND created_by = ".$uid);
                    }
                } elseif ($pending === 'delete') {
                    // attempt to delete the user's event from their Google calendar
                    if ($existing_gid) {
                        $access = $this->get_valid_access_token($uid);
                        if ($access) {
                            $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . urlencode($existing_gid));
                            curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST=>'DELETE', CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$access]]);
                            $raw = curl_exec($ch); $err = curl_errno($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
                            if (($http >= 200 && $http < 300) || $http == 404) {
                                $d = $this->conn->prepare("DELETE FROM event_google_map WHERE event_id = ? AND user_id = ?");
                                $d->bind_param('ii', $eid, $uid); $d->execute();
                                $result = ['ok'=>true,'http'=>$http];
                            } else {
                                $result = ['ok'=>false,'http'=>$http,'raw'=>$raw];
                            }
                        } else {
                            // still no access - leave pending
                            $result = ['ok'=>false,'error'=>'no_access'];
                        }
                    } else {
                        // no mapping - nothing to delete
                        $d = $this->conn->prepare("DELETE FROM event_google_map WHERE event_id = ? AND user_id = ?");
                        $d->bind_param('ii', $eid, $uid); $d->execute();
                        $result = ['ok'=>true,'skipped'=>'no_mapping'];
                    }
                }
            }

            $logEntry = date('c') . " SYNC_PENDING user={$uid} event_id={$eid} pending=" . ($pending ?? 'null') . " res=" . json_encode($result) . PHP_EOL;
            @file_put_contents($logDir . '/google_push.log', $logEntry, FILE_APPEND);
            $summary[$eid] = $result;
        }
        return $summary;
    }

    // Public helper: import events from user's Google Calendar into system (visibility = self)
    public function importGoogleForUser($userId) {
        $uid = (int)$userId;
        $access = $this->get_valid_access_token($uid);
        if (!$access) return ['status'=>false,'message'=>'no_access'];

        $timeMin = urlencode(date('c', strtotime('-1 year')));
        $timeMax = urlencode(date('c', strtotime('+1 year')));
        $url = "https://www.googleapis.com/calendar/v3/calendars/primary/events?singleEvents=true&orderBy=startTime&timeMin={$timeMin}&timeMax={$timeMax}&maxResults=2500";
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$access]]);
        $raw = curl_exec($ch); $err = curl_errno($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($err || $http >= 400) { $decoded = $raw ? json_decode($raw, true) : null; return ['status'=>false,'http'=>$http,'raw'=>$decoded]; }
        $decoded = json_decode($raw, true);
        $imported = [];
        if (!empty($decoded['items'])) {
            foreach ($decoded['items'] as $g) {
                if (empty($g['start'])) continue;
                $gstart = $g['start']['dateTime'] ?? ($g['start']['date'] . 'T00:00:00');
                $gend = $g['end']['dateTime'] ?? ($g['end']['date'] . 'T23:59:59');
                $gId = $g['id'];
                $chk = $this->conn->prepare("SELECT 1 FROM event_google_map WHERE user_id = ? AND google_event_id = ?");
                $chk->bind_param('is', $uid, $gId); $chk->execute(); $res = $chk->get_result();
                if ($res->num_rows) continue;

                $ins = $this->conn->prepare("INSERT INTO event (title,description,start_time,end_time,created_by,visibility,google_event_id) VALUES (?,?,?,?,?,?,?)");
                $title = $g['summary'] ?? '(No title)';
                $desc = $g['description'] ?? '';
                $start_time = date('Y-m-d H:i:s', strtotime($gstart));
                $end_time = date('Y-m-d H:i:s', strtotime($gend));
                $vis = 'self';
                $ins->bind_param('ssssiis', $title, $desc, $start_time, $end_time, $uid, $vis, $gId);
                if ($ins->execute()) {
                    $eid = $ins->insert_id;
                    $map = $this->conn->prepare("INSERT INTO event_google_map (event_id,user_id,google_event_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id)");
                    $map->bind_param('iis', $eid, $uid, $gId); $map->execute();
                    $imported[] = $eid;
                }
            }
        }
        return ['status'=>true,'imported'=>$imported];
    }
}
?>