<?php
require_once 'config.php';
require_once 'functions.php';

requireAdmin();

$pageTitle = 'Admin Dashboard';

// Handle Delete
if (isset($_GET['delete'])) {
    $result = deleteWarranty($_GET['delete']);
    logAdminActivity('DELETE', $_GET['delete'], 'Deleted warranty');
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirect('admin.php');
}

// Handle Resend WhatsApp
if (isset($_GET['resend'])) {
    $warranty = getWarrantyById($_GET['resend']);
    if ($warranty) {
        $message = "YR Team\n\n";
        $message .= "Berikut adalah data garansi Anda:\n\n";
        $message .= "ID Garansi: *" . $warranty['id'] . "*\n";
        $message .= "Nama: " . $warranty['nama'] . "\n";
        $message .= "No HP: " . $warranty['nohp'] . "\n";
        $message .= "Model Motor: " . $warranty['model'] . "\n";
        $message .= "Tgl Registrasi: " . date('d/m/Y', strtotime($warranty['registration_date'])) . "\n";
        $message .= "Masa Berlaku: " . $warranty['warranty_days'] . " Hari\n";
        $message .= "Berlaku s/d: " . date('d/m/Y', strtotime($warranty['expiry_date'])) . "\n\n";
        
        if ($warranty['is_active']) {
            $expiryDate = new DateTime($warranty['expiry_date']);
            $today = new DateTime();
            $interval = $today->diff($expiryDate);
            $daysRemaining = $interval->days;
        }
        
        $message .= "Website : YrTeam.com\n";
        $message .= "*SIMPAN ID GARANSI ANDA*\n";
        $message .= "Gunakan ID ini untuk cek masa aktif garansi kapan saja.\n\n";
        $message .= "Terima kasih telah mempercayai layanan kami! 🙏";
        
        $sendResult = sendWhatsAppMessageWithFallback($warranty['nohp'], $message);
        
        logAdminActivity('RESEND_WA', $warranty['id'], 'Resent warranty info via WhatsApp Bot');
        
        if ($sendResult['success']) {
            if ($sendResult['method'] === 'api') {
                setFlashMessage('✅ Pesan berhasil dikirim via WhatsApp Bot!', 'success');
            } else {
                setFlashMessage('⚠️ Bot tidak tersedia, menggunakan WhatsApp Web...', 'success');
                header("Location: " . $sendResult['url']);
                exit();
            }
        } else {
            setFlashMessage('❌ Gagal mengirim pesan: ' . $sendResult['error'], 'error');
        }
        
        redirect('admin.php');
    }
}

// Handle Extend Warranty
if (isset($_POST['extend_warranty'])) {
    $oldId = sanitize($_POST['old_id']);
    $newId = strtoupper(trim(sanitize($_POST['new_id'])));
    $warrantyDays = intval($_POST['warranty_days']);
    
    if (empty($newId)) {
        setFlashMessage('ID garansi baru harus diisi', 'error');
    } else {
        $result = extendWarranty($oldId, $newId, $warrantyDays);
        if ($result['success']) {
            logAdminActivity('EXTEND', $newId, "Extended warranty from $oldId");
            
            if (isset($result['whatsapp'])) {
                if ($result['whatsapp']['success']) {
                    if ($result['whatsapp']['method'] === 'api') {
                        setFlashMessage('✅ Garansi diperpanjang & pesan terkirim via Bot! ID Baru: ' . $result['new_id'], 'success');
                    } else {
                        setFlashMessage('✅ Garansi diperpanjang! ID Baru: ' . $result['new_id'], 'success');
                    }
                } else {
                    setFlashMessage('⚠️ Garansi diperpanjang tapi gagal kirim WA: ' . $result['whatsapp']['error'], 'error');
                }
            } else {
                setFlashMessage($result['message'] . ' - ID Baru: ' . $result['new_id'], 'success');
            }
        } else {
            setFlashMessage($result['message'], 'error');
        }
        redirect('admin.php');
    }
}

// Handle Update Duration
if (isset($_POST['update_duration'])) {
    $id = sanitize($_POST['warranty_id']);
    $warrantyDays = intval($_POST['warranty_days']);
    
    $result = updateWarrantyDuration($id, $warrantyDays);
    logAdminActivity('UPDATE_DURATION', $id, "Updated warranty duration to $warrantyDays days");
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirect('admin.php');
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$warranties = getAllWarranties($search);
$stats = getStatistics();

$extendWarranty = null;
if (isset($_GET['extend'])) {
    $extendWarranty = getWarrantyById($_GET['extend']);
}

$durationWarranty = null;
if (isset($_GET['duration'])) {
    $durationWarranty = getWarrantyById($_GET['duration']);
}

$botStatus = getBotStatus();

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
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
        
        .btn-action {
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            transform: scale(1.1);
        }
        
        .pulse-dot {
            animation: pulse 2s ease-in-out infinite;
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
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-white">Admin Dashboard</h1>
                            <p class="text-slate-400 text-sm">Sistem Garansi Remap ECU</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="register.php" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-semibold transition flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tambah
                        </a>
                        <a href="logout.php" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Bot Status -->
                <div class="mt-4 p-4 rounded-xl <?php echo $botStatus['online'] && $botStatus['connected'] ? 'bg-green-500' : 'bg-yellow-500'; ?> bg-opacity-10 border <?php echo $botStatus['online'] && $botStatus['connected'] ? 'border-green-500' : 'border-yellow-500'; ?> border-opacity-30">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full <?php echo $botStatus['online'] && $botStatus['connected'] ? 'bg-green-500 pulse-dot' : 'bg-yellow-500'; ?>"></div>
                        <div class="flex-1">
                            <p class="font-semibold <?php echo $botStatus['online'] && $botStatus['connected'] ? 'text-green-400' : 'text-yellow-400'; ?>">
                                <?php if ($botStatus['online'] && $botStatus['connected']): ?>
                                    ✅ WhatsApp Bot Connected
                                    <?php if (isset($botStatus['botNumber'])): ?>
                                        <span class="text-sm font-normal">(<?php echo $botStatus['botNumber']; ?>)</span>
                                    <?php endif; ?>
                                <?php elseif ($botStatus['online']): ?>
                                    ⚠️ Bot Online - Waiting for QR Scan
                                <?php else: ?>
                                    ❌ Bot Offline
                                <?php endif; ?>
                            </p>
                            <p class="text-slate-400 text-xs mt-1">
                                <?php if ($botStatus['online'] && $botStatus['connected']): ?>
                                    Pesan otomatis aktif
                                <?php else: ?>
                                    Menggunakan WhatsApp Web (fallback)
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($botStatus['online'] && !$botStatus['connected']): ?>
                            <a href="<?php echo BOT_QR; ?>" target="_blank" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm font-semibold">
                                Scan QR
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="glass-card rounded-2xl p-6 stat-card slide-up" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm font-semibold mb-1">Total Garansi</p>
                            <p class="text-3xl font-bold text-white"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 stat-card slide-up" style="animation-delay: 0.2s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm font-semibold mb-1">Garansi Aktif</p>
                            <p class="text-3xl font-bold text-green-400"><?php echo $stats['active']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 stat-card slide-up" style="animation-delay: 0.3s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm font-semibold mb-1">Expired</p>
                            <p class="text-3xl font-bold text-red-400"><?php echo $stats['expired']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="glass-card rounded-2xl p-6 slide-up" style="animation-delay: 0.4s;">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                    <h2 class="text-xl font-bold text-white">Daftar Garansi</h2>
                    
                    <!-- Search Form -->
                    <form method="GET" class="flex gap-2 w-full lg:w-auto">
                        <input
                            type="text"
                            name="search"
                            placeholder="Cari ID atau Nama..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="px-4 py-2 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition flex-1 lg:w-64"
                        />
                        <button type="submit" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg font-semibold transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                        <?php if ($search): ?>
                        <a href="admin.php" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-semibold transition">
                            Reset
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (empty($warranties)): ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <p class="text-slate-400"><?php echo $search ? 'Tidak ada hasil pencarian' : 'Belum ada data garansi'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-700">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">ID Garansi</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Nama</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">No HP</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Model</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Durasi</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Tgl Daftar</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warranties as $warranty): ?>
                                <tr class="border-b border-slate-700 border-opacity-50 hover:bg-slate-700 hover:bg-opacity-30 transition">
                                    <td class="px-4 py-3 text-sm font-mono text-slate-300"><?php echo htmlspecialchars($warranty['id']); ?></td>
                                    <td class="px-4 py-3 text-sm text-white"><?php echo htmlspecialchars($warranty['nama']); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-300"><?php echo htmlspecialchars($warranty['nohp']); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-300"><?php echo htmlspecialchars($warranty['model']); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-300"><?php echo $warranty['warranty_days']; ?> hari</td>
                                    <td class="px-4 py-3 text-sm text-slate-300"><?php echo date('d/m/Y', strtotime($warranty['registration_date'])); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $warranty['is_active'] ? 'bg-green-500 bg-opacity-20 text-green-400' : 'bg-red-500 bg-opacity-20 text-red-400'; ?>">
                                            <?php echo $warranty['is_active'] ? 'Aktif' : 'Expired'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <a href="?resend=<?php echo urlencode($warranty['id']); ?>" class="p-2 text-green-400 hover:bg-green-500 hover:bg-opacity-20 rounded-lg transition btn-action" title="Kirim via Bot">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                </svg>
                                            </a>
                                            <a href="register.php?edit=<?php echo urlencode($warranty['id']); ?>" class="p-2 text-blue-400 hover:bg-blue-500 hover:bg-opacity-20 rounded-lg transition btn-action" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            <a href="?duration=<?php echo urlencode($warranty['id']); ?>" class="p-2 text-yellow-400 hover:bg-yellow-500 hover:bg-opacity-20 rounded-lg transition btn-action" title="Durasi">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </a>
                                            <a href="?extend=<?php echo urlencode($warranty['id']); ?>" class="p-2 text-purple-400 hover:bg-purple-500 hover:bg-opacity-20 rounded-lg transition btn-action" title="Perpanjang">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </a>
                                            <a href="admin.php?delete=<?php echo urlencode($warranty['id']); ?>" onclick="return confirm('Yakin ingin menghapus?')" class="p-2 text-red-400 hover:bg-red-500 hover:bg-opacity-20 rounded-lg transition btn-action" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01 -1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Extend Warranty Modal -->
    <?php if ($extendWarranty): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="glass-card rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-xl font-bold text-white mb-4">Perpanjang Garansi</h3>
            <form method="POST">
                <input type="hidden" name="old_id" value="<?php echo htmlspecialchars($extendWarranty['id']); ?>">
                
                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-semibold mb-2">ID Garansi Lama</label>
                    <input type="text" value="<?php echo htmlspecialchars($extendWarranty['id']); ?>" disabled class="w-full px-4 py-2 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-lg text-white">
                </div>

                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-semibold mb-2">ID Garansi Baru *</label>
                    <input type="text" name="new_id" required class="w-full px-4 py-2 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition" placeholder="Masukkan ID baru">
                </div>

                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-semibold mb-2">Durasi Garansi (Hari) *</label>
                    <input type="number" name="warranty_days" value="<?php echo htmlspecialchars($extendWarranty['warranty_days']); ?>" required class="w-full px-4 py-2 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition">
                </div>

                <div class="flex gap-2">
                    <button type="submit" name="extend_warranty" class="flex-1 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg font-semibold transition">
                        Perpanjang
                    </button>
                    <a href="admin.php" class="flex-1 px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-semibold transition text-center">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Update Duration Modal -->
    <?php if ($durationWarranty): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="glass-card rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-xl font-bold text-white mb-4">Update Durasi Garansi</h3>
            <form method="POST">
                <input type="hidden" name="warranty_id" value="<?php echo htmlspecialchars($durationWarranty['id']); ?>">
                
                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-semibold mb-2">ID Garansi</label>
                    <input type="text" value="<?php echo htmlspecialchars($durationWarranty['id']); ?>" disabled class="w-full px-4 py-2 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-lg text-white">
                </div>

                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-semibold mb-2">Durasi Saat Ini</label>
                    <input type="text" value="<?php echo htmlspecialchars($durationWarranty['warranty_days']); ?> hari" disabled class="w-full px-4 py-2 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-lg text-white">
                </div>

                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-semibold mb-2">Durasi Baru (Hari) *</label>
                    <input type="number" name="warranty_days" value="<?php echo htmlspecialchars($durationWarranty['warranty_days']); ?>" required class="w-full px-4 py-2 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition">
                </div>

                <div class="flex gap-2">
                    <button type="submit" name="update_duration" class="flex-1 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-semibold transition">
                        Update
                    </button>
                    <a href="admin.php" class="flex-1 px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-semibold transition text-center">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>