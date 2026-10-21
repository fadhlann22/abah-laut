<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($whatsapp === '' || $password === '') {
            $error = 'Nomor WhatsApp dan password wajib diisi.';
        } else {
            $result = loginUser($whatsapp, $password);
            if ($result['success']) {
                redirect('/dashboard.php');
            }
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Login</h1>
        <p class="subtitle">Masuk ke akun Abah Laut Anda</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <div class="form-group">
                <label for="whatsapp">Nomor WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= e($_POST['whatsapp'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p style="text-align:center;margin-top:20px;font-size:0.9rem;">
            Belum punya akun? <a href="<?= BASE_URL ?>/register.php">Daftar di sini</a>
        </p>
        <div style="margin-top:24px;padding:12px;background:#f8f9fa;border-radius:8px;font-size:0.85rem;color:#555;">
            <strong>Akun Demo:</strong><br>
            Admin: 081234567890 / admin123<br>
            Petugas: 081298765432 / petugas123<br>
            Pengunjung: 081112223333 / pengunjung123
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
