<?php
// booking-functions.php - Booking System Functions

// ========================================
// SERVICE FUNCTIONS
// ========================================

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

function generateBookingCode() {
    $date = date('ymd');
    $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    return 'BK-' . $date . '-' . $random;
}

function generateUniqueCode() {
    global $conn;
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
    
    do {
        $code = rand(1, 999);
    } while (in_array($code, $usedCodes));
    
    return $code;
}

function createBooking($data) {
    global $conn;
    
    $service = getServiceById($data['service_id']);
    if (!$service) {
        return ['success' => false, 'message' => 'Layanan tidak ditemukan'];
    }
    
    $bookingCode = generateBookingCode();
    $uniqueCode = generateUniqueCode();
    $totalAmount = $service['price'] + $uniqueCode;
    $phone = normalizePhone($data['customer_phone']);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
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
        $data['payment_method'],
        $expiresAt
    );
    
    if ($stmt->execute()) {
        $bookingId = $conn->insert_id;
        logBookingActivity($bookingId, 'CREATE', 'Booking created', 'user');
        
        return [
            'success' => true,
            'booking_id' => $bookingId,
            'booking_code' => $bookingCode,
            'total_amount' => $totalAmount,
            'unique_code' => $uniqueCode
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal membuat booking'];
}

function getBookingByCode($bookingCode) {
    global $conn;
    
    $sql = "SELECT b.*, s.name as service_name, s.description as service_description, s.warranty_days,
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
        $booking['is_expired'] = ($booking['expires_at'] && strtotime($booking['expires_at']) < time() && $booking['payment_status'] === 'pending');
        return $booking;
    }
    
    return null;
}

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

function uploadPaymentProof($bookingId, $file) {
    global $conn;
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxSize = 5 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Format file harus JPG atau PNG'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 5MB'];
    }
    
    $uploadDir = __DIR__ . '/uploads/payments/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'payment_' . $bookingId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $relativeFilepath = 'uploads/payments/' . $filename;
        
        $sql = "INSERT INTO payment_confirmations (booking_id, proof_image) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $bookingId, $relativeFilepath);
        $stmt->execute();
        
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

function verifyPayment($bookingId, $adminId, $notes = '') {
    global $conn;
    
    $booking = getBookingById($bookingId);
    if (!$booking) {
        return ['success' => false, 'message' => 'Booking tidak ditemukan'];
    }
    
    $sql = "UPDATE bookings SET 
            payment_status = 'paid',
            booking_status = 'confirmed',
            paid_at = NOW()
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookingId);
    
    if ($stmt->execute()) {
        $updateConfirm = "UPDATE payment_confirmations SET 
                          verified_by = ?,
                          verified_at = NOW(),
                          verification_notes = ?
                          WHERE booking_id = ?
                          ORDER BY upload_time DESC LIMIT 1";
        
        $confirmStmt = $conn->prepare($updateConfirm);
        $confirmStmt->bind_param("isi", $adminId, $notes, $bookingId);
        $confirmStmt->execute();
        
        // Auto-create warranty if applicable
        if ($booking['warranty_days'] > 0) {
            $warrantyId = generateWarrantyId();
            addWarranty(
                $warrantyId,
                $booking['customer_name'],
                $booking['customer_phone'],
                $booking['motorcycle_model'],
                $booking['warranty_days']
            );
            
            $updateWarranty = "UPDATE bookings SET warranty_id = ? WHERE id = ?";
            $warrantyStmt = $conn->prepare($updateWarranty);
            $warrantyStmt->bind_param("si", $warrantyId, $bookingId);
            $warrantyStmt->execute();
            
            $linkUser = "UPDATE warranties SET user_id = ? WHERE id = ?";
            $linkStmt = $conn->prepare($linkUser);
            $linkStmt->bind_param("is", $booking['user_id'], $warrantyId);
            $linkStmt->execute();
        }
        
        logBookingActivity($bookingId, 'VERIFY_PAYMENT', 'Payment verified by admin', 'admin');
        
        return [
            'success' => true,
            'message' => 'Pembayaran berhasil diverifikasi'
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal verifikasi pembayaran'];
}

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

function getAllBookings($search = '', $status = '') {
    global $conn;
    
    $sql = "SELECT b.*, s.name as service_name, u.full_name as customer_name_user 
            FROM bookings b
            INNER JOIN services s ON b.service_id = s.id
            INNER JOIN users u ON b.user_id = u.id
            WHERE 1=1";
    
    if ($search) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (b.booking_code LIKE '%$search%' OR b.customer_name LIKE '%$search%')";
    }
    
    if ($status) {
        $status = $conn->real_escape_string($status);
        $sql .= " AND b.payment_status = '$status'";
    }
    
    $sql .= " ORDER BY b.created_at DESC LIMIT 100";
    
    $result = $conn->query($sql);
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

function logBookingActivity($bookingId, $action, $description, $performedBy = 'system') {
    global $conn;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO booking_logs (booking_id, action, description, performed_by, ip_address)
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $bookingId, $action, $description, $performedBy, $ipAddress);
    $stmt->execute();
}

function getBookingStatistics() {
    global $conn;
    
    $stats = [];
    
    $stats['total'] = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
    $stats['pending_payment'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'pending'")->fetch_assoc()['count'];
    $stats['confirmed'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'")->fetch_assoc()['count'];
    $stats['completed'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'completed'")->fetch_assoc()['count'];
    
    $revenue = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'")->fetch_assoc();
    $stats['revenue'] = $revenue['total'] ?? 0;
    
    return $stats;
}

?>