<?php
/**
 * Authentication Helpers - Abah Laut
 */

require_once __DIR__ . '/functions.php';

startSecureSession();

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function currentUser() {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, whatsapp, name, role, status FROM users WHERE id = ? AND status = "active"');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            // Session invalid
            logoutUser();
            return null;
        }
    }
    return $user;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Silakan login terlebih dahulu.');
        redirect('/login.php');
    }
}

/**
 * Require specific role(s)
 */
function requireRole($roles) {
    requireLogin();
    $user = currentUser();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($user['role'], $roles)) {
        setFlash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect('/dashboard.php');
    }
}

/**
 * Login user
 */
function loginUser($whatsapp, $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, whatsapp, password, name, role, status FROM users WHERE whatsapp = ? LIMIT 1');
    $stmt->execute([$whatsapp]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Nomor WhatsApp atau password salah.'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Nomor WhatsApp atau password salah.'];
    }

    // Regenerate session ID
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];

    logActivity('login', $user['name'] . ' berhasil login');

    return ['success' => true, 'role' => $user['role']];
}

/**
 * Logout
 */
function logoutUser() {
    if (isLoggedIn()) {
        logActivity('logout', 'User logout');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Register pengunjung only
 */
function registerPengunjung($whatsapp, $password, $name) {
    $db = getDB();

    // Check existing
    $stmt = $db->prepare('SELECT id FROM users WHERE whatsapp = ?');
    $stmt->execute([$whatsapp]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Nomor WhatsApp sudah terdaftar.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
    }

    if (strlen($name) < 2) {
        return ['success' => false, 'message' => 'Nama terlalu pendek.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (whatsapp, password, name, role, status) VALUES (?, ?, ?, "pengunjung", "active")');
    $stmt->execute([$whatsapp, $hash, $name]);

    logActivity('register', 'Pengunjung baru terdaftar: ' . $name);

    return ['success' => true, 'message' => 'Registrasi berhasil. Silakan login.'];
}
