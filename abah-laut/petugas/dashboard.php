<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('petugas');

$db = getDB();
$userId = $_SESSION['user_id'];

$stats = [
    'menus' => $db->query('SELECT COUNT(*) FROM menus WHERE status="active"')->fetchColumn(),
    'pending_orders' => $db->query('SELECT COUNT(*) FROM orders WHERE status="pending"')->fetchColumn(),
    'reservations' => $db->query('SELECT COUNT(*) FROM reservations WHERE status="pending"')->fetchColumn(),
];
$stmt = $db->prepare('SELECT COUNT(*) FROM orders WHERE petugas_id = ?');
$stmt->execute([$userId]);
$stats['my_orders'] = $stmt->fetchColumn();

$recentOrders = $db->query('SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 8')->fetchAll();

$pageTitle = 'Dashboard Petugas';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Dashboard Petugas</h1>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon red">🍽️</div>
                <div class="stat-info"><h3><?= (int)$stats['menus'] ?></h3><p>Menu Aktif</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">⏳</div>
                <div class="stat-info"><h3><?= (int)$stats['pending_orders'] ?></h3><p>Pesanan Pending</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-info"><h3><?= (int)$stats['my_orders'] ?></h3><p>Pesanan Saya Tangani</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-info"><h3><?= (int)$stats['reservations'] ?></h3><p>Reservasi Pending</p></div>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-header">
                <h2>Pesanan Terbaru</h2>
                <a href="<?= BASE_URL ?>/petugas/orders.php" class="btn btn-sm btn-primary">Kelola Pesanan</a>
            </div>
            <div class="content-card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><?= e($o['order_code']) ?></td>
                                <td><?= e($o['customer_name']) ?></td>
                                <td><?= formatRupiah($o['total_amount']) ?></td>
                                <td>
                                    <?php
                                    $badge = match($o['status']) {
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge badge-<?= $badge ?>"><?= e($o['status']) ?></span>
                                </td>
                                <td><?= e(date('d/m H:i', strtotime($o['created_at']))) ?></td>
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
