<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}
$flash = getFlash();
$pageTitle = $pageTitle ?? 'Abah Laut';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Abah Laut</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <?php if (!empty($useDashboardCss)): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <?php endif; ?>
</head>
<body>
<?php if ($flash): ?>
<div class="flash-message flash-<?= e($flash['type']) ?>" id="flashMsg">
    <?= e($flash['message']) ?>
    <button type="button" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>
