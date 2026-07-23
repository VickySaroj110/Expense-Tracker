<?php 
require_once 'config.php'; 
if (isLoggedIn()) header('Location: dashboard.php');
$error = '';
if ($_POST) {
    // 🔒 PASSWORD VALIDATION - MINIMUM 6 CHARACTERS
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$_POST['username'], $_POST['username']]);
        $user = $stmt->fetch();
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php'); exit;
        }
        $error = "Invalid credentials!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Daily Expense Tracker</title>
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
        body.light .subtitle { color: #64748b; }
        
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
            margin-bottom: 1.75rem;
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
        
        body.dark .form-control.invalid {
            border-color: #e74c3c !important;
            background: rgba(231,76,60,0.15) !important;
            box-shadow: 0 0 0 0.3rem rgba(231,76,60,0.2) !important;
        }
        body.light .form-control.invalid {
            border-color: #ef4444 !important;
            background: #fef2f2 !important;
            box-shadow: 0 0 0 0.3rem rgba(239,68,68,0.15) !important;
        }
        
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
        
        .btn-login {
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
        
        body.dark .btn-login {
            background: var(--primary) !important;
            color: white !important;
            box-shadow: 0 15px 35px rgba(102,126,234,0.4);
        }
        body.dark .btn-login:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 25px 50px rgba(102,126,234,0.6) !important;
        }
        
        body.light .btn-login {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%) !important;
            color: white !important;
            box-shadow: 0 15px 35px rgba(59,130,246,0.3);
        }
        body.light .btn-login:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 25px 50px rgba(59,130,246,0.5) !important;
        }
        
        body.dark .btn-login:active { transform: translateY(-2px) !important; }
        body.light .btn-login:active { transform: translateY(-2px) !important; }
        
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
        
        .link-register {
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            text-decoration: none;
        }
        body.dark .link-register { color: var(--cyan-light) !important; }
        body.dark .link-register:hover { color: var(--cyan-main) !important; }
        body.light .link-register { color: #3b82f6 !important; }
        body.light .link-register:hover { color: #1d4ed8 !important; }
        
        .password-info {
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        body.dark .password-info { color: var(--cyan-muted); }
        body.light .password-info { color: #64748b; }
        
        .password-strength {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
        }
        
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
            <div class="subtitle">Sign in to track your expenses</div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger error p-3">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm" novalidate>
            <div class="form-group mb-4">
                <label class="form-label">
                    <i class="fas fa-user me-2"></i>Username or Email
                </label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
            </div>
            
            <!-- 🔓 PASSWORD WITH TOGGLE BUTTON & VALIDATION -->
            <div class="form-group mb-4">
                <label class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <div style="position: relative;">
                    <input type="password" 
                           name="password" 
                           id="passwordInput" 
                           class="form-control" 
                           placeholder="Enter your password (min 6 chars)" 
                           required 
                           minlength="6"
                           pattern=".{6,}">
                    <button type="button" class="password-toggle" id="togglePassword" title="Show password">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
                <div class="password-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Minimum 6 characters required</span>
                    <span id="passwordLength" class="password-strength">0/6</span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login text-white shadow-lg">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>
        
        <div class="text-center mt-5 pt-4">
            <p class="mb-0" style="color: var(--cyan-muted);">Don't have an account? 
                <a href="register.php" class="link-register">Create Account</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 🌙 LIGHT/DARK MODE TOGGLE
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const toggleIcon = themeToggle.querySelector('i');
        
        // Check for saved theme preference or default to dark
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
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('passwordInput');
        const toggleIconPw = togglePassword.querySelector('i');
        const passwordLength = document.getElementById('passwordLength');
        const loginForm = document.getElementById('loginForm');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            if (type === 'password') {
                toggleIconPw.classList.remove('fa-eye');
                toggleIconPw.classList.add('fa-eye-slash');
                togglePassword.classList.remove('active');
            } else {
                toggleIconPw.classList.remove('fa-eye-slash');
                toggleIconPw.classList.add('fa-eye');
                togglePassword.classList.add('active');
            }
        });

        // 🔒 REAL-TIME PASSWORD VALIDATION
        passwordInput.addEventListener('input', function() {
            const length = this.value.length;
            const minLength = 6;
            
            passwordLength.textContent = `${length}/${minLength}`;
            
            if (length >= minLength) {
                this.classList.remove('invalid');
                passwordLength.style.background = 'rgba(39,174,96,0.2)';
                passwordLength.style.color = '#27ae60';
            } else {
                this.classList.add('invalid');
                passwordLength.style.background = 'rgba(231,76,60,0.2)';
                passwordLength.style.color = '#e74c3c';
            }
        });

        // 🔒 FORM SUBMISSION VALIDATION
        loginForm.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            if (password.length < 6) {
                e.preventDefault();
                passwordInput.classList.add('invalid');
                passwordInput.focus();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });

        // 🔒 INITIAL PASSWORD FIELD FOCUS
        passwordInput.addEventListener('blur', function() {
            if (this.value.length < 6 && this.value.length > 0) {
                this.classList.add('invalid');
            }
        });
    </script>
</body>
</html>
