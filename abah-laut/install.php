<?php
/**
 * Install Helper - Abah Laut
 * Hapus atau rename file ini setelah instalasi selesai untuk keamanan.
 */
$step = $_GET['step'] ?? 'check';
$messages = [];
$canProceed = true;

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $messages[] = ['type' => 'error', 'text' => 'PHP minimal 7.4 diperlukan. Versi saat ini: ' . PHP_VERSION];
    $canProceed = false;
} else {
    $messages[] = ['type' => 'success', 'text' => 'PHP version: ' . PHP_VERSION];
}

// Check PDO
if (!extension_loaded('pdo_mysql')) {
    $messages[] = ['type' => 'error', 'text' => 'Ekstensi pdo_mysql tidak tersedia.'];
    $canProceed = false;
} else {
    $messages[] = ['type' => 'success', 'text' => 'PDO MySQL tersedia.'];
}

// Check folders writable
$writable = ['uploads/menus', 'storage'];
foreach ($writable as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    if (is_writable($path)) {
        $messages[] = ['type' => 'success', 'text' => "Folder $dir dapat ditulis."];
    } else {
        $messages[] = ['type' => 'warning', 'text' => "Folder $dir tidak dapat ditulis. Perbaiki permission."];
    }
}

// Test DB connection
$dbOk = false;
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $messages[] = ['type' => 'success', 'text' => 'Koneksi MySQL berhasil (user: root).'];
    $dbOk = true;

    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'db_alan'");
    if ($stmt->fetch()) {
        $messages[] = ['type' => 'success', 'text' => 'Database db_alan sudah ada.'];
        // Check tables
        $pdo->exec('USE db_alan');
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $messages[] = ['type' => 'info', 'text' => 'Tabel ditemukan: ' . (count($tables) ? implode(', ', $tables) : 'belum ada (impor SQL dulu)')];
    } else {
        $messages[] = ['type' => 'warning', 'text' => 'Database db_alan belum dibuat. Buat di phpMyAdmin lalu impor database/db_alan.sql'];
    }
} catch (PDOException $e) {
    $messages[] = ['type' => 'error', 'text' => 'Koneksi MySQL gagal: ' . $e->getMessage()];
    $canProceed = false;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi - Abah Laut</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f5f5f5; padding: 40px 16px; }
        .box { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h1 { margin-bottom: 8px; }
        .msg { padding: 10px 14px; border-radius: 6px; margin-bottom: 8px; }
        .msg.success { background: #d4edda; color: #155724; }
        .msg.error { background: #f8d7da; color: #721c24; }
        .msg.warning { background: #fff3cd; color: #856404; }
        .msg.info { background: #d1ecf1; color: #0c5460; }
        .steps { margin-top: 24px; line-height: 1.8; }
        .btn { display: inline-block; margin-top: 16px; padding: 10px 20px; background: #c0392b; color: #fff; text-decoration: none; border-radius: 6px; }
        .warn-box { background: #fff3cd; padding: 12px; border-radius: 6px; margin-top: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="box">
    <h1>🐟 Instalasi Abah Laut</h1>
    <p>Pemeriksaan lingkungan sebelum menjalankan website.</p>

    <?php foreach ($messages as $m): ?>
        <div class="msg <?= htmlspecialchars($m['type']) ?>"><?= htmlspecialchars($m['text']) ?></div>
    <?php endforeach; ?>

    <div class="steps">
        <h3>Langkah Instalasi</h3>
        <ol>
            <li>Pastikan Apache & MySQL aktif di XAMPP.</li>
            <li>Buka <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a>.</li>
            <li>Buat database bernama <strong>db_alan</strong>.</li>
            <li>Impor file <code>database/db_alan.sql</code>.</li>
            <li>Buka <a href="index.php">Beranda Abah Laut</a>.</li>
            <li>Login dengan akun demo (lihat README.md).</li>
            <li><strong>Hapus atau rename file install.php</strong> setelah selesai.</li>
        </ol>
    </div>

    <div class="warn-box">
        ⚠️ File ini harus dihapus/dinonaktifkan setelah instalasi untuk keamanan.
    </div>

    <a href="index.php" class="btn">Ke Beranda</a>
    <a href="login.php" class="btn" style="background:#27ae60;">Ke Login</a>
</div>
</body>
</html>
