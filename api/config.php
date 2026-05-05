<?php
ob_start();
// ── Session Settings ───────────────────────────────────────────
// Use default path and domain so session persists properly across requests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disable error display for API cleanliness
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// ── DB Credentials ────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voyage_db');

// ── Connect ───────────────────────────────────────────────────
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        if ($conn->connect_error) {
            jsonError('Database connection failed: ' . $conn->connect_error, 500);
        }
    }
    return $conn;
}

// ── CORS Headers ────────────────────────────────────────────────
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── JSON helpers ──────────────────────────────────────────────
function jsonOut(array $data, int $code = 200): void {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $msg, int $code = 400): void {
    jsonOut(['success' => false, 'error' => $msg], $code);
}

function jsonSuccess(array $data = [], string $msg = 'OK'): void {
    jsonOut(array_merge(['success' => true, 'message' => $msg], $data));
}

// ── Auth helpers ──────────────────────────────────────────────
function isAdmin(): bool {
    return !empty($_SESSION['voyage_admin']);
}

function requireAdmin(): void {
    if (!isAdmin()) jsonError('Unauthorized. Please log in as admin.', 401);
}

// ── Request helpers ───────────────────────────────────────────
function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

function method(): string {
  return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}
