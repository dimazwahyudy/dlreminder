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

        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => $e->getMessage()]);
        }
        exit;
    }
}

?>
