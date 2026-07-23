<?php 
require_once 'config.php'; 
if (isLoggedIn()) header('Location: dashboard.php');
$error = '';
if ($_POST) {
    $password = $_POST['password'];
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif ($_POST['password'] === $_POST['confirm_password']) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['username'], $_POST['email'], $hash]);
            header('Location: login.php'); exit;
        } catch (PDOException $e) {
            $error = "Username or email already exists!";
        }
    } else {
        $error = "Passwords don't match!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Daily Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Dark Mode Variables */
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --dark: #1a1a2e;
            --darker: #16213e;
            --card-bg: rgba(255,255,255,0.08);
            --glass: rgba(255,255,255,0.15);
            --cyan-main: #00d4ff;
            --cyan-light: #66e0ff;
            --cyan-muted: #99e6ff;
            --accent: #f093fb;
            --success: #27ae60;
            
            /* Light Mode Variables */
            --light-bg: #f8fafc;
            --light-card: #ffffff;
            --light-text: #1e293b;
            --light-border: #e2e8f0;
            --light-shadow: rgba(0,0,0,0.1);
            --light-muted: #64748b;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body.dark {
            background: linear-gradient(135deg, var(--dark), var(--darker));
        }
        
        body.light {
            background: linear-gradient(135deg, #e2e8f0 0%, #f1f5f9 100%);
        }

        /* Floating Background Shapes */
        .floating-shapes {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
            transition: opacity 0.4s ease;
        }
        body.light .floating-shapes { opacity: 0; }
        
        .shape { 
            position: absolute;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.08;
            animation: float 20s infinite linear;
        }
        .shape:nth-child(1) { width: 100px; height: 100px; top: 15%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 150px; height: 150px; top: 70%; right: 15%; animation-delay: -7s; }
        .shape:nth-child(3) { width: 80px; height: 80px; bottom: 20%; left: 20%; animation-delay: -12s; }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        /* Theme Toggle Button */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary);
            border: none;
            border-radius: 50%;
            width: 55px;
            height: 55px;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(102,126,234,0.4);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px);
        }
        .theme-toggle:hover {
            transform: scale(1.1) rotate(180deg);
            box-shadow: 0 15px 40px rgba(102,126,234,0.6);
        }

        .login-container {
            backdrop-filter: blur(30px);
            border-radius: 28px;
            box-shadow: 0 30px 60px var(--light-shadow);
            border: 1px solid rgba(102,224,255,0.3);
            padding: 3.5rem 3rem;
            width: 100%;
            max-width: 450px;
            animation: slideUp 1s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            z-index: 1;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Dark Mode Container */
        body.dark .login-container {
            background: rgba(26,26,46,0.95);
        }
        body.dark .login-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(0,212,255,0.1), rgba(102,224,255,0.05));
            border-radius: 28px;
            z-index: 0;
        }
        body.dark .login-container > * { position: relative; z-index: 1; }
        
        /* Light Mode Container */
        body.light .login-container {
            background: var(--light-card);
            border-color: var(--light-border);
            box-shadow: 0 25px 50px var(--light-shadow);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(60px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .logo { 
            font-size: 2.8rem; 
            font-weight: 800;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.2rem;
            letter-spacing: -1px;
            transition: all 0.4s ease;
        }
        body.light .logo {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .subtitle {
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 2.5rem;
            transition: all 0.4s ease;
        }
        body.dark .subtitle { color: var(--cyan-muted); }
        body.light .subtitle { color: var(--light-muted); }
        
        .form-label {
            font-weight: 600 !important;
            margin-bottom: 0.75rem;
            font-size: 1rem;
            transition: all 0.4s ease;
        }
        body.dark .form-label { color: var(--cyan-light) !important; }
        body.light .form-label { color: #475569 !important; }
        
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border-radius: 18px !important;
            padding: 18px 24px 18px 24px !important;
            font-size: 1.05rem;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(15px);
            width: 100%;
        }
        
        /* Dark Mode Form Control */
        body.dark .form-control {
            background: rgba(102,224,255,0.12) !important;
            border: 2px solid rgba(102,224,255,0.3) !important;
            color: var(--cyan-main) !important;
        }
        body.dark .form-control::placeholder { color: var(--cyan-muted) !important; }
        body.dark .form-control:focus {
            background: rgba(102,224,255,0.25) !important;
            border-color: var(--cyan-main) !important;
            box-shadow: 0 0 0 0.3rem rgba(0,212,255,0.2), 0 15px 35px rgba(0,212,255,0.3) !important;
            color: var(--cyan-main) !important;
            transform: translateY(-2px);
        }
        
        /* Light Mode Form Control */
        body.light .form-control {
            background: #f8fafc !important;
            border: 2px solid #e2e8f0 !important;
            color: #1e293b !important;
        }
        body.light .form-control::placeholder { color: #94a3b8 !important; }
        body.light .form-control:focus {
            background: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.3rem rgba(59,130,246,0.15), 0 0 0 0.5rem rgba(59,130,246,0.1) !important;
            color: #1e293b !important;
            transform: translateY(-2px);
        }
        
        /* Password Strength Indicator */
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            border-radius: 2px;
            background: rgba(102,224,255,0.2);
            transition: all 0.3s ease;
        }
        body.dark .password-strength.weak { background: #e74c3c; height: 4px; }
        body.dark .password-strength.medium { background: #f39c12; height: 6px; }
        body.dark .password-strength.strong { background: #27ae60; height: 8px; }
        body.light .password-strength.weak { background: #ef4444; height: 4px; }
        body.light .password-strength.medium { background: #f59e0b; height: 6px; }
        body.light .password-strength.strong { background: #10b981; height: 8px; }
        
        /* 🔓 PASSWORD TOGGLE BUTTON */
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            transition: all 0.3s ease;
            z-index: 2;
        }
        body.dark .password-toggle { color: var(--cyan-muted); }
        body.dark .password-toggle:hover { color: var(--cyan-main) !important; }
        body.dark .password-toggle.active { color: var(--cyan-light) !important; }
        body.light .password-toggle { color: #64748b; }
        body.light .password-toggle:hover { color: #3b82f6 !important; }
        body.light .password-toggle.active { color: #1d4ed8 !important; }
        
        .btn-register {
            border: none !important;
            border-radius: 18px !important;
            padding: 18px !important;
            font-weight: 700 !important;
            font-size: 1.15rem !important;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        body.dark .btn-register {
            background: var(--primary) !important;
            color: white !important;
            box-shadow: 0 15px 35px rgba(102,126,234,0.4);
        }
        body.dark .btn-register:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 25px 50px rgba(102,126,234,0.6) !important;
        }
        
        body.light .btn-register {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%) !important;
            color: white !important;
            box-shadow: 0 15px 35px rgba(59,130,246,0.3);
        }
        body.light .btn-register:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 25px 50px rgba(59,130,246,0.5) !important;
        }
        
        body.dark .btn-register:active, body.light .btn-register:active { transform: translateY(-2px) !important; }
        
        .error {
            border-radius: 18px !important;
            backdrop-filter: blur(15px);
            font-weight: 500;
            margin-bottom: 1.75rem;
            padding: 1rem 1.5rem !important;
        }
        body.dark .error {
            background: rgba(231,76,60,0.25) !important;
            border: 2px solid rgba(231,76,60,0.5) !important;
            color: var(--cyan-main) !important;
        }
        body.light .error {
            background: #fef2f2 !important;
            border: 2px solid #fecaca !important;
            color: #dc2626 !important;
        }
        
        .link-login {
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            text-decoration: none;
        }
        body.dark .link-login { color: var(--cyan-light) !important; }
        body.dark .link-login:hover { 
            color: var(--cyan-main) !important;
            text-shadow: 0 0 10px rgba(0,212,255,0.5);
        }
        body.light .link-login { color: #3b82f6 !important; }
        body.light .link-login:hover { color: #1d4ed8 !important; }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2.5rem 2rem;
            }
            .logo { font-size: 2.2rem; }
            .password-toggle { right: 15px; font-size: 1.1rem; }
            .theme-toggle { top: 15px; right: 15px; width: 50px; height: 50px; }
        }
    </style>
</head>
<body class="dark">
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" title="Switch to Light Mode">
        <i class="fas fa-sun"></i>
    </button>

    <!-- Floating Background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="text-center mb-5">
            <div class="logo">Daily Expense Tracker</div>
            <div class="subtitle">Create your account to get started</div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger error p-3 mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group mb-4">
                <label class="form-label">
                    <i class="fas fa-user me-2"></i>Username
                </label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
            </div>
            
            <div class="form-group mb-4">
                <label class="form-label">
                    <i class="fas fa-envelope me-2"></i>Email
                </label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
            </div>
            
            <!-- 🔓 PASSWORD WITH TOGGLE + STRENGTH METER -->
            <div class="form-group mb-4">
                <label class="form-label">
                    <i class="fas fa-lock me-2"></i>Password <small style="color: var(--cyan-muted);">(Min 6 characters)</small>
                </label>
                <div style="position: relative;">
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Minimum 6 characters" required>
                    <button type="button" class="password-toggle" id="togglePassword" title="Show password">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
            </div>
            
            <!-- 🔓 CONFIRM PASSWORD WITH TOGGLE -->
            <div class="form-group mb-5">
                <label class="form-label">
                    <i class="fas fa-lock me-2"></i>Confirm Password
                </label>
                <div style="position: relative;">
                    <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control" placeholder="Confirm your password" required>
                    <button type="button" class="password-toggle" id="toggleConfirmPassword" title="Show password">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-register text-white shadow-lg">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>
        
        <div class="text-center mt-5 pt-4">
            <p class="mb-0" style="color: var(--cyan-muted);">Already have an account? 
                <a href="login.php" class="link-login">Sign In</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 🌙 LIGHT/DARK MODE TOGGLE - PERFECTLY SYNCHRONIZED
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const toggleIcon = themeToggle.querySelector('i');
        
        // Sync with localStorage (same as login/reports)
        if (localStorage.getItem('theme') === 'light' || 
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            body.classList.remove('dark');
            body.classList.add('light');
            toggleIcon.classList.remove('fa-sun');
            toggleIcon.classList.add('fa-moon');
        }
        
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('dark');
            body.classList.toggle('light');
            
            if (body.classList.contains('light')) {
                toggleIcon.classList.remove('fa-sun');
                toggleIcon.classList.add('fa-moon');
                localStorage.setItem('theme', 'light');
            } else {
                toggleIcon.classList.remove('fa-moon');
                toggleIcon.classList.add('fa-sun');
                localStorage.setItem('theme', 'dark');
            }
        });

        // 🔓 PASSWORD TOGGLE FUNCTIONALITY
        function togglePasswordVisibility(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            const icon = toggle.querySelector('i');

            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                if (type === 'password') {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    toggle.classList.remove('active');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    toggle.classList.add('active');
                }
            });
        }

        // Password Strength Checker
        const passwordInput = document.getElementById('passwordInput');
        const strengthBar = document.getElementById('passwordStrength');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const length = password.length;
            
            strengthBar.className = 'password-strength';
            strengthBar.style.width = '0%';
            
            if (length === 0) {
                // Empty
            } else if (length < 6) {
                strengthBar.classList.add('weak');
                strengthBar.style.width = '33%';
            } else if (length < 10) {
                strengthBar.classList.add('medium');
                strengthBar.style.width = '66%';
            } else {
                strengthBar.classList.add('strong');
                strengthBar.style.width = '100%';
            }
        });

        // Initialize both password toggles
        togglePasswordVisibility('passwordInput', 'togglePassword');
        togglePasswordVisibility('confirmPasswordInput', 'toggleConfirmPassword');
    </script>
</body>
</html>
