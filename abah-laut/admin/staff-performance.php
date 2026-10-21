<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();

$petugasId = isset($_GET['petugas_id']) ? (int)$_GET['petugas_id'] : 0;
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$petugasList = $db->query('SELECT id, name FROM users WHERE role="petugas" AND status="active" ORDER BY name')->fetchAll();

$sql = 'SELECT u.id, u.name,
        COUNT(o.id) AS total_orders,
        SUM(CASE WHEN o.status="completed" THEN 1 ELSE 0 END) AS completed_orders,
        MAX(o.updated_at) AS last_order_activity
        FROM users u
        LEFT JOIN orders o ON o.petugas_id = u.id AND DATE(o.created_at) BETWEEN ? AND ?
        WHERE u.role = "petugas" AND u.status = "active"';
$params = [$from, $to];

if ($petugasId > 0) {
    $sql .= ' AND u.id = ?';
    $params[] = $petugasId;
}
$sql .= ' GROUP BY u.id, u.name ORDER BY total_orders DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$performance = $stmt->fetchAll();

// Activity count
$actSql = 'SELECT user_id, COUNT(*) AS act_count, MAX(created_at) AS last_act
           FROM activity_logs
           WHERE DATE(created_at) BETWEEN ? AND ?
           GROUP BY user_id';
$actStmt = $db->prepare($actSql);
$actStmt->execute([$from, $to]);
$activities = [];
foreach ($actStmt->fetchAll() as $a) {
    $activities[$a['user_id']] = $a;
}

$pageTitle = 'Kinerja Petugas';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Kinerja Petugas</h1>
        </div>

        <div class="content-card">
            <div class="content-card-body">
                <form method="GET" class="filter-bar">
                    <div class="form-group">
                        <label>Petugas</label>
                        <select name="petugas_id" class="form-control">
                            <option value="0">Semua Petugas</option>
                            <?php foreach ($petugasList as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $petugasId === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Dari</label>
                        <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
                    </div>
                    <div class="form-group">
                        <label>Sampai</label>
                        <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                <p style="font-size:0.9rem;color:#666;margin-bottom:16px;">
                    <em>Catatan: Jumlah pesanan yang ditangani adalah indikator aktivitas, bukan otomatis ukuran kualitas kerja.</em>
                </p>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Petugas</th>
                                <th>Total Pesanan Ditangani</th>
                                <th>Pesanan Selesai</th>
                                <th>Aktivitas Log</th>
                                <th>Aktivitas Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($performance)): ?>
                            <tr><td colspan="5" class="empty-state">Tidak ada data.</td></tr>
                            <?php else: foreach ($performance as $row): ?>
                            <tr>
                                <td><?= e($row['name']) ?></td>
                                <td><?= (int)$row['total_orders'] ?></td>
                                <td><?= (int)$row['completed_orders'] ?></td>
                                <td><?= (int)($activities[$row['id']]['act_count'] ?? 0) ?></td>
                                <td>
                                    <?php
                                    $last = $activities[$row['id']]['last_act'] ?? $row['last_order_activity'];
                                    echo $last ? e(date('d/m/Y H:i', strtotime($last))) : '-';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-header">
                <h2>Cetak Laporan <span class="coming-soon">Segera Hadir</span></h2>
            </div>
            <div class="content-card-body">
                <p>Fitur pencetakan dan ekspor laporan kinerja petugas belum tersedia.</p>
                <button class="btn btn-secondary" disabled>Cetak Laporan (Belum Tersedia)</button>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
