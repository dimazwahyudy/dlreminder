<?php
$host = "localhost";
$user = "dlreminderfp";
$pass = "finalproject321";
$db   = "FinalProject";

// Use Database class for connection while keeping $conn variable for backward compatibility
require_once __DIR__ .  '/../app/Controllers/Database.php';
try {
    $database = new Database($host, $user, $pass, $db);
    $conn = $database->getConnection();
} catch (Exception $e) {
    // Keep behavior similar to previous: return JSON error when used in API context
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        echo json_encode(["status" => false, "message" => $e->getMessage()]);
        exit;
    } else {
        throw $e;
    }
}

// Load secrets from .env if present, otherwise fall back to google/credentials.json
function loadDotEnv($path) {
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $data = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($k,$v) = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        // strip surrounding quotes
        if ((substr($v,0,1) === '"' && substr($v,-1) === '"') || (substr($v,0,1) === "'" && substr($v,-1) === "'")) {
            $v = substr($v,1,-1);
        }
        $data[$k] = $v;
    }
    return $data;
}

$env = loadDotEnv(__DIR__ . '/../.env');
if (!empty($env['GOOGLE_CLIENT_ID']) && !empty($env['GOOGLE_CLIENT_SECRET']) && !empty($env['GOOGLE_REDIRECT_URI'])) {
    define('GOOGLE_CLIENT_ID',     $env['GOOGLE_CLIENT_ID']);
    define('GOOGLE_CLIENT_SECRET', $env['GOOGLE_CLIENT_SECRET']);
    define('GOOGLE_REDIRECT_URI',  $env['GOOGLE_REDIRECT_URI']);
} else {
    // Enforce presence of Google credentials in .env. No fallback to repository files.
    $msg = "Missing required Google credentials in .env. Please create a .env file with GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI.";
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        echo json_encode(["status" => false, "message" => $msg]);
        exit;
    } else {
        throw new \RuntimeException($msg);
    }
}

// Do not force JSON header here — APIs will set JSON header when needed.

// Extend PHP session lifetimes to 3 days so users remain logged in across browser restarts
// Only attempt to change session ini settings if a session has not been started yet.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string)(3*24*60*60));
    ini_set('session.cookie_lifetime', (string)(3*24*60*60));
}

// Define constants for database configuration
define('DB_HOST', $host);
define('DB_USER', $user);
define('DB_PASS', $pass);
define('DB_NAME', $db);
?>