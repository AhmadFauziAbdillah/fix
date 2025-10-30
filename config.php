<?php
// config.php - Konfigurasi untuk Wasmer.io Hosting dengan Admin Authentication
session_start();

// ========================================
// DATABASE CONFIGURATION (WASMER.IO)
// ========================================
define('DB_HOST', 'db.fr-pari1.bengt.wasmernet.com');
define('DB_PORT', 10272);
define('DB_USER', '086ddaff79e08000139f9c29af2f');
define('DB_PASS', '0690086d-daff-7ceb-8000-a8fa0cd36712');
define('DB_NAME', 'YRTeam');

// Database Connection dengan Error Handling untuk Wasmer
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please check configuration.");
    }
    
    $conn->set_charset("utf8mb4");
    
    // Test connection
    if (!$conn->ping()) {
        throw new Exception("Database connection lost");
    }
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// ========================================
// APPLICATION CONSTANTS
// ========================================
// HAPUS ADMIN_PASSWORD dari sini - sekarang di database
define('WA_NUMBER', '62859106545737');
define('SITE_NAME', 'Sistem Garansi Remap ECU');

// ========================================
// SECURITY CONSTANTS
// ========================================
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 menit
define('SESSION_TIMEOUT', 3600); // 1 jam

// ========================================
// BOT WHATSAPP CONFIGURATION
// ========================================
define('BOT_API_URL', 'https://whatsapp-production-335b.up.railway.app/send-message');
define('BOT_STATUS_URL', 'https://whatsapp-production-335b.up.railway.app/status');
define('BOT_QR', 'https://whatsapp-production-335b.up.railway.app');    
define('BOT_TIMEOUT', 30);

// ========================================
// WASMER.IO SPECIFIC SETTINGS
// ========================================
// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting untuk production
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Session configuration untuk Wasmer
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Sanitize input data
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

/**
 * Redirect function
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is admin
 */
function isAdmin() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logoutAdmin();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Get current admin info
 */
function getCurrentAdmin() {
    if (!isAdmin()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'full_name' => $_SESSION['admin_full_name'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null
    ];
}

/**
 * Require admin access
 */
function requireAdmin() {
    if (!isAdmin()) {
        setFlashMessage('Silakan login terlebih dahulu', 'error');
        redirect('admin-login.php');
    }
}

/**
 * Logout admin
 */
function logoutAdmin() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Flash message system
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type']
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

// ========================================
// ADMIN AUTHENTICATION FUNCTIONS
// ========================================

/**
 * Verify admin login
 */
function verifyAdminLogin($username, $password) {
    global $conn;
    
    $username = sanitize($username);
    
    $sql = "SELECT * FROM admins WHERE username = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Username atau password salah'];
    }
    
    $admin = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $admin['password'])) {
        // Update last login
        $updateSql = "UPDATE admins SET last_login = NOW() WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $admin['id']);
        $updateStmt->execute();
        
        // Set session
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_full_name'] = $admin['full_name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['last_activity'] = time();
        
        return [
            'success' => true,
            'admin' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'full_name' => $admin['full_name'],
                'email' => $admin['email']
            ]
        ];
    }
    
    return ['success' => false, 'message' => 'Username atau password salah'];
}

/**
 * Change admin password
 */
function changeAdminPassword($adminId, $oldPassword, $newPassword) {
    global $conn;
    
    if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        return [
            'success' => false, 
            'message' => 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter'
        ];
    }
    
    // Get current password
    $sql = "SELECT password FROM admins WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Admin tidak ditemukan'];
    }
    
    $admin = $result->fetch_assoc();
    
    // Verify old password
    if (!password_verify($oldPassword, $admin['password'])) {
        return ['success' => false, 'message' => 'Password lama salah'];
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Update password
    $updateSql = "UPDATE admins SET password = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $hashedPassword, $adminId);
    
    if ($updateStmt->execute()) {
        return ['success' => true, 'message' => 'Password berhasil diubah'];
    }
    
    return ['success' => false, 'message' => 'Gagal mengubah password'];
}

/**
 * Create new admin (untuk script setup)
 */
function createAdmin($username, $password, $email, $fullName) {
    global $conn;
    
    $username = sanitize($username);
    $email = sanitize($email);
    $fullName = sanitize($fullName);
    
    // Validasi
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return [
            'success' => false, 
            'message' => 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter'
        ];
    }
    
    // Check if username exists
    $checkSql = "SELECT id FROM admins WHERE username = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username sudah digunakan'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert admin
    $sql = "INSERT INTO admins (username, password, email, full_name) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $hashedPassword, $email, $fullName);
    
    if ($stmt->execute()) {
        return [
            'success' => true, 
            'message' => 'Admin berhasil dibuat',
            'admin_id' => $conn->insert_id
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal membuat admin'];
}

// ========================================
// WHATSAPP BOT FUNCTIONS
// ========================================

/**
 * Check WhatsApp Bot status
 */
function getBotStatus() {
    try {
        $ch = curl_init(BOT_STATUS_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return [
                'online' => true,
                'connected' => isset($data['connected']) ? $data['connected'] : false,
                'botNumber' => isset($data['user']) ? $data['user'] : null
            ];
        }
    } catch (Exception $e) {
        error_log("Bot status check failed: " . $e->getMessage());
    }
    
    return ['online' => false, 'connected' => false];
}

/**
 * Send WhatsApp message via Bot API
 */
function sendWhatsAppMessage($phone, $message) {
    try {
        $payload = json_encode([
            'phone' => $phone,
            'message' => $message
        ]);
        
        $ch = curl_init(BOT_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => BOT_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return [
                'success' => isset($data['success']) ? $data['success'] : true,
                'method' => 'api',
                'response' => $data
            ];
        }
        
        return [
            'success' => false,
            'error' => $error ?: "HTTP $httpCode",
            'method' => 'api'
        ];
        
    } catch (Exception $e) {
        error_log("WhatsApp send failed: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'method' => 'api'
        ];
    }
}

/**
 * Send WhatsApp with fallback to web.whatsapp.com
 */
function sendWhatsAppMessageWithFallback($phone, $message) {
    // Try Bot API first
    $result = sendWhatsAppMessage($phone, $message);
    
    // If Bot fails, fallback to WhatsApp Web
    if (!$result['success']) {
        $waURL = 'https://wa.me/' . $phone . '?text=' . urlencode($message);
        return [
            'success' => true,
            'method' => 'fallback',
            'url' => $waURL,
            'error' => $result['error']
        ];
    }
    
    return $result;
}

// ========================================
// WASMER.IO COMPATIBILITY CHECKS
// ========================================

function isWasmerEnvironment() {
    return getenv('WASMER_ENV') !== false || 
           strpos($_SERVER['HTTP_HOST'] ?? '', 'wasmer') !== false;
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME']);
    $base = $protocol . '://' . $host . rtrim($script, '/');
    return $base;
}

define('BASE_URL', getBaseUrl());

// ========================================
// LOGGING FUNCTIONS
// ========================================

function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] $message\n";
    
    $logFile = __DIR__ . '/logs/app.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    if (is_writable($logDir)) {
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    } else {
        error_log($logEntry);
    }
}

// ========================================
// CLEANUP & OPTIMIZATION
// ========================================

register_shutdown_function(function() {
    global $conn;
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
});

if (!isset($_SESSION['env_logged'])) {
    logMessage('Environment: ' . (isWasmerEnvironment() ? 'Wasmer.io' : 'Standard'));
    logMessage('Base URL: ' . BASE_URL);
    logMessage('PHP Version: ' . PHP_VERSION);
    $_SESSION['env_logged'] = true;
}

// ========================================
// SECURITY HEADERS
// ========================================

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!headers_sent()) {
    header("Content-Security-Policy: default-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://wa.me https://web.whatsapp.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com;");
}

?>