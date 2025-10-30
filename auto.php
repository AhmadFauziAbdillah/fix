<?php
/**
 * Auto-create all user pages at once
 * Upload this file and run once
 */

$files = [
    'user-login.php' => 'USER_LOGIN_CONTENT',
    'user-register.php' => 'USER_REGISTER_CONTENT',
    'user-dashboard.php' => 'USER_DASHBOARD_CONTENT',
    'user-profile.php' => 'USER_PROFILE_CONTENT',
    'user-logout.php' => 'USER_LOGOUT_CONTENT'
];

$created = [];
$skipped = [];
$errors = [];

foreach ($files as $filename => $contentKey) {
    if (file_exists($filename) && !isset($_GET['force'])) {
        $skipped[] = $filename . ' (already exists)';
        continue;
    }
    
    $content = constant($contentKey);
    if (file_put_contents($filename, $content)) {
        $created[] = $filename;
    } else {
        $errors[] = $filename;
    }
}

// File contents as constants
const USER_LOGIN_CONTENT = <<<'EOD'
<?php
require_once 'config.php';
require_once 'functions.php';

$pageTitle = 'Login Pelanggan';

if (isUserLoggedIn()) {
    redirect('user-dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrPhone = sanitize($_POST['email_phone']);
    $password = $_POST['password'];
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($emailOrPhone) || empty($password)) {
        setFlashMessage('Email/HP dan password harus diisi', 'error');
    } else {
        $result = loginUser($emailOrPhone, $password, $rememberMe);
        
        if ($result['success']) {
            setFlashMessage('Login berhasil! Selamat datang ' . $result['user']['name'], 'success');
            redirect('user-dashboard.php');
        } else {
            setFlashMessage($result['message'], 'error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900">
    <?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="fixed top-6 right-6 z-50 max-w-md">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-6 py-4 rounded-2xl shadow-2xl">
            <span class="font-semibold"><?php echo htmlspecialchars($flash['message']); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-slate-800 rounded-3xl shadow-2xl p-8">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-full"></div>
                    <span class="text-white text-xl font-bold">YR Team</span>
                </div>
                <a href="index.php" class="text-slate-400 hover:text-white transition">← Kembali</a>
            </div>

            <h1 class="text-4xl font-bold text-white mb-3">Login<span class="text-blue-500">.</span></h1>
            <p class="text-slate-400 mb-8">Masuk ke akun Anda</p>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-2">Email atau Nomor HP</label>
                    <input type="text" name="email_phone" class="w-full px-4 py-3.5 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                </div>

                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3.5 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember_me" class="w-4 h-4">
                        <span class="ml-2 text-sm text-slate-400">Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-4 rounded-xl font-semibold">
                    Masuk
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-slate-400 text-sm">
                    Belum punya akun?
                    <a href="user-register.php" class="text-blue-400 hover:text-blue-300 font-semibold">Daftar sekarang</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
EOD;

const USER_REGISTER_CONTENT = <<<'EOD'
<?php
require_once 'config.php';
require_once 'functions.php';

$pageTitle = 'Daftar Akun';

if (isUserLoggedIn()) {
    redirect('user-dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        setFlashMessage('Semua field harus diisi', 'error');
    } elseif ($password !== $confirmPassword) {
        setFlashMessage('Password tidak cocok', 'error');
    } elseif (strlen($password) < 8) {
        setFlashMessage('Password minimal 8 karakter', 'error');
    } else {
        $result = registerUser($email, $password, $fullName, $phone);
        
        if ($result['success']) {
            loginUser($email, $password);
            setFlashMessage('Registrasi berhasil! Selamat datang ' . $fullName, 'success');
            redirect('user-dashboard.php');
        } else {
            setFlashMessage($result['message'], 'error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900">
    <?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="fixed top-6 right-6 z-50 max-w-md">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-6 py-4 rounded-2xl shadow-2xl">
            <span class="font-semibold"><?php echo htmlspecialchars($flash['message']); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-slate-800 rounded-3xl shadow-2xl p-8">
            <div class="flex items-center justify-between mb-8">
                <span class="text-white text-xl font-bold">YR Team</span>
                <a href="index.php" class="text-slate-400 hover:text-white transition">← Kembali</a>
            </div>

            <h1 class="text-4xl font-bold text-white mb-3">Daftar Akun<span class="text-blue-500">.</span></h1>
            <p class="text-slate-400 mb-8">Buat akun untuk booking & tracking</p>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-2">Nama Lengkap</label>
                    <input type="text" name="full_name" class="w-full px-4 py-3.5 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                </div>

                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-4 py-3.5 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                </div>

                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-2">Nomor HP</label>
                    <input type="tel" name="phone" class="w-full px-4 py-3.5 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                </div>

                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3.5 bg-slate-700 border border-slate-600 rounded-xl text-white" required minlength="8">
                </div>

                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-2">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" class="w-full px-4 py-3.5 bg-slate-700 border border-slate-600 rounded-xl text-white" required minlength="8">
                </div>

                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-4 rounded-xl font-semibold">
                    Daftar Sekarang
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-slate-400 text-sm">
                    Sudah punya akun?
                    <a href="user-login.php" class="text-blue-400 hover:text-blue-300 font-semibold">Login di sini</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
EOD;

const USER_DASHBOARD_CONTENT = <<<'EOD'
<?php
require_once 'config.php';
require_once 'functions.php';
requireUserLogin();

$currentUser = getCurrentUser();
$userStats = getUserStats($currentUser['id']);

$warrantyStmt = $conn->prepare("SELECT * FROM warranties WHERE user_id = ? OR nohp = ? ORDER BY registration_date DESC LIMIT 10");
$warrantyStmt->bind_param("is", $currentUser['id'], $currentUser['phone']);
$warrantyStmt->execute();
$warrantyResult = $warrantyStmt->get_result();

$userWarranties = [];
while ($row = $warrantyResult->fetch_assoc()) {
    $expiryDate = new DateTime($row['expiry_date']);
    $today = new DateTime();
    $row['is_active'] = $expiryDate > $today;
    if ($row['is_active']) {
        $interval = $today->diff($expiryDate);
        $row['days_remaining'] = $interval->days;
    }
    $userWarranties[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 min-h-screen">
    <div class="p-4 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-slate-800 rounded-2xl p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-white">Halo, <?php echo htmlspecialchars($currentUser['name']); ?>! 👋</h1>
                        <p class="text-slate-400"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="user-profile.php" class="px-4 py-2 bg-slate-700 text-white rounded-lg">Profil</a>
                        <a href="user-logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg">Logout</a>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="bg-slate-800 rounded-2xl p-6">
                    <p class="text-slate-400 text-sm mb-1">Garansi Aktif</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $userStats['active_warranties']; ?></p>
                </div>
                <div class="bg-slate-800 rounded-2xl p-6">
                    <p class="text-slate-400 text-sm mb-1">Total Booking</p>
                    <p class="text-3xl font-bold text-white"><?php echo $userStats['total_bookings']; ?></p>
                </div>
            </div>

            <div class="bg-slate-800 rounded-2xl p-6">
                <h2 class="text-xl font-bold text-white mb-6">Riwayat Garansi</h2>
                <?php if (empty($userWarranties)): ?>
                    <p class="text-slate-400 text-center py-12">Belum ada data garansi</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($userWarranties as $warranty): ?>
                        <div class="bg-slate-700 rounded-xl p-5">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $warranty['is_active'] ? 'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
                                        <?php echo $warranty['is_active'] ? 'Aktif' : 'Expired'; ?>
                                    </span>
                                    <p class="text-white font-semibold mt-2"><?php echo htmlspecialchars($warranty['model']); ?></p>
                                    <p class="text-slate-400 text-sm">ID: <?php echo htmlspecialchars($warranty['id']); ?></p>
                                </div>
                                <?php if ($warranty['is_active']): ?>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-green-400"><?php echo $warranty['days_remaining']; ?> hari</p>
                                    <p class="text-slate-400 text-xs">tersisa</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
EOD;

const USER_PROFILE_CONTENT = <<<'EOD'
<?php
require_once 'config.php';
require_once 'functions.php';
requireUserLogin();

$currentUser = getCurrentUser();
$userData = getUserById($currentUser['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    $result = updateUserProfile($currentUser['id'], $fullName, $email, $phone);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    if ($result['success']) redirect('user-profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        setFlashMessage('Password baru tidak cocok', 'error');
    } else {
        $result = changeUserPassword($currentUser['id'], $oldPassword, $newPassword);
        setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 min-h-screen">
    <?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="fixed top-6 right-6 z-50">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-6 py-4 rounded-2xl">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="p-4 lg:p-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-slate-800 rounded-2xl p-6 mb-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-white">Edit Profil</h1>
                    <a href="user-dashboard.php" class="px-4 py-2 bg-slate-700 text-white rounded-lg">← Kembali</a>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-slate-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-6">Informasi Profil</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Nama Lengkap</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($userData['full_name']); ?>" 
                                class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" 
                                class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Nomor HP</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($userData['phone']); ?>" 
                                class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <button type="submit" name="update_profile" class="w-full px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-semibold">
                            Simpan Perubahan
                        </button>
                    </form>
                </div>

                <div class="bg-slate-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-6">Ganti Password</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Password Lama</label>
                            <input type="password" name="old_password" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Password Baru</label>
                            <input type="password" name="new_password" minlength="8" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Konfirmasi Password</label>
                            <input type="password" name="confirm_password" minlength="8" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <button type="submit" name="change_password" class="w-full px-4 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-semibold">
                            Ganti Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
EOD;

const USER_LOGOUT_CONTENT = <<<'EOD'
<?php
require_once 'config.php';
require_once 'functions.php';

logoutUser();
setFlashMessage('Berhasil logout. Sampai jumpa lagi!', 'success');
redirect('index.php');
?>
EOD;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User Pages</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui; background: #0f172a; color: #fff; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: #1e293b; padding: 2rem; border-radius: 1rem; }
        h1 { font-size: 2rem; margin-bottom: 1rem; }
        .success { background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; }
        .warning { background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; }
        ul { margin-left: 1.5rem; margin-top: 0.5rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 0.5rem; margin-top: 1rem; margin-right: 0.5rem; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✨ User Pages Creator</h1>
        
        <?php if (!empty($created)): ?>
        <div class="success">
            <strong>✅ Successfully Created:</strong>
            <ul>
                <?php foreach ($created as $file): ?>
                    <li><?php echo $file; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($skipped)): ?>
        <div class="warning">
            <strong>⚠️ Skipped (already exists):</strong>
            <ul>
                <?php foreach ($skipped as $file): ?>
                    <li><?php echo $file; ?></li>
                <?php endforeach; ?>
            </ul>
            <p style="margin-top: 1rem;">Add <code>?force=1</code> to URL to overwrite existing files.</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>❌ Errors:</strong>
            <ul>
                <?php foreach ($errors as $file): ?>
                    <li><?php echo $file; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (empty($created) && empty($errors)): ?>
        <div class="warning">
            <strong>ℹ️ All files already exist!</strong>
        </div>
        <?php endif; ?>

        <div>
            <a href="user-login.php" class="btn">Go to Login</a>
            <a href="user-register.php" class="btn" style="background:#8b5cf6">Register</a>
            <a href="user-dashboard.php" class="btn" style="background:#22c55e">Dashboard</a>
            <a href="index.php" class="btn" style="background:#64748b">Home</a>
        </div>
    </div>
</body>
</html>