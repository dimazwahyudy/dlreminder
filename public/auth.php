<?php
require __DIR__ . '/../config/config.php';

// HAPUS TOKEN GOOGLE YANG SUDAH EXPIRED

$sql = "DELETE FROM google_token WHERE expires_at < NOW()";
$stmt = $conn->prepare($sql);
$stmt->execute();

// START SESSION SETELAH CLEANUP

session_start();

require_once __DIR__ . '/../app/Controllers/Auth.php';

$auth = new Auth($conn);
$auth->handle();
exit;
?>
