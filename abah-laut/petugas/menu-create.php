<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'petugas']);

$db = getDB();
$categories = $db->query('SELECT id, name FROM categories WHERE status="active" ORDER BY name')->fetchAll();
$sauces = $db->query("SELECT id,name,price FROM sauces WHERE status='active' ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($name === '' || $category_id <= 0 || $price < 0) {
            $error = 'Nama, kategori, dan harga wajib diisi dengan benar.';
        } else {
            $imagePath = null;
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = uploadMenuImage($_FILES['image']);
                if (!$upload['success']) {
                    $error = $upload['message'];
                } else {
                    $imagePath = $upload['path'];
                }
            }

            if ($error === '') {
                $db->beginTransaction();
                $stmt = $db->prepare('INSERT INTO menus (category_id, name, description, price, image_path, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$category_id, $name, $description, $price, $imagePath, $status, $_SESSION['user_id']]);
                $menuId = (int)$db->lastInsertId();
                $assign = $db->prepare("INSERT INTO menu_sauces (menu_id,sauce_id) SELECT ?,id FROM sauces WHERE id=? AND status='active'");
                foreach (array_unique(array_map('intval', $_POST['sauce_ids'] ?? [])) as $sauceId) $assign->execute([$menuId, $sauceId]);
                $db->commit();
                logActivity('menu_create', 'Menambahkan menu: ' . $name, 'menu', $menuId);
                setFlash('success', 'Menu "' . $name . '" berhasil ditambahkan' . ($imagePath ? ' beserta gambar.' : '.'));
                redirect('/petugas/menus.php');
            }
        }
    }
}

$pageTitle = 'Tambah Menu';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Tambah Menu Baru</h1>
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
                            <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" required placeholder="Contoh: Ikan Bakar Madu">
                        </div>
                        <div class="form-group">
                            <label>Kategori *</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi singkat menu..."><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Harga (Rp) *</label>
                            <input type="number" name="price" class="form-control" min="0" step="500" value="<?= e($_POST['price'] ?? '') ?>" required placeholder="85000">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilihan saus untuk menu ini (opsional)</label>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <?php foreach ($sauces as $sauce): ?>
                            <label style="display:flex;gap:6px;align-items:center;"><input type="checkbox" name="sauce_ids[]" value="<?= (int)$sauce['id'] ?>" <?= in_array((int)$sauce['id'], array_map('intval', $_POST['sauce_ids'] ?? []), true) ? 'checked' : '' ?>> <?= e($sauce['name']) ?> (<?= formatRupiah($sauce['price']) ?>)</label>
                        <?php endforeach; ?>
                        </div><small>Kosongkan jika menu tidak menyediakan pilihan saus.</small>
                    </div>
                    <div class="form-group">
                        <label>Gambar Menu</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <p class="upload-hint">Format: JPG, PNG, WEBP, GIF · Maksimal 2 MB · Gambar akan disimpan di server</p>
                        <img id="imgPreview" class="img-preview" style="display:none;" alt="Preview">
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Simpan Menu</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
