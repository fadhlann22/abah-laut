<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();
$error = '';
$editUser = null;
$editId = (int)($_GET['edit'] ?? 0);

// Load user for edit
if ($editId > 0) {
    $stmt = $db->prepare('SELECT id, whatsapp, name, role, status FROM users WHERE id = ? AND role != "admin"');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
    if (!$editUser) {
        setFlash('error', 'Pengguna tidak ditemukan atau tidak dapat diedit.');
        redirect('/admin/users.php');
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        // Toggle status
        if (isset($_POST['toggle_id'])) {
            $id = (int)$_POST['toggle_id'];
            if ($id !== (int)$_SESSION['user_id']) {
                $stmt = $db->prepare('UPDATE users SET status = IF(status="active","inactive","active") WHERE id = ? AND role != "admin"');
                $stmt->execute([$id]);
                logActivity('user_toggle', 'Mengubah status pengguna ID ' . $id, 'user', $id);
                setFlash('success', 'Status pengguna berhasil diubah.');
            }
            redirect('/admin/users.php');
        }

        // Delete petugas/pengguna
        if (isset($_POST['delete_id'])) {
            $id = (int)$_POST['delete_id'];
            if ($id === (int)$_SESSION['user_id']) {
                setFlash('error', 'Anda tidak dapat menghapus akun sendiri.');
                redirect('/admin/users.php');
            }
            $stmt = $db->prepare('SELECT id, name, role FROM users WHERE id = ? AND role != "admin"');
            $stmt->execute([$id]);
            $toDelete = $stmt->fetch();
            if ($toDelete) {
                // FK sudah ON DELETE SET NULL, aman dihapus
                $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$id]);
                logActivity('user_delete', 'Menghapus pengguna: ' . $toDelete['name'] . ' (' . $toDelete['role'] . ')', 'user', $id);
                setFlash('success', 'Pengguna "' . $toDelete['name'] . '" berhasil dihapus.');
            } else {
                setFlash('error', 'Pengguna tidak ditemukan atau tidak dapat dihapus.');
            }
            redirect('/admin/users.php');
        }

        // Create new petugas / user
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $name = trim($_POST['name'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'petugas';
            $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

            if (!in_array($role, ['petugas', 'pengunjung'], true)) {
                $role = 'petugas';
            }

            if ($name === '' || strlen($name) < 2) {
                $error = 'Nama wajib diisi (minimal 2 karakter).';
            } elseif ($whatsapp === '' || !preg_match('/^[0-9+]{10,20}$/', $whatsapp)) {
                $error = 'Nomor WhatsApp tidak valid (10-20 digit).';
            } elseif (strlen($password) < 6) {
                $error = 'Password minimal 6 karakter.';
            } else {
                // Cek duplikat whatsapp
                $stmt = $db->prepare('SELECT id FROM users WHERE whatsapp = ?');
                $stmt->execute([$whatsapp]);
                if ($stmt->fetch()) {
                    $error = 'Nomor WhatsApp sudah terdaftar.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare('INSERT INTO users (whatsapp, password, name, role, status) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$whatsapp, $hash, $name, $role, $status]);
                    $newId = $db->lastInsertId();
                    logActivity('user_create', 'Menambahkan ' . $role . ': ' . $name, 'user', $newId);
                    setFlash('success', ucfirst($role) . ' "' . $name . '" berhasil ditambahkan.');
                    redirect('/admin/users.php');
                }
            }
        }

        // Update existing user (edit)
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = (int)($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'petugas';
            $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

            if (!in_array($role, ['petugas', 'pengunjung'], true)) {
                $role = 'petugas';
            }

            if ($id <= 0 || $id === (int)$_SESSION['user_id']) {
                $error = 'Data tidak valid.';
            } elseif ($name === '' || strlen($name) < 2) {
                $error = 'Nama wajib diisi (minimal 2 karakter).';
            } elseif ($whatsapp === '' || !preg_match('/^[0-9+]{10,20}$/', $whatsapp)) {
                $error = 'Nomor WhatsApp tidak valid (10-20 digit).';
            } else {
                // Pastikan user ada dan bukan admin
                $stmt = $db->prepare('SELECT id FROM users WHERE id = ? AND role != "admin"');
                $stmt->execute([$id]);
                if (!$stmt->fetch()) {
                    $error = 'Pengguna tidak ditemukan.';
                } else {
                    // Cek duplikat whatsapp (kecuali milik sendiri)
                    $stmt = $db->prepare('SELECT id FROM users WHERE whatsapp = ? AND id != ?');
                    $stmt->execute([$whatsapp, $id]);
                    if ($stmt->fetch()) {
                        $error = 'Nomor WhatsApp sudah digunakan pengguna lain.';
                    } else {
                        if ($password !== '') {
                            if (strlen($password) < 6) {
                                $error = 'Password minimal 6 karakter.';
                            } else {
                                $hash = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $db->prepare('UPDATE users SET whatsapp=?, password=?, name=?, role=?, status=? WHERE id=?');
                                $stmt->execute([$whatsapp, $hash, $name, $role, $status, $id]);
                            }
                        } else {
                            $stmt = $db->prepare('UPDATE users SET whatsapp=?, name=?, role=?, status=? WHERE id=?');
                            $stmt->execute([$whatsapp, $name, $role, $status, $id]);
                        }

                        if ($error === '') {
                            logActivity('user_update', 'Mengubah data pengguna: ' . $name . ' (ID ' . $id . ')', 'user', $id);
                            setFlash('success', 'Data pengguna "' . $name . '" berhasil diperbarui.');
                            redirect('/admin/users.php');
                        }
                    }
                }
            }
            // Jika error, reload edit data
            if ($error !== '') {
                $editId = $id;
                $editUser = [
                    'id' => $id,
                    'name' => $name,
                    'whatsapp' => $whatsapp,
                    'role' => $role,
                    'status' => $status,
                ];
            }
        }
    }
}

