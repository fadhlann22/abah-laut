<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('petugas');

$db = getDB();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $orderId = (int)$_POST['order_id'];
        $newStatus = $_POST['new_status'];
        $allowed = ['pending', 'processing', 'completed', 'cancelled'];
        if (in_array($newStatus, $allowed, true)) {
            $stmt = $db->prepare('UPDATE orders SET status = ?, petugas_id = ? WHERE id = ?');
            $stmt->execute([$newStatus, $_SESSION['user_id'], $orderId]);
            $codeStmt = $db->prepare('SELECT order_code FROM orders WHERE id = ?');
            $codeStmt->execute([$orderId]);
            $code = $codeStmt->fetchColumn();
            logActivity('order_update', "Mengubah status pesanan $code menjadi $newStatus", 'order', $orderId);
            setFlash('success', "Status pesanan $code diperbarui menjadi $newStatus.");
        }
    }
    redirect('/petugas/orders.php');
}

$filter = $_GET['status'] ?? '';
$sql = 'SELECT o.*, u.name AS customer_name, u.whatsapp AS customer_wa,
        p.name AS petugas_name
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN users p ON p.id = o.petugas_id';
$params = [];
if (in_array($filter, ['pending','processing','completed','cancelled'], true)) {
    $sql .= ' WHERE o.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY FIELD(o.status, "pending","processing","completed","cancelled"), o.created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Load items for all orders
$orderIds = array_column($orders, 'id');
$itemsByOrder = [];
if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY id");
    $itemStmt->execute($orderIds);
    foreach ($itemStmt->fetchAll() as $item) {
        $itemsByOrder[$item['order_id']][] = $item;
    }
}

$pageTitle = 'Kelola Pesanan';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Kelola Pesanan</h1>
        </div>

        <div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="?status=" class="btn btn-sm <?= $filter===''?'btn-primary':'btn-secondary' ?>">Semua</a>
            <a href="?status=pending" class="btn btn-sm <?= $filter==='pending'?'btn-primary':'btn-secondary' ?>">Pending</a>
            <a href="?status=processing" class="btn btn-sm <?= $filter==='processing'?'btn-primary':'btn-secondary' ?>">Processing</a>
            <a href="?status=completed" class="btn btn-sm <?= $filter==='completed'?'btn-primary':'btn-secondary' ?>">Completed</a>
            <a href="?status=cancelled" class="btn btn-sm <?= $filter==='cancelled'?'btn-primary':'btn-secondary' ?>">Cancelled</a>
        </div>

        <div class="content-card">
            <div class="content-card-body">
                <?php if (empty($orders)): ?>
                    <div class="empty-state"><div class="icon">🛒</div><p>Belum ada pesanan<?= $filter ? " dengan status $filter" : '' ?>.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pelanggan</th>
                                <th>WhatsApp</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Petugas</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o):
                                $badge = match($o['status']) {
                                    'pending' => 'warning', 'processing' => 'info',
                                    'completed' => 'success', 'cancelled' => 'danger', default => 'secondary'
                                };
                                $items = $itemsByOrder[$o['id']] ?? [];
                            ?>
                            <tr>
                                <td><strong><?= e($o['order_code']) ?></strong></td>
                                <td><?= e($o['customer_name']) ?></td>
                                <td><?= e($o['customer_wa']) ?></td>
                                <td><?= formatRupiah($o['total_amount']) ?></td>
                                <td><span class="badge badge-<?= $badge ?>"><?= e($o['status']) ?></span></td>
                                <td><?= e($o['petugas_name'] ?? '—') ?></td>
                                <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                                <td>
                                    <form method="POST" style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                        <select name="new_status" class="status-select">
                                            <?php foreach (['pending','processing','completed','cancelled'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="order-detail-row">
                                <td colspan="8">
                                    <strong style="font-size:0.85rem;color:#666;">Detail Item:</strong>
                                    <?php if (empty($items)): ?>
                                        <span style="color:#999;font-size:0.85rem;"> Tidak ada item</span>
                                    <?php else: ?>
                                    <ul class="order-items-list">
                                        <?php foreach ($items as $it): ?>
                                        <li>
                                            <span><?= e($it['menu_name']) ?> — <?= e($it['sauce_name'] ?? 'Saus Asam Manis') ?> × <?= (int)$it['quantity'] ?></span>
                                            <span><?= formatRupiah($it['subtotal']) ?></span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php if ($o['notes']): ?>
                                        <p style="margin-top:6px;font-size:0.85rem;color:#666;"><em>Catatan: <?= e($o['notes']) ?></em></p>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <div style="margin-top:8px;">
                                        <button class="btn btn-sm btn-secondary" disabled>Cetak Struk <span class="coming-soon">Segera Hadir</span></button>
                                    </div>
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
