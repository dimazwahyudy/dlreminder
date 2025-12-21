<?php
class Auth {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $action = $_POST['action'] ?? '';

        if ($action === 'register') return $this->register();
        if ($action === 'login') return $this->login();
    }

    private function register() {
        $name  = $_POST['name'];
        $email = $_POST['email'];
        $nim   = $_POST['nim'];
        $kelas = $_POST['kelas'];
        $prodi = $_POST['prodi'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("INSERT INTO user (name,email,password) VALUES (?,?,?)");
            $stmt->bind_param("sss", $name,$email,$password);
            $stmt->execute();
            $user_id = $stmt->insert_id;

            $qKelas = $this->conn->prepare("SELECT id FROM kelas WHERE kode_kelas=?");
            $qKelas->bind_param("s", $kelas);
            $qKelas->execute();
            $res = $qKelas->get_result();

            if (!$res->num_rows) throw new Exception("Kelas tidak ditemukan");

            $kelas_id = $res->fetch_assoc()['id'];

            $stmt = $this->conn->prepare(
                "INSERT INTO mahasiswa (user_id,nim,prodi,kelas_id) VALUES (?,?,?,?)"
            );
            $stmt->bind_param("issi",$user_id,$nim,$prodi,$kelas_id);
            $stmt->execute();

            $this->conn->commit();
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION['success'] = "Registrasi berhasil, silakan login";
            header("Location: login.php");
            exit;

        } catch (Exception $e) {
            $this->conn->rollback();
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION['error'] = $e->getMessage();
            header("Location: login.php");
            exit;
        }
    }

    private function login() {
        $email = $_POST['email'];
        $pass  = $_POST['password'];

        $q = $this->conn->prepare("SELECT * FROM user WHERE email=?");
        $q->bind_param("s", $email);
        $q->execute();
        $res = $q->get_result();

        if (!$res->num_rows) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION['error'] = "Email tidak ditemukan"; header("Location: login.php"); exit;
        }

        $user = $res->fetch_assoc();
        if (!password_verify($pass, $user['password'])) { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); $_SESSION['error'] = "Password salah"; header("Location: login.php"); exit; }

        $role = 'admin';
        if ($this->conn->query("SELECT 1 FROM mahasiswa WHERE user_id={$user['id']}")->num_rows) $role = 'mahasiswa';
        if ($this->conn->query("SELECT 1 FROM dosen WHERE user_id={$user['id']}")->num_rows) $role = 'dosen';

        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['user'] = [
            "id"   => $user['id'],
            "name" => $user['name'],
            "role" => $role
        ];

        $threeDays = 3 * 24 * 60 * 60;
        setcookie(session_name(), session_id(), time() + $threeDays, "/");

        $check = $this->conn->prepare("SELECT 1 FROM google_token WHERE user_id = ?");
        $check->bind_param('i', $user['id']);
        $check->execute();
        $hasToken = $check->get_result()->num_rows > 0;

        // kalau user sudah punya token, lakukan pending import dan tarik data dari google.
        if ($hasToken) {
            require_once __DIR__ . '/EventAPI.php';
            $eventApi = new EventAPI($this->conn, $_SESSION['user']);
            try { $eventApi->syncPendingForUser($user['id']); } catch (
                Exception $e) { /* ignore sync errors on login */ }
            try { $eventApi->importGoogleForUser($user['id']); } catch (\Exception $e) { /* ignore import errors */ }
            header("Location: dashboard.php");
        } else {
            header("Location: google_login.php");
        }
        exit;
    }
}

?>
