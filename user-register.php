<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'user-functions.php';

$pageTitle = 'Daftar Akun';

// Redirect if already logged in
if (isUserLoggedIn()) {
    redirect('user-dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        setFlashMessage('Semua field harus diisi', 'error');
    } elseif ($password !== $confirmPassword) {
        setFlashMessage('Password tidak cocok', 'error');
    } elseif (strlen($password) < 8) {
        setFlashMessage('Password minimal 8 karakter', 'error');
    } else {
        $result = registerUser($email, $password, $fullName, $phone);
        
        if ($result['success']) {
            // Auto login after register
            loginUser($email, $password);
            setFlashMessage('Registrasi berhasil! Selamat datang ' . $fullName, 'success');
            redirect('user-dashboard.php');
        } else {
            setFlashMessage($result['message'], 'error');
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
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out;
        }
        
        .pulse-slow {
            animation: pulse 3s ease-in-out infinite;
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
                radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(99, 102, 241, 0.15) 0%, transparent 50%);
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
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
        <div class="w-full max-w-lg relative z-10">
            <div class="glass-morphism rounded-3xl shadow-2xl p-8 lg:p-10 fade-in">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center pulse-slow">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                        <span class="text-white text-xl font-bold">YR Team</span>
                    </div>
                    <a href="index.php" class="text-slate-400 hover:text-white transition text-sm font-medium">
                        ← Kembali
                    </a>
                </div>

                <!-- Title -->
                <div class="slide-up mb-8">
                    <p class="text-blue-400 text-sm font-semibold mb-3 uppercase tracking-wider">NEW ACCOUNT</p>
                    <h1 class="text-4xl font-bold text-white mb-3">
                        Daftar Akun<span class="text-blue-500">.</span>
                    </h1>
                    <p class="text-slate-400">Buat akun untuk booking layanan & tracking garansi</p>
                </div>

                <!-- Form -->
                <form method="POST" id="registerForm" class="space-y-5 slide-up" style="animation-delay: 0.2s;">
                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Nama Lengkap *</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="full_name"
                                id="full_name"
                                placeholder="Masukkan nama lengkap"
                                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition pl-12"
                                required
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Email *</label>
                        <div class="relative">
                            <input
                                type="email"
                                name="email"
                                id="email"
                                placeholder="email@example.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition pl-12"
                                required
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Nomor HP (WhatsApp) *</label>
                        <div class="relative">
                            <input
                                type="tel"
                                name="phone"
                                id="phone"
                                placeholder="08xxxxxxxxxx"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition pl-12"
                                pattern="[0-9]+"
                                required
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Password *</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="Minimal 8 karakter"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition pl-12 pr-12"
                                required
                                minlength="8"
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <button
                                type="button"
                                onclick="togglePassword('password')"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-white transition"
                            >
                                <svg id="eye-password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2">
                            <div id="password-strength" class="password-strength bg-slate-700"></div>
                            <p id="password-text" class="text-xs text-slate-500 mt-1">Gunakan kombinasi huruf, angka & simbol</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Konfirmasi Password *</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                placeholder="Ulangi password"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition pl-12 pr-12"
                                required
                                minlength="8"
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <button
                                type="button"
                                onclick="togglePassword('confirm_password')"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-white transition"
                            >
                                <svg id="eye-confirm_password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full btn-primary text-white py-4 rounded-xl font-semibold shadow-lg flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Daftar Sekarang
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-slate-400 text-sm">
                        Sudah punya akun?
                        <a href="user-login.php" class="text-blue-400 hover:text-blue-300 font-semibold transition">
                            Login di sini
                        </a>
                    </p>
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

    <script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById('eye-' + fieldId);
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
        } else {
            field.type = 'password';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
        }
    }

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('password-strength');
    const strengthText = document.getElementById('password-text');

    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!]+/)) strength++;
        
        switch(strength) {
            case 0:
            case 1:
                strengthBar.className = 'password-strength bg-red-500';
                strengthBar.style.width = '25%';
                strengthText.textContent = 'Lemah';
                strengthText.className = 'text-xs text-red-400 mt-1';
                break;
            case 2:
            case 3:
                strengthBar.className = 'password-strength bg-yellow-500';
                strengthBar.style.width = '50%';
                strengthText.textContent = 'Sedang';
                strengthText.className = 'text-xs text-yellow-400 mt-1';
                break;
            case 4:
                strengthBar.className = 'password-strength bg-blue-500';
                strengthBar.style.width = '75%';
                strengthText.textContent = 'Kuat';
                strengthText.className = 'text-xs text-blue-400 mt-1';
                break;
            case 5:
                strengthBar.className = 'password-strength bg-green-500';
                strengthBar.style.width = '100%';
                strengthText.textContent = 'Sangat Kuat';
                strengthText.className = 'text-xs text-green-400 mt-1';
                break;
        }
    });

    // Focus first field
    document.getElementById('full_name').focus();
    </script>
</body>
</html>