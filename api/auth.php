<?php
/**
 * api/auth.php
 * POST body JSON: { "action": "login"|"signup"|"logout"|"check", ... }
 */
require_once __DIR__ . '/config.php';

$body   = getBody();
$action = $body['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── LOGIN ──────────────────────────────────────────────────────
    case 'login':
        $email = trim($body['email'] ?? '');
        $pass  = $body['password'] ?? '';

        if (!$email || !$pass) jsonError('Email and password required.');

        $db = db();

        // Check admins first
        $stmt = $db->prepare("SELECT id, name, password FROM admins WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['voyage_admin']    = true;
            $_SESSION['voyage_admin_id'] = $admin['id'];
            $_SESSION['voyage_name']     = $admin['name'];
            jsonSuccess(['role' => 'admin', 'name' => $admin['name']], 'Admin login successful.');
        }

        // Check regular users
        $stmt = $db->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['voyage_user_id'] = $user['id'];
            $_SESSION['voyage_name']    = $user['name'];
            jsonSuccess(['role' => 'user', 'name' => $user['name']], 'Login successful.');
        }

        jsonError('Invalid email or password.', 401);
        break;

    // ── SIGNUP ─────────────────────────────────────────────────────
    case 'signup':
        $name  = trim($body['name'] ?? '');
        $email = trim($body['email'] ?? '');
        $pass  = $body['password'] ?? '';

        if (!$name || !$email || !$pass) jsonError('Name, email and password required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  jsonError('Invalid email address.');
        if (strlen($pass) < 6) jsonError('Password must be at least 6 characters.');

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $db   = db();
        $stmt = $db->prepare("INSERT INTO users (name,email,password) VALUES(?,?,?)");
        $stmt->bind_param('sss', $name, $email, $hash);

        if (!$stmt->execute()) {
            $stmt->close();
            jsonError('Email already registered. Please log in.');
        }
        $stmt->close();

        $_SESSION['voyage_user_id'] = $db->insert_id;
        $_SESSION['voyage_name']    = $name;
        jsonSuccess(['role' => 'user', 'name' => $name], 'Account created successfully!');
        break;

    // ── LOGOUT ─────────────────────────────────────────────────────
    case 'logout':
        session_destroy();
        jsonSuccess([], 'Logged out.');
        break;

    // ── CHECK SESSION ──────────────────────────────────────────────
    case 'check':
        if (isAdmin()) {
            jsonSuccess(['role' => 'admin', 'name' => $_SESSION['voyage_name'] ?? 'Admin'], 'Admin session active.');
        } elseif (!empty($_SESSION['voyage_user_id'])) {
            jsonSuccess(['role' => 'user', 'name' => $_SESSION['voyage_name'] ?? 'User'], 'User session active.');
        } else {
            jsonError('Not logged in.', 401);
        }
        break;

    default:
        jsonError('Unknown action.');
}