$search = trim($_GET['q'] ?? '');
$userSql = 'SELECT id, whatsapp, name, role, status, created_at FROM users';
$userParams = [];
if ($search !== '') {
    $userSql .= ' WHERE name LIKE ? OR whatsapp LIKE ? OR role LIKE ? OR status LIKE ? OR CAST(id AS CHAR) LIKE ?';
    $like = '%' . $search . '%';
    $userParams = [$like, $like, $like, $like, $like];
}
$userSql .= ' ORDER BY role, name';
$userStmt = $db->prepare($userSql);
$userStmt->execute($userParams);
$users = $userStmt->fetchAll();

$pageTitle = 'Manajemen Pengguna';
$useDashboardCss = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Manajemen Pengguna</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Form Tambah / Edit -->
        <div class="content-card" style="max-width:700px; margin-bottom:24px;">
            <div class="content-card-body">
                <h3 style="margin-top:0; margin-bottom:16px;">
                    <?= $editUser ? '✏️ Edit Pengguna / Petugas' : '➕ Tambah Petugas / Pengguna Baru' ?>
                </h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
                    <?php if ($editUser): ?>
                        <input type="hidden" name="user_id" value="<?= (int)$editUser['id'] ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama *</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= e($editUser['name'] ?? $_POST['name'] ?? '') ?>"
                                   placeholder="Nama lengkap">
                        </div>
                        <div class="form-group">
                            <label>Nomor WhatsApp *</label>
                            <input type="text" name="whatsapp" class="form-control" required
                                   value="<?= e($editUser['whatsapp'] ?? $_POST['whatsapp'] ?? '') ?>"
                                   placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password <?= $editUser ? '(kosongkan jika tidak diubah)' : '*' ?></label>
                            <input type="password" name="password" class="form-control"
                                   <?= $editUser ? '' : 'required' ?>
                                   minlength="6" placeholder="<?= $editUser ? 'Biarkan kosong jika tidak ganti' : 'Minimal 6 karakter' ?>">
                        </div>
                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" class="form-control" required>
                                <option value="petugas" <?= (($editUser['role'] ?? $_POST['role'] ?? 'petugas') === 'petugas') ? 'selected' : '' ?>>Petugas</option>
                                <option value="pengunjung" <?= (($editUser['role'] ?? $_POST['role'] ?? '') === 'pengunjung') ? 'selected' : '' ?>>Pengunjung</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= (($editUser['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Aktif</option>
                            <option value="inactive" <?= (($editUser['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:8px;">
                        <button type="submit" class="btn btn-primary">
                            <?= $editUser ? '💾 Simpan Perubahan' : '➕ Tambah' ?>
                        </button>
                        <?php if ($editUser): ?>
                            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Pengguna -->
        <div class="content-card">
            <div class="content-card-body">
                <h3 style="margin-top:0; margin-bottom:16px;">Daftar Petugas &amp; Pengguna</h3>
                <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center;">
                    <?php if ($editUser): ?><input type="hidden" name="edit" value="<?= (int)$editUser['id'] ?>"><?php endif; ?>
                    <input type="search" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Cari nama, WhatsApp, ID, role, atau status..." aria-label="Cari petugas dan pengguna" style="flex:1;min-width:220px;">
                    <button type="submit" class="btn btn-primary">🔍 Cari</button>
                    <?php if ($search !== ''): ?><a href="<?= BASE_URL ?>/admin/users.php<?= $editUser ? '?edit=' . (int)$editUser['id'] : '' ?>" class="btn btn-secondary">Reset</a><?php endif; ?>
                </form>
                <p style="color:var(--text-muted,#777);font-size:13px;margin:0 0 14px;">Ditemukan <?= count($users) ?> akun<?= $search !== '' ? ' untuk pencarian “' . e($search) . '”' : '' ?>.</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>WhatsApp</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$users): ?>
                            <tr><td colspan="7" style="text-align:center;padding:24px;color:#777;">Petugas atau pengguna tidak ditemukan. Coba kata kunci lain.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= e($u['name']) ?></td>
                                <td><?= e($u['whatsapp']) ?></td>
                                <td><span class="badge badge-info"><?= e($u['role']) ?></span></td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td>
                                <td style="white-space:nowrap;">
                                    <?php if ($u['role'] !== 'admin' && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <a href="<?= BASE_URL ?>/admin/users.php?edit=<?= (int)$u['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                            <input type="hidden" name="toggle_id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning" data-confirm="Ubah status pengguna ini?">Toggle</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                            <input type="hidden" name="delete_id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Yakin ingin menghapus pengguna &quot;<?= e($u['name']) ?>&quot;? Tindakan ini tidak dapat dibatalkan.">Hapus</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#888;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
