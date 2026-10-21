<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('petugas');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['res_id'], $_POST['new_status'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $resId = (int)$_POST['res_id'];
        $newStatus = $_POST['new_status'];
        $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];
        if (in_array($newStatus, $allowed)) {
            $stmt = $db->prepare('UPDATE reservations SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $resId]);
            logActivity('reservation_update', "Mengubah status reservasi ID $resId menjadi $newStatus", 'reservation', $resId);
            setFlash('success', 'Status reservasi diperbarui.');
        }
    }
    redirect('/petugas/reservations.php');
}

$reservations = $db->query('SELECT r.*, u.name AS customer_name, u.whatsapp FROM reservations r JOIN users u ON u.id = r.user_id ORDER BY r.reservation_date DESC, r.reservation_time DESC')->fetchAll();

$pageTitle = 'Kelola Reservasi';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Kelola Reservasi</h1>
        </div>
        <div class="content-card">
            <div class="content-card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pelanggan</th>
                                <th>WhatsApp</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Tamu</th>
                                <th>Status</th>
                                <th>Ubah Status</th>
                                <th>Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td><?= e($r['reservation_code']) ?></td>
                                <td><?= e($r['customer_name']) ?></td>
                                <td><?= e($r['whatsapp']) ?></td>
                                <td><?= e(date('d/m/Y', strtotime($r['reservation_date']))) ?></td>
                                <td><?= e(substr($r['reservation_time'], 0, 5)) ?></td>
                                <td><?= (int)$r['guest_count'] ?></td>
                                <td>
                                    <?php
                                    $badge = match($r['status']) {
                                        'pending' => 'warning', 'confirmed' => 'info',
                                        'completed' => 'success', 'cancelled' => 'danger', default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge badge-<?= $badge ?>"><?= e($r['status']) ?></span>
                                </td>
                                <td>
                                    <form method="POST" style="display:flex;gap:4px;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="res_id" value="<?= (int)$r['id'] ?>">
                                        <select name="new_status" class="form-control" style="width:auto;padding:4px 8px;">
                                            <?php foreach (['pending','confirmed','cancelled','completed'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" disabled>Cetak Bukti <span class="coming-soon">Segera Hadir</span></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
