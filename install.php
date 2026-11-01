<?php
/**
 * RuangUMKM - 1-Click Database Installer & Environment Checker
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/database/init_db.php';

$migrationResult = null;
$error = null;

// Handle installation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $host = trim($_POST['host'] ?? '127.0.0.1');
    $user = trim($_POST['user'] ?? 'root');
    $pass = $_POST['pass'] ?? '';
    $port = (int)($_POST['port'] ?? 3306);

    $migrationResult = runDatabaseMigration($host, $user, $pass, $port);
}

// Check current connection status
$dbStatus = false;
$existingTables = [];
$connError = null;

try {
    $testPdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=ruangumkm;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $dbStatus = true;
    $stmt = $testPdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $connError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemasangan Database - RuangUMKM</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #f7f9fa 100%);
            color: #1e293b;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background: #1b4332;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .header p { color: #d8f3dc; font-size: 14px; }
        .body-content { padding: 32px; }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .status-badge.connected { background: #dcfce7; color: #166534; }
        .status-badge.disconnected { background: #fee2e2; color: #991b1b; }
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert ul { margin-left: 20px; margin-top: 8px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #334155; }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-control:focus { outline: none; border-color: #2d6a4f; box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.15); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #2d6a4f; color: #ffffff; }
        .btn-primary:hover { background: #1b4332; }
        .btn-success { background: #10b981; color: #ffffff; }
        .btn-success:hover { background: #059669; }
        .demo-accounts {
            margin-top: 32px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
        }
        .demo-accounts h3 { font-size: 16px; font-weight: 700; margin-bottom: 12px; color: #0f172a; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-weight: 600; color: #475569; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-admin { background: #f3e8ff; color: #6b21a8; }
        .badge-seller { background: #dcfce7; color: #15803d; }
        .badge-creator { background: #e0e7ff; color: #3730a3; }
        .badge-editor { background: #fef3c7; color: #b45309; }
        .badge-organizer { background: #ffe4e6; color: #be123c; }
        .badge-buyer { background: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Pemasangan Database RuangUMKM</h1>
            <p>Pengaturan & Inisialisasi Database MySQL XAMPP</p>
        </div>

        <div class="body-content">
            <?php if ($migrationResult): ?>
                <?php if ($migrationResult['success']): ?>
                    <div class="alert alert-success">
                        <strong><i class="fa-solid fa-circle-check"></i> Berhasil!</strong>
                        <p><?= htmlspecialchars($migrationResult['message']) ?></p>
                        <ul>
                            <?php foreach ($migrationResult['details'] as $detail): ?>
                                <li><?= htmlspecialchars($detail) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <a href="<?= BASE_URL ?>" class="btn btn-success" style="margin-bottom: 24px;">
                        <i class="fa-solid fa-house"></i> Masuk ke Halaman Utama RuangUMKM
                    </a>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong><i class="fa-solid fa-triangle-exclamation"></i> Gagal Memasang Database:</strong>
                        <p><?= htmlspecialchars($migrationResult['message']) ?></p>
                        <ul>
                            <?php foreach ($migrationResult['details'] as $detail): ?>
                                <li><?= htmlspecialchars($detail) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($dbStatus): ?>
                <div class="status-badge connected">
                    <i class="fa-solid fa-circle-check"></i> Database Terhubung (<?= count($existingTables) ?> Tabel Siap Digunakan)
                </div>
                <div style="margin-bottom: 24px;">
                    <a href="<?= BASE_URL ?>" class="btn btn-primary">
                        <i class="fa-solid fa-arrow-right"></i> Buka Aplikasi RuangUMKM Sekarang
                    </a>
                </div>
            <?php else: ?>
                <div class="status-badge disconnected">
                    <i class="fa-solid fa-circle-xmark"></i> Belum Terhubung: <?= htmlspecialchars($connError ?? 'Koneksi gagal') ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="install">
                
                <h3 style="font-size: 16px; margin-bottom: 14px;">Konfigurasi MySQL Lokal XAMPP:</h3>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Host MySQL</label>
                        <input type="text" name="host" class="form-control" value="127.0.0.1" required>
                    </div>
                    <div class="form-group">
                        <label>Port</label>
                        <input type="number" name="port" class="form-control" value="3306" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Username MySQL</label>
                        <input type="text" name="user" class="form-control" value="root" required>
                    </div>
                    <div class="form-group">
                        <label>Password MySQL (Kosongkan jika default XAMPP)</label>
                        <input type="password" name="pass" class="form-control" value="" placeholder="Default XAMPP: kosong">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-bolt"></i> <?= $dbStatus ? 'Pasang Ulang / Reset Database & Data Awal' : 'Mulai Pemasangan Database Otomatis' ?>
                </button>
            </form>

            <div class="demo-accounts">
                <h3><i class="fa-solid fa-users"></i> Akun Demo Pengujian (Password: <code>password123</code>)</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Peran (Role)</th>
                                <th>Nama</th>
                                <th>Email Demo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-admin">Administrator</span></td>
                                <td>Admin RuangUMKM</td>
                                <td>admin@ruangumkm.id</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-seller">Penjual UMKM</span></td>
                                <td>Budi Santoso (Kopi Gayo)</td>
                                <td>budi.seller@ruangumkm.id</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-seller">Penjual UMKM</span></td>
                                <td>Siti Nurhaliza (Batik Solo)</td>
                                <td>siti.seller@ruangumkm.id</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-creator">Content Creator</span></td>
                                <td>Dian Ayu (Food Creator)</td>
                                <td>dian.creator@ruangumkm.id</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-editor">Video Editor</span></td>
                                <td>Fajar Visual (Short-form Editor)</td>
                                <td>fajar.editor@ruangumkm.id</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-organizer">Penyelenggara Event</span></td>
                                <td>Hendra Wijaya (Nusantara Expo)</td>
                                <td>hendra.event@ruangumkm.id</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-buyer">Pembeli & Multi-Peran</span></td>
                                <td>Maya Permata</td>
                                <td>maya.buyer@ruangumkm.id</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

