<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'booking-functions.php';

requireAdminLogin();

$pageTitle = 'Kelola Booking';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    
    switch ($action) {
        case 'confirm':
            if (updateBookingStatus($bookingId, 'confirmed')) {
                setFlashMessage('Booking berhasil dikonfirmasi', 'success');
            } else {
                setFlashMessage('Gagal mengkonfirmasi booking', 'error');
            }
            break;
            
        case 'complete':
            if (updateBookingStatus($bookingId, 'completed')) {
                setFlashMessage('Booking berhasil diselesaikan', 'success');
            } else {
                setFlashMessage('Gagal menyelesaikan booking', 'error');
            }
            break;
            
        case 'cancel':
            $reason = $_POST['cancel_reason'] ?? 'Dibatalkan oleh admin';
            if (cancelBooking($bookingId, $reason)) {
                setFlashMessage('Booking berhasil dibatalkan', 'success');
            } else {
                setFlashMessage('Gagal membatalkan booking', 'error');
            }
            break;
            
        case 'add_notes':
            $notes = trim($_POST['admin_notes'] ?? '');
            if (addAdminNotes($bookingId, $notes)) {
                setFlashMessage('Catatan berhasil ditambahkan', 'success');
            } else {
                setFlashMessage('Gagal menambahkan catatan', 'error');
            }
            break;
    }
    
    redirect('admin-bookings.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Get all bookings with filters
$bookings = getAllBookingsAdmin($statusFilter, $searchQuery, $dateFilter);

// Get statistics
$stats = getBookingStatistics();

include 'includes/admin-header.php';
?>

<div class="flex h-screen bg-gray-100">
    <?php include 'includes/admin-sidebar.php'; ?>
    
    <div class="flex-1 overflow-x-hidden overflow-y-auto">
        <div class="container mx-auto px-6 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Kelola Booking</h1>
                <div class="flex items-center space-x-2">
                    <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Booking</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pending</p>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
                        </div>
                        <div class="bg-yellow-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Paid</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $stats['paid']; ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Confirmed</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo $stats['confirmed']; ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Completed</p>
                            <p class="text-2xl font-bold text-purple-600"><?php echo $stats['completed']; ?></p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                        <input 
                            type="date" 
                            name="date" 
                            value="<?php echo htmlspecialchars($dateFilter); ?>"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            placeholder="Kode/Nama/Email..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div class="flex items-end space-x-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Filter
                        </button>
                        <a href="admin-bookings.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Bookings Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Booking</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Layanan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Jam</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        Tidak ada data booking
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($booking['booking_code']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                                            <p class="text-gray-500"><?php echo htmlspecialchars($booking['customer_email']); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm">
                                            <p class="text-gray-900"><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></p>
                                            <p class="text-gray-500"><?php echo date('H:i', strtotime($booking['booking_time'])); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold text-gray-900">Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo getBookingStatusBadge($booking['status']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button 
                                            onclick="openBookingModal(<?php echo htmlspecialchars(json_encode($booking)); ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-3"
                                        >
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Detail Modal -->
<div id="bookingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Detail Booking</h3>
            <button onclick="closeBookingModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div id="modalContent" class="space-y-4">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<script>
function openBookingModal(booking) {
    const modal = document.getElementById('bookingModal');
    const content = document.getElementById('modalContent');
    
    let paymentProofHtml = '';
    if (booking.payment_proof) {
        paymentProofHtml = `
            <div>
                <h4 class="font-semibold text-gray-700 mb-2">Bukti Pembayaran:</h4>
                <img src="${booking.payment_proof}" alt="Bukti Pembayaran" class="w-full max-w-md rounded-lg shadow-md cursor-pointer" onclick="window.open(this.src, '_blank')">
            </div>
        `;
    }
    
    let actionsHtml = '';
    if (booking.status === 'paid') {
        actionsHtml = `
            <form method="POST" class="flex space-x-2">
                <input type="hidden" name="booking_id" value="${booking.id}">
                <input type="hidden" name="action" value="confirm">
                <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    Konfirmasi Pembayaran
                </button>
            </form>
        `;
    } else if (booking.status === 'confirmed') {
        actionsHtml = `
            <form method="POST" class="flex space-x-2">
                <input type="hidden" name="booking_id" value="${booking.id}">
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                    Selesaikan Booking
                </button>
            </form>
        `;
    }
    
    if (booking.status !== 'completed' && booking.status !== 'cancelled') {
        actionsHtml += `
            <form method="POST" class="mt-2">
                <input type="hidden" name="booking_id" value="${booking.id}">
                <input type="hidden" name="action" value="cancel">
                <input type="text" name="cancel_reason" placeholder="Alasan pembatalan..." class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-2" required>
                <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    Batalkan Booking
                </button>
            </form>
        `;
    }
    
    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Kode Booking</p>
                <p class="font-semibold">${booking.booking_code}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Status</p>
                <p>${getStatusBadge(booking.status)}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Customer</p>
                <p class="font-semibold">${booking.customer_name}</p>
                <p class="text-sm text-gray-500">${booking.customer_email}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Layanan</p>
                <p class="font-semibold">${booking.service_name}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Tanggal</p>
                <p class="font-semibold">${formatDate(booking.booking_date)}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Jam</p>
                <p class="font-semibold">${booking.booking_time}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Harga</p>
                <p class="font-semibold text-lg text-blue-600">Rp ${formatPrice(booking.total_price)}</p>
            </div>
        </div>
        
        ${booking.notes ? `
            <div class="bg-gray-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Catatan Customer:</p>
                <p class="text-gray-800">${booking.notes}</p>
            </div>
        ` : ''}
        
        ${paymentProofHtml}
        
        <div class="border-t pt-4">
            <form method="POST">
                <input type="hidden" name="booking_id" value="${booking.id}">
                <input type="hidden" name="action" value="add_notes">
                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Admin:</label>
                <textarea name="admin_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-2" placeholder="Tambahkan catatan...">${booking.admin_notes || ''}</textarea>
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    Simpan Catatan
                </button>
            </form>
        </div>
        
        ${actionsHtml}
    `;
    
    modal.classList.remove('hidden');
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.add('hidden');
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'paid': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Paid</span>',
        'confirmed': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Confirmed</span>',
        'completed': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Completed</span>',
        'cancelled': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Cancelled</span>'
    };
    return badges[status] || status;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

function formatPrice(price) {
    return new Intl.NumberFormat('id-ID').format(price);
}

// Close modal when clicking outside
document.getElementById('bookingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBookingModal();
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>