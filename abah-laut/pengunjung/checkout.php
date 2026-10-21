<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengunjung');

if (empty($_SESSION['cart'])) {
    setFlash('warning', 'Keranjang kosong.');
    redirect('/pengunjung/cart.php');
}

$db = getDB();
$cart = $_SESSION['cart'];
$error = '';

// Recalculate menu and sauce prices from db_alan; never trust session prices.
$total = 0;
$validatedItems = [];
foreach ($cart as $item) {
    $stmt = $db->prepare('SELECT id, name, price FROM menus WHERE id = ? AND status="active"');
    $stmt->execute([(int)$item['id']]);
    $menu = $stmt->fetch();
    if (!$menu) continue;
    $qty = min(50, max(1, (int)$item['qty']));
    $sauceOptions = getSauceOptions((int)$menu['id']);
    $sauceKey = (string)($item['sauce_key'] ?? 'tanpa_saus');
    if ($sauceOptions) {
        if (!isset($sauceOptions[$sauceKey])) {
            $error = 'Pilihan saus untuk salah satu menu sudah tidak tersedia. Kembali ke keranjang dan pilih saus aktif.';
            continue;
        }
        $sauce = $sauceOptions[$sauceKey];
    } else {
        $sauce = ['name' => 'Tanpa saus tambahan', 'price' => 0];
        $sauceKey = 'tanpa_saus';
    }
    $unitPrice = (float)$menu['price'] + (float)$sauce['price'];
    $sub = $unitPrice * $qty;
    $total += $sub;
    $validatedItems[] = [
        'menu_id' => $menu['id'],
        'name' => $menu['name'],
        'sauce_name' => $sauce['name'],
        'sauce_price' => (float)$sauce['price'],
        'qty' => $qty,
        'unit_price' => $unitPrice,
        'subtotal' => $sub
    ];
}

if (empty($validatedItems) && $error === '') {
    unset($_SESSION['cart']);
    setFlash('error', 'Item tidak valid atau menu sudah tidak tersedia.');
    redirect('/pengunjung/menus.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token tidak valid.';
    } else {
        $notes = trim($_POST['notes'] ?? '');
        try {
            $db->beginTransaction();
            $code = generateCode('ORD');
            $stmt = $db->prepare('INSERT INTO orders (user_id, order_code, total_amount, status, notes) VALUES (?, ?, ?, "pending", ?)');
            $stmt->execute([$_SESSION['user_id'], $code, $total, $notes]);
            $orderId = $db->lastInsertId();

            $itemStmt = $db->prepare('INSERT INTO order_items (order_id, menu_id, menu_name, quantity, unit_price, subtotal, sauce_name, sauce_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($validatedItems as $vi) {
                $itemStmt->execute([$orderId, $vi['menu_id'], $vi['name'], $vi['qty'], $vi['unit_price'], $vi['subtotal'], $vi['sauce_name'], $vi['sauce_price']]);
            }
            $db->commit();
            unset($_SESSION['cart']);
            logActivity('order_create', "Membuat pesanan $code", 'order', $orderId);
            setFlash('success', "Pesanan $code berhasil dibuat! Status: pending. Petugas akan memproses segera.");
            redirect('/pengunjung/order-history.php');
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Gagal menyimpan pesanan. Silakan coba lagi.';
        }
    }
}

$pageTitle = 'Checkout';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Checkout</h1>
        </div>
        <div class="content-card">
            <div class="content-card-body">
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                <h3 style="margin-bottom:12px;">Ringkasan Pesanan</h3>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Menu & Saus</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
                        <tbody>
                            <?php foreach ($validatedItems as $vi): ?>
                            <tr>
                                <td><?= e($vi['name']) ?><br><small><?= e($vi['sauce_name']) ?><?= $vi['sauce_price'] > 0 ? ' (+' . formatRupiah($vi['sauce_price']) . ')' : '' ?></small></td>
                                <td><?= (int)$vi['qty'] ?></td>
                                <td><?= formatRupiah($vi['unit_price']) ?></td>
                                <td><?= formatRupiah($vi['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="3" style="text-align:right;font-weight:700;">Total</td><td style="font-weight:700;color:var(--primary);"><?= formatRupiah($total) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
                <form method="POST" style="margin-top:20px;">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <div class="form-group">
                        <label>Catatan (opsional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: makan di tempat, bungkus, dll"><?= e($_POST['notes'] ?? '') ?></textarea>
                    </div>
                    <p style="font-size:0.9rem;color:#666;margin-bottom:12px;">Pesanan akan disimpan dengan status <strong>pending</strong>. Fitur pembayaran online dan cetak struk belum tersedia.</p>
                    <?php if ($error === ''): ?><button type="submit" class="btn btn-primary">Konfirmasi Pesanan</button><?php else: ?><a href="<?= BASE_URL ?>/pengunjung/cart.php" class="btn btn-primary">Perbaiki Keranjang</a><?php endif; ?>
                    <a href="<?= BASE_URL ?>/pengunjung/cart.php" class="btn btn-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
