<?php
require_once 'config.php';
require_once 'functions.php';

$pageTitle = 'Daftar Garansi';
$editMode = false;
$editData = null;

if (isset($_GET['edit']) && isAdmin()) {
    $editMode = true;
    $editData = getWarrantyById($_GET['edit']);
    if (!$editData) {
        redirect('admin.php');
    }
    $pageTitle = 'Edit Garansi';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? sanitize($_POST['nama']) : '';
    $nohp = isset($_POST['nohp']) ? sanitize($_POST['nohp']) : '';
    $model = isset($_POST['model']) ? sanitize($_POST['model']) : '';
    
    if (empty($nama) || empty($nohp) || empty($model)) {
        setFlashMessage('Semua field harus diisi', 'error');
    } elseif (strlen($nohp) < 10) {
        setFlashMessage('Nomor HP tidak valid (minimal 10 digit)', 'error');
    } else {
        if (isAdmin() && !empty($_POST['warranty_id'])) {
            $warrantyId = strtoupper(trim($_POST['warranty_id']));
        } else {
            $warrantyId = generateWarrantyId();
        }
        
        $warrantyDays = isset($_POST['warranty_days']) ? intval($_POST['warranty_days']) : 7;
        
        if ($warrantyDays < 1 || $warrantyDays > 365) {
            setFlashMessage('Durasi garansi harus antara 1-365 hari', 'error');
        } else {
            if ($editMode && isset($_POST['edit_id'])) {
                $result = updateWarranty($_POST['edit_id'], $nama, $nohp, $model);
                if ($result['success']) {
                    logAdminActivity('UPDATE', $_POST['edit_id'], "Updated warranty data");
                    setFlashMessage($result['message'], 'success');
                    redirect('admin.php');
                } else {
                    setFlashMessage($result['message'], 'error');
                }
            } else {
                $result = addWarranty($warrantyId, $nama, $nohp, $model, $warrantyDays);
                if ($result['success']) {
                    logAdminActivity('CREATE', $result['id'], "Created new warranty");
                    
                    $message = generateWhatsAppMessage(
                        $result['id'],
                        $nama,
                        $result['data']['nohp'],
                        $model,
                        $result['data']['registration_date'],
                        $warrantyDays
                    );
                    
                    setFlashMessage('Registrasi berhasil!');
                    redirect('admin.php');
                    exit();
                } else {
                    setFlashMessage($result['message'], 'error');
                }
            }
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
        
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out;
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

    <div class="min-h-screen flex items-center justify-center p-4 bg-pattern">
        <div class="w-full max-w-2xl relative z-10">
            <div class="glass-morphism rounded-3xl shadow-2xl p-8 lg:p-12 fade-in">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <span class="text-white text-xl font-bold">YR Team</span>
                    </div>
                    <a href="<?php echo $editMode ? 'admin.php' : 'index.php'; ?>" class="text-slate-400 hover:text-white transition text-sm font-medium">
                        ← Kembali
                    </a>
                </div>

                <!-- Title -->
                <div class="slide-up mb-8">
                    <p class="text-blue-400 text-sm font-semibold mb-3 uppercase tracking-wider">
                        <?php echo $editMode ? 'UPDATE WARRANTY' : 'NEW REGISTRATION'; ?>
                    </p>
                    <h1 class="text-4xl lg:text-5xl font-bold text-white mb-3">
                        <?php echo $editMode ? 'Edit Garansi' : 'Daftar Garansi'; ?><span class="text-blue-500">.</span>
                    </h1>
                    <p class="text-slate-400">
                        <?php echo $editMode ? 'Update informasi garansi' : 'Daftarkan garansi remap ECU motor Anda'; ?>
                    </p>
                </div>

                <!-- Form -->
                <form method="POST" class="space-y-5 slide-up" style="animation-delay: 0.2s;">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($editData['id']); ?>">
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">
                            ID Garansi <?php if (!$editMode): ?><span class="text-slate-500">(Opsional)</span><?php endif; ?>
                        </label>
                        <input
                            type="text"
                            name="warranty_id"
                            placeholder="ECU-XXXXX-XXXXX atau kosongkan untuk auto"
                            value="<?php echo $editMode ? htmlspecialchars($editData['id']) : ''; ?>"
                            class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition uppercase font-mono"
                            <?php echo $editMode ? 'readonly' : ''; ?>
                        />
                    </div>
                    <?php endif; ?>

                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Nama Lengkap *</label>
                            <input
                                type="text"
                                name="nama"
                                placeholder="Masukkan nama lengkap"
                                value="<?php echo $editMode ? htmlspecialchars($editData['nama']) : (isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''); ?>"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Nomor HP *</label>
                            <input
                                type="tel"
                                name="nohp"
                                placeholder="08xxxxxxxxxx"
                                value="<?php echo $editMode ? htmlspecialchars($editData['nohp']) : (isset($_POST['nohp']) ? htmlspecialchars($_POST['nohp']) : ''); ?>"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition"
                                pattern="[0-9]+"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Model Motor *</label>
                        <input
                            type="text"
                            name="model"
                            placeholder="Contoh: Honda CBR150R"
                            value="<?php echo $editMode ? htmlspecialchars($editData['model']) : (isset($_POST['model']) ? htmlspecialchars($_POST['model']) : ''); ?>"
                            class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition"
                            required
                        />
                    </div>

                    <?php if (isAdmin()): ?>
                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Durasi Garansi (Hari) *</label>
                        <input
                            type="number"
                            name="warranty_days"
                            placeholder="7"
                            value="<?php echo $editMode ? htmlspecialchars($editData['warranty_days']) : '7'; ?>"
                            min="1"
                            max="365"
                            class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition"
                            required
                        />
                        <p class="text-slate-500 text-xs mt-2">Default: 7 hari | Maksimal: 365 hari</p>
                    </div>
                    <?php endif; ?>

                    <button
                        type="submit"
                        class="w-full btn-primary text-white py-4 rounded-xl font-semibold shadow-lg flex items-center justify-center gap-2"
                    >
                        <?php if ($editMode): ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Update Data Garansi
                        <?php else: ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Daftar Sekarang
                        <?php endif; ?>
                    </button>
                </form>

                <!-- Info Box -->
                <div class="mt-8 p-5 bg-blue-500 bg-opacity-10 border border-blue-500 border-opacity-30 rounded-xl slide-up" style="animation-delay: 0.3s;">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-blue-400 font-semibold text-sm mb-2">Informasi Penting</p>
                            <ul class="text-slate-400 text-sm space-y-1">
                                <li>• Garansi berlaku <?php echo isAdmin() ? 'sesuai durasi' : '7 hari'; ?> sejak registrasi</li>
                                <li>• ID garansi dikirim via WhatsApp</li>
                                <li>• Simpan ID untuk cek status kapan saja</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 pt-6 border-t border-slate-700 flex justify-center gap-6 text-sm">
                    <a href="#" class="text-slate-400 hover:text-white transition">Term of use</a>
                    <span class="text-slate-600">|</span>
                    <a href="#" class="text-slate-400 hover:text-white transition">Privacy policy</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>