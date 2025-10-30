<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'user-functions.php';

$pageTitle = 'Login Pelanggan';

// Redirect if already logged in
if (isUserLoggedIn()) {
    redirect('user-dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrPhone = sanitize($_POST['email_phone']);
    $password = $_POST['password'];
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($emailOrPhone) || empty($password)) {
        setFlashMessage('Email/HP dan password harus diisi', 'error');
    } else {
        $result = loginUser($emailOrPhone, $password, $rememberMe);
        
        if ($result['success']) {
            setFlashMessage('Login berhasil! Selamat datang ' . $result['user']['name'], 'success');
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
        <div class="w-full max-w-md relative z-10">
            <div class="glass-morphism rounded-3xl shadow-2xl p-8 lg:p-10 fade-in">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center pulse-slow">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
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
                    <p class="text-blue-400 text-sm font-semibold mb-3 uppercase tracking-wider">WELCOME BACK</p>
                    <h1 class="text-4xl font-bold text-white mb-3">
                        Login<span class="text-blue-500">.</span>
                    </h1>
                    <p class="text-slate-400">Masuk ke akun Anda untuk booking & tracking</p>
                </div>

                <!-- Form -->
                <form method="POST" class="space-y-5 slide-up" style="animation-delay: 0.2s;">
                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Email atau Nomor HP</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="email_phone"
                                id="email_phone"
                                placeholder="email@example.com atau 08xxx"
                                value="<?php echo isset($_POST['email_phone']) ? htmlspecialchars($_POST['email_phone']) : ''; ?>"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition pl-12"
                                required
                                autocomplete="username"
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2">Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="••••••••"
                                class="w-full px-4 py-3.5 bg-slate-700 bg-opacity-50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none input-glow transition pl-12 pr-12"
                                required
                                autocomplete="current-password"
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <button
                                type="button"
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-white transition"
                            >
                                <svg id="eye-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg id="eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" name="remember_me" class="w-4 h-4 text-blue-500 bg-slate-700 border-slate-600 rounded focus:ring-blue-500 focus:ring-2">
                            <span class="ml-2 text-sm text-slate-400 group-hover:text-white transition">Ingat saya</span>
                        </label>
                        <a href="#" class="text-sm text-blue-400 hover:text-blue-300 transition">Lupa password?</a>
                    </div>

                    <button
                        type="submit"
                        class="w-full btn-primary text-white py-4 rounded-xl font-semibold shadow-lg flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Masuk
                    </button>
                </form>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <p class="text-slate-400 text-sm">
                        Belum punya akun?
                        <a href="user-register.php" class="text-blue-400 hover:text-blue-300 font-semibold transition">
                            Daftar sekarang
                        </a>
                    </p>
                </div>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-slate-800 text-slate-400">Atau</span>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-2 gap-3">
                    <a href="index.php" class="px-4 py-3 bg-slate-700 bg-opacity-50 hover:bg-opacity-70 text-slate-300 hover:text-white rounded-xl font-medium transition text-center text-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Cek Garansi
                    </a>
                    <a href="admin-login.php" class="px-4 py-3 bg-slate-700 bg-opacity-50 hover:bg-opacity-70 text-slate-300 hover:text-white rounded-xl font-medium transition text-center text-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Admin
                    </a>
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
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    }

    document.getElementById('email_phone').focus();
    </script>
</body>
</html>