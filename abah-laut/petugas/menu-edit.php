<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'petugas']);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM menus WHERE id = ?');
$stmt->execute([$id]);
$menu = $stmt->fetch();
if (!$menu) {
    setFlash('error', 'Menu tidak ditemukan.');
    redirect('/petugas/menus.php');
}

$categories = $db->query('SELECT id, name FROM categories WHERE status="active" ORDER BY name')->fetchAll();
$sauces = $db->query("SELECT id,name,price FROM sauces WHERE status='active' ORDER BY name")->fetchAll();
$menuSauceStmt = $db->prepare('SELECT sauce_id FROM menu_sauces WHERE menu_id=?');
$menuSauceStmt->execute([$id]);
$currentSauceIds = array_map('intval', $menuSauceStmt->fetchAll(PDO::FETCH_COLUMN));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($name === '' || $category_id <= 0 || $price < 0) {
            $error = 'Data tidak valid.';
        } else {
            $imagePath = $menu['image_path'];
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = uploadMenuImage($_FILES['image']);
                if (!$upload['success']) {
                    $error = $upload['message'];
                } else {
                    // Hapus gambar lama jika ada dan berbeda
                    if ($imagePath && $imagePath !== $upload['path'] && file_exists(BASE_PATH . '/' . $imagePath)) {
                        @unlink(BASE_PATH . '/' . $imagePath);
                    }
                    $imagePath = $upload['path'];
                }
            }

            if ($error === '') {
                $db->beginTransaction();
                $stmt = $db->prepare('UPDATE menus SET category_id=?, name=?, description=?, price=?, image_path=?, status=? WHERE id=?');
                $stmt->execute([$category_id, $name, $description, $price, $imagePath, $status, $id]);
                $del = $db->prepare('DELETE FROM menu_sauces WHERE menu_id=?'); $del->execute([$id]);
                $assign = $db->prepare("INSERT INTO menu_sauces (menu_id,sauce_id) SELECT ?,id FROM sauces WHERE id=? AND status='active'");
                foreach (array_unique(array_map('intval', $_POST['sauce_ids'] ?? [])) as $sauceId) $assign->execute([$id, $sauceId]);
                $db->commit();
                logActivity('menu_update', 'Mengubah menu: ' . $name, 'menu', $id);
                setFlash('success', 'Menu berhasil diperbarui.');
                redirect('/petugas/menus.php');
            }
        }
    }
}

$pageTitle = 'Edit Menu';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Edit Menu</h1>
            <a href="<?= BASE_URL ?>/petugas/menus.php" class="btn btn-secondary">← Kembali</a>
        </div>
        <div class="content-card" style="max-width:700px;">
            <div class="content-card-body">
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Menu *</label>
                            <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? $menu['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori *</label>
                            <select name="category_id" class="form-control" required>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (($_POST['category_id'] ?? $menu['category_id']) == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? $menu['description']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Harga (Rp) *</label>
                            <input type="number" name="price" class="form-control" min="0" step="500" value="<?= e($_POST['price'] ?? $menu['price']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?= ($menu['status'] === 'active') ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= ($menu['status'] === 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilihan saus untuk menu ini (opsional)</label>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <?php foreach ($sauces as $sauce): ?>
                            <label style="display:flex;gap:6px;align-items:center;"><input type="checkbox" name="sauce_ids[]" value="<?= (int)$sauce['id'] ?>" <?= in_array((int)$sauce['id'], array_map('intval', $_POST['sauce_ids'] ?? $currentSauceIds), true) ? 'checked' : '' ?>> <?= e($sauce['name']) ?> (<?= formatRupiah($sauce['price']) ?>)</label>
                        <?php endforeach; ?>
                        </div><small>Kosongkan jika menu tidak menyediakan pilihan saus.</small>
                    </div>
                    <div class="form-group">
                        <label>Gambar Saat Ini</label><br>
                        <img src="<?= menuImageUrl($menu['image_path']) ?>" class="img-preview" alt="Gambar menu" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.png'">
                    </div>
                    <div class="form-group">
                        <label>Ganti Gambar (opsional)</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <p class="upload-hint">Kosongkan jika tidak ingin mengganti · Format JPG/PNG/WEBP/GIF · Maks 2 MB</p>
                        <img id="imgPreview" class="img-preview" style="display:none;" alt="Preview">
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
