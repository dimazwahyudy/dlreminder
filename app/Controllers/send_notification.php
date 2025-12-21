<?php
require 'vendor/autoload.php';
require 'config/config.php'; 

use PHPMailer\PHPMailer\PHPMailer;

// --- CONFIG EMAIL ---
$my_email = 'dlreminderfp@gmail.com'; 
$my_pass  = 'fshj mvvk vqnb mwwi';    

date_default_timezone_set('Asia/Jakarta');

try {
    // Cari event yang akan berakhir dalam 2 hari ke depan (H-2 reminder)
    $sql = "SELECT * FROM event WHERE end_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 DAY)";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $eventId = (int)$row['id'];
            $creatorId = (int)$row['created_by'];
            $visibility = $row['visibility'] ?? 'self';

            // determine recipients based on visibility
            $recipient_ids = [];
            if ($visibility === 'dosen_mahasiswa') {
                // if creator is dosen -> their students, otherwise all mahasiswa
                $isDosen = ($conn->query("SELECT user_id FROM dosen WHERE user_id = $creatorId")->num_rows > 0);
                if ($isDosen) {
                    $q = $conn->prepare("SELECT m.user_id FROM kelas_dosen kd JOIN mahasiswa m ON m.kelas_id = kd.kelas_id WHERE kd.dosen_user_id = ?");
                    $q->bind_param('i', $creatorId); $q->execute(); $r = $q->get_result();
                    while ($ro = $r->fetch_assoc()) $recipient_ids[] = (int)$ro['user_id'];
                } else {
                    $r = $conn->query("SELECT user_id FROM mahasiswa"); while ($ro = $r->fetch_assoc()) $recipient_ids[] = (int)$ro['user_id'];
                }
            } elseif ($visibility === 'mahasiswa') {
                $r = $conn->query("SELECT user_id FROM mahasiswa"); while ($ro = $r->fetch_assoc()) $recipient_ids[] = (int)$ro['user_id'];
            } elseif ($visibility === 'dosen') {
                $r = $conn->query("SELECT user_id FROM dosen"); while ($ro = $r->fetch_assoc()) $recipient_ids[] = (int)$ro['user_id'];
            } elseif ($visibility === 'all') {
                $r = $conn->query("SELECT id FROM user"); while ($ro = $r->fetch_assoc()) $recipient_ids[] = (int)$ro['id'];
            }

            // always include the creator (to ensure they get reminder)
            $recipient_ids[] = $creatorId;

            // deduplicate
            $recipient_ids = array_unique($recipient_ids);

            foreach ($recipient_ids as $rid) {
                // check existing notification to avoid duplicates
                $chkStmt = $conn->prepare("SELECT id FROM notification WHERE event_id = ? AND receiver_id = ? LIMIT 1");
                $chkStmt->bind_param('ii', $eventId, $rid); $chkStmt->execute(); $chkRes = $chkStmt->get_result();
                if ($chkRes && $chkRes->num_rows > 0) continue; // already sent

                // fetch receiver info
                $uStmt = $conn->prepare("SELECT id, name, email FROM user WHERE id = ? LIMIT 1");
                $uStmt->bind_param('i', $rid); $uStmt->execute(); $uRes = $uStmt->get_result();
                if (!$uRes || $uRes->num_rows === 0) continue;
                $usr = $uRes->fetch_assoc();

                // send email
                $sent = sendSimpleEmail($usr['email'], $usr['name'], $row['title'], $row['end_time'], $my_email, $my_pass);

                if ($sent) {
                    // record notification
                    $msg = "Reminder: " . $row['title'];
                    $ins = $conn->prepare("INSERT INTO notification (message, sent_at, receiver_id, event_id) VALUES (?, NOW(), ?, ?)");
                    $ins->bind_param('sii', $msg, $usr['id'], $eventId); $ins->execute();
                    echo "Terkirim ke: {$usr['email']}\n";
                } else {
                    echo "Gagal mengirim ke: {$usr['email']}\n";
                }
            }
        }
    } else {
        echo "Tidak ada tugas H-2.\n";
    }

} catch (Exception $e) { echo "Error: " . $e->getMessage(); }

function sendSimpleEmail($to, $name, $title, $deadline, $fromMail, $fromPass) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $fromMail;
        $mail->Password   = $fromPass;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom($fromMail, 'DLReminder');
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = "REMINDER! $title";
        $mail->Body    = "Halo $name,<br><br>Tugas <b>$title</b> deadline pada <b>$deadline</b>.<br>Segera kerjakan!";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('sendSimpleEmail error: ' . $e->getMessage());
        return false;
    }
}
?>