<?php
require_once __DIR__ . '/../includes/auth.php';
// Public view allowed, but cart needs login

$db = getDB();
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$categories = $db->query('SELECT * FROM categories WHERE status="active" ORDER BY name')->fetchAll();

$sql = 'SELECT m.*, c.name AS category_name FROM menus m JOIN categories c ON c.id = m.category_id WHERE m.status="active"';
$params = [];
if ($catId > 0) {
    $sql .= ' AND m.category_id = ?';
    $params[] = $catId;
}
$sql .= ' ORDER BY c.name, m.name';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$menus = $stmt->fetchAll();

// Tambah ke keranjang: hanya pengunjung dan validasi saus berdasarkan menu di database.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_id'])) {
    requireRole('pengunjung');
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token keamanan tidak valid.');
        redirect('/pengunjung/menus.php' . ($catId ? '?cat='.$catId : ''));
    }
    $menuId = (int)$_POST['menu_id'];
    $qty = min(20, max(1, (int)($_POST['qty'] ?? 1)));
    $stmt = $db->prepare('SELECT id, name, price FROM menus WHERE id=? AND status="active"');
    $stmt->execute([$menuId]);
    $menu = $stmt->fetch();
    if (!$menu) {
        setFlash('error', 'Menu tidak tersedia.');
        redirect('/pengunjung/menus.php');
    }
    $options = getSauceOptions($menuId);
    $sauceKey = trim((string)($_POST['sauce'] ?? ''));
    if ($options) {
        if (!isset($options[$sauceKey])) {
            setFlash('error', 'Silakan pilih saus yang tersedia untuk menu ini.');
            redirect('/pengunjung/menus.php' . ($catId ? '?cat='.$catId : ''));
        }
        $sauce = $options[$sauceKey];
    } else {
        $sauceKey = 'tanpa_saus';
        $sauce = ['name' => 'Tanpa saus tambahan', 'price' => 0];
    }
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $cartKey = $menuId . '_' . $sauceKey;
    $unitPrice = (float)$menu['price'] + (float)$sauce['price'];
    if (isset($_SESSION['cart'][$cartKey])) {
        $_SESSION['cart'][$cartKey]['qty'] = min(50, (int)$_SESSION['cart'][$cartKey]['qty'] + $qty);
    } else {
        $_SESSION['cart'][$cartKey] = [
            'cart_key' => $cartKey, 'id' => (int)$menu['id'],
            'name' => $menu['name'], 'price' => $unitPrice,
            'base_price' => (float)$menu['price'],
            'sauce_key' => $sauceKey, 'sauce_name' => $sauce['name'],
            'sauce_price' => (float)$sauce['price'], 'qty' => $qty
        ];
    }
    setFlash('success', $menu['name'] . ' (' . $sauce['name'] . ') ditambahkan ke keranjang.');
    redirect('/pengunjung/menus.php' . ($catId ? '?cat='.$catId : ''));
}

$pageTitle = 'Menu';
$isPublic = !isLoggedIn();
if ($isPublic) {
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
} else {
    $useDashboardCss = true;
    require_once __DIR__ . '/../includes/header.php';
}
?>
<?php if (!$isPublic): ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
<?php else: ?>
<div class="section">
    <div class="container">
<?php endif; ?>

        <div class="page-header" style="<?= $isPublic ? '' : '' ?>">
            <h1>Daftar Menu</h1>
            <?php if (isLoggedIn() && ((currentUser()['role'] ?? '') === 'pengunjung')): ?>
            <a href="<?= BASE_URL ?>/pengunjung/cart.php" class="btn btn-primary">Keranjang (<?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0 ?>)</a>
            <?php endif; ?>
        </div>

        <div style="margin-bottom:20px;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="?cat=0" class="btn btn-sm <?= $catId===0?'btn-primary':'btn-secondary' ?>">Semua</a>
            <?php foreach ($categories as $c): ?>
            <a href="?cat=<?= (int)$c['id'] ?>" class="btn btn-sm <?= $catId===(int)$c['id']?'btn-primary':'btn-secondary' ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="menu-grid">
            <?php foreach ($menus as $menu): ?>
            <div class="menu-card">
                <img src="<?= menuImageUrl($menu['image_path']) ?>" alt="<?= e($menu['name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.png'">
                <div class="menu-card-body">
                    <span class="badge badge-info"><?= e($menu['category_name']) ?></span>
                    <h3><?= e($menu['name']) ?></h3>
                    <p class="desc"><?= e($menu['description']) ?></p>
                    <div class="price"><?= formatRupiah($menu['price']) ?></div>
                    <?php if (isLoggedIn() && ((currentUser()['role'] ?? '') === 'pengunjung')): ?>
                    <form method="POST" style="margin-top:12px;display:flex;flex-direction:column;gap:8px;">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                        <input type="hidden" name="menu_id" value="<?= (int)$menu['id'] ?>">
                        <?php $menuSauceOptions = getSauceOptions((int)$menu['id']); ?>
                        <?php if ($menuSauceOptions): ?>
                        <label style="font-size:.9rem;font-weight:600;" for="sauce-<?= (int)$menu['id'] ?>">Pilih saus:</label>
                        <select id="sauce-<?= (int)$menu['id'] ?>" name="sauce" class="form-control" required>
                            <?php foreach ($menuSauceOptions as $sauceKey => $sauceOption): ?>
                            <option value="<?= e($sauceKey) ?>"><?= e($sauceOption['name']) ?><?= $sauceOption['price'] > 0 ? ' (+' . formatRupiah($sauceOption['price']) . ')' : ' (Gratis)' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?><input type="hidden" name="sauce" value="tanpa_saus"><small>Menu ini tidak memiliki pilihan saus.</small><?php endif; ?>
                        <div style="display:flex;gap:8px;align-items:center;"><input type="number" name="qty" value="1" min="1" max="20" style="width:70px;padding:6px;" class="form-control">
                        <button type="submit" class="btn btn-sm btn-primary">+ Keranjang</button></div>
                    </form>
                    <?php elseif (!isLoggedIn()): ?>
                    <p style="margin-top:10px;font-size:0.85rem;"><a href="<?= BASE_URL ?>/login.php">Login</a> sebagai pengunjung untuk memesan</p>
                    <?php else: ?>
                    <p style="margin-top:10px;font-size:0.85rem;color:#777;">Akun admin/petugas hanya untuk mengelola aplikasi.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

<?php if (!$isPublic): ?>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
<?php else: ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php endif; ?>
