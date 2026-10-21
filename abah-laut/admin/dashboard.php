<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();

$stats = [
    'menus' => $db->query('SELECT COUNT(*) FROM menus WHERE status="active"')->fetchColumn(),
    'pengunjung' => $db->query('SELECT COUNT(*) FROM users WHERE role="pengunjung" AND status="active"')->fetchColumn(),
    'petugas' => $db->query('SELECT COUNT(*) FROM users WHERE role="petugas" AND status="active"')->fetchColumn(),
    'orders' => $db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'reservations' => $db->query('SELECT COUNT(*) FROM reservations')->fetchColumn(),
    'pending_orders' => $db->query('SELECT COUNT(*) FROM orders WHERE status="pending"')->fetchColumn(),
];

$recentLogs = $db->query('SELECT al.*, u.name AS user_name FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 10')->fetchAll();

$pageTitle = 'Dashboard Admin';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Dashboard Admin</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon red">🍽️</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['menus'] ?></h3>
                    <p>Menu Aktif</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['pengunjung'] ?></h3>
                    <p>Pengunjung</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">👷</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['petugas'] ?></h3>
                    <p>Petugas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">🛒</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['orders'] ?></h3>
                    <p>Total Pesanan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">📅</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['reservations'] ?></h3>
                    <p>Reservasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">⏳</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['pending_orders'] ?></h3>
                    <p>Pesanan Pending</p>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-header">
                <h2>Aktivitas Terbaru</h2>
                <a href="<?= BASE_URL ?>/admin/activity-log.php" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="content-card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                            <tr><td colspan="4" class="empty-state">Belum ada aktivitas.</td></tr>
                            <?php else: foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><?= e(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
                                <td><?= e($log['user_name'] ?? '-') ?></td>
                                <td><span class="badge badge-info"><?= e($log['action']) ?></span></td>
                                <td><?= e($log['description']) ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
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
