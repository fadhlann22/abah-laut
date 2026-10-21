<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'petugas']);
$db = getDB();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($action === 'create') {
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                if ($name === '') throw new RuntimeException('Nama kategori wajib diisi.');
                $stmt = $db->prepare('INSERT INTO categories (name, description, status) VALUES (?, ?, ?)');
                $stmt->execute([$name, $description, ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active']);
                logActivity('category_create', 'Menambah kategori: '.$name, 'category', (int)$db->lastInsertId());
                setFlash('success', 'Kategori berhasil ditambahkan.');
                redirect('/petugas/categories.php');
            } elseif ($action === 'update') {
                $name = trim($_POST['name'] ?? '');
                if ($id < 1 || $name === '') throw new RuntimeException('Data kategori tidak valid.');
                $stmt = $db->prepare('UPDATE categories SET name=?, description=?, status=? WHERE id=?');
                $stmt->execute([$name, trim($_POST['description'] ?? ''), ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active', $id]);
                logActivity('category_update', 'Mengubah kategori: '.$name, 'category', $id);
                setFlash('success', 'Kategori berhasil diperbarui.');
                redirect('/petugas/categories.php');
            } elseif ($action === 'delete') {
                $stmt = $db->prepare('SELECT COUNT(*) FROM menus WHERE category_id=?');
                $stmt->execute([$id]);
                if ((int)$stmt->fetchColumn() > 0) {
                    throw new RuntimeException('Kategori masih digunakan menu. Pindahkan menu ke kategori lain terlebih dahulu.');
                }
                $stmt = $db->prepare('DELETE FROM categories WHERE id=?');
                $stmt->execute([$id]);
                logActivity('category_delete', 'Menghapus kategori ID '.$id, 'category', $id);
                setFlash('success', 'Kategori berhasil dihapus.');
                redirect('/petugas/categories.php');
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000' ? 'Nama kategori sudah digunakan.' : 'Perubahan kategori gagal disimpan.';
        }
    }
}
$categories = $db->query('SELECT c.*, (SELECT COUNT(*) FROM menus m WHERE m.category_id=c.id) AS menu_count FROM categories c ORDER BY c.name')->fetchAll();
$pageTitle = 'Kelola Kategori';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
 <div class="page-header"><h1>Kelola Kategori</h1></div>
 <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
 <div class="content-card" style="margin-bottom:20px"><div class="content-card-header"><h2>Tambah Kategori</h2></div><div class="content-card-body">
 <form method="post" class="form-row"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="create">
 <div class="form-group"><label>Nama kategori *</label><input class="form-control" name="name" maxlength="100" required></div>
 <div class="form-group"><label>Deskripsi</label><input class="form-control" name="description" maxlength="500"></div>
 <div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></div>
 <div class="form-group" style="align-self:end"><button class="btn btn-primary">Tambah kategori</button></div></form></div></div>
 <div class="content-card"><div class="content-card-body"><div class="table-responsive"><table><thead><tr><th>Nama</th><th>Deskripsi</th><th>Jumlah menu</th><th>Status</th><th>Kelola</th></tr></thead><tbody>
 <?php foreach ($categories as $c): ?><tr><td colspan="5"><form method="post" class="form-row" style="align-items:center;margin:0"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="action" value="update">
 <div class="form-group"><input class="form-control" name="name" value="<?= e($c['name']) ?>" required></div><div class="form-group"><input class="form-control" name="description" value="<?= e($c['description']) ?>"></div><div><?= (int)$c['menu_count'] ?> menu</div><div class="form-group"><select class="form-control" name="status"><option value="active" <?= $c['status']==='active'?'selected':'' ?>>Aktif</option><option value="inactive" <?= $c['status']==='inactive'?'selected':'' ?>>Nonaktif</option></select></div><div style="display:flex;gap:6px;flex-wrap:wrap"><button class="btn btn-sm btn-primary">Simpan</button></form>
 <form method="post" style="display:inline" onsubmit="return confirm('Hapus kategori ini?')"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-danger" <?= (int)$c['menu_count']>0?'disabled title="Pindahkan menu terlebih dahulu"':'' ?>>Hapus</button></form></div></td></tr><?php endforeach; ?>
 <?php if (!$categories): ?><tr><td colspan="5">Belum ada kategori.</td></tr><?php endif; ?>
 </tbody></table></div></div></div>
</main></div><script src="<?= BASE_URL ?>/assets/js/script.js"></script></body></html>
