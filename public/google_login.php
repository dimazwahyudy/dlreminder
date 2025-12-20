<?php
session_start();
require __DIR__ .  '/../config/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$params = [
    'response_type' => 'code',
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'scope'         => 'https://www.googleapis.com/auth/calendar',
    'access_type'   => 'offline',
    'prompt'        => 'consent'
];

$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

header("Location: $auth_url");
exit;
