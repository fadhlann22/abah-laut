<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengunjung');

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Update quantity / pilihan saus / remove.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        if (isset($_POST['remove_id'])) {
            unset($_SESSION['cart'][(string)$_POST['remove_id']]);
            setFlash('success', 'Item dihapus dari keranjang.');
        } elseif (isset($_POST['update_cart'])) {
            $updated = [];
            foreach ($_SESSION['cart'] as $oldKey => $item) {
                $qty = min(50, max(0, (int)($_POST['qty'][$oldKey] ?? $item['qty'])));
                if ($qty === 0) continue;
                $db = getDB();
                $stmt = $db->prepare('SELECT id,name,price FROM menus WHERE id=? AND status="active"');
                $stmt->execute([(int)$item['id']]);
                $menu = $stmt->fetch();
                if (!$menu) continue;
                $options = getSauceOptions((int)$menu['id']);
                $sauceKey = (string)($_POST['sauce'][$oldKey] ?? ($item['sauce_key'] ?? 'tanpa_saus'));
                if ($options) {
                    if (!isset($options[$sauceKey])) $sauceKey = isset($options[$item['sauce_key'] ?? '']) ? $item['sauce_key'] : array_key_first($options);
                    $sauce = $options[$sauceKey];
                } else {
                    $sauceKey = 'tanpa_saus';
                    $sauce = ['name' => 'Tanpa saus tambahan', 'price' => 0];
                }
                $key = $menu['id'] . '_' . $sauceKey;
                $unitPrice = (float)$menu['price'] + (float)$sauce['price'];
                if (isset($updated[$key])) $updated[$key]['qty'] = min(50, $updated[$key]['qty'] + $qty);
                else $updated[$key] = [
                    'cart_key'=>$key,'id'=>(int)$menu['id'],'name'=>$menu['name'],
                    'price'=>$unitPrice,'base_price'=>(float)$menu['price'],
                    'sauce_key'=>$sauceKey,'sauce_name'=>$sauce['name'],
                    'sauce_price'=>(float)$sauce['price'],'qty'=>$qty
                ];
            }
            $_SESSION['cart'] = $updated;
            setFlash('success', 'Jumlah dan pilihan saus berhasil diperbarui.');
        }
    } else {
        setFlash('error', 'Token keamanan tidak valid.');
    }
    redirect('/pengunjung/cart.php');
}

$cart = $_SESSION['cart'];
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}

$pageTitle = 'Keranjang';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Keranjang Belanja</h1>
            <a href="<?= BASE_URL ?>/pengunjung/menus.php" class="btn btn-secondary">Lanjut Belanja</a>
        </div>
        <div class="content-card">
            <div class="content-card-body">
                <?php if (empty($cart)): ?>
                    <div class="empty-state"><div class="icon">🛒</div><p>Keranjang kosong. <a href="<?= BASE_URL ?>/pengunjung/menus.php">Pilih menu</a></p></div>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>Menu & Saus</th><th>Harga Satuan</th><th>Qty</th><th>Subtotal</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $item): ?>
                                <tr data-base-price="<?= (float)($item['base_price'] ?? $item['price']) ?>">
                                    <td><strong><?= e($item['name']) ?></strong><br>
                                    <?php $cartSauces = getSauceOptions((int)$item['id']); ?>
                                    <?php if ($cartSauces): ?><label><small>Pilihan saus</small><select name="sauce[<?= e($item['cart_key'] ?? $item['id']) ?>]" class="form-control">
                                        <?php foreach ($cartSauces as $key => $opt): ?><option value="<?= e($key) ?>" data-price="<?= (float)$opt['price'] ?>" <?= ($item['sauce_key'] ?? '')===$key?'selected':'' ?>><?= e($opt['name']) ?><?= $opt['price']>0?' (+'.formatRupiah($opt['price']).')':' (Gratis)' ?></option><?php endforeach; ?>
                                    </select></label>
                                    <?php else: ?><small>Tanpa pilihan saus</small><?php endif; ?></td>
                                    <td class="unit-price" data-price="<?= (float)$item['price'] ?>"><?= formatRupiah($item['price']) ?></td>
                                    <td><input type="number" name="qty[<?= e($item['cart_key'] ?? $item['id']) ?>]" value="<?= (int)$item['qty'] ?>" min="0" max="50" style="width:70px;" class="form-control cart-qty"></td>
                                    <td class="line-subtotal"><?= formatRupiah($item['price'] * $item['qty']) ?></td>
                                    <td>
                                        <button type="submit" name="remove_id" value="<?= e($item['cart_key'] ?? $item['id']) ?>" class="btn btn-sm btn-danger" data-confirm="Hapus item?">Hapus</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align:right;font-weight:700;">Total</td>
                                    <td colspan="2" id="cart-total" style="font-weight:700;color:var(--primary);"><?= formatRupiah($total) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div style="margin-top:16px;display:flex;gap:12px;">
                        <button type="submit" name="update_cart" value="1" class="btn btn-secondary">Update Qty</button>
                        <a href="<?= BASE_URL ?>/pengunjung/checkout.php" class="btn btn-primary">Checkout</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
(() => {
  const money = value => 'Rp ' + new Intl.NumberFormat('id-ID', {maximumFractionDigits: 0}).format(value);
  const update = () => {
    let total = 0;
    document.querySelectorAll('tbody tr[data-base-price]').forEach(row => {
      const base = Number(row.dataset.basePrice || 0);
      const select = row.querySelector('select[name^="sauce["]');
      const saucePrice = select ? Number(select.selectedOptions[0]?.dataset.price || 0) : 0;
      const qty = Math.max(0, Math.min(50, Number(row.querySelector('.cart-qty')?.value || 0)));
      const unit = base + saucePrice;
      const subtotal = unit * qty;
      const unitCell = row.querySelector('.unit-price');
      const subCell = row.querySelector('.line-subtotal');
      if (unitCell) unitCell.textContent = money(unit);
      if (subCell) subCell.textContent = money(subtotal);
      total += subtotal;
    });
    const totalNode = document.getElementById('cart-total');
    if (totalNode) totalNode.textContent = money(total);
  };
  document.querySelectorAll('select[name^="sauce["], .cart-qty').forEach(el => el.addEventListener('change', update));
  update();
})();
</script>
</body>
</html>
