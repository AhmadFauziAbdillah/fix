<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'user-functions.php';

requireUserLogin();

$pageTitle = 'Dashboard';
$currentUser = getCurrentUser();
$userStats = getUserStats($currentUser['id']);

// Get user's warranties
$warrantyStmt = $conn->prepare(
    "SELECT * FROM warranties 
     WHERE user_id = ? OR nohp = ?
     ORDER BY registration_date DESC LIMIT 10"
);
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
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="bg-slate-900 min-h-screen fade-in">
    <?php 
    $flash = getFlashMessage();
    if ($flash): 
    ?>
    <div class="fixed top-6 right-6 z-50 max-w-md">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3">
            <?php if ($flash['type'] === 'success'): ?>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            <?php else: ?>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            <?php endif; ?>
            <span class="font-semibold"><?php echo htmlspecialchars($flash['message']); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="p-4 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="glass-card rounded-2xl p-6 mb-6 slide-up">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-bold text-white">
                                <?php echo strtoupper(substr($currentUser['name'], 0, 2)); ?>
                            </span>
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-white">
                                Halo, <?php echo htmlspecialchars($currentUser['name']); ?>! 👋
                            </h1>
                            <p class="text-slate-400 text-sm"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="user-profile.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-semibold transition flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Profil
                        </a>
                        <a href="user-logout.php" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                            Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="glass-card rounded-2xl p-6 stat-card slide-up" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm font-semibold mb-1">Total Booking</p>
                            <p class="text-3xl font-bold text-white"><?php echo $userStats['total_bookings']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 stat-card slide-up" style="animation-delay: 0.2s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm font-semibold mb-1">Garansi Aktif</p>
                            <p class="text-3xl font-bold text-green-400"><?php echo $userStats['active_warranties']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 stat-card slide-up" style="animation-delay: 0.3s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm font-semibold mb-1">Member Sejak</p>
                            <p class="text-xl font-bold text-blue-400">
                                <?php 
                                $userData = getUserById($currentUser['id']);
                                echo date('M Y', strtotime($userData['created_at'])); 
                                ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-card rounded-2xl p-6 mb-6 slide-up" style="animation-delay: 0.4s;">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Quick Actions
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="#" class="p-4 bg-blue-500 bg-opacity-10 hover:bg-opacity-20 border border-blue-500 border-opacity-30 rounded-xl transition flex items-center gap-4 group">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-semibold">Buat Booking</p>
                            <p class="text-slate-400 text-sm">Booking layanan baru</p>
                        </div>
                    </a>

                    <a href="index.php" class="p-4 bg-green-500 bg-opacity-10 hover:bg-opacity-20 border border-green-500 border-opacity-30 rounded-xl transition flex items-center gap-4 group">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-semibold">Cek Garansi</p>
                            <p class="text-slate-400 text-sm">Track status garansi</p>
                        </div>
                    </a>

                    <a href="user-profile.php" class="p-4 bg-purple-500 bg-opacity-10 hover:bg-opacity-20 border border-purple-500 border-opacity-30 rounded-xl transition flex items-center gap-4 group">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-semibold">Edit Profil</p>
                            <p class="text-slate-400 text-sm">Update informasi</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Warranties List -->
            <div class="glass-card rounded-2xl p-6 slide-up" style="animation-delay: 0.5s;">
                <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Riwayat Garansi
                </h2>

                <?php if (empty($userWarranties)): ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <p class="text-slate-400">Belum ada data garansi</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($userWarranties as $warranty): ?>
                        <div class="bg-slate-700 bg-opacity-50 rounded-xl p-5 border border-slate-600 hover:border-blue-500 transition">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $warranty['is_active'] ? 'bg-green-500 bg-opacity-20 text-green-400' : 'bg-red-500 bg-opacity-20 text-red-400'; ?>">
                                            <?php echo $warranty['is_active'] ? 'Aktif' : 'Expired'; ?>
                                        </span>
                                        <span class="text-slate-400 font-mono text-sm"><?php echo htmlspecialchars($warranty['id']); ?></span>
                                    </div>
                                    <p class="text-white font-semibold text-lg mb-2"><?php echo htmlspecialchars($warranty['model']); ?></p>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <p class="text-slate-400">
                                            Terdaftar: <span class="text-white"><?php echo date('d/m/Y', strtotime($warranty['registration_date'])); ?></span>
                                        </p>
                                        <p class="text-slate-400">
                                            Berlaku s/d: <span class="text-white"><?php echo date('d/m/Y', strtotime($warranty['expiry_date'])); ?></span>
                                        </p>
                                    </div>
                                </div>
                                <?php if ($warranty['is_active']): ?>
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <p class="text-slate-400 text-xs mb-1">Sisa Waktu</p>
                                        <p class="text-2xl font-bold text-green-400"><?php echo $warranty['days_remaining']; ?> hari</p>
                                    </div>
                                    <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
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