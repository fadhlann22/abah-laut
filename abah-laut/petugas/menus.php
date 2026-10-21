<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'petugas']);

$db = getDB();

// Hapus menu secara aman. Menu yang sudah dipakai dalam transaksi tidak bisa dihapus karena riwayat pesanan harus tetap utuh.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
        redirect('/petugas/menus.php');
    }

    $id = (int)$_POST['delete_id'];
    $stmt = $db->prepare('SELECT id, name, image_path FROM menus WHERE id = ?');
    $stmt->execute([$id]);
    $menuToDelete = $stmt->fetch();

    if (!$menuToDelete) {
        setFlash('error', 'Menu tidak ditemukan.');
    } else {
        $stmt = $db->prepare('SELECT COUNT(*) FROM order_items WHERE menu_id = ?');
        $stmt->execute([$id]);
        $usedInOrders = (int)$stmt->fetchColumn();

        if ($usedInOrders > 0) {
            setFlash('error', 'Menu "' . $menuToDelete['name'] . '" sudah masuk riwayat pesanan, jadi tidak bisa dihapus. Gunakan tombol Nonaktifkan.');
        } else {
            try {
                $stmt = $db->prepare('DELETE FROM menus WHERE id = ?');
                $stmt->execute([$id]);
                if (!empty($menuToDelete['image_path'])) {
                    $imageFile = BASE_PATH . '/' . ltrim($menuToDelete['image_path'], '/');
                    if (is_file($imageFile)) @unlink($imageFile);
                }
                logActivity('menu_delete', 'Menghapus menu: ' . $menuToDelete['name'], 'menu', $id);
                setFlash('success', 'Menu berhasil dihapus.');
            } catch (PDOException $e) {
                setFlash('error', 'Menu tidak bisa dihapus karena masih terhubung dengan data lain. Coba nonaktifkan menu.');
            }
        }
    }
    redirect('/petugas/menus.php');
}

// Toggle status (aktif/nonaktif)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $id = (int)$_POST['toggle_id'];
        $stmt = $db->prepare('UPDATE menus SET status = IF(status="active","inactive","active") WHERE id = ?');
        $stmt->execute([$id]);
        logActivity('menu_toggle', 'Mengubah status menu ID ' . $id, 'menu', $id);
        setFlash('success', 'Status menu berhasil diubah.');
    }
    redirect('/petugas/menus.php');
}

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT m.*, c.name AS category_name FROM menus m JOIN categories c ON c.id = m.category_id';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE m.name LIKE ? OR c.name LIKE ? OR CAST(m.price AS CHAR) LIKE ? OR m.status LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
}
$sql .= ' ORDER BY m.status DESC, m.name';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$menus = $stmt->fetchAll();

$pageTitle = 'Kelola Menu';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Kelola Menu</h1>
            <a href="<?= BASE_URL ?>/petugas/menu-create.php" class="btn btn-primary">+ Tambah Menu</a>
        </div>
        <div class="content-card">
            <div class="content-card-body">
                <form method="GET" class="search-form" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center;">
                    <input type="search" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Cari nama menu, kategori, harga, atau status..." aria-label="Cari menu" style="flex:1;min-width:220px;">
                    <button type="submit" class="btn btn-primary">🔍 Cari</button>
                    <?php if ($search !== ''): ?><a href="<?= BASE_URL ?>/petugas/menus.php" class="btn btn-secondary">Reset</a><?php endif; ?>
                </form>
                <p style="color:var(--text-muted,#777);font-size:13px;margin:0 0 14px;">Ditemukan <?= count($menus) ?> menu<?= $search !== '' ? ' untuk pencarian “' . e($search) . '”' : '' ?>.</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$menus): ?>
                            <tr><td colspan="6" style="text-align:center;padding:24px;color:#777;">Menu tidak ditemukan. Coba kata kunci lain.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($menus as $m): ?>
                            <tr>
                                <td>
                                    <img src="<?= menuImageUrl($m['image_path']) ?>" alt="" style="width:60px;height:45px;object-fit:cover;border-radius:4px;" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.png'">
                                </td>
                                <td><?= e($m['name']) ?></td>
                                <td><?= e($m['category_name']) ?></td>
                                <td><?= formatRupiah($m['price']) ?></td>
                                <td>
                                    <?php if ($m['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-btns">
                                    <a href="<?= BASE_URL ?>/petugas/menu-edit.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="toggle_id" value="<?= (int)$m['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $m['status']==='active'?'btn-danger':'btn-success' ?>" data-confirm="Ubah status menu ini?">
                                            <?= $m['status']==='active'?'Nonaktifkan':'Aktifkan' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus menu ini? Tindakan ini tidak bisa dibatalkan.');">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="delete_id" value="<?= (int)$m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
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
