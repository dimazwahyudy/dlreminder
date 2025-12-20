<?php

// Aktifkan debugging untuk menangkap error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tangkap semua output yang tidak diinginkan
ob_start();


// Pastikan metode POST digunakan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); // Bersihkan output buffer
    http_response_code(405); // Method Not Allowed
    exit;
}

// Normalize includes relative to project root. __DIR__ is app/Controllers
require __DIR__ . '/Database.php';
require __DIR__ . '/../../vendor/autoload.php'; // Ensure PHPMailer is installed via Composer
require_once __DIR__ . '/../../config/config.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); // Bersihkan output buffer
    http_response_code(405); // Method Not Allowed
}

$action = $_POST['action'] ?? '';
$email = $_POST['email'] ?? '';

// Helper untuk mengirim respons JSON yang bersih dan konsisten
function send_json($data) {
    // Catat isi output buffer saat ini (jika ada)
    $buf = '';
    if (ob_get_length()) {
        $buf = ob_get_contents();
        $logPath = __DIR__ . '/../../storage/logs/debug.log';
        @file_put_contents($logPath, $buf, FILE_APPEND);
    }

    // Bersihkan semua level buffer untuk memastikan output JSON bersih
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    // Pastikan output dikirim
    flush();
    exit;
}
$logFile = __DIR__ . '/../storage/logs/debug_action.log';

// Tambahkan debugging untuk memeriksa nilai action
file_put_contents($logFile, "Action: $action, Email: $email\n", FILE_APPEND);


$db = new Database(DB_HOST, DB_USER, DB_PASS, DB_NAME); // Tambahkan parameter konfigurasi
$conn = $db->getConnection();

// Tambahkan logika untuk membuat tabel otp_requests jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS otp_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE(email)
)");

if ($action === 'request_otp') {

    // Generate OTP
    $otp = rand(100000, 999999);

    // Save OTP to database
    $stmt = $conn->prepare("INSERT INTO otp_requests (email, otp, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE otp = ?, created_at = NOW()");
    $stmt->bind_param('sss', $email, $otp, $otp);
    if (!$stmt->execute()) {
        send_json(['status' => false, 'message' => 'Gagal menyimpan OTP.']);
    }

    $SEND_MAIL = true;

    // If we are not sending email (testing mode), return success immediately so client receives valid JSON
    if (!$SEND_MAIL) {
        send_json(['status' => true, 'message' => 'Kode OTP berhasil dibuat dan disimpan (testing mode).', 'debug_otp' => $otp]);
    }

    // Send OTP via email
    $mail = new PHPMailer(true);

    try {
        // Server settings
        // PHPMailer debug output will be written to mail_debug.log instead of stdout
        $logFile = __DIR__ . '/../../storage/logs/mail_debug.log';
        $mail->SMTPDebug = 2; // increase verbosity while debugging
        $mail->Debugoutput = function($str, $level) {
            file_put_contents(__DIR__ . $logFile, "[" . date('c') . "] " . $str . PHP_EOL, FILE_APPEND);
        };

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dlreminderfp@gmail.com'; 
        $mail->Password = 'fshj mvvk vqnb mwwi';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Workaround for OpenSSL certificate verification failures during testing.
        // Prefer installing system CA certificates or fixing OpenSSL config in production.
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Recipients
        $mail->setFrom('dlreminderfp@gmail.com', 'DLReminder');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP Anda';
        $mail->Body = "<p>Kode OTP Anda adalah: <strong>$otp</strong></p>";

        $mail->send();
        
        send_json(['status' => true, 'message' => 'Kode OTP berhasil dikirim.', 'debug_otp' => $otp]);
    } catch (Exception $e) {
        // Log PHPMailer exception details
        $logPath = __DIR__ . '/../../storage/logs/mail_error.log';
        @file_put_contents($logPath, '[' . date('c') . '] ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        // Also log ErrorInfo if available
        if (isset($mail) && property_exists($mail, 'ErrorInfo')) {
            @file_put_contents($logPath, '[' . date('c') . '] PHPMailer ErrorInfo: ' . $mail->ErrorInfo . PHP_EOL, FILE_APPEND);
        }
        send_json(['status' => false, 'message' => 'Gagal mengirim email.']);
    }
} elseif ($action === 'verify_otp') {
    $otp = $_POST['otp'] ?? '';

    if (empty($email) || empty($otp)) {
        send_json(['status' => false, 'message' => 'Email dan OTP tidak boleh kosong.']);
    }

    // Verify OTP
    $stmt = $conn->prepare("SELECT * FROM otp_requests WHERE email = ? AND otp = ? AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
    $stmt->bind_param('ss', $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        send_json(['status' => true, 'message' => 'OTP valid.']);
    } else {
        send_json(['status' => false, 'message' => 'OTP tidak valid.']);
    }
} elseif ($action === 'reset_password') {
    $new_password = $_POST['new_password'] ?? '';
    $otp = $_POST['otp'] ?? '';

    if (empty($email) || empty($otp) || empty($new_password)) {
        send_json(['status' => false, 'message' => 'Semua parameter harus diisi.']);
    }

    if (strlen($new_password) < 6) {
        send_json(['status' => false, 'message' => 'Password minimal 6 karakter.']);
    }

    // Verify OTP (still within valid window)
    $stmt = $conn->prepare("SELECT id FROM otp_requests WHERE email = ? AND otp = ? AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
    $stmt->bind_param('ss', $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        // Update password for the user
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        $upd = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
        if (!$upd) {
            send_json(['status' => false, 'message' => 'Kesalahan database saat menyiapkan query update.']);
        }
        $upd->bind_param('ss', $hashed_password, $email);
        if ($upd->execute()) {
            // Remove used OTP to prevent reuse
            $del = $conn->prepare("DELETE FROM otp_requests WHERE email = ?");
            if ($del) {
                $del->bind_param('s', $email);
                $del->execute();
            }
            send_json(['status' => true, 'message' => 'Password berhasil diubah.']);
        } else {
            send_json(['status' => false, 'message' => 'Gagal mengubah password.']);
        }
    } else {
        send_json(['status' => false, 'message' => 'OTP tidak valid atau sudah kedaluwarsa.']);
    }
} else {
    send_json(['status' => false, 'message' => 'Aksi tidak valid.']);
}

// Bersihkan output buffer (log jika ada) dan selesai
if (ob_get_length()) {
    $outPath = __DIR__ . '/../../storage/logs/debug_output.log';
    @file_put_contents($outPath, ob_get_contents(), FILE_APPEND);
    ob_end_clean();
}

