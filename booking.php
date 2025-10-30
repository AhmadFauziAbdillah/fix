<?php
require_once 'config.php';
require_once 'functions.php';

requireUserLogin();

$pageTitle = 'Buat Booking';
$currentUser = getCurrentUser();
$services = getAllServices();

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingData = [
        'user_id' => $currentUser['id'],
        'service_id' => intval($_POST['service_id']),
        'customer_name' => sanitize($_POST['customer_name']),
        'customer_phone' => sanitize($_POST['customer_phone']),
        'customer_email' => sanitize($_POST['customer_email']),
        'motorcycle_brand' => sanitize($_POST['motorcycle_brand']),
        'motorcycle_model' => sanitize($_POST['motorcycle_model']),
        'motorcycle_year' => intval($_POST['motorcycle_year']),
        'motorcycle_plate' => strtoupper(sanitize($_POST['motorcycle_plate'])),
        'booking_date' => sanitize($_POST['booking_date']),
        'booking_time' => sanitize($_POST['booking_time']),
        'notes' => sanitize($_POST['notes']),
        'payment_method' => 'dana'
    ];
    
    $result = createBooking($bookingData);
    
    if ($result['success']) {
        setFlashMessage('Booking berhasil dibuat!', 'success');
        redirect('booking-payment.php?code=' . $result['booking_code']);
    } else {
        setFlashMessage($result['message'], 'error');
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
        .service-card { transition: all 0.3s ease; cursor: pointer; }
        .service-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .service-card.selected { border-color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body class="bg-slate-900 min-h-screen">
    <?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="fixed top-6 right-6 z-50 max-w-md">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-6 py-4 rounded-2xl shadow-2xl">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="p-4 lg:p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-slate-800 rounded-2xl p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Buat Booking Baru</h1>
                        <p class="text-slate-400">Pilih layanan dan isi detail booking Anda</p>
                    </div>
                    <a href="user-dashboard.php" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition">
                        ← Kembali
                    </a>
                </div>
            </div>

            <form method="POST" id="bookingForm">
                <!-- Step 1: Select Service -->
                <div class="bg-slate-800 rounded-2xl p-6 mb-6">
                    <h2 class="text-xl font-bold text-white mb-4">1. Pilih Layanan</h2>
                    
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($services as $service): ?>
                        <div class="service-card bg-slate-700 rounded-xl p-5 border-2 border-slate-600" 
                             onclick="selectService(<?php echo $service['id']; ?>, <?php echo $service['price']; ?>)">
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-4xl"><?php echo $service['icon'] ?? '🔧'; ?></span>
                                <input type="radio" name="service_id" value="<?php echo $service['id']; ?>" 
                                       class="w-5 h-5" required>
                            </div>
                            <h3 class="text-white font-bold text-lg mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p class="text-slate-400 text-sm mb-3"><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="flex items-center justify-between">
                                <span class="text-blue-400 font-bold text-xl">
                                    Rp <?php echo number_format($service['price'], 0, ',', '.'); ?>
                                </span>
                                <?php if ($service['warranty_days'] > 0): ?>
                                <span class="px-2 py-1 bg-green-500 bg-opacity-20 text-green-400 text-xs rounded-full">
                                    Garansi <?php echo $service['warranty_days']; ?> hari
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 2: Customer Info -->
                <div class="bg-slate-800 rounded-2xl p-6 mb-6">
                    <h2 class="text-xl font-bold text-white mb-4">2. Informasi Pelanggan</h2>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Nama Lengkap *</label>
                            <input type="text" name="customer_name" 
                                   value="<?php echo htmlspecialchars($currentUser['name']); ?>"
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">No. HP (WhatsApp) *</label>
                            <input type="tel" name="customer_phone" 
                                   value="<?php echo htmlspecialchars($currentUser['phone']); ?>"
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Email</label>
                            <input type="email" name="customer_email" 
                                   value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Motorcycle Info -->
                <div class="bg-slate-800 rounded-2xl p-6 mb-6">
                    <h2 class="text-xl font-bold text-white mb-4">3. Informasi Motor</h2>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Merek Motor *</label>
                            <select name="motorcycle_brand" 
                                    class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                                <option value="">Pilih Merek</option>
                                <option value="Honda">Honda</option>
                                <option value="Yamaha">Yamaha</option>
                                <option value="Suzuki">Suzuki</option>
                                <option value="Kawasaki">Kawasaki</option>
                                <option value="TVS">TVS</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Model Motor *</label>
                            <input type="text" name="motorcycle_model" placeholder="Contoh: CBR150R" 
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Tahun *</label>
                            <input type="number" name="motorcycle_year" placeholder="2020" min="1990" max="2025"
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Plat Nomor *</label>
                            <input type="text" name="motorcycle_plate" placeholder="B 1234 XYZ" 
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white uppercase" required>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Schedule -->
                <div class="bg-slate-800 rounded-2xl p-6 mb-6">
                    <h2 class="text-xl font-bold text-white mb-4">4. Jadwal Booking</h2>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Tanggal *</label>
                            <input type="date" name="booking_date" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                        </div>
                        <div>
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Waktu *</label>
                            <select name="booking_time" 
                                    class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white" required>
                                <option value="">Pilih Waktu</option>
                                <option value="09:00:00">09:00 WIB</option>
                                <option value="10:00:00">10:00 WIB</option>
                                <option value="11:00:00">11:00 WIB</option>
                                <option value="13:00:00">13:00 WIB</option>
                                <option value="14:00:00">14:00 WIB</option>
                                <option value="15:00:00">15:00 WIB</option>
                                <option value="16:00:00">16:00 WIB</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-slate-300 text-sm font-semibold mb-2">Catatan (Opsional)</label>
                            <textarea name="notes" rows="3" placeholder="Tambahkan catatan atau permintaan khusus..."
                                      class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Summary & Submit -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-2xl p-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="text-white">
                            <p class="text-sm opacity-90 mb-1">Total yang harus dibayar:</p>
                            <p class="text-3xl font-bold" id="totalAmount">Rp 0</p>
                            <p class="text-sm opacity-75 mt-1" id="breakdown"></p>
                        </div>
                        <button type="submit" 
                                class="px-8 py-4 bg-white text-blue-600 rounded-xl font-bold hover:bg-slate-100 transition">
                            Lanjut ke Pembayaran →
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    let selectedServicePrice = 0;

    function selectService(serviceId, price) {
        // Unselect all
        document.querySelectorAll('.service-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Select clicked
        event.currentTarget.classList.add('selected');
        document.querySelector(`input[value="${serviceId}"]`).checked = true;
        
        selectedServicePrice = price;
        updateTotal();
    }

    function updateTotal() {
        const uniqueCode = Math.floor(Math.random() * 999) + 1;
        const total = selectedServicePrice + uniqueCode;
        
        document.getElementById('totalAmount').textContent = 
            'Rp ' + total.toLocaleString('id-ID');
        
        document.getElementById('breakdown').textContent = 
            `Rp ${selectedServicePrice.toLocaleString('id-ID')} + Rp ${uniqueCode} (kode unik)`;
    }

    // Auto-select first service if available
    window.addEventListener('DOMContentLoaded', () => {
        const firstService = document.querySelector('.service-card');
        if (firstService) {
            firstService.click();
        }
    });
    </script>
</body>
</html>