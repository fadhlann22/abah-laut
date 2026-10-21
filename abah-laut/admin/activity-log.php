<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$action = trim($_GET['action'] ?? '');

$sql = 'SELECT al.*, u.name AS user_name FROM activity_logs al
        LEFT JOIN users u ON u.id = al.user_id
        WHERE DATE(al.created_at) BETWEEN ? AND ?';
$params = [$from, $to];

if ($action !== '') {
    $sql .= ' AND al.action LIKE ?';
    $params[] = '%' . $action . '%';
}
$sql .= ' ORDER BY al.created_at DESC LIMIT 200';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = 'Activity Log';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Activity Log</h1>
        </div>
        <div class="content-card">
            <div class="content-card-body">
                <form method="GET" class="filter-bar">
                    <div class="form-group">
                        <label>Dari</label>
                        <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
                    </div>
                    <div class="form-group">
                        <label>Sampai</label>
                        <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
                    </div>
                    <div class="form-group">
                        <label>Aksi</label>
                        <input type="text" name="action" class="form-control" placeholder="login, menu_create, ..." value="<?= e($action) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Deskripsi</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="empty-state">Tidak ada log.</td></tr>
                            <?php else: foreach ($logs as $log): ?>
                            <tr>
                                <td><?= e(date('d/m/Y H:i:s', strtotime($log['created_at']))) ?></td>
                                <td><?= e($log['user_name'] ?? 'System') ?></td>
                                <td><span class="badge badge-info"><?= e($log['action']) ?></span></td>
                                <td><?= e($log['description']) ?></td>
                                <td><?= e($log['ip_address'] ?? '-') ?></td>
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
