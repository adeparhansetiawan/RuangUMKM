<?php
/**
 * RuangUMKM - Logout Handler
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
flash('info', 'Anda telah berhasil keluar (logout). Sampai jumpa kembali!');
redirect('');

