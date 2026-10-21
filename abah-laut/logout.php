<?php
require_once __DIR__ . '/includes/auth.php';
logoutUser();
setFlash('success', 'Anda telah berhasil logout.');
redirect('/login.php');
