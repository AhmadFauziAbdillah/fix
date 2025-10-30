<?php
// ============================================
// FILE 2: user-logout.php
// ============================================
?>
<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'user-functions.php';

logoutUser();

setFlashMessage('Berhasil logout. Sampai jumpa lagi!', 'success');
redirect('index.php');
?>