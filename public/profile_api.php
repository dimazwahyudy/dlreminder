<?php
session_start();
require __DIR__ .  '/../config/config.php';
require_once __DIR__ .  '/../app/Controllers/ProfileAPI.php';

$user = $_SESSION['user'] ?? null;
$api = new ProfileAPI($conn, $user);
$api->handle();
exit;
?>