<?php
require_once 'config.php';
require_once 'functions.php';

$pageTitle = 'Cek Garansi';
$searchResult = null;
$showResendForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchId = strtoupper(trim($_POST['search_id']));
    
    if (empty($searchId)) {
        setFlashMessage('Masukkan ID Garansi', 'error');
    } else {
        $searchResult = getWarrantyById($searchId);
        if (!$searchResult) {
            $searchResult = ['notFound' => true];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_id'])) {
    $nohp = sanitize($_POST['resend_nohp']);
    
    if (empty($nohp)) {
        setFlashMessage('Masukkan nomor HP yang terdaftar', 'error');
    } else {
        $normalizedPhone = normalizePhone($nohp);
        $warranty = getWarrantyByPhone($normalizedPhone);
        
        if ($warranty) {
            $message = "*INFORMASI GARANSI REMAP ECU*\n\n";
            $message .= "Halo *" . $warranty['nama'] . "*,\n\n";
            $message .= "🔒 ID Garansi: *" . $warranty['id'] . "*\n";
            $message .= "👤 Nama: " . $warranty['nama'] . "\n";
            $message .= "📱 No HP: " . $warranty['nohp'] . "\n";
            $message .= "🏍️ Model Motor: " . $warranty['model'] . "\n";
            $message .= "📅 Tgl Registrasi: " . date('d/m/Y', strtotime($warranty['registration_date'])) . "\n";
            $message .= "⏰ Masa Berlaku: " . $warranty['warranty_days'] . " Hari\n";
            $message .= "📆 Berlaku s/d: " . date('d/m/Y', strtotime($warranty['expiry_date'])) . "\n\n";
            
            if ($warranty['is_active']) {
                $message .= "✅ Status: *AKTIF*\n";
                $expiryDate = new DateTime($warranty['expiry_date']);
                $today = new DateTime();
                $interval = $today->diff($expiryDate);
                $daysRemaining = $interval->days;
                $message .= "⏳ Sisa Waktu: $daysRemaining hari\n\n";
            } else {
                $message .= "❌ Status: *EXPIRED*\n\n";
            }
            
            $message .= "*SIMPAN ID GARANSI ANDA*";
            
            $waURL = 'https://wa.me/' . $normalizedPhone . '?text=' . urlencode($message);
            
            setFlashMessage('Data garansi ditemukan! Mengarahkan ke WhatsApp...', 'success');
            header("refresh:2;url=$waURL");
            exit();
        } else {
            setFlashMessage('Nomor HP tidak terdaftar dalam sistem', 'error');
        }
    }
}

if (isset($_GET['resend'])) {
    $showResendForm = true;
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
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes drawPath {
            to { stroke-dashoffset: 0; }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out;
        }
        
        .float-tree {
            animation: float 8s ease-in-out infinite;
        }
        
        .animated-path {
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: drawPath 3s ease-out forwards;
        }
        
        .glass-morphism {
            background: rgba(45, 52, 70, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }
        
        .btn-secondary {
            background: rgba(71, 85, 105, 0.8);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(71, 85, 105, 1);
        }
        
        .logo-pulse {
            animation: pulse 2s ease-in-out infinite;
        }
        
        .bg-pattern {
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(99, 102, 241, 0.1) 0%, transparent 50%);
        }
    </style>
</head>
<body class="bg-slate-900 overflow-x-hidden">
    <?php 
    $flash = getFlashMessage();
    if ($flash): 
    ?>
    <div class="fixed top-6 right-6 z-50 max-w-md">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 fade-in">
            <?php if ($flash['type'] === 'success'): ?>
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            <?php else: ?>
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            <?php endif; ?>
            <span class="font-semibold"><?php echo htmlspecialchars($flash['message']); ?></span>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const notification = document.querySelector('.fade-in');
            if (notification) {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }
        }, 4000);
    </script>
    <?php endif; ?>

    <div class="min-h-screen flex items-center justify-center p-4 bg-pattern relative">
        <!-- Decorative SVG Path -->
        <svg class="absolute inset-0 w-full h-full opacity-10" xmlns="http://www.w3.org/2000/svg">
            <path class="animated-path" d="M 0,400 Q 250,300 500,400 T 1000,400" stroke="rgba(59, 130, 246, 0.5)" stroke-width="2" fill="none"/>
            <path class="animated-path" d="M 0,500 Q 250,450 500,500 T 1000,500" stroke="rgba(99, 102, 241, 0.5)" stroke-width="2" fill="none" style="animation-delay: 0.5s;"/>
        </svg>

        <!-- Floating Tree Silhouette -->
        <div class="absolute top-10 right-10 opacity-20 float-tree hidden lg:block">
            <svg width="200" height="300" viewBox="0 0 200 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M100 280 Q90 250 80 200 Q70 150 75 100 Q80 50 100 20" stroke="#64748b" stroke-width="8" stroke-linecap="round"/>
                <circle cx="100" cy="30" r="40" fill="#475569" opacity="0.5"/>
                <circle cx="70" cy="70" r="35" fill="#475569" opacity="0.6"/>
                <circle cx="130" cy="70" r="35" fill="#475569" opacity="0.6"/>
                <circle cx="100" cy="100" r="45" fill="#475569" opacity="0.7"/>
            </svg>
        </div>

        <div class="w-full max-w-2xl relative z-10">
            <div class="glass-morphism rounded-3xl shadow-2xl overflow-hidden fade-in">
                <div class="p-8 lg:p-12">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center logo-pulse">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <span class="text-white text-xl font-bold">YR Team</span>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="slide-up" style="animation-delay: 0.2s;">
                        <h1 class="text-4xl lg:text-5xl font-bold text-white mb-3">
                            <?php echo $showResendForm ? 'Kirim Ulang ID' : 'Cek Garansi'; ?><span class="text-blue-500">.</span>
                        </h1>
                        <p class="text-slate-400 mb-8">
                            <?php echo $showResendForm ? 'Masukkan nomor HP untuk menerima ID garansi' : 'Sudah punya ID Garansi?'; ?>
                            <?php if (!$showResendForm): ?>
                                <a href="?resend=1" class="text-blue-400 hover:text-blue-300 transition ml-1">Lupa ID?</a>
                            <?php endif; ?>
                        </p>

                        <?php if (!$showResendForm): ?>
                        <!-- Search Form -->
                        <form method="POST" class="space-y-5 slide-up" style="animation-delay: 0.3s;">
                            <div>
                                <label class="block text-slate-300 text-sm font-semibold mb-2">ID Garansi</label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        name="search_id"
                                        placeholder="ECU-XXXXX-XXXXX"
                                        class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition uppercase font-mono"
                                        value="<?php echo isset($_POST['search_id']) ? htmlspecialchars($_POST['search_id']) : ''; ?>"
                                        required
                                    />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <button
                                type="submit"
                                name="search"
                                class="w-full btn-primary text-white py-3.5 rounded-xl font-semibold shadow-lg"
                            >
                                Cek Garansi Sekarang
                            </button>

                        </form>

                        <!-- Search Result -->
                        <?php if ($searchResult): ?>
                        <div class="mt-6 slide-up">
                            <?php if (isset($searchResult['notFound'])): ?>
                                <div class="text-center py-6 px-4 bg-red-500 bg-opacity-10 border border-red-500 border-opacity-30 rounded-2xl">
                                    <div class="w-16 h-16 bg-red-500 bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                    <p class="text-red-400 font-bold text-lg">ID Tidak Ditemukan</p>
                                    <p class="text-slate-400 text-sm mt-1">Periksa kembali ID garansi Anda</p>
                                </div>
                            <?php else: ?>
                                <div class="bg-slate-700 bg-opacity-50 rounded-2xl p-6 backdrop-blur-lg border border-slate-600">
                                    <!-- Status Badge -->
                                    <div class="text-center mb-6">
                                        <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full <?php echo $searchResult['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?> text-white font-bold shadow-lg">
                                            <?php if ($searchResult['is_active']): ?>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Garansi Aktif</span>
                                            <?php else: ?>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Expired</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($searchResult['is_active']): ?>
                                            <p class="text-blue-400 font-semibold mt-3 text-lg"><?php echo $searchResult['days_remaining']; ?> hari tersisa</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Details -->
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-xs text-slate-400 mb-1">Nama</p>
                                                <p class="text-white font-semibold"><?php echo htmlspecialchars($searchResult['nama']); ?></p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-xs text-slate-400 mb-1">No HP</p>
                                                <p class="text-white font-semibold"><?php echo htmlspecialchars($searchResult['nohp']); ?></p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <span class="text-lg">🏍️</span>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-xs text-slate-400 mb-1">Model Motor</p>
                                                <p class="text-white font-semibold"><?php echo htmlspecialchars($searchResult['model']); ?></p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-600">
                                            <div>
                                                <p class="text-xs text-slate-400 mb-1">Terdaftar</p>
                                                <p class="text-white font-semibold text-sm"><?php echo date('d/m/Y', strtotime($searchResult['registration_date'])); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-400 mb-1">Berlaku s/d</p>
                                                <p class="text-white font-semibold text-sm"><?php echo date('d/m/Y', strtotime($searchResult['expiry_date'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <!-- Resend Form -->
                        <form method="POST" class="space-y-5 slide-up" style="animation-delay: 0.3s;">
                            <div>
                                <label class="block text-slate-300 text-sm font-semibold mb-2">Nomor HP</label>
                                <div class="relative">
                                    <input
                                        type="tel"
                                        name="resend_nohp"
                                        placeholder="08xxxxxxxxxx"
                                        class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition"
                                        pattern="[0-9]+"
                                        required
                                    />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                name="resend_id"
                                class="w-full btn-primary text-white py-3.5 rounded-xl font-semibold shadow-lg"
                            >
                                Kirim via WhatsApp
                            </button>

                            <a
                                href="index.php"
                                class="block w-full text-center btn-secondary text-white py-3.5 rounded-xl font-semibold"
                            >
                                Kembali
                            </a>
                        </form>
                        <?php endif; ?>

                        <!-- Footer Links -->
                        <div class="mt-8 pt-6 border-t border-slate-700 flex justify-center gap-6 text-sm">
                            <a href="#" class="text-slate-400 hover:text-white transition">Term of use</a>
                            <span class="text-slate-600">|</span>
                            <a href="#" class="text-slate-400 hover:text-white transition">Privacy policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>