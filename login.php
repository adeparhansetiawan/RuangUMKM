<?php
/**
 * RuangUMKM - User Login Page
 */

$pageTitle = "Masuk Akun";
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (isLoggedIn()) {
    redirect('dashboard/index.php');
}

$error = null;
$redirectTarget = $_GET['redirect'] ?? 'dashboard/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfOrDie();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirectTarget = $_POST['redirect'] ?? 'dashboard/index.php';

    if (empty($email) || empty($password)) {
        $error = "Silakan isi alamat email dan kata sandi.";
    } else {
        $loginResult = loginUser($email, $password);
        if ($loginResult['success']) {
            flash('success', "Selamat datang kembali, " . $loginResult['user']['name'] . "!");
            redirect($redirectTarget);
        } else {
            $error = $loginResult['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 480px; margin: 40px auto 70px;">
    <div style="background: #ffffff; border-radius: var(--radius-xl); border: 1px solid var(--slate-200); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%); color: #fff; padding: 28px; text-align: center;">
            <h1 style="font-size: 22px; font-weight: 800; margin-bottom: 6px;">Masuk ke RuangUMKM</h1>
            <p style="font-size: 13px; color: #d8f3dc;">Akses satu akun untuk seluruh layanan UMKM Indonesia</p>
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
                <input type="hidden" name="redirect" value="<?= e($redirectTarget) ?>">

                <div class="form-group">
                    <label class="form-label">Alamat Email</label>
                    <input type="email" name="email" id="loginEmail" class="form-control" required placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label class="form-label" style="margin-bottom: 0;">Kata Sandi</label>
                        <span style="font-size: 12px; color: var(--slate-500);">Min. 6 Karakter</span>
                    </div>
                    <input type="password" name="password" id="loginPass" class="form-control" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 15px; margin-top: 8px;">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk Sekarang
                </button>
            </form>

            <!-- Quick Demo Accounts Switcher -->
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed var(--slate-200);">
                <span style="display: block; font-size: 12px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; margin-bottom: 10px;">
                    ⚡ Pilih Akun Demo Uji Coba:
                </span>
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="fillDemo('budi.seller@ruangumkm.id')">Penjual UMKM (Budi)</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="fillDemo('dian.creator@ruangumkm.id')">Creator (Dian)</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="fillDemo('fajar.editor@ruangumkm.id')">Editor (Fajar)</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="fillDemo('hendra.event@ruangumkm.id')">Organizer (Hendra)</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="fillDemo('maya.buyer@ruangumkm.id')">Pembeli (Maya)</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="fillDemo('admin@ruangumkm.id')">Admin</button>
                </div>
            </div>

            <div style="margin-top: 24px; text-align: center; font-size: 14px; color: var(--slate-600);">
                Belum punya akun? <a href="<?= BASE_URL ?>register.php" style="font-weight: 700;">Daftar Akun Baru</a>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemo(email) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPass').value = 'password123';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

