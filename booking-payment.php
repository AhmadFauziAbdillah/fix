<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'booking-functions.php';

requireUserLogin();

$pageTitle = 'Pembayaran Booking';

// Get booking by code
if (!isset($_GET['code'])) {
    redirect('user-dashboard.php');
}

$bookingCode = sanitize($_GET['code']);
$booking = getBookingByCode($bookingCode);

if (!$booking) {
    setFlashMessage('Booking tidak ditemukan', 'error');
    redirect('user-dashboard.php');
}

// Check if booking belongs to current user
$currentUser = getCurrentUser();
if ($booking['user_id'] != $currentUser['id']) {
    setFlashMessage('Akses ditolak', 'error');
    redirect('user-dashboard.php');
}

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $result = uploadPaymentProof($booking['id'], $_FILES['payment_proof']);
    
    if ($result['success']) {
        setFlashMessage('Bukti pembayaran berhasil diupload! Tunggu verifikasi admin.', 'success');
        redirect('booking-payment.php?code=' . $bookingCode);
    } else {
        setFlashMessage($result['message'], 'error');
    }
}

// Calculate remaining time
$expiresAt = new DateTime($booking['expires_at']);
$now = new DateTime();
$interval = $now->diff($expiresAt);
$isExpired = $now > $expiresAt && $booking['payment_status'] === 'pending';
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
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .slide-up { animation: slideUp 0.6s ease-out; }
        .glass-card { background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(148, 163, 184, 0.1); }
        .pulse-slow { animation: pulse 2s ease-in-out infinite; }
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
                    <div>
                        <h1 class="text-2xl font-bold text-white mb-2">Detail Pembayaran</h1>
                        <p class="text-slate-400 text-sm">Kode Booking: <span class="font-mono text-blue-400"><?php echo htmlspecialchars($booking['booking_code']); ?></span></p>
                    </div>
                    <a href="user-dashboard.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                        ← Dashboard
                    </a>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Payment Instructions -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Status Card -->
                    <div class="glass-card rounded-2xl p-6 slide-up">
                        <div class="flex items-center gap-4 mb-6">
                            <?php if ($booking['payment_status'] === 'paid'): ?>
                                <div class="w-16 h-16 bg-green-500 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-green-400">Pembayaran Berhasil</h3>
                                    <p class="text-slate-400 text-sm">Pembayaran telah diverifikasi</p>
                                </div>
                            <?php elseif ($isExpired): ?>
                                <div class="w-16 h-16 bg-red-500 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-red-400">Pembayaran Expired</h3>
                                    <p class="text-slate-400 text-sm">Waktu pembayaran telah habis</p>
                                </div>
                            <?php else: ?>
                                <div class="w-16 h-16 bg-yellow-500 rounded-2xl flex items-center justify-center pulse-slow">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-yellow-400">Menunggu Pembayaran</h3>
                                    <p class="text-slate-400 text-sm">Sisa waktu: <span class="font-semibold" id="countdown"></span></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($booking['payment_status'] === 'pending' && !$isExpired): ?>
                        <!-- Payment Info -->
                        <div class="space-y-4">
                            <div class="p-4 bg-blue-500 bg-opacity-10 border border-blue-500 border-opacity-30 rounded-xl">
                                <p class="text-blue-400 font-semibold mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Cara Pembayaran
                                </p>
                                <ol class="text-slate-300 text-sm space-y-2 ml-7">
                                    <li>1. Transfer ke rekening DANA di bawah</li>
                                    <li>2. Gunakan nominal + kode unik</li>
                                    <li>3. Upload bukti transfer</li>
                                    <li>4. Tunggu verifikasi admin (maks 1 jam)</li>
                                </ol>
                            </div>

                            <!-- Bank Account -->
                            <div class="p-5 bg-gradient-to-br from-blue-600 to-blue-500 rounded-xl">
                                <p class="text-blue-100 text-sm mb-3">Transfer ke:</p>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-white font-bold text-2xl mb-1">DANA</p>
                                        <p class="text-blue-100 font-mono text-lg">0859-1065-45737</p>
                                        <p class="text-blue-100 text-sm">a.n. YR Team</p>
                                    </div>
                                    <button onclick="copyAccount()" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Upload Form -->
                            <?php if (!$booking['payment_proof']): ?>
                            <form method="POST" enctype="multipart/form-data" class="p-5 bg-slate-700 bg-opacity-30 rounded-xl border border-slate-600">
                                <label class="block text-slate-300 font-semibold mb-3">Upload Bukti Transfer</label>
                                <input type="file" name="payment_proof" accept="image/*" required
                                    class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600 cursor-pointer">
                                <p class="text-slate-500 text-xs mt-2">Format: JPG, PNG (Max 5MB)</p>
                                <button type="submit" class="mt-4 w-full px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-semibold transition">
                                    Upload Bukti Pembayaran
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="p-4 bg-green-500 bg-opacity-10 border border-green-500 border-opacity-30 rounded-xl">
                                <p class="text-green-400 font-semibold flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Bukti transfer sudah diupload
                                </p>
                                <p class="text-slate-400 text-sm mt-1">Menunggu verifikasi admin...</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div class="space-y-6">
                    <div class="glass-card rounded-2xl p-6 slide-up">
                        <h3 class="text-lg font-bold text-white mb-4">Ringkasan Booking</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Layanan</span>
                                <span class="text-white font-semibold"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Jadwal</span>
                                <span class="text-white"><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Waktu</span>
                                <span class="text-white"><?php echo date('H:i', strtotime($booking['booking_time'])); ?> WIB</span>
                            </div>
                            <div class="pt-3 border-t border-slate-600">
                                <div class="flex justify-between text-slate-400 mb-2">
                                    <span>Harga Layanan</span>
                                    <span>Rp <?php echo number_format($booking['service_price'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="flex justify-between text-slate-400 mb-3">
                                    <span>Kode Unik</span>
                                    <span>Rp <?php echo number_format($booking['unique_code'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-slate-600">
                                    <span class="text-white font-bold">Total Bayar</span>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-blue-400">
                                            Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>
                                        </p>
                                        <button onclick="copyAmount()" class="text-xs text-slate-400 hover:text-white transition">
                                            Salin nominal
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="glass-card rounded-2xl p-6 slide-up">
                        <h3 class="text-lg font-bold text-white mb-4">Informasi Pelanggan</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-slate-400 mb-1">Nama</p>
                                <p class="text-white font-semibold"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400 mb-1">No. HP</p>
                                <p class="text-white"><?php echo htmlspecialchars($booking['customer_phone']); ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400 mb-1">Motor</p>
                                <p class="text-white"><?php echo htmlspecialchars($booking['motorcycle_model']); ?> (<?php echo htmlspecialchars($booking['motorcycle_plate']); ?>)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyAccount() {
        navigator.clipboard.writeText('085910654573');
        alert('Nomor rekening berhasil disalin!');
    }

    function copyAmount() {
        navigator.clipboard.writeText('<?php echo $booking['total_amount']; ?>');
        alert('Nominal berhasil disalin!');
    }

    <?php if ($booking['payment_status'] === 'pending' && !$isExpired): ?>
    // Countdown timer
    const expiresAt = new Date('<?php echo $booking['expires_at']; ?>').getTime();
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = expiresAt - now;
        
        if (distance < 0) {
            document.getElementById('countdown').textContent = 'Expired';
            location.reload();
            return;
        }
        
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        document.getElementById('countdown').textContent = 
            `${hours}j ${minutes}m ${seconds}d`;
    }
    
    updateCountdown();
    setInterval(updateCountdown, 1000);
    <?php endif; ?>
    </script>
</body>
</html>