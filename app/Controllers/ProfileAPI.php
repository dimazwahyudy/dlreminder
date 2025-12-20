<?php
class ProfileAPI {
    private $conn;
    private $user_id;

    public function __construct($conn, $user) {
        $this->conn = $conn;
        $this->user_id = $user['id'] ?? null;
    }

    public function handle() {
        header('Content-Type: application/json');
        if (!$this->user_id) {
            http_response_code(401);
            echo json_encode(["status"=>false, "message"=>"Unauthorized"]);
            exit;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET') return $this->getProfile();
        if ($method === 'POST') return $this->updateProfile();
    }

    private function getProfile() {
        $stmt = $this->conn->prepare("SELECT id, name, email FROM user WHERE id = ?");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $userRow = $stmt->get_result()->fetch_assoc();

        $isDosen = $this->conn->prepare("SELECT 1 FROM dosen WHERE user_id = ?");
        $isDosen->bind_param('i', $this->user_id); $isDosen->execute(); $isD = $isDosen->get_result()->num_rows > 0;
        $userRow['is_dosen'] = $isD;
        if ($isD) {
            $classes = [];
            $r = $this->conn->query("SELECT id, kode_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
            while ($c = $r->fetch_assoc()) {
                $classes[] = [
                    'id' => (int)$c['id'],
                    'kode_kelas' => $c['kode_kelas'],
                    'nama_kelas' => $c['nama_kelas']
                ];
            }
            $sel = [];
            $s = $this->conn->prepare("SELECT kelas_id FROM kelas_dosen WHERE dosen_user_id = ?");
            $s->bind_param('i', $this->user_id); $s->execute(); $sr = $s->get_result();
            while ($row = $sr->fetch_assoc()) $sel[] = (int)$row['kelas_id'];
            $userRow['classes'] = $classes;
            $userRow['selected_classes'] = $sel;
        }

        echo json_encode($userRow);
        exit;
    }

    private function updateProfile() {
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name)) { echo json_encode(["status"=>false, "message"=>"Nama tidak boleh kosong"]); exit; }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE user SET name = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $hash, $this->user_id);
        } else {
            $stmt = $this->conn->prepare("UPDATE user SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $this->user_id);
        }

        if ($stmt->execute()) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION['user']['name'] = $name;
            if (isset($_POST['classes']) && is_array($_POST['classes'])) {
                $chk = $this->conn->prepare("SELECT 1 FROM dosen WHERE user_id = ?"); $chk->bind_param('i',$this->user_id); $chk->execute(); $isD = $chk->get_result()->num_rows > 0;
                if ($isD) {
                    $classes = array_map('intval', $_POST['classes']);
                    $stmt2 = $this->conn->prepare("DELETE FROM kelas_dosen WHERE dosen_user_id = ?"); $stmt2->bind_param('i', $this->user_id); $stmt2->execute();
                    foreach ($classes as $k) {
                        $ins = $this->conn->prepare("INSERT IGNORE INTO kelas_dosen (dosen_user_id, kelas_id) VALUES (?,?)");
                        $ins->bind_param('ii', $this->user_id, $k); $ins->execute();
                    }
                }
            }
            echo json_encode(["status"=>true, "message"=>"Profil berhasil diperbarui"]);
        } else {
            $err = $this->conn->error ?: 'unknown';
            echo json_encode(["status"=>false, "message"=>"Gagal update profile", "error"=>$err]);
        }
        exit;
    }
}

?>
