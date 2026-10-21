<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();
$error = '';
function makeSauceKey($name) {
    $key = strtolower(trim($name));
    $key = preg_replace('/[^a-z0-9]+/i', '_', $key);
    return trim($key, '_') ?: 'saus_' . bin2hex(random_bytes(3));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($action === 'create' || $action === 'update') {
                $name = trim($_POST['name'] ?? '');
                $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT);
                $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';
                if ($name === '' || strlen($name) > 100 || $price === false || $price < 0) throw new RuntimeException('Nama saus dan harga harus valid. Harga tidak boleh negatif.');
                if ($action === 'create') {
                    $key = makeSauceKey($name);
                    $stmt = $db->prepare('INSERT INTO sauces (sauce_key,name,price,status) VALUES (?,?,?,?)');
                    $stmt->execute([$key,$name,$price,$status]);
                    $id = (int)$db->lastInsertId();
                    logActivity('sauce_create','Menambah saus: '.$name,'sauce',$id);
                    setFlash('success','Saus berhasil ditambahkan.');
                } else {
                    $stmt = $db->prepare('UPDATE sauces SET name=?, price=?, status=? WHERE id=?');
                    $stmt->execute([$name,$price,$status,$id]);
                    logActivity('sauce_update','Mengubah saus: '.$name,'sauce',$id);
                    setFlash('success','Saus berhasil diperbarui.');
                }
                redirect('/admin/sauces.php');
            } elseif ($action === 'delete') {
                $db->beginTransaction();
                $stmt = $db->prepare('DELETE FROM menu_sauces WHERE sauce_id=?');
                $stmt->execute([$id]);
                $stmt = $db->prepare('DELETE FROM sauces WHERE id=?');
                $stmt->execute([$id]);
                $db->commit();
                logActivity('sauce_delete','Menghapus saus ID '.$id,'sauce',$id);
                setFlash('success','Saus dihapus. Riwayat transaksi lama tetap tersimpan.');
                redirect('/admin/sauces.php');
            }
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = $e->getCode()==='23000' ? 'Nama saus sudah digunakan.' : 'Perubahan saus gagal disimpan.';
        }
    }
}
$sauces = $db->query('SELECT s.*, (SELECT COUNT(*) FROM menu_sauces ms WHERE ms.sauce_id=s.id) AS menu_count FROM sauces s ORDER BY s.name')->fetchAll();
$menus = $db->query("SELECT id,name FROM menus WHERE status='active' ORDER BY name")->fetchAll();
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='assign' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $menuId=(int)($_POST['menu_id'] ?? 0); $ids=array_values(array_unique(array_filter(array_map('intval',$_POST['sauce_ids'] ?? []))));
    try {
        $db->beginTransaction();
        $stmt=$db->prepare('DELETE FROM menu_sauces WHERE menu_id=?'); $stmt->execute([$menuId]);
        $check=$db->prepare("SELECT id FROM menus WHERE id=? AND status='active'"); $check->execute([$menuId]);
        if (!$check->fetch()) throw new RuntimeException('Menu tidak ditemukan.');
        $ins=$db->prepare("INSERT INTO menu_sauces (menu_id,sauce_id) SELECT ?,id FROM sauces WHERE id=? AND status='active'");
        foreach($ids as $sid) $ins->execute([$menuId,$sid]);
        $db->commit(); setFlash('success','Pilihan saus untuk menu berhasil diperbarui.'); redirect('/admin/sauces.php');
    } catch(Throwable $e) { if($db->inTransaction()) $db->rollBack(); $error=$e instanceof RuntimeException?$e->getMessage():'Gagal menyimpan pilihan saus menu.'; }
}
$assigned = [];
foreach ($db->query('SELECT menu_id,sauce_id FROM menu_sauces')->fetchAll() as $r) $assigned[(int)$r['menu_id']][]=(int)$r['sauce_id'];
$pageTitle='Kelola Saus'; $useDashboardCss=true; require_once __DIR__.'/../includes/header.php';
?>
<div class="dashboard-layout"><?php require_once __DIR__.'/../includes/sidebar.php'; ?><main class="main-content">
<div class="page-header"><h1>Kelola Pilihan Saus</h1><p>Kelola saus dan atur menu yang mendukung pilihan saus. Ini bukan kategori makanan.</p></div>
<?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="content-card" style="margin-bottom:20px"><div class="content-card-header"><h2>Tambah Saus</h2></div><div class="content-card-body">
<form method="post" class="form-row"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="create">
<div class="form-group"><label>Nama saus *</label><input name="name" class="form-control" required maxlength="100" placeholder="Saus Padang"></div><div class="form-group"><label>Biaya tambahan (Rp) *</label><input type="number" name="price" min="0" step="500" value="0" class="form-control" required></div><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></div><div class="form-group" style="align-self:end"><button class="btn btn-primary">Tambah saus</button></div></form></div></div>
<div class="content-card" style="margin-bottom:20px"><div class="content-card-header"><h2>Daftar Saus</h2></div><div class="content-card-body"><div class="table-responsive"><table><thead><tr><th>Nama</th><th>Tambahan</th><th>Status</th><th>Menu terkait</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($sauces as $s): ?><tr>
<td colspan="4"><form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
<input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
<div><label>Nama</label><input name="name" class="form-control" value="<?= e($s['name']) ?>" required style="min-width:140px;max-width:220px"></div>
<div><label>Tambahan (Rp)</label><input type="number" name="price" class="form-control" min="0" step="500" value="<?= (int)$s['price'] ?>" required style="width:130px"></div>
<div><label>Status</label><select name="status" class="form-control"><option value="active" <?= $s['status']==='active'?'selected':'' ?>>Aktif</option><option value="inactive" <?= $s['status']==='inactive'?'selected':'' ?>>Nonaktif</option></select></div>
<div><label>Menu terkait</label><p><?= (int)$s['menu_count'] ?></p></div><div style="align-self:end"><button class="btn btn-sm btn-primary">Simpan</button></div></form></td>
<td><form method="post" onsubmit="return confirm('Hapus saus ini? Riwayat pesanan lama tetap tersimpan.')"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-sm btn-danger">Hapus</button></form></td></tr><?php endforeach; ?>
<?php if(!$sauces): ?><tr><td colspan="5">Belum ada saus.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="content-card"><div class="content-card-header"><h2>Atur saus per menu</h2></div><div class="content-card-body">
<?php foreach($menus as $m): ?><form method="post" style="padding:14px 0;border-bottom:1px solid #eee"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="assign"><input type="hidden" name="menu_id" value="<?= (int)$m['id'] ?>"><h3><?= e($m['name']) ?></h3><div style="display:flex;gap:12px;flex-wrap:wrap;margin:10px 0">
<?php foreach($sauces as $s): if($s['status']!=='active') continue; ?><label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="sauce_ids[]" value="<?= (int)$s['id'] ?>" <?= in_array((int)$s['id'],$assigned[(int)$m['id']]??[],true)?'checked':'' ?>><?= e($s['name']) ?> (<?= formatRupiah($s['price']) ?>)</label><?php endforeach; ?>
</div><button class="btn btn-sm btn-secondary">Simpan saus untuk menu ini</button></form><?php endforeach; ?>
</div></div>
</main></div><script src="<?= BASE_URL ?>/assets/js/script.js"></script></body></html>
