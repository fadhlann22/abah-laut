<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = getDB();
$stmt = $db->query('SELECT m.*, c.name AS category_name FROM menus m JOIN categories c ON c.id = m.category_id WHERE m.status = "active" ORDER BY m.id DESC LIMIT 6');
$featured = $stmt->fetchAll();
?>

<section class="hero">
    <div class="container hero-content">
        <h1>Selamat Datang di Abah Laut</h1>
        <p>Nikmati kelezatan seafood segar dengan cita rasa autentik. Restoran pilihan keluarga untuk hidangan laut terbaik.</p>
        <div class="hero-btns">
            <a href="<?= BASE_URL ?>/pengunjung/menus.php" class="btn btn-primary">Lihat Menu</a>
            <?php if (!isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline">Daftar Sekarang</a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline">Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Menu Unggulan</h2>
            <p>Pilihan hidangan terbaik dari dapur Abah Laut</p>
        </div>
        <div class="menu-grid">
            <?php foreach ($featured as $menu): ?>
            <div class="menu-card">
                <img src="<?= menuImageUrl($menu['image_path']) ?>" alt="<?= e($menu['name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.png'">
                <div class="menu-card-body">
                    <span class="badge badge-info"><?= e($menu['category_name']) ?></span>
                    <h3><?= e($menu['name']) ?></h3>
                    <p class="desc"><?= e($menu['description']) ?></p>
                    <div class="price"><?= formatRupiah($menu['price']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:32px;">
            <a href="<?= BASE_URL ?>/pengunjung/menus.php" class="btn btn-primary">Lihat Semua Menu</a>
        </div>
    </div>
</section>

<section class="section" style="background:#fff;">
    <div class="container">
        <div class="section-title">
            <h2>Mengapa Abah Laut?</h2>
        </div>
        <div class="features">
            <div class="feature-card">
                <div class="icon">🦐</div>
                <h3>Seafood Segar</h3>
                <p>Bahan baku dipilih setiap hari dari nelayan lokal terpercaya.</p>
            </div>
            <div class="feature-card">
                <div class="icon">👨‍🍳</div>
                <h3>Resep Autentik</h3>
                <p>Bumbu tradisional yang diracik khusus oleh chef berpengalaman.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🏠</div>
                <h3>Suasana Nyaman</h3>
                <p>Tempat ideal untuk keluarga, teman, atau acara spesial.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📱</div>
                <h3>Pesan Mudah</h3>
                <p>Pesan online atau reservasi meja melalui website kami.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
