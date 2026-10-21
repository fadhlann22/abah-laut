<?php
/**
 * Helper Functions - Abah Laut
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Start secure session
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape output
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect($path) {
    $url = (strpos($path, 'http') === 0) ? $path : BASE_URL . $path;
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Log activity
 */
function logActivity($action, $description = null, $relatedType = null, $relatedId = null) {
    try {
        $db = getDB();
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $db->prepare('INSERT INTO activity_logs (user_id, action, description, related_type, related_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $action, $description, $relatedType, $relatedId, $ip]);
    } catch (Exception $e) {
        // Silent fail for logging
    }
}

/**
 * Format Rupiah
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Generate unique code
 */
function generateCode($prefix) {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Get menu image URL with fallback
 */
function menuImageUrl($path) {
    if ($path && file_exists(BASE_PATH . '/' . $path)) {
        return BASE_URL . '/' . $path;
    }
    return BASE_URL . '/assets/images/no-image.png';
}

/**
 * Check if current page is active for nav
 */
function isActive($page) {
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $page ? 'active' : '';
}

/**
 * Safe upload image for menu
 * Returns ['success'=>bool, 'path'=>string|null, 'message'=>string]
 */
function uploadMenuImage($file) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas server (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas form.',
        UPLOAD_ERR_PARTIAL    => 'File hanya ter-upload sebagian. Coba lagi.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak tersedia.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk server.',
        UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh ekstensi PHP.',
    ];

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $code = $file['error'] ?? -1;
        return ['success' => false, 'message' => $errorMessages[$code] ?? 'Upload gagal (kode: ' . $code . ').'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'File upload tidak valid.'];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']);
    } else {
        // Fallback by extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
        $mime = $map[$ext] ?? '';
    }

    if (!in_array($mime, $allowed, true)) {
        return ['success' => false, 'message' => 'Format gambar tidak diizinkan. Gunakan JPG, PNG, WEBP, atau GIF.'];
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Ukuran gambar maksimal 2MB.'];
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };

    $filename = 'menu_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destDir = BASE_PATH . '/uploads/menus/';
    if (!is_dir($destDir)) {
        if (!@mkdir($destDir, 0755, true)) {
            return ['success' => false, 'message' => 'Folder uploads/menus tidak dapat dibuat. Periksa permission.'];
        }
    }
    if (!is_writable($destDir)) {
        return ['success' => false, 'message' => 'Folder uploads/menus tidak dapat ditulis. Periksa permission (chmod 755/775).'];
    }

    $dest = $destDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'message' => 'Gagal memindahkan file ke folder uploads.'];
    }

    // Ensure readable
    @chmod($dest, 0644);

    return ['success' => true, 'path' => 'uploads/menus/' . $filename, 'message' => 'Upload berhasil.'];
}

/**
 * Mengambil pilihan saus aktif. Jika $menuId diberikan, hanya saus yang
 * dihubungkan ke menu tersebut melalui menu_sauces yang dikembalikan.
 */
function getSauceOptions($menuId = null) {
    $db = getDB();
    if ($menuId !== null) {
        $stmt = $db->prepare("SELECT s.id, s.sauce_key, s.name, s.price
            FROM sauces s
            INNER JOIN menu_sauces ms ON ms.sauce_id = s.id
            WHERE ms.menu_id = ? AND s.status = 'active'
            ORDER BY s.name");
        $stmt->execute([(int)$menuId]);
    } else {
        $stmt = $db->query("SELECT id, sauce_key, name, price FROM sauces WHERE status = 'active' ORDER BY name");
    }
    $options = [];
    foreach ($stmt->fetchAll() as $row) {
        $options[(string)$row['sauce_key']] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'price' => (float)$row['price'],
        ];
    }
    return $options;
}
