<?php
class UserAPI {
    private $conn;
    private $user;

    public function __construct($conn, $user) {
        $this->conn = $conn;
        $this->user = $user;
    }

    public function handle() {
        header('Content-Type: application/json');
        if (!$this->user || $this->user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => false, "message" => "Akses ditolak"]);
            exit;
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') return $this->handleGet();
        if ($method === 'POST') return $this->handlePost();
        if ($method === 'DELETE') return $this->handleDelete();
    }

    private function handleGet() {
        $type = $_GET['type'] ?? 'mahasiswa';
        $id   = $_GET['id'] ?? null;

        if ($id) {
            $sql = "SELECT u.id, u.name, u.email, 
                    m.nim, m.prodi, m.kelas_id, 
                    d.no_induk 
                    FROM user u 
                    LEFT JOIN mahasiswa m ON m.user_id = u.id 
                    LEFT JOIN dosen d ON d.user_id = u.id 
                    WHERE u.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_assoc());
            exit;
        }

        $data = [];
        if ($type === 'mahasiswa') {
            $sql = "SELECT u.id, u.name, u.email, m.nim, m.prodi, k.kode_kelas 
                    FROM user u 
                    JOIN mahasiswa m ON m.user_id = u.id 
                    LEFT JOIN kelas k ON k.id = m.kelas_id 
                    ORDER BY k.kode_kelas ASC, u.name ASC";
        } else {
            $sql = "SELECT u.id, u.name, u.email, d.no_induk 
                    FROM user u 
                    JOIN dosen d ON d.user_id = u.id 
                    ORDER BY u.name ASC";
        }
        $result = $this->conn->query($sql);
        while($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }

    private function handlePost() {
        $action = $_POST['action'] ?? 'create';
        $role   = $_POST['role'] ?? 'mahasiswa';
        $name  = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        $this->conn->begin_transaction();
        try {
            if ($action === 'create') {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("INSERT INTO user (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $password);
                $stmt->execute();
                $user_id = $stmt->insert_id;

                if ($role === 'mahasiswa') {
                    $stmt = $this->conn->prepare("INSERT INTO mahasiswa (user_id, nim, prodi, kelas_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("issi", $user_id, $_POST['nim'], $_POST['prodi'], $_POST['kelas_id']);
                } else {
                    $stmt = $this->conn->prepare("INSERT INTO dosen (user_id, no_induk) VALUES (?, ?)");
                    $stmt->bind_param("is", $user_id, $_POST['no_induk']);
                }
                $stmt->execute();
                $msg = "User berhasil ditambahkan";

            } elseif ($action === 'edit') {
                $id = $_POST['id'];
                if (!empty($_POST['password'])) {
                    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $this->conn->prepare("UPDATE user SET name=?, email=?, password=? WHERE id=?");
                    $stmt->bind_param("sssi", $name, $email, $pass, $id);
                } else {
                    $stmt = $this->conn->prepare("UPDATE user SET name=?, email=? WHERE id=?");
                    $stmt->bind_param("ssi", $name, $email, $id);
                }
                $stmt->execute();

                if ($role === 'mahasiswa') {
                    $stmt = $this->conn->prepare("UPDATE mahasiswa SET nim=?, prodi=?, kelas_id=? WHERE user_id=?");
                    $stmt->bind_param("ssii", $_POST['nim'], $_POST['prodi'], $_POST['kelas_id'], $id);
                } else {
                    $stmt = $this->conn->prepare("UPDATE dosen SET no_induk=? WHERE user_id=?");
                    $stmt->bind_param("si", $_POST['no_induk'], $id);
                }
                $stmt->execute();
                $msg = "User berhasil diupdate";
            }

            $this->conn->commit();
            echo json_encode(["status" => true, "message" => $msg]);

        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(["status" => false, "message" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    private function handleDelete() {
        parse_str(file_get_contents("php://input"), $data);
        $id = $_GET['id'] ?? null;

        if ($id) {
            $id = (int)$id;
            // Prevent admin deleting their own account accidentally
            if ($id === (int)$this->user['id']) {
                echo json_encode(["status" => false, "message" => "Tidak bisa menghapus akun sendiri"]);
                exit;
            }

            $this->conn->begin_transaction();
            try {
                // remove dependent rows first to avoid foreign key constraint errors
                $d1 = $this->conn->prepare("DELETE FROM mahasiswa WHERE user_id = ?");
                $d1->bind_param('i', $id); $d1->execute();
                $d2 = $this->conn->prepare("DELETE FROM dosen WHERE user_id = ?");
                $d2->bind_param('i', $id); $d2->execute();
                // also remove Google token and event mappings referencing this user
                $d3 = $this->conn->prepare("DELETE FROM google_token WHERE user_id = ?");
                $d3->bind_param('i', $id); $d3->execute();
                $d4 = $this->conn->prepare("DELETE FROM event_google_map WHERE user_id = ?");
                $d4->bind_param('i', $id); $d4->execute();

                $stmt = $this->conn->prepare("DELETE FROM user WHERE id = ?");
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) throw new Exception("User delete failed: " . $this->conn->error);

                $this->conn->commit();
                echo json_encode(["status" => true, "message" => "User berhasil dihapus"]);
            } catch (Exception $e) {
                $this->conn->rollback();
                echo json_encode(["status" => false, "message" => "Gagal menghapus: " . $e->getMessage()]);
            }
        }
        exit;
    }
}

?>
