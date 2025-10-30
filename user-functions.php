<?php
// user-functions.php - User Management Functions

function registerUser($email, $password, $fullName, $phone) {
    global $conn;
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password minimal 8 karakter'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Format email tidak valid'];
    }
    
    $phone = normalizePhone($phone);
    
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email sudah terdaftar'];
    }
    
    $checkPhone = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $checkPhone->bind_param("s", $phone);
    $checkPhone->execute();
    if ($checkPhone->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Nomor HP sudah terdaftar'];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $registrationIp = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO users (email, password, full_name, phone, registration_ip) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $email, $hashedPassword, $fullName, $phone, $registrationIp);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        logUserActivity($userId, 'REGISTER', 'User registered successfully');
        
        $message = "*YR Team - Selamat Datang!*\n\nHalo *$fullName*,\n\nAkun Anda berhasil dibuat!\n\n📧 Email: $email\n📱 No HP: $phone\n\nSekarang Anda bisa:\n✅ Booking layanan remap ECU\n✅ Cek status garansi\n✅ Track booking Anda\n\nWebsite: yrteam.wasmer.app\n\nTerima kasih telah bergabung! 🙏";
        
        sendWhatsAppMessageWithFallback($phone, $message);
        
        return ['success' => true, 'message' => 'Registrasi berhasil!', 'user_id' => $userId];
    }
    
    return ['success' => false, 'message' => 'Gagal membuat akun'];
}

function loginUser($emailOrPhone, $password, $rememberMe = false) {
    global $conn;
    
    $isEmail = filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL);
    
    if ($isEmail) {
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
    } else {
        $normalizedPhone = normalizePhone($emailOrPhone);
        $sql = "SELECT * FROM users WHERE phone = ? AND is_active = 1";
        $emailOrPhone = $normalizedPhone;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $emailOrPhone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Email/HP atau password salah'];
    }
    
    $user = $result->fetch_assoc();
    
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remainingTime = ceil((strtotime($user['locked_until']) - time()) / 60);
        return ['success' => false, 'message' => "Akun terkunci. Coba lagi dalam $remainingTime menit"];
    }
    
    if (password_verify($password, $user['password'])) {
        $resetAttempts = $conn->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $resetAttempts->bind_param("i", $user['id']);
        $resetAttempts->execute();
        
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_last_activity'] = time();
        
        if ($rememberMe) {
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            $insertSession = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $insertSession->bind_param("issss", $user['id'], $sessionToken, $ipAddress, $userAgent, $expiresAt);
            $insertSession->execute();
            setcookie('remember_token', $sessionToken, strtotime('+30 days'), '/', '', false, true);
        }
        
        logUserActivity($user['id'], 'LOGIN', 'User logged in successfully');
        
        return ['success' => true, 'message' => 'Login berhasil!', 'user' => ['id' => $user['id'], 'email' => $user['email'], 'name' => $user['full_name'], 'phone' => $user['phone']]];
    } else {
        $attempts = $user['login_attempts'] + 1;
        
        if ($attempts >= 5) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $lockAccount = $conn->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
            $lockAccount->bind_param("isi", $attempts, $lockedUntil, $user['id']);
            $lockAccount->execute();
            return ['success' => false, 'message' => 'Terlalu banyak percobaan gagal. Akun dikunci selama 15 menit'];
        } else {
            $updateAttempts = $conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
            $updateAttempts->bind_param("ii", $attempts, $user['id']);
            $updateAttempts->execute();
            $remaining = 5 - $attempts;
            return ['success' => false, 'message' => "Email/HP atau password salah. Sisa percobaan: $remaining"];
        }
    }
}

function isUserLoggedIn() {
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        if (isset($_SESSION['user_last_activity']) && (time() - $_SESSION['user_last_activity'] > 1800)) {
            logoutUser();
            return false;
        }
        $_SESSION['user_last_activity'] = time();
        return true;
    }
    
    if (isset($_COOKIE['remember_token'])) {
        return validateRememberToken($_COOKIE['remember_token']);
    }
    
    return false;
}

function validateRememberToken($token) {
    global $conn;
    $sql = "SELECT u.* FROM users u INNER JOIN user_sessions s ON u.id = s.user_id WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_last_activity'] = time();
        return true;
    }
    
    return false;
}

function getCurrentUser() {
    if (!isUserLoggedIn()) return null;
    return ['id' => $_SESSION['user_id'], 'email' => $_SESSION['user_email'], 'name' => $_SESSION['user_name'], 'phone' => $_SESSION['user_phone']];
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        setFlashMessage('Silakan login terlebih dahulu', 'error');
        redirect('user-login.php');
    }
}

function logoutUser() {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) logUserActivity($userId, 'LOGOUT', 'User logged out');
    
    if (isset($_COOKIE['remember_token'])) {
        global $conn;
        $token = $_COOKIE['remember_token'];
        $deleteToken = $conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $deleteToken->bind_param("s", $token);
        $deleteToken->execute();
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

function updateUserProfile($userId, $fullName, $email, $phone) {
    global $conn;
    $phone = normalizePhone($phone);
    
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkEmail->bind_param("si", $email, $userId);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) return ['success' => false, 'message' => 'Email sudah digunakan'];
    
    $checkPhone = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
    $checkPhone->bind_param("si", $phone, $userId);
    $checkPhone->execute();
    if ($checkPhone->get_result()->num_rows > 0) return ['success' => false, 'message' => 'Nomor HP sudah digunakan'];
    
    $sql = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $fullName, $email, $phone, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_phone'] = $phone;
        logUserActivity($userId, 'UPDATE_PROFILE', 'Profile updated');
        return ['success' => true, 'message' => 'Profil berhasil diupdate'];
    }
    
    return ['success' => false, 'message' => 'Gagal mengupdate profil'];
}

function changeUserPassword($userId, $oldPassword, $newPassword) {
    global $conn;
    
    if (strlen($newPassword) < 8) return ['success' => false, 'message' => 'Password baru minimal 8 karakter'];
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) return ['success' => false, 'message' => 'User tidak ditemukan'];
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($oldPassword, $user['password'])) return ['success' => false, 'message' => 'Password lama salah'];
    
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userId);
    
    if ($updateStmt->execute()) {
        logUserActivity($userId, 'CHANGE_PASSWORD', 'Password changed');
        return ['success' => true, 'message' => 'Password berhasil diubah'];
    }
    
    return ['success' => false, 'message' => 'Gagal mengubah password'];
}

function logUserActivity($userId, $action, $description = '') {
    global $conn;
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $sql = "INSERT INTO user_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $userId, $action, $description, $ipAddress, $userAgent);
    $stmt->execute();
}

function getUserById($userId) {
    global $conn;
    $sql = "SELECT id, email, full_name, phone, is_verified, is_active, profile_photo, last_login, created_at FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function getUserStats($userId) {
    global $conn;
    $bookings = 0;
    $warrantyStmt = $conn->prepare("SELECT COUNT(*) as count FROM warranties WHERE user_id = ? AND expiry_date > NOW()");
    $warrantyStmt->bind_param("i", $userId);
    $warrantyStmt->execute();
    $warranties = $warrantyStmt->get_result()->fetch_assoc()['count'];
    return ['total_bookings' => $bookings, 'active_warranties' => $warranties];
}
?>