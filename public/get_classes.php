<?php
require __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

try {
    $res = $conn->query("SELECT id, kode_kelas, nama_kelas FROM kelas ORDER BY kode_kelas ASC");
    $out = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
    }
    echo json_encode($out);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'failed', 'message' => $e->getMessage()]);
}

?>
