<?php
// booking-functions.php - Booking System Functions

// ========================================
// SERVICE FUNCTIONS
// ========================================

/**
 * Get all active services
 */
function getAllServices() {
    global $conn;
    
    $sql = "SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC";
    $result = $conn->query($sql);
    
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    
    return $services;
}

/**
 * Get service by ID
 */
function getServiceById($serviceId) {
    global $conn;
    
    $sql = "SELECT * FROM services WHERE id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// ========================================
// BOOKING FUNCTIONS
// ========================================

/**
 * Generate unique booking code
 */
function generateBookingCode() {
    $date = date('ymd');
    $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    return 'BK-' . $date . '-' . $random;
}

/**
 * Generate unique payment code (1-999)
 */
function generateUniqueCode() {
    global $conn;
    
    // Get used codes today
    $today = date('Y-m-d');
    $sql = "SELECT unique_code FROM bookings WHERE DATE(created_at) = ? AND unique_code > 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usedCodes = [];
    while ($row = $result->fetch_assoc()) {
        $usedCodes[] = $row['unique_code'];
    }
    
    // Generate unique code
    do {
        $code = rand(1, 999);
    } while (in_array($code, $usedCodes));
    
    return $code;
}

/**
 * Create new booking
 */
function createBooking($data) {
    global $conn;
    
    // Validations
    if (empty($data['user_id']) || empty($data['service_id'])) {
        return ['success' => false, 'message' => 'Data tidak lengkap'];
    }
    
    // Get service
    $service = getServiceById($data['service_id']);
    if (!$service) {
        return ['success' => false, 'message' => 'Layanan tidak ditemukan'];
    }
    
    // Generate codes
    $bookingCode = generateBookingCode();
    $uniqueCode = generateUniqueCode();
    $totalAmount = $service['price'] + $uniqueCode;
    
    // Normalize phone
    $phone = normalizePhone($data['customer_phone']);
    
    // Calculate expiry (24 jam dari sekarang)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Insert booking
    $sql = "INSERT INTO bookings (
                booking_code, user_id, service_id,
                customer_name, customer_phone, customer_email,
                motorcycle_brand, motorcycle_model, motorcycle_year, motorcycle_plate,
                booking_date, booking_time, notes,
                service_price, unique_code, total_amount,
                payment_method, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "siisssssississddss",
        $bookingCode,
        $data['user_id'],
        $data['service_id'],
        $data['customer_name'],
        $phone,
        $data['customer_email'],
        $data['motorcycle_brand'],
        $data['motorcycle_model'],
        $data['motorcycle_year'],
        $data['motorcycle_plate'],
        $data['booking_date'],
        $data['booking_time'],
        $data['notes'],
        $service['price'],
        $uniqueCode,
        $totalAmount,
        $data['payment_method'] ?? 'dana',
        $expiresAt
    );
    
    if ($stmt->execute()) {
        $bookingId = $conn->insert_id;
        
        // Log activity
        logBookingActivity($bookingId, 'CREATE', 'Booking created', 'user');
        
        // Send WhatsApp notification
        $message = generateBookingWhatsAppMessage($bookingCode);
        sendWhatsAppMessageWithFallback($phone, $message);
        
        return [
            'success' => true,
            'message' => 'Booking berhasil dibuat',
            'booking_id' => $bookingId,
            'booking_code' => $bookingCode,
            'total_amount' => $totalAmount,
            'unique_code' => $uniqueCode
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal membuat booking'];
}

/**
 * Get booking by code
 */
function getBookingByCode($bookingCode) {
    global $conn;
    
    $sql = "SELECT b.*, s.name as service_name, s.description as service_description,
                   u.full_name as user_name, u.email as user_email
            FROM bookings b
            INNER JOIN services s ON b.service_id = s.id
            INNER JOIN users u ON b.user_id = u.id
            WHERE b.booking_code = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $bookingCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        
        // Check if expired
        if ($booking['expires_at'] && strtotime($booking['expires_at']) < time() && $booking['payment_status'] === 'pending') {
            $booking['is_expired'] = true;
        } else {
            $booking['is_expired'] = false;
        }
        
        return $booking;
    }
    
    return null;
}

/**
 * Get bookings by user
 */
function getBookingsByUser($userId, $limit = 20) {
    global $conn;
    
    $sql = "SELECT b.*, s.name as service_name, s.icon as service_icon
            FROM bookings b
            INNER JOIN services s ON b.service_id = s.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

/**
 * Upload payment proof
 */
function uploadPaymentProof($bookingId, $file) {
    global $conn;
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Format file harus JPG atau PNG'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 5MB'];
    }
    
    // Create upload directory if not exists
    $uploadDir = __DIR__ . '/uploads/payments/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'payment_' . $bookingId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Save to database
        $relativeFilepath = 'uploads/payments/' . $filename;
        
        $sql = "INSERT INTO payment_confirmations (booking_id, proof_image) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $bookingId, $relativeFilepath);
        $stmt->execute();
        
        // Update booking status
        $updateSql = "UPDATE bookings SET payment_proof = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $relativeFilepath, $bookingId);
        $updateStmt->execute();
        
        logBookingActivity($bookingId, 'UPLOAD_PROOF', 'Payment proof uploaded', 'user');
        
        return [
            'success' => true,
            'message' => 'Bukti pembayaran berhasil diupload',
            'filename' => $relativeFilepath
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file'];
}

/**
 * Verify payment (Admin)
 */
function verifyPayment($bookingId, $adminId, $notes = '') {
    global $conn;
    
    // Get booking
    $booking = getBookingById($bookingId);
    if (!$booking) {
        return ['success' => false, 'message' => 'Booking tidak ditemukan'];
    }
    
    // Update payment status
    $sql = "UPDATE bookings SET 
            payment_status = 'paid',
            booking_status = 'confirmed',
            paid_at = NOW()
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookingId);
    
    if ($stmt->execute()) {
        // Update payment confirmation
        $updateConfirm = "UPDATE payment_confirmations SET 
                          verified_by = ?,
                          verified_at = NOW(),
                          verification_notes = ?
                          WHERE booking_id = ?
                          ORDER BY upload_time DESC LIMIT 1";
        
        $confirmStmt = $conn->prepare($updateConfirm);
        $confirmStmt->bind_param("isi", $adminId, $notes, $bookingId);
        $confirmStmt->execute();
        
        // Create warranty automatically
        $warrantyResult = createWarrantyFromBooking($bookingId);
        
        logBookingActivity($bookingId, 'VERIFY_PAYMENT', 'Payment verified by admin', 'admin');
        
        // Send WhatsApp notification
        $message = generatePaymentConfirmedMessage($booking['booking_code']);
        sendWhatsAppMessageWithFallback($booking['customer_phone'], $message);
        
        return [
            'success' => true,
            'message' => 'Pembayaran berhasil diverifikasi',
            'warranty' => $warrantyResult
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal verifikasi pembayaran'];
}

/**
 * Create warranty from booking
 */
function createWarrantyFromBooking($bookingId) {
    global $conn;
    
    $booking = getBookingById($bookingId);
    if (!$booking) {
        return ['success' => false, 'message' => 'Booking tidak ditemukan'];
    }
    
    // Get service for warranty days
    $service = getServiceById($booking['service_id']);
    if ($service['warranty_days'] == 0) {
        return ['success' => false, 'message' => 'Layanan ini tidak termasuk garansi'];
    }
    
    // Generate warranty ID
    $warrantyId = generateWarrantyId();
    
    // Create warranty
    $result = addWarranty(
        $warrantyId,
        $booking['customer_name'],
        $booking['customer_phone'],
        $booking['motorcycle_model'],
        $service['warranty_days']
    );
    
    if ($result['success']) {
        // Link warranty to booking
        $updateSql = "UPDATE bookings SET warranty_id = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $warrantyId, $bookingId);
        $updateStmt->execute();
        
        // Link warranty to user
        $warrantyUpdate = "UPDATE warranties SET user_id = ? WHERE id = ?";
        $warrantyStmt = $conn->prepare($warrantyUpdate);
        $warrantyStmt->bind_param("is", $booking['user_id'], $warrantyId);
        $warrantyStmt->execute();
        
        return [
            'success' => true,
            'warranty_id' => $warrantyId,
            'message' => 'Garansi berhasil dibuat'
        ];
    }
    
    return $result;
}

/**
 * Get booking by ID
 */
function getBookingById($bookingId) {
    global $conn;
    
    $sql = "SELECT b.*, s.name as service_name, s.warranty_days
            FROM bookings b
            INNER JOIN services s ON b.service_id = s.id
            WHERE b.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Log booking activity
 */
function logBookingActivity($bookingId, $action, $description, $performedBy = 'system') {
    global $conn;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO booking_logs (booking_id, action, description, performed_by, ip_address)
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $bookingId, $action, $description, $performedBy, $ipAddress);
    $stmt->execute();
}

/**
 * Generate booking WhatsApp message
 */
function generateBookingWhatsAppMessage($bookingCode) {
    $booking = getBookingByCode($bookingCode);
    
    $message = "*YR Team - Booking Confirmation*\n\n";
    $message .= "✅ Booking berhasil dibuat!\n\n";
    $message .= "📋 Kode Booking: *" . $bookingCode . "*\n";
    $message .= "🏍️ Layanan: " . $booking['service_name'] . "\n";
    $message .= "💰 Total: *Rp " . number_format($booking['total_amount'], 0, ',', '.') . "*\n";
    $message .= "   (Rp " . number_format($booking['service_price'], 0, ',', '.') . " + Rp " . $booking['unique_code'] . ")\n\n";
    $message .= "📅 Tanggal: " . date('d/m/Y', strtotime($booking['booking_date'])) . "\n";
    $message .= "🕐 Waktu: " . date('H:i', strtotime($booking['booking_time'])) . "\n\n";
    $message .= "💳 *CARA PEMBAYARAN:*\n";
    $message .= "Transfer ke DANA: *0859-1065-45737*\n";
    $message .= "a.n. YR Team\n\n";
    $message .= "Nominal: *Rp " . number_format($booking['total_amount'], 0, ',', '.') . "*\n";
    $message .= "(Wajib transfer sesuai nominal + kode unik)\n\n";
    $message .= "⏰ Bayar sebelum:\n";
    $message .= date('d/m/Y H:i', strtotime($booking['expires_at'])) . "\n\n";
    $message .= "Upload bukti transfer di:\n";
    $message .= "yrteam.wasmer.app\n\n";
    $message .= "Terima kasih! 🙏";
    
    return $message;
}

/**
 * Generate payment confirmed message
 */
function generatePaymentConfirmedMessage($bookingCode) {
    $booking = getBookingByCode($bookingCode);
    
    $message = "*YR Team - Pembayaran Dikonfirmasi*\n\n";
    $message .= "✅ Pembayaran Anda telah dikonfirmasi!\n\n";
    $message .= "📋 Kode Booking: *" . $bookingCode . "*\n";
    $message .= "🏍️ Layanan: " . $booking['service_name'] . "\n";
    $message .= "📅 Jadwal: " . date('d/m/Y H:i', strtotime($booking['booking_date'] . ' ' . $booking['booking_time'])) . "\n\n";
    
    if ($booking['warranty_id']) {
        $message .= "🎁 *GARANSI AKTIF*\n";
        $message .= "ID Garansi: *" . $booking['warranty_id'] . "*\n";
        $message .= "Masa Berlaku: " . $booking['warranty_days'] . " hari\n\n";
    }
    
    $message .= "Silakan datang sesuai jadwal.\n";
    $message .= "Jika ada pertanyaan, hubungi kami.\n\n";
    $message .= "Terima kasih! 🙏";
    
    return $message;
}

/**
 * Get booking statistics
 */
function getBookingStatistics() {
    global $conn;
    
    $stats = [];
    
    // Total bookings
    $stats['total'] = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
    
    // Pending payment
    $stats['pending_payment'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'pending'")->fetch_assoc()['count'];
    
    // Confirmed
    $stats['confirmed'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'")->fetch_assoc()['count'];
    
    // Completed
    $stats['completed'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'completed'")->fetch_assoc()['count'];
    
    // Total revenue (paid only)
    $revenue = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'")->fetch_assoc();
    $stats['revenue'] = $revenue['total'] ?? 0;
    
    return $stats;
}

?>