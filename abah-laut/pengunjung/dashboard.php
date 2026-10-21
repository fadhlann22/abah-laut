<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengunjung');

$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$stmt->execute([$userId]);
$orderCount = $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ?');
$stmt->execute([$userId]);
$resCount = $stmt->fetchColumn();

$stmt = $db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

$pageTitle = 'Dashboard Pengunjung';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Halo, <?= e(currentUser()['name']) ?>!</h1>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orange">🛒</div>
                <div class="stat-info"><h3><?= (int)$orderCount ?></h3><p>Total Pesanan</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-info"><h3><?= (int)$resCount ?></h3><p>Reservasi</p></div>
            </div>
        </div>
        <div class="content-card">
            <div class="content-card-header">
                <h2>Pesanan Terakhir</h2>
                <a href="<?= BASE_URL ?>/pengunjung/order-history.php" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="content-card-body">
                <?php if (empty($recentOrders)): ?>
                    <div class="empty-state"><div class="icon">🛒</div><p>Belum ada pesanan. <a href="<?= BASE_URL ?>/pengunjung/menus.php">Lihat menu</a></p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Kode</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><?= e($o['order_code']) ?></td>
                                <td><?= formatRupiah($o['total_amount']) ?></td>
                                <td><span class="badge badge-info"><?= e($o['status']) ?></span></td>
                                <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;">
            <a href="<?= BASE_URL ?>/pengunjung/menus.php" class="btn btn-primary">Lihat Menu & Pesan</a>
            <a href="<?= BASE_URL ?>/pengunjung/reservations.php" class="btn btn-secondary">Buat Reservasi</a>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
