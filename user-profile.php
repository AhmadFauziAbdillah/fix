<?php
// ============================================
// FILE 1: user-profile.php
// ============================================
?>
<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'user-functions.php';

requireUserLogin();

$pageTitle = 'Edit Profil';
$currentUser = getCurrentUser();
$userData = getUserById($currentUser['id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    if (empty($fullName) || empty($email) || empty($phone)) {
        setFlashMessage('Semua field harus diisi', 'error');
    } else {
        $result = updateUserProfile($currentUser['id'], $fullName, $email, $phone);
        setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
        
        if ($result['success']) {
            redirect('user-profile.php');
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlashMessage('Semua field password harus diisi', 'error');
    } elseif ($newPassword !== $confirmPassword) {
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
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .slide-up { animation: slideUp 0.6s ease-out; }
        .glass-card { background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(148, 163, 184, 0.1); }
    </style>
</head>
<body class="bg-slate-900 min-h-screen fade-in">
    <?php 
    $flash = getFlashMessage();
    if ($flash): 
    ?>
    <div class="fixed top-6 right-6 z-50 max-w-md">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3">
            <span class="font-semibold"><?php echo htmlspecialchars($flash['message']); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="p-4 lg:p-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="glass-card rounded-2xl p-6 mb-6 slide-up">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Edit Profil
                    </h1>
                    <a href="user-dashboard.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                        ← Kembali
                    </a>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Profile Form -->
                <div class="glass-card rounded-2xl p-6 slide-up" style="animation-delay: 0.1s;">
                    <h2 class="text-xl font-bold text-white mb-6">Informasi Profil</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Nama Lengkap</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($userData['full_name']); ?>" 
                                class="w-full px-4 py-3 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-blue-500 transition" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" 
                                class="w-full px-4 py-3 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-blue-500 transition" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Nomor HP</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($userData['phone']); ?>" 
                                class="w-full px-4 py-3 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-blue-500 transition" required>
                        </div>
                        <button type="submit" name="update_profile" 
                            class="w-full px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-semibold transition">
                            Simpan Perubahan
                        </button>
                    </form>
                </div>

                <!-- Password Form -->
                <div class="glass-card rounded-2xl p-6 slide-up" style="animation-delay: 0.2s;">
                    <h2 class="text-xl font-bold text-white mb-6">Ganti Password</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Password Lama</label>
                            <input type="password" name="old_password" 
                                class="w-full px-4 py-3 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-blue-500 transition" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Password Baru</label>
                            <input type="password" name="new_password" minlength="8"
                                class="w-full px-4 py-3 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-blue-500 transition" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" minlength="8"
                                class="w-full px-4 py-3 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-blue-500 transition" required>
                        </div>
                        <button type="submit" name="change_password" 
                            class="w-full px-4 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-semibold transition">
                            Ganti Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Info -->
            <div class="glass-card rounded-2xl p-6 mt-6 slide-up" style="animation-delay: 0.3s;">
                <h2 class="text-xl font-bold text-white mb-4">Informasi Akun</h2>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-3 p-3 bg-slate-700 bg-opacity-30 rounded-lg">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <p class="text-slate-400">Terdaftar Sejak</p>
                            <p class="text-white font-semibold"><?php echo date('d M Y', strtotime($userData['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-slate-700 bg-opacity-30 rounded-lg">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-slate-400">Status</p>
                            <p class="text-green-400 font-semibold">Verified</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>