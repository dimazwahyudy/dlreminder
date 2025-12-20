<?php
session_start();
require __DIR__ .  '/../config/config.php';
require_once __DIR__ .  '/../app/Controllers/UserAPI.php';

$user = $_SESSION['user'] ?? null;
$api = new UserAPI($conn, $user);
$api->handle();
exit;
?>