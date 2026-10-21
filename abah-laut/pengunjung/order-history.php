<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengunjung');

$db = getDB();
$stmt = $db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'Riwayat Pesanan';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Riwayat Pesanan</h1>
        </div>
        <div class="content-card">
            <div class="content-card-body">
                <?php if (empty($orders)): ?>
                    <div class="empty-state"><div class="icon">📜</div><p>Belum ada pesanan.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Kode</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Catatan</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><?= e($o['order_code']) ?></td>
                                <td><?= formatRupiah($o['total_amount']) ?></td>
                                <td><span class="badge badge-info"><?= e($o['status']) ?></span></td>
                                <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                                <td><?= e($o['notes'] ?? '-') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" disabled>Cetak Struk <span class="coming-soon">Segera Hadir</span></button>
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
