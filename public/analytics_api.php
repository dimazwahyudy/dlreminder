<?php
session_start();
require __DIR__ .  '/../config/config.php';
require_once __DIR__ .  '/../app/Controllers/AnalyticsAPI.php';

$user = $_SESSION['user'] ?? null;
$api = new AnalyticsAPI($conn, $user);
$api->handle();
exit;
?>