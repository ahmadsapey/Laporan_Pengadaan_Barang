<?php
// Dashboard Admin - Update Data Monitoring
// File: admin.php

session_start();
require_once 'config.php';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.html');
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        if ($_POST['username'] === 'admin' && $_POST['password'] === '123456') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = 'Administrator';
        } else {
            $error = 'Username atau password salah!';
        }
    }

    if (!isset($_SESSION['admin_logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Login Admin</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="d-flex align-items-center justify-content-center vh-100 bg-light">
        <form method="post" class="p-4 bg-white rounded shadow" style="min-width:300px;">
            <h4 class="mb-3">Login Admin</h4>
            <?php if (!empty($error)) echo '<div class="alert alert-danger">'.$error.'</div>'; ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        </body>
        </html>
        <?php
        exit;
    }
}

function validateJson($filePath, &$error) {
    $json = file_get_contents($filePath);
    if ($json === false) {
        $error = 'Tidak dapat membaca file JSON.';
        return null;
    }

    $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);
    $json = trim($json);
    $data = json_decode($json, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        if (function_exists('mb_convert_encoding')) {
            $jsonUtf8 = mb_convert_encoding($json, 'UTF-8', 'Windows-1252');
            $data = json_decode($jsonUtf8, true);
        }
        if ($data === null) {
            $error = 'File JSON tidak valid: ' . json_last_error_msg();
            return null;
        }
    }
    return $data;
}

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note'], $_POST['update_time'])) {
    $update_only = isset($_POST['update_only']);

    if ($update_only) {
        $stmt = $pdo->query('SELECT id FROM monitoring_pengadaan ORDER BY id DESC LIMIT 1');
        $latest = $stmt->fetch();
        if (!$latest) {
            $error = 'Tidak ada data yang dapat diperbarui.';
        } else {
            $stmt = $pdo->prepare('UPDATE monitoring_pengadaan SET update_note = :update_note, update_time = :update_time WHERE id = :id');
            $stmt->execute([
                ':update_note' => $_POST['update_note'],
                ':update_time' => $_POST['update_time'],
                ':id' => $latest['id'],
            ]);
            $success = 'Keterangan update berhasil diperbarui!';
        }
    } else {
        if (!isset($_FILES['jsonFile']) || $_FILES['jsonFile']['error'] !== UPLOAD_ERR_OK) {
            $error = 'File JSON harus dipilih!';
        } else {
            $data = validateJson($_FILES['jsonFile']['tmp_name'], $error);
            if (!$error && $data !== null) {
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }

                $originalName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['jsonFile']['name']));
                $uploadName = uniqid('upload_', true) . '_' . $originalName;
                $uploadPath = 'uploads/' . $uploadName;

                if (move_uploaded_file($_FILES['jsonFile']['tmp_name'], $uploadPath)) {
                    $stmt = $pdo->prepare('INSERT INTO monitoring_pengadaan (file_name, file_path, update_note, update_time, admin) VALUES (:file_name, :file_path, :update_note, :update_time, :admin)');
                    $stmt->execute([
                        ':file_name' => $_FILES['jsonFile']['name'],
                        ':file_path' => $uploadPath,
                        ':update_note' => $_POST['update_note'],
                        ':update_time' => $_POST['update_time'],
                        ':admin' => $_SESSION['admin_name'] ?? 'Admin',
                    ]);
                    $success = 'Data dan keterangan update berhasil disimpan!';
                } else {
                    $error = 'Gagal menyimpan file JSON ke server.';
                }
            }
        }
    }
}

$meta = [];
$stmt = $pdo->query('SELECT update_note, update_time, admin FROM monitoring_pengadaan ORDER BY id DESC LIMIT 1');
$latest = $stmt->fetch();
if ($latest) {
    $meta = $latest;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Update Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>Update Data Monitoring</div>
                    <a href="?logout=1" class="btn btn-sm btn-light">Logout</a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                    <!-- Form Update Data Lengkap -->
                    <form method="post" enctype="multipart/form-data" class="mb-4">
                        <h5 class="mb-3">Update Data Lengkap</h5>
                        <div class="mb-3">
                            <label class="form-label">File Data Monitoring (JSON)</label>
                            <input type="file" name="jsonFile" class="form-control" accept=".json" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan Update</label>
                            <input type="text" name="update_note" class="form-control" placeholder="Contoh: Update data PO April" required value="<?= htmlspecialchars($meta['update_note'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waktu Update</label>
                            <input type="datetime-local" name="update_time" class="form-control" required value="<?= isset($meta['update_time']) ? date('Y-m-d\TH:i', strtotime($meta['update_time'])) : '' ?>">
                        </div>
                        <button type="submit" class="btn btn-success">Simpan Data & Keterangan</button>
                    </form>

                    <hr>

                    <!-- Form Update Keterangan Saja -->
                    <form method="post" class="mt-4">
                        <h5 class="mb-3">Update Keterangan Saja</h5>
                        <input type="hidden" name="update_only" value="1">
                        <div class="mb-3">
                            <label class="form-label">Keterangan Update</label>
                            <input type="text" name="update_note" class="form-control" placeholder="Contoh: Update data PO April" required value="<?= htmlspecialchars($meta['update_note'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waktu Update</label>
                            <input type="datetime-local" name="update_time" class="form-control" required value="<?= isset($meta['update_time']) ? date('Y-m-d\TH:i', strtotime($meta['update_time'])) : '' ?>">
                        </div>
                        <button type="submit" class="btn btn-warning">Update Keterangan Saja</button>
                    </form>
                </div>
                <?php if ($meta): ?>
                <div class="card-footer bg-light">
                    <strong>Terakhir Update:</strong><br>
                    <small><?= htmlspecialchars($meta['update_note'] ?? '-') ?><br>
                    Oleh: <?= htmlspecialchars($meta['admin'] ?? '-') ?><br>
                    Pada: <?= htmlspecialchars($meta['update_time'] ?? '-') ?></small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
