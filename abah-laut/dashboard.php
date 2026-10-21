<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
switch ($user['role']) {
    case 'admin':
        redirect('/admin/dashboard.php');
        break;
    case 'petugas':
        redirect('/petugas/dashboard.php');
        break;
    case 'pengunjung':
        redirect('/pengunjung/dashboard.php');
        break;
    default:
        logoutUser();
        redirect('/login.php');
}
