<?php
// functions.php - Fungsi-fungsi Business Logic dengan Bot Integration & Admin Logging
require_once 'config.php';

// Generate ID Garansi Unik
function generateWarrantyId() {
    $timestamp = base_convert(time(), 10, 36);
    $random = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
    return 'ECU-' . strtoupper($timestamp) . '-' . $random;
}

// Cek ID sudah digunakan atau belum
function checkWarrantyIdExists($id) {
    global $conn;
    
    $sql = "SELECT id FROM warranties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Normalisasi Nomor HP
function normalizePhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    return $phone;
}

// Cek Duplikat Nomor HP
function checkDuplicatePhone($nohp, $excludeId = null) {
    global $conn;
    $normalizedPhone = normalizePhone($nohp);
    
    $sql = "SELECT id FROM warranties WHERE nohp = ?";
    if ($excludeId) {
        $sql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    if ($excludeId) {
        $stmt->bind_param("ss", $normalizedPhone, $excludeId);
    } else {
        $stmt->bind_param("s", $normalizedPhone);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Get warranty by phone number
function getWarrantyByPhone($nohp) {
    global $conn;
    
    $sql = "SELECT * FROM warranties WHERE nohp = ? ORDER BY registration_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nohp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $warranty = $result->fetch_assoc();
        
        // Hitung sisa hari
        $expiryDate = new DateTime($warranty['expiry_date']);
        $today = new DateTime();
        $interval = $today->diff($expiryDate);
        $daysRemaining = $interval->invert ? 0 : $interval->days;
        
        $warranty['days_remaining'] = $daysRemaining;
        $warranty['is_active'] = $daysRemaining > 0;
        
        return $warranty;
    }
    
    return null;
}

// Tambah Garansi Baru dengan Auto-Send WhatsApp
function addWarranty($id, $nama, $nohp, $model, $warrantyDays = 7) {
    global $conn;
    
    // Cek ID sudah digunakan
    if (checkWarrantyIdExists($id)) {
        return ['success' => false, 'message' => 'ID Garansi sudah digunakan!'];
    }
    
    $normalizedPhone = normalizePhone($nohp);
    $registrationDate = date('Y-m-d H:i:s');
    $expiryDate = date('Y-m-d H:i:s', strtotime("+$warrantyDays days"));
    
    $sql = "INSERT INTO warranties (id, nama, nohp, model, registration_date, expiry_date, warranty_days) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $id, $nama, $normalizedPhone, $model, $registrationDate, $expiryDate, $warrantyDays);
    
    if ($stmt->execute()) {
        // Generate WhatsApp message
        $message = generateWhatsAppMessage($id, $nama, $normalizedPhone, $model, $registrationDate, $warrantyDays);
        
        // Send via Bot API with fallback
        $sendResult = sendWhatsAppMessageWithFallback($normalizedPhone, $message);
        
        return [
            'success' => true,
            'id' => $id,
            'data' => [
                'nama' => $nama,
                'nohp' => $normalizedPhone,
                'model' => $model,
                'registration_date' => $registrationDate,
                'warranty_days' => $warrantyDays
            ],
            'whatsapp' => $sendResult
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal menyimpan data'];
}

// Cari Garansi by ID
function getWarrantyById($id) {
    global $conn;
    
    $sql = "SELECT * FROM warranties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $warranty = $result->fetch_assoc();
        
        // Hitung sisa hari
        $expiryDate = new DateTime($warranty['expiry_date']);
        $today = new DateTime();
        $interval = $today->diff($expiryDate);
        $daysRemaining = $interval->invert ? 0 : $interval->days;
        
        $warranty['days_remaining'] = $daysRemaining;
        $warranty['is_active'] = $daysRemaining > 0;
        
        return $warranty;
    }
    
    return null;
}

// Get Semua Garansi dengan filter search
function getAllWarranties($search = '') {
    global $conn;
    
    $sql = "SELECT * FROM warranties";
    
    if (!empty($search)) {
        $sql .= " WHERE id LIKE ? OR nama LIKE ? OR nohp LIKE ?";
    }
    
    $sql .= " ORDER BY registration_date DESC";
    
    if (!empty($search)) {
        $stmt = $conn->prepare($sql);
        $searchParam = "%$search%";
        $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $warranties = [];
    while ($row = $result->fetch_assoc()) {
        $expiryDate = new DateTime($row['expiry_date']);
        $today = new DateTime();
        $row['is_active'] = $expiryDate > $today;
        $warranties[] = $row;
    }
    
    return $warranties;
}

// Update Garansi
function updateWarranty($id, $nama, $nohp, $model) {
    global $conn;
    
    $normalizedPhone = normalizePhone($nohp);
    
    $sql = "UPDATE warranties SET nama = ?, nohp = ?, model = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nama, $normalizedPhone, $model, $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Data berhasil diupdate'];
    }
    
    return ['success' => false, 'message' => 'Gagal mengupdate data'];
}

// Extend/Perpanjang Garansi dengan ID baru dan Auto-Send WhatsApp
function extendWarranty($oldId, $newId, $warrantyDays) {
    global $conn;
    
    // Cek ID baru sudah digunakan
    if (checkWarrantyIdExists($newId)) {
        return ['success' => false, 'message' => 'ID Garansi baru sudah digunakan!'];
    }
    
    // Get data lama
    $oldWarranty = getWarrantyById($oldId);
    if (!$oldWarranty) {
        return ['success' => false, 'message' => 'Data garansi lama tidak ditemukan'];
    }
    
    // Insert garansi baru dengan ID baru
    $registrationDate = date('Y-m-d H:i:s');
    $expiryDate = date('Y-m-d H:i:s', strtotime("+$warrantyDays days"));
    
    $sql = "INSERT INTO warranties (id, nama, nohp, model, registration_date, expiry_date, warranty_days) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $newId, $oldWarranty['nama'], $oldWarranty['nohp'], 
                      $oldWarranty['model'], $registrationDate, $expiryDate, $warrantyDays);
    
    if ($stmt->execute()) {
        // Update status garansi lama menjadi expired
        $updateOld = "UPDATE warranties SET status = 'expired' WHERE id = ?";
        $stmtUpdate = $conn->prepare($updateOld);
        $stmtUpdate->bind_param("s", $oldId);
        $stmtUpdate->execute();
        
        // Generate WhatsApp message untuk perpanjangan
        $message = "*YR Team*\n\n";
        $message .= "Garansi berhasil diperpanjang!\n\n";
        $message .= "ID Garansi Lama: $oldId\n";
        $message .= "ID Garansi Baru: *$newId*\n\n";
        $message .= "Nama: " . $oldWarranty['nama'] . "\n";
        $message .= "No HP: " . $oldWarranty['nohp'] . "\n";
        $message .= "Model Motor: " . $oldWarranty['model'] . "\n";
        $message .= "Tgl Perpanjangan: " . date('d/m/Y H:i') . "\n";
        $message .= "Masa Berlaku: $warrantyDays Hari\n";
        $message .= "Berlaku s/d: " . date('d/m/Y', strtotime($expiryDate)) . "\n\n";
        $message .= "Website : yrteam.wasmer.app\n";
        $message .= "*SIMPAN ID GARANSI BARU ANDA*\n";
        $message .= "Gunakan ID baru ini untuk cek masa aktif garansi";
        
        // Send via Bot API with fallback
        $sendResult = sendWhatsAppMessageWithFallback($oldWarranty['nohp'], $message);
        
        return [
            'success' => true, 
            'message' => 'Garansi berhasil diperpanjang',
            'new_id' => $newId,
            'whatsapp' => $sendResult
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal memperpanjang garansi'];
}

// Update durasi garansi
function updateWarrantyDuration($id, $warrantyDays) {
    global $conn;
    
    $warranty = getWarrantyById($id);
    if (!$warranty) {
        return ['success' => false, 'message' => 'Data tidak ditemukan'];
    }
    
    // Hitung expiry date baru dari registration date
    $registrationDate = $warranty['registration_date'];
    $expiryDate = date('Y-m-d H:i:s', strtotime($registrationDate . " +$warrantyDays days"));
    
    $sql = "UPDATE warranties SET expiry_date = ?, warranty_days = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $expiryDate, $warrantyDays, $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Durasi garansi berhasil diupdate'];
    }
    
    return ['success' => false, 'message' => 'Gagal mengupdate durasi'];
}

// Hapus Garansi
function deleteWarranty($id) {
    global $conn;
    
    $sql = "DELETE FROM warranties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Data berhasil dihapus'];
    }
    
    return ['success' => false, 'message' => 'Gagal menghapus data'];
}

// Get Statistik
function getStatistics() {
    global $conn;
    
    $total = $conn->query("SELECT COUNT(*) as count FROM warranties")->fetch_assoc()['count'];
    $active = $conn->query("SELECT COUNT(*) as count FROM warranties WHERE expiry_date > NOW()")->fetch_assoc()['count'];
    $expired = $total - $active;
    
    return [
        'total' => $total,
        'active' => $active,
        'expired' => $expired
    ];
}

// Generate WhatsApp Message
function generateWhatsAppMessage($id, $nama, $nohp, $model, $registrationDate, $warrantyDays) {
    $expiryDate = date('d/m/Y', strtotime($registrationDate . " +$warrantyDays days"));
    
    $message = "*YR Team*\n\n";
    $message .= "Registrasi Berhasil!\n\n";
    $message .= "Berikut adalah data garansi Anda:\n\n";
    $message .= "ID Garansi: *$id*\n";
    $message .= "Nama: $nama\n";
    $message .= "No HP: $nohp\n";
    $message .= "Model Motor: $model\n";
    $message .= "Tanggal Registrasi: " . date('d/m/Y H:i', strtotime($registrationDate)) . "\n";
    $message .= "Masa Berlaku: $warrantyDays Hari\n";
    $message .= "Berlaku s/d: $expiryDate\n\n";
    $message .= "Website : yrteam.wasmer.app\n";
    $message .= "*SIMPAN ID GARANSI ANDA*\n";
    $message .= "Gunakan ID ini untuk cek masa aktif garansi kapan saja.\n\n";
    $message .= "Terima kasih telah mempercayai layanan kami! 🙏";
    
    return $message;
}

// Resend warranty info via WhatsApp
function resendWarrantyInfo($warrantyId) {
    $warranty = getWarrantyById($warrantyId);
    
    if (!$warranty) {
        return ['success' => false, 'message' => 'Data garansi tidak ditemukan'];
    }
    
    // Generate message
    $message = "*YR Team*\n\n";
    $message .= "Halo *" . $warranty['nama'] . "*,\n\n";
    $message .= "Berikut adalah data garansi Anda:\n\n";
    $message .= "ID Garansi: *" . $warranty['id'] . "*\n";
    $message .= "Nama: " . $warranty['nama'] . "\n";
    $message .= "No HP: " . $warranty['nohp'] . "\n";
    $message .= "Model Motor: " . $warranty['model'] . "\n";
    $message .= "Tgl Registrasi: " . date('d/m/Y', strtotime($warranty['registration_date'])) . "\n";
    $message .= "Masa Berlaku: " . $warranty['warranty_days'] . " Hari\n";
    $message .= "Berlaku s/d: " . date('d/m/Y', strtotime($warranty['expiry_date'])) . "\n\n";
    $message .= "Website : yrteam.wasmer.app\n";
    
    if ($warranty['is_active']) {
        $message .= "✅ Status: *AKTIF*\n";
        $message .= "⏳ Sisa Waktu: " . $warranty['days_remaining'] . " hari\n\n";
    } else {
        $message .= "❌ Status: *EXPIRED*\n\n";
    }
    
    $message .= "*SIMPAN ID GARANSI ANDA*\n";
    $message .= "Gunakan ID ini untuk cek masa aktif garansi";
    
    // Send via Bot API with fallback
    $sendResult = sendWhatsAppMessageWithFallback($warranty['nohp'], $message);
    
    return [
        'success' => true,
        'warranty' => $warranty,
        'whatsapp' => $sendResult
    ];
}

// Log Admin Activity dengan tracking admin
function logAdminActivity($action, $warrantyId = null, $description = '') {
    global $conn;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Get current admin info
    $admin = getCurrentAdmin();
    $adminId = $admin['id'] ?? null;
    $username = $admin['username'] ?? null;
    
    $sql = "INSERT INTO admin_logs (admin_id, username, action, warranty_id, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $adminId, $username, $action, $warrantyId, $description, $ipAddress, $userAgent);
    $stmt->execute();
}

// Get Admin Logs
function getAdminLogs($limit = 50, $adminId = null) {
    global $conn;
    
    $sql = "SELECT al.*, a.full_name 
            FROM admin_logs al 
            LEFT JOIN admins a ON al.admin_id = a.id";
    
    if ($adminId) {
        $sql .= " WHERE al.admin_id = ?";
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($adminId) {
        $stmt->bind_param("ii", $adminId, $limit);
    } else {
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    return $logs;
}

// Get All Admins
function getAllAdmins() {
    global $conn;
    
    $sql = "SELECT id, username, email, full_name, is_active, last_login, created_at 
            FROM admins 
            ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    
    return $admins;
}

// Toggle Admin Status
function toggleAdminStatus($adminId) {
    global $conn;
    
    // Tidak bisa menonaktifkan diri sendiri
    $currentAdmin = getCurrentAdmin();
    if ($currentAdmin && $currentAdmin['id'] == $adminId) {
        return ['success' => false, 'message' => 'Tidak dapat menonaktifkan akun sendiri'];
    }
    
    $sql = "UPDATE admins SET is_active = NOT is_active WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $adminId);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Status admin berhasil diubah'];
    }
    
    return ['success' => false, 'message' => 'Gagal mengubah status admin'];
}
// ========================================
// INCLUDE USER MANAGEMENT FUNCTIONS
// ========================================
//require_once __DIR__ . '/user-functions.php';


// Include Booking Functions
require_once __DIR__ . '/booking-functions.php';
?>