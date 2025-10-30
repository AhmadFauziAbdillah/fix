<?php
require_once 'config.php';
require_once 'functions.php';

logAdminActivity('LOGOUT', null, 'Admin logged out');

// Destroy session
session_destroy();

setFlashMessage('Berhasil logout', 'success');
redirect('index.php');
?>