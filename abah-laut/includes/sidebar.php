<?php
$user = currentUser();
$role = $user['role'] ?? '';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="logo-icon">🐟</span>
        <strong>Abah Laut</strong>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
        <div>
            <div class="user-name"><?= e($user['name'] ?? '') ?></div>
            <div class="user-role"><?= e(ucfirst($role)) ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= isActive('dashboard.php') ?>"><span>📊</span> Dashboard</a>
            <a href="<?= BASE_URL ?>/petugas/menus.php" class="<?= isActive('menus.php') ?>"><span>🍽️</span> Kelola Menu</a>
            <a href="<?= BASE_URL ?>/petugas/categories.php" class="<?= isActive('categories.php') ?>"><span>📁</span> Kategori</a>
            <a href="<?= BASE_URL ?>/admin/sauces.php" class="<?= isActive('sauces.php') ?>"><span>🌶️</span> Pilihan Saus</a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="<?= isActive('users.php') ?>"><span>👥</span> Pengguna</a>
            <a href="<?= BASE_URL ?>/admin/staff-performance.php" class="<?= isActive('staff-performance.php') ?>"><span>📈</span> Kinerja Petugas</a>
            <a href="<?= BASE_URL ?>/admin/activity-log.php" class="<?= isActive('activity-log.php') ?>"><span>📋</span> Activity Log</a>
        <?php elseif ($role === 'petugas'): ?>
            <a href="<?= BASE_URL ?>/petugas/dashboard.php" class="<?= isActive('dashboard.php') ?>"><span>📊</span> Dashboard</a>
            <a href="<?= BASE_URL ?>/petugas/menus.php" class="<?= isActive('menus.php') ?>"><span>🍽️</span> Menu</a>
            <a href="<?= BASE_URL ?>/petugas/categories.php" class="<?= isActive('categories.php') ?>"><span>📁</span> Kategori</a>
            <a href="<?= BASE_URL ?>/petugas/orders.php" class="<?= isActive('orders.php') ?>"><span>🛒</span> Pesanan</a>
            <a href="<?= BASE_URL ?>/petugas/reservations.php" class="<?= isActive('reservations.php') ?>"><span>📅</span> Reservasi</a>
        <?php elseif ($role === 'pengunjung'): ?>
            <a href="<?= BASE_URL ?>/pengunjung/dashboard.php" class="<?= isActive('dashboard.php') ?>"><span>📊</span> Dashboard</a>
            <a href="<?= BASE_URL ?>/pengunjung/menus.php" class="<?= isActive('menus.php') ?>"><span>🍽️</span> Lihat Menu</a>
            <a href="<?= BASE_URL ?>/pengunjung/cart.php" class="<?= isActive('cart.php') ?>"><span>🛒</span> Keranjang</a>
            <a href="<?= BASE_URL ?>/pengunjung/order-history.php" class="<?= isActive('order-history.php') ?>"><span>📜</span> Riwayat Pesanan</a>
            <a href="<?= BASE_URL ?>/pengunjung/reservations.php" class="<?= isActive('reservations.php') ?>"><span>📅</span> Reservasi</a>
            <a href="<?= BASE_URL ?>/pengunjung/profile.php" class="<?= isActive('profile.php') ?>"><span>👤</span> Profil</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/index.php"><span>🏠</span> Beranda</a>
        <a href="<?= BASE_URL ?>/logout.php" class="logout-link"><span>🚪</span> Logout</a>
    </nav>
</aside>
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">☰</button>
