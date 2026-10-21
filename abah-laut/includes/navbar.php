<?php
$user = currentUser();
?>
<header class="main-header">
    <div class="container header-inner">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
            <span class="logo-icon">🐟</span>
            <span class="logo-text">Abah Laut</span>
        </a>
        <nav class="main-nav" id="mainNav">
            <a href="<?= BASE_URL ?>/index.php" class="<?= isActive('index.php') ?>">Beranda</a>
            <a href="<?= BASE_URL ?>/pengunjung/menus.php">Menu</a>
            <?php if ($user): ?>
                <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
                <a href="<?= BASE_URL ?>/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="<?= isActive('login.php') ?>">Login</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn-nav <?= isActive('register.php') ?>">Daftar</a>
            <?php endif; ?>
        </nav>
        <button class="nav-toggle" id="navToggle" aria-label="Menu">☰</button>
    </div>
</header>
