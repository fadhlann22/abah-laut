<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengunjung');

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token tidak valid.';
    } else {
        $date = $_POST['reservation_date'] ?? '';
        $time = $_POST['reservation_time'] ?? '';
        $guests = max(1, (int)($_POST['guest_count'] ?? 1));
        $notes = trim($_POST['notes'] ?? '');

        if ($date === '' || $time === '') {
            $error = 'Tanggal dan waktu wajib diisi.';
        } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $error = 'Tanggal tidak boleh di masa lalu.';
        } else {
            $code = generateCode('RSV');
            $stmt = $db->prepare('INSERT INTO reservations (user_id, reservation_code, reservation_date, reservation_time, guest_count, status, notes) VALUES (?, ?, ?, ?, ?, "pending", ?)');
            $stmt->execute([$_SESSION['user_id'], $code, $date, $time, $guests, $notes]);
            $resId = $db->lastInsertId();
            logActivity('reservation_create', "Membuat reservasi $code", 'reservation', $resId);
            setFlash('success', "Reservasi $code berhasil dibuat dengan status pending.");
            redirect('/pengunjung/reservations.php');
        }
    }
}

$stmt = $db->prepare('SELECT * FROM reservations WHERE user_id = ? ORDER BY reservation_date DESC');
$stmt->execute([$_SESSION['user_id']]);
$reservations = $stmt->fetchAll();

$pageTitle = 'Reservasi';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Reservasi Meja</h1>
        </div>

        <div class="content-card" style="margin-bottom:20px;">
            <div class="content-card-header"><h2>Buat Reservasi Baru</h2></div>
            <div class="content-card-body">
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <input type="hidden" name="create_reservation" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tanggal *</label>
                            <input type="date" name="reservation_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['reservation_date'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Waktu *</label>
                            <input type="time" name="reservation_time" class="form-control" value="<?= e($_POST['reservation_time'] ?? '12:00') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jumlah Tamu *</label>
                            <input type="number" name="guest_count" class="form-control" min="1" max="30" value="<?= e($_POST['guest_count'] ?? '2') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="Preferensi meja, dll" value="<?= e($_POST['notes'] ?? '') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim Reservasi</button>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-header"><h2>Riwayat Reservasi Saya</h2></div>
            <div class="content-card-body">
                <?php if (empty($reservations)): ?>
                    <div class="empty-state"><p>Belum ada reservasi.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Kode</th><th>Tanggal</th><th>Waktu</th><th>Tamu</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td><?= e($r['reservation_code']) ?></td>
                                <td><?= e(date('d/m/Y', strtotime($r['reservation_date']))) ?></td>
                                <td><?= e(substr($r['reservation_time'], 0, 5)) ?></td>
                                <td><?= (int)$r['guest_count'] ?></td>
                                <td><span class="badge badge-info"><?= e($r['status']) ?></span></td>
                                <td><?= e($r['notes'] ?? '-') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" disabled>Cetak Bukti <span class="coming-soon">Segera Hadir</span></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
