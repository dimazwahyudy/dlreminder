<?php
session_start();
require __DIR__ .  '/../config/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['code'])) {
    die("Authorization failed");
}

$tokenRequest = [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query($tokenRequest),
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($response['access_token'])) {
    die("Failed get token: " . json_encode($response));
}

$user_id = $_SESSION['user']['id'];

/* Google kadang TIDAK mengirim refresh_token lagi */
$refreshToken = $response['refresh_token'] ?? null;
$expires      = $response['expires_in'];

// ensure google_token table exists
$conn->query("CREATE TABLE IF NOT EXISTS google_token (
    user_id INT PRIMARY KEY,
    access_token TEXT,
    refresh_token TEXT,
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $conn->prepare("
    INSERT INTO google_token (user_id, access_token, refresh_token, expires_at)
    VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
    ON DUPLICATE KEY UPDATE
        access_token = VALUES(access_token),
        refresh_token = IFNULL(VALUES(refresh_token), refresh_token),
        expires_at = VALUES(expires_at)
");

$stmt->bind_param(
    "issi",
    $user_id,
    $response['access_token'],
    $refreshToken,
    $expires
);

$stmt->execute();

// After storing tokens, trigger pending sync for this user so they receive events immediately
require_once __DIR__ .  '/../app/Controllers/EventAPI.php';
$eventApi = new EventAPI($conn, $_SESSION['user']);
$syncRes = $eventApi->syncPendingForUser($user_id);
// Also import user's Google Calendar items immediately after connecting
try {
    $importRes = $eventApi->importGoogleForUser($user_id);
} catch (Exception $e) {
}

header("Location: dashboard.php?google=connected");
exit;