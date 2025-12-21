<?php
class AnalyticsAPI {
    private $conn;
    private $user;

    public function __construct($conn, $user) {
        $this->conn = $conn;
        $this->user = $user;
    }

    public function handle() {
        header('Content-Type: application/json');
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        if (!$this->user) {
            http_response_code(401);
            echo json_encode(["status" => false, "message" => "Unauthorized"]);
            exit;
        }

        try {
            $range = $_GET['range'] ?? 'year';
            $uid = (int)$this->user['id'];
            $role = $this->user['role'];

            $endDate = date('Y-m-d 23:59:59');
            if ($range === 'week') {
                $startDate = date('Y-m-d 00:00:00', strtotime('-6 days'));
                $sqlGroup = "DATE(e.start_time)";
                $sqlSelect = "DATE(e.start_time) as period";
            } elseif ($range === 'month') {
                $startDate = date('Y-m-01 00:00:00');
                $endDate = date('Y-m-t 23:59:59');
                $sqlGroup = "DATE(e.start_time)";
                $sqlSelect = "DATE(e.start_time) as period";
            } else {
                $startDate = date('Y-01-01 00:00:00');
                $endDate = date('Y-12-31 23:59:59');
                $sqlGroup = "MONTH(e.start_time)";
                $sqlSelect = "MONTH(e.start_time) as period";
            }

            // Standard year/month/week handling kept as before
            if ($range !== 'semester') {
                $sql = "SELECT $sqlSelect, COUNT(e.id) as total FROM event e ";

                if ($role === 'admin') {
                    $sql .= "WHERE 1=1 ";
                } elseif ($role === 'dosen') {
                    $sql .= "WHERE (e.created_by = $uid OR e.visibility = 'all') ";
                } else {
                    $sql .= "LEFT JOIN kelas_dosen kd ON kd.dosen_user_id = e.created_by 
                              LEFT JOIN mahasiswa m ON m.user_id = $uid AND m.kelas_id = kd.kelas_id 
                              WHERE (
                                  (e.visibility = 'all') OR
                                  (e.visibility = 'mahasiswa') OR
                                  (e.created_by = $uid) OR
                                  (e.visibility = 'dosen_mahasiswa' AND m.user_id IS NOT NULL)
                              ) ";
                }

                $sql .= "AND e.start_time BETWEEN '$startDate' AND '$endDate' 
                         GROUP BY $sqlGroup 
                         ORDER BY $sqlGroup ASC";

                $result = $this->conn->query($sql);
                if (!$result) throw new Exception("Database Error: " . $this->conn->error);

                $dataMap = [];
                while ($row = $result->fetch_assoc()) {
                    $dataMap[$row['period']] = (int)$row['total'];
                }

                $labels = [];
                $counts = [];

                if ($range === 'week') {
                    for ($i = 6; $i >= 0; $i--) {
                        $d = date('Y-m-d', strtotime("-$i days"));
                        $labels[] = date('D', strtotime($d));
                        $counts[] = $dataMap[$d] ?? 0;
                    }
                } elseif ($range === 'month') {
                    $endDay = (int)date('t');
                    for ($i = 1; $i <= $endDay; $i++) {
                        $d = date('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT);
                        $labels[] = (string)$i;
                        $counts[] = $dataMap[$d] ?? 0;
                    }
                } else {
                    for ($i = 1; $i <= 12; $i++) {
                        $monthName = date('M', mktime(0, 0, 0, $i, 10));
                        $labels[] = $monthName;
                        $counts[] = $dataMap[$i] ?? 0;
                    }
                }

                echo json_encode([
                    "status" => true,
                    "labels" => $labels,
                    "data" => $counts,
                    "range" => $range
                ]);
                exit;
            }

            // ----- Semester-specific analytics and CSV export -----
            // Expect GET params: year (YYYY) and sem (1 or 2). Sem 1: Jan-Jun, Sem 2: Jul-Dec
            $year = intval($_GET['year'] ?? date('Y'));
            $sem = intval($_GET['sem'] ?? (date('n') <= 6 ? 1 : 2));
            if ($sem === 1) {
                $sStart = "$year-01-01 00:00:00";
                $sEnd = "$year-06-30 23:59:59";
            } else {
                $sStart = "$year-07-01 00:00:00";
                $sEnd = "$year-12-31 23:59:59";
            }

            // Build role-aware WHERE clause (reuse same logic as above)
            if ($role === 'admin') {
                $where = "WHERE 1=1";
            } elseif ($role === 'dosen') {
                $where = "WHERE (e.created_by = $uid OR e.visibility = 'all')";
            } else {
                $where = "LEFT JOIN kelas_dosen kd ON kd.dosen_user_id = e.created_by 
                          LEFT JOIN mahasiswa m ON m.user_id = $uid AND m.kelas_id = kd.kelas_id 
                          WHERE (
                              (e.visibility = 'all') OR
                              (e.visibility = 'mahasiswa') OR
                              (e.created_by = $uid) OR
                              (e.visibility = 'dosen_mahasiswa' AND m.user_id IS NOT NULL)
                          )";
            }

            $sqlEvents = "SELECT e.* FROM event e $where AND e.start_time BETWEEN '" . $this->conn->real_escape_string($sStart) . "' AND '" . $this->conn->real_escape_string($sEnd) . "' ORDER BY e.start_time ASC";
            $resEvents = $this->conn->query($sqlEvents);
            if (!$resEvents) throw new Exception("Database Error: " . $this->conn->error);

            $events = [];
            while ($r = $resEvents->fetch_assoc()) $events[] = $r;

            $total = count($events);
            $weeks = max(1, ceil((strtotime($sEnd) - strtotime($sStart)) / (7*24*3600)));
            $avg_per_week = $total / $weeks;

            // busiest month (within semester)
            $monthCounts = [];
            $weekdayCounts = [];
            $totalDurationSec = 0;
            $now = time();
            $upcomingCount = 0;
            foreach ($events as $ev) {
                $m = intval(date('n', strtotime($ev['start_time'])));
                $monthCounts[$m] = ($monthCounts[$m] ?? 0) + 1;
                $wd = intval(date('N', strtotime($ev['start_time']))); // 1=Mon..7=Sun
                $weekdayCounts[$wd] = ($weekdayCounts[$wd] ?? 0) + 1;
                $st = strtotime($ev['start_time']);
                $en = strtotime($ev['end_time']);
                if ($en && $st) $totalDurationSec += max(0, $en - $st);
                if ($st >= $now && $st <= ($now + 30*24*3600)) $upcomingCount++;
            }

            arsort($monthCounts);
            $busiestMonth = key($monthCounts) ?: null;
            $busiestMonthCount = current($monthCounts) ?: 0;

            arsort($weekdayCounts);
            $busiestWeekday = key($weekdayCounts) ?: null;
            $busiestWeekdayCount = current($weekdayCounts) ?: 0;

            $avgDurationHours = $total ? ($totalDurationSec / $total / 3600) : 0;

            // Recommendations simple rules
            $recommendation = [];
            if ($avg_per_week >= 10) {
                $recommendation[] = 'Beban sangat tinggi — pertimbangkan untuk menjadwalkan waktu belajar tambahan dan mengerjakan tugas lebih awal.';
            } elseif ($avg_per_week >= 5) {
                $recommendation[] = 'Beban tinggi — bagi tugas menjadi beberapa sesi dan alokasikan setidaknya 2 jam per tugas.';
            } else {
                $recommendation[] = 'Beban normal — pertahankan kebiasaan manajemen waktu.';
            }
            // Suggest study hours estimate
            $suggestedStudyHoursPerWeek = round($avg_per_week * 2, 1); // assume 2 hours per event

            // If export CSV requested, generate CSV download
            if (isset($_GET['export']) && $_GET['export'] === 'csv') {
                $filename = sprintf('workload_%s_sem%d_%s.csv', $year, $sem, date('Ymd'));
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['id','title','description','start_time','end_time','duration_hours','visibility','created_by']);
                foreach ($events as $ev) {
                    $st = strtotime($ev['start_time']);
                    $en = strtotime($ev['end_time']);
                    $dur = ($st && $en) ? round(max(0, $en - $st)/3600,2) : '';
                    fputcsv($out, [$ev['id'],$ev['title'],$ev['description'],date('c', $st),date('c', $en),$dur,$ev['visibility'],$ev['created_by']]);
                }
                fclose($out);
                exit;
            }

            // Return JSON metrics and small sample list (trimmed)
            $weekdayNames = [1=>'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
            // build month series (labels + counts) for the semester range
            $month_labels = [];
            $month_data = [];
            if ($sem === 1) {
                $months = range(1,6);
            } else {
                $months = range(7,12);
            }
            foreach ($months as $mm) {
                $month_labels[] = date('M', mktime(0,0,0,$mm,10));
                $month_data[] = $monthCounts[$mm] ?? 0;
            }

            // weekday series Monday..Sunday
            $weekday_labels = [];
            $weekday_data = [];
            for ($d=1;$d<=7;$d++) { $weekday_labels[] = $weekdayNames[$d]; $weekday_data[] = $weekdayCounts[$d] ?? 0; }

            $resp = [
                'status'=>true,
                'period'=>['year'=>$year,'semester'=>$sem,'start'=>$sStart,'end'=>$sEnd],
                'total_events'=>$total,
                'weeks'=>$weeks,
                'avg_per_week'=>round($avg_per_week,2),
                'busiest_month'=>$busiestMonth,
                'busiest_month_count'=>$busiestMonthCount,
                'busiest_weekday'=>$weekdayNames[$busiestWeekday] ?? null,
                'busiest_weekday_count'=>$busiestWeekdayCount,
                'avg_duration_hours'=>round($avgDurationHours,2),
                'upcoming_30d'=>$upcomingCount,
                'recommendations'=>$recommendation,
                'suggested_study_hours_per_week'=>$suggestedStudyHoursPerWeek,
                'sample_events'=>array_slice($events,0,20),
                'month_series'=>['labels'=>$month_labels,'data'=>$month_data],
                'weekday_series'=>['labels'=>$weekday_labels,'data'=>$weekday_data]
            ];

            echo json_encode($resp);

        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => $e->getMessage()]);
        }
        exit;
    }
}

?>
