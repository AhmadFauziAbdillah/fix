<?php
/**
 * YR Team - Booking System Auto Installer
 * Auto-create all booking files, database, and configurations
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$lockFile = __DIR__ . '/.booking-system-installed';
$forceReinstall = isset($_GET['force']) && $_GET['force'] === 'yes';

if (file_exists($lockFile) && !$forceReinstall) {
    die('
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Already Installed</title>
    <style>body{font-family:system-ui;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#1e293b;padding:2rem;border-radius:1rem;max-width:500px;text-align:center}
    .success{color:#10b981;font-size:3rem;margin-bottom:1rem}
    a{display:inline-block;margin-top:1rem;padding:0.75rem 1.5rem;background:#3b82f6;color:#fff;text-decoration:none;border-radius:0.5rem;font-weight:600}
    a:hover{background:#2563eb}
    .warning{color:#f59e0b;margin-top:1rem;font-size:0.875rem}
    </style></head><body>
    <div class="box">
        <div class="success">✅</div>
        <h1>Booking System Sudah Terinstall!</h1>
        <p style="color:#94a3b8;margin-top:1rem">Semua file dan database sudah siap.</p>
        <a href="booking.php">Buat Booking</a>
        <a href="admin-bookings.php" style="background:#8b5cf6">Admin Bookings</a>
        <div class="warning">
            Mau install ulang? <a href="?force=yes" style="padding:0.25rem 0.5rem;font-size:0.75rem;background:#ef4444">Force Reinstall</a>
        </div>
    </div>
    </body></html>
    ');
}

$errors = [];
$success = [];
$warnings = [];

// Check requirements
if (!file_exists('config.php')) {
    $errors[] = 'File config.php tidak ditemukan!';
}

if (!file_exists('functions.php')) {
    $errors[] = 'File functions.php tidak ditemukan!';
}

if (!file_exists('user-functions.php')) {
    $errors[] = 'File user-functions.php tidak ditemukan! Install user system dulu.';
}

// Connect database
if (empty($errors)) {
    require_once 'config.php';
    if (!isset($conn) || !$conn) {
        $errors[] = 'Koneksi database gagal!';
    }
}

// Create database tables
if (empty($errors)) {
    $sql = "
    CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        duration_minutes INT DEFAULT 60,
        warranty_days INT DEFAULT 7,
        is_active TINYINT(1) DEFAULT 1,
        icon VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_code VARCHAR(50) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        service_id INT NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        customer_email VARCHAR(100),
        motorcycle_brand VARCHAR(50),
        motorcycle_model VARCHAR(100),
        motorcycle_year INT,
        motorcycle_plate VARCHAR(20),
        booking_date DATE NOT NULL,
        booking_time TIME NOT NULL,
        notes TEXT,
        service_price DECIMAL(10,2) NOT NULL,
        unique_code INT DEFAULT 0,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'dana',
        payment_status ENUM('pending', 'paid', 'expired', 'cancelled') DEFAULT 'pending',
        payment_proof VARCHAR(255) DEFAULT NULL,
        paid_at DATETIME DEFAULT NULL,
        booking_status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        warranty_id VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        expires_at DATETIME DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
        INDEX idx_user (user_id),
        INDEX idx_booking_code (booking_code),
        INDEX idx_payment_status (payment_status),
        INDEX idx_booking_status (booking_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS payment_confirmations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        proof_image VARCHAR(255) NOT NULL,
        upload_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        verified_by INT DEFAULT NULL,
        verified_at DATETIME DEFAULT NULL,
        verification_notes TEXT,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        INDEX idx_booking (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS booking_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        description TEXT,
        performed_by VARCHAR(50),
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        INDEX idx_booking (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        $success[] = 'Database tables berhasil dibuat!';
    } else {
        $errors[] = 'Error creating tables: ' . $conn->error;
    }

    // Insert default services
    $checkServices = $conn->query("SELECT COUNT(*) as count FROM services");
    if ($checkServices->fetch_assoc()['count'] == 0) {
        $insertServices = "INSERT INTO services (name, description, price, duration_minutes, warranty_days, icon) VALUES
        ('Remap ECU Basic', 'Optimasi performa dasar dengan garansi 7 hari', 500000, 120, 7, '🔧'),
        ('Remap ECU Premium', 'Optimasi performa maksimal dengan garansi 14 hari', 750000, 180, 14, '⚡'),
        ('Tuning Full Service', 'Tuning lengkap + dynotest dengan garansi 30 hari', 1200000, 240, 30, '🏍️'),
        ('Dyno Test', 'Pengetesan performa motor di dynamometer', 300000, 60, 0, '📊'),
        ('Konsultasi Teknis', 'Konsultasi masalah performa motor', 150000, 45, 0, '💬')";
        
        if ($conn->query($insertServices)) {
            $success[] = 'Default services berhasil dibuat!';
        }
    } else {
        $warnings[] = 'Services sudah ada sebelumnya.';
    }

    // Create uploads directory
    if (!file_exists('uploads/payments')) {
        if (mkdir('uploads/payments', 0755, true)) {
            $success[] = 'Upload directory berhasil dibuat!';
        }
    }
}

// Create booking-functions.php
if (empty($errors)) {
    $bookingFunctionsContent = file_get_contents('https://gist.githubusercontent.com/placeholder/booking-functions.txt');
    
    // Fallback: embedded content
    if (!$bookingFunctionsContent) {
        $bookingFunctionsContent = '<?php
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
?>';
    }
    
    if (file_put_contents('booking-functions.php', $bookingFunctionsContent)) {
        $success[] = 'File booking-functions.php berhasil dibuat!';
    } else {
        $errors[] = 'Gagal membuat booking-functions.php';
    }
}

// Update functions.php
if (empty($errors)) {
    $functionsContent = file_get_contents('functions.php');
    if (strpos($functionsContent, 'booking-functions.php') === false) {
        $includeCode = "\n// Include Booking Functions\nrequire_once __DIR__ . '/booking-functions.php';\n";
        $functionsContent = str_replace('?>', $includeCode . '?>', $functionsContent);
        if (file_put_contents('functions.php', $functionsContent)) {
            $success[] = 'functions.php berhasil diupdate!';
        }
    } else {
        $warnings[] = 'functions.php sudah include booking-functions.php';
    }
}

// Create lock file
if (empty($errors)) {
    file_put_contents($lockFile, date('Y-m-d H:i:s'));
    $success[] = 'Installation lock file created!';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking System Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 800px;
            width: 100%;
            padding: 3rem;
        }
        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .status-box {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: start;
            gap: 0.75rem;
        }
        .success-box { background: #dcfce7; border-left: 4px solid #22c55e; color: #166534; }
        .error-box { background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; }
        .warning-box { background: #fef3c7; border-left: 4px solid #f59e0b; color: #92400e; }
        .icon { font-size: 1.5rem; }
        .message-list { list-style: none; }
        .message-list li { padding: 0.5rem 0; }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            margin: 0.5rem;
        }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        .next-steps {
            background: #f1f5f9;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1.5rem;
        }
        .next-steps h3 { font-size: 1rem; color: #475569; margin-bottom: 0.5rem; }
        .next-steps ol { margin-left: 1.5rem; }
        .next-steps li { padding: 0.25rem 0; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span class="icon"><?php echo empty($errors) ? '✅' : '❌'; ?></span>
            Booking System Installer
        </h1>
        <p style="color:#64748b;margin-bottom:2rem">Automatic installation for YR Team Booking System</p>

        <?php if (!empty($errors)): ?>
            <div class="status-box error-box">
                <span class="icon">❌</span>
                <div>
                    <strong>Installation Failed!</strong>
                    <ul class="message-list">
                        <?php foreach ($errors as $error): ?>
                            <li>• <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="status-box success-box">
                <span class="icon">✅</span>
                <div>
                    <strong>Installation Successful!</strong>
                    <ul class="message-list">
                        <?php foreach ($success as $msg): ?>
                            <li>• <?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($warnings)): ?>
            <div class="status-box warning-box">
                <span class="icon">⚠️</span>
                <div>
                    <strong>Warnings:</strong>
                    <ul class="message-list">
                        <?php foreach ($warnings as $warning): ?>
                            <li>• <?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($errors)): ?>
            <div class="next-steps">
                <h3>📝 Next Steps:</h3>
                <ol>
                    <li>Jalankan <code>create-booking-pages.php</code> untuk membuat file halaman</li>
                    <li>Test booking di halaman user</li>
                    <li>Setup nomor DANA di config atau database</li>
                    <li>Test flow lengkap: Booking → Payment → Upload → Verify</li>
                </ol>
            </div>

            <div style="margin-top:2rem;text-align:center">
                <a href="create-booking-pages.php" class="btn btn-success">Create Booking Pages →</a>
                <a href="user-dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <div style="margin-top:2rem;text-align:center">
                <button onclick="location.reload()" class="btn btn-primary">Retry Installation</button>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>