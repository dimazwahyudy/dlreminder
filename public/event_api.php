<?php
session_start();
require __DIR__ .  '/../config/config.php';
require_once __DIR__ .  '/../app/Controllers/EventAPI.php';

$user = $_SESSION['user'] ?? null;
if (!$user) {
    http_response_code(401);
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$api = new EventAPI($conn, $user);
$api->handle();
exit;