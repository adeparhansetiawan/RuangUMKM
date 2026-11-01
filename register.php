<?php
/**
 * RuangUMKM - Unified User Registration Page
 * Allows activating one or multiple roles in one account.
 */

$pageTitle = "Daftar Akun Baru";
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (isLoggedIn()) {
    redirect('dashboard/index.php');
}

$error = null;
$preSelectedRole = $_GET['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfOrDie();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $rolesChosen = $_POST['roles'] ?? ['buyer'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Nama lengkap, alamat email, dan kata sandi wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format alamat email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Kata sandi minimal 6 karakter.";
    } else {
        $regResult = registerUser($name, $email, $password, $phone, $rolesChosen);
        if ($regResult['success']) {
            // Auto login
            loginUser($email, $password);
            flash('success', "Akun Anda berhasil didaftarkan! Selamat bergabung di RuangUMKM.");

            // Redirect based on active role
            if (in_array('seller', $rolesChosen)) {
                redirect('dashboard/seller.php');
            } elseif (in_array('creator', $rolesChosen)) {
                redirect('dashboard/creator.php');
            } elseif (in_array('editor', $rolesChosen)) {
                redirect('dashboard/editor.php');
            } elseif (in_array('organizer', $rolesChosen)) {
                redirect('dashboard/organizer.php');
            } else {
                redirect('dashboard/index.php');
            }
        } else {
            $error = $regResult['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 580px; margin: 40px auto 70px;">
    <div style="background: #ffffff; border-radius: var(--radius-xl); border: 1px solid var(--slate-200); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%); color: #fff; padding: 28px; text-align: center;">
            <h1 style="font-size: 22px; font-weight: 800; margin-bottom: 6px;">Daftar Akun RuangUMKM</h1>
            <p style="font-size: 13px; color: #d8f3dc;">Satu akun fleksibel untuk jual produk, promosi, editing, hingga event</p>
        </div>

        <div style="padding: 28px;">
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <div class="alert-content">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= e($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" required placeholder="Contoh: Budi Santoso" value="<?= e($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp / HP</label>
                        <input type="tel" name="phone" class="form-control" placeholder="081234567890" value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Kata Sandi (Password)</label>
                    <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                </div>

                <!-- Multi-Role Selection Section -->
                <div class="form-group" style="background: var(--slate-50); padding: 18px; border-radius: var(--radius-lg); border: 1px solid var(--slate-200);">
                    <label class="form-label" style="font-size: 14px; font-weight: 700; color: var(--slate-900); margin-bottom: 4px;">
                        Pilih Peran Aktivitas Anda di RuangUMKM:
                    </label>
                    <p style="font-size: 12px; color: var(--slate-500); margin-bottom: 12px;">
                        Anda dapat memilih lebih dari satu peran sesuai kebutuhan. Peran juga dapat ditambah kapan saja dari menu profil.
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="buyer" checked style="margin-top: 3px;">
                            <div>
                                <strong>Pembeli / Pelanggan Umum</strong>
                                <div style="color: var(--slate-500); font-size: 12px;">Belanja produk UMKM, simpan favorit, dan riwayat pesanan.</div>
                            </div>
                        </label>

                        <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="seller" <?= $preSelectedRole === 'seller' ? 'checked' : '' ?> style="margin-top: 3px;">
                            <div>
                                <strong style="color: #166534;"><i class="fa-solid fa-store"></i> Penjual UMKM</strong>
                                <div style="color: var(--slate-500); font-size: 12px;">Buka toko, pasarkan produk lokal, dan terima pesanan online.</div>
                            </div>
                        </label>

                        <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="creator" <?= $preSelectedRole === 'creator' ? 'checked' : '' ?> style="margin-top: 3px;">
                            <div>
                                <strong style="color: #6366f1;"><i class="fa-solid fa-camera"></i> Content Creator</strong>
                                <div style="color: var(--slate-500); font-size: 12px;">Tawarkan jasa endorsement produk dan kolaborasi promosi bersama UMKM.</div>
                            </div>
                        </label>

                        <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="editor" <?= $preSelectedRole === 'editor' ? 'checked' : '' ?> style="margin-top: 3px;">
                            <div>
                                <strong style="color: #d97706;"><i class="fa-solid fa-scissors"></i> Video Editor</strong>
                                <div style="color: var(--slate-500); font-size: 12px;">Tawarkan jasa edit video promosi, Reels, TikTok, atau iklan UMKM.</div>
                            </div>
                        </label>

                        <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="organizer" <?= $preSelectedRole === 'organizer' ? 'checked' : '' ?> style="margin-top: 3px;">
                            <div>
                                <strong style="color: #e11d48;"><i class="fa-solid fa-calendar-star"></i> Penyelenggara Event & Bazar</strong>
                                <div style="color: var(--slate-500); font-size: 12px;">Publikasikan event, info tiket, dan buka pendaftaran sewa booth UMKM.</div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 15px;">
                    <i class="fa-solid fa-user-plus"></i> Daftarkan Akun Saya
                </button>
            </form>

            <div style="margin-top: 24px; text-align: center; font-size: 14px; color: var(--slate-600);">
                Sudah memiliki akun? <a href="<?= BASE_URL ?>login.php" style="font-weight: 700;">Masuk di Sini</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

