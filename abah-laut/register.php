<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if ($password !== $confirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $result = registerPengunjung($whatsapp, $password, $name);
            if ($result['success']) {
                setFlash('success', $result['message']);
                redirect('/login.php');
            }
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Daftar';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Daftar Akun</h1>
        <p class="subtitle">Buat akun pengunjung Abah Laut</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="whatsapp">Nomor WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= e($_POST['whatsapp'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Konfirmasi Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Daftar</button>
        </form>
        <p style="text-align:center;margin-top:20px;font-size:0.9rem;">
            Sudah punya akun? <a href="<?= BASE_URL ?>/login.php">Login di sini</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
