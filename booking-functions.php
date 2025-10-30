<?php
// booking-functions.php included via installer
require_once "config.php";

function getAllServices() {
    global $conn;
    return $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC")->fetch_all(MYSQLI_ASSOC);
}

function getServiceById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function generateBookingCode() {
    return "BK-" . date("ymd") . "-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

function generateUniqueCode() {
    global $conn;
    $today = date("Y-m-d");
    $result = $conn->query("SELECT unique_code FROM bookings WHERE DATE(created_at) = \"$today\" AND unique_code > 0");
    $used = [];
    while ($row = $result->fetch_assoc()) $used[] = $row["unique_code"];
    do { $code = rand(1, 999); } while (in_array($code, $used));
    return $code;
}

function createBooking($data) {
    global $conn;
    $service = getServiceById($data["service_id"]);
    if (!$service) return ["success" => false, "message" => "Layanan tidak ditemukan"];
    
    $bookingCode = generateBookingCode();
    $uniqueCode = generateUniqueCode();
    $totalAmount = $service["price"] + $uniqueCode;
    $phone = normalizePhone($data["customer_phone"]);
    $expiresAt = date("Y-m-d H:i:s", strtotime("+24 hours"));
    
    $sql = "INSERT INTO bookings (booking_code, user_id, service_id, customer_name, customer_phone, customer_email,
            motorcycle_brand, motorcycle_model, motorcycle_year, motorcycle_plate, booking_date, booking_time, notes,
            service_price, unique_code, total_amount, payment_method, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siisssssississddss", $bookingCode, $data["user_id"], $data["service_id"],
        $data["customer_name"], $phone, $data["customer_email"], $data["motorcycle_brand"],
        $data["motorcycle_model"], $data["motorcycle_year"], $data["motorcycle_plate"],
        $data["booking_date"], $data["booking_time"], $data["notes"], $service["price"],
        $uniqueCode, $totalAmount, $data["payment_method"], $expiresAt);
    
    if ($stmt->execute()) {
        $bookingId = $conn->insert_id;
        logBookingActivity($bookingId, "CREATE", "Booking created", "user");
        return ["success" => true, "booking_id" => $bookingId, "booking_code" => $bookingCode, 
                "total_amount" => $totalAmount, "unique_code" => $uniqueCode];
    }
    return ["success" => false, "message" => "Gagal membuat booking"];
}

function getBookingByCode($code) {
    global $conn;
    $stmt = $conn->prepare("SELECT b.*, s.name as service_name, s.warranty_days, u.full_name as user_name
                            FROM bookings b INNER JOIN services s ON b.service_id = s.id
                            INNER JOIN users u ON b.user_id = u.id WHERE b.booking_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        $booking["is_expired"] = ($booking["expires_at"] && strtotime($booking["expires_at"]) < time() && $booking["payment_status"] === "pending");
        return $booking;
    }
    return null;
}

function getBookingsByUser($userId, $limit = 20) {
    global $conn;
    $stmt = $conn->prepare("SELECT b.*, s.name as service_name FROM bookings b 
                            INNER JOIN services s ON b.service_id = s.id 
                            WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function uploadPaymentProof($bookingId, $file) {
    global $conn;
    $allowed = ["image/jpeg", "image/jpg", "image/png"];
    if (!in_array($file["type"], $allowed)) return ["success" => false, "message" => "Format file harus JPG/PNG"];
    if ($file["size"] > 5*1024*1024) return ["success" => false, "message" => "Ukuran maksimal 5MB"];
    
    $uploadDir = __DIR__ . "/uploads/payments/";
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $filename = "payment_" . $bookingId . "_" . time() . "." . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file["tmp_name"], $filepath)) {
        $relative = "uploads/payments/" . $filename;
        $conn->query("INSERT INTO payment_confirmations (booking_id, proof_image) VALUES ($bookingId, \"$relative\")");
        $conn->query("UPDATE bookings SET payment_proof = \"$relative\" WHERE id = $bookingId");
        logBookingActivity($bookingId, "UPLOAD_PROOF", "Payment proof uploaded", "user");
        return ["success" => true, "message" => "Bukti berhasil diupload", "filename" => $relative];
    }
    return ["success" => false, "message" => "Gagal upload file"];
}

function verifyPayment($bookingId, $adminId, $notes = "") {
    global $conn;
    $booking = getBookingById($bookingId);
    if (!$booking) return ["success" => false, "message" => "Booking tidak ditemukan"];
    
    $conn->query("UPDATE bookings SET payment_status = \"paid\", booking_status = \"confirmed\", paid_at = NOW() WHERE id = $bookingId");
    $conn->query("UPDATE payment_confirmations SET verified_by = $adminId, verified_at = NOW(), verification_notes = \"$notes\" 
                  WHERE booking_id = $bookingId ORDER BY upload_time DESC LIMIT 1");
    
    if ($booking["warranty_days"] > 0) {
        $warrantyId = generateWarrantyId();
        addWarranty($warrantyId, $booking["customer_name"], $booking["customer_phone"], 
                   $booking["motorcycle_model"], $booking["warranty_days"]);
        $conn->query("UPDATE bookings SET warranty_id = \"$warrantyId\" WHERE id = $bookingId");
        $conn->query("UPDATE warranties SET user_id = {$booking["user_id"]} WHERE id = \"$warrantyId\"");
    }
    
    logBookingActivity($bookingId, "VERIFY_PAYMENT", "Payment verified", "admin");
    return ["success" => true, "message" => "Pembayaran berhasil diverifikasi"];
}

function getBookingById($id) {
    global $conn;
    $result = $conn->query("SELECT b.*, s.warranty_days FROM bookings b INNER JOIN services s ON b.service_id = s.id WHERE b.id = $id");
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function getAllBookings($search = "", $status = "") {
    global $conn;
    $sql = "SELECT b.*, s.name as service_name, u.full_name as customer_name_user 
            FROM bookings b INNER JOIN services s ON b.service_id = s.id 
            INNER JOIN users u ON b.user_id = u.id WHERE 1=1";
    
    if ($search) $sql .= " AND (b.booking_code LIKE \"%$search%\" OR b.customer_name LIKE \"%$search%\")";
    if ($status) $sql .= " AND b.payment_status = \"$status\"";
    
    $sql .= " ORDER BY b.created_at DESC LIMIT 100";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function logBookingActivity($bookingId, $action, $description, $by = "system") {
    global $conn;
    $ip = $_SERVER["REMOTE_ADDR"];
    $conn->query("INSERT INTO booking_logs (booking_id, action, description, performed_by, ip_address) 
                  VALUES ($bookingId, \"$action\", \"$description\", \"$by\", \"$ip\")");
}

function getBookingStatistics() {
    global $conn;
    $stats = [];
    $stats["total"] = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()["c"];
    $stats["pending_payment"] = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE payment_status = \"pending\"")->fetch_assoc()["c"];
    $stats["confirmed"] = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE booking_status = \"confirmed\"")->fetch_assoc()["c"];
    $stats["completed"] = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE booking_status = \"completed\"")->fetch_assoc()["c"];
    $revenue = $conn->query("SELECT SUM(total_amount) as t FROM bookings WHERE payment_status = \"paid\"")->fetch_assoc();
    $stats["revenue"] = $revenue["t"] ?? 0;
    return $stats;
}
?>