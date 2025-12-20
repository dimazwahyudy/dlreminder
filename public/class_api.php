<?php
require __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $kode = isset($_POST['kode_kelas']) ? trim($_POST['kode_kelas']) : '';
        $nama = isset($_POST['nama_kelas']) ? trim($_POST['nama_kelas']) : '';
        if ($kode === '' || $nama === '') {
            http_response_code(400);
            echo json_encode(['status'=>false,'message'=>'kode_kelas dan nama_kelas wajib']);
            exit;
        }
        if ($id) {
            $stmt = $conn->prepare("UPDATE kelas SET kode_kelas = ?, nama_kelas = ? WHERE id = ?");
            $stmt->bind_param('ssi', $kode, $nama, $id);
            $ok = $stmt->execute();
            if ($ok) echo json_encode(['status'=>true,'message'=>'Kelas diperbarui']);
            else { http_response_code(500); echo json_encode(['status'=>false,'message'=>$conn->error]); }
            exit;
        } else {
            $stmt = $conn->prepare("INSERT INTO kelas (kode_kelas, nama_kelas) VALUES (?,?)");
            $stmt->bind_param('ss', $kode, $nama);
            $ok = $stmt->execute();
            if ($ok) echo json_encode(['status'=>true,'message'=>'Kelas dibuat','id'=>$conn->insert_id]);
            else { http_response_code(500); echo json_encode(['status'=>false,'message'=>$conn->error]); }
            exit;
        }
    } elseif ($method === 'DELETE') {
        // Expect id in query string
        if (!isset($_GET['id'])) {
            http_response_code(400); echo json_encode(['status'=>false,'message'=>'Missing id']); exit;
        }
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("DELETE FROM kelas WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        if ($ok) echo json_encode(['status'=>true,'message'=>'Kelas dihapus']);
        else { http_response_code(500); echo json_encode(['status'=>false,'message'=>$conn->error]); }
        exit;
    } else {
        http_response_code(405); echo json_encode(['status'=>false,'message'=>'Method not allowed']); exit;
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['status'=>false,'message'=>$e->getMessage()]); exit;
}
