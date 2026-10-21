<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengunjung');

$db = getDB();
$user = currentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token tidak valid.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($name) < 2) {
            $error = 'Nama terlalu pendek.';
        } else {
            $stmt = $db->prepare('UPDATE users SET name = ? WHERE id = ?');
            $stmt->execute([$name, $_SESSION['user_id']]);
            $_SESSION['user_name'] = $name;

            if ($newPass !== '') {
                if (strlen($newPass) < 6) {
                    $error = 'Password baru minimal 6 karakter.';
                } elseif ($newPass !== $confirm) {
                    $error = 'Konfirmasi password tidak cocok.';
                } else {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $stmt->execute([$hash, $_SESSION['user_id']]);
                    logActivity('profile_update', 'Mengubah password');
                }
            }

            if ($error === '') {
                logActivity('profile_update', 'Memperbarui profil');
                setFlash('success', 'Profil berhasil diperbarui.');
                redirect('/pengunjung/profile.php');
            }
        }
    }
}

// Refresh user
$user = currentUser();

$pageTitle = 'Profil Saya';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Profil Saya</h1>
        </div>
        <div class="content-card" style="max-width:500px;">
            <div class="content-card-body">
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <div class="form-group">
                        <label>Nomor WhatsApp</label>
                        <input type="text" class="form-control" value="<?= e($user['whatsapp']) ?>" disabled>
                        <small style="color:#888;">Nomor WhatsApp tidak dapat diubah.</small>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                    </div>
                    <hr style="margin:20px 0;">
                    <p style="font-size:0.9rem;color:#666;">Kosongkan jika tidak ingin mengubah password.</p>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" class="form-control" minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
