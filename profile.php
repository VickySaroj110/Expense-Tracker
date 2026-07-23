<?php 
require_once 'config.php'; 
if (!isLoggedIn()) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Update profile
if ($_POST) {
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->execute([$_POST['username'], $_POST['email'], $user_id]);
    $_SESSION['username'] = $_POST['username'];
    $success = "Profile updated successfully!";
}

// Get user info
$stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Daily Expense Tracker</title>
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
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body.dark { 
            background: linear-gradient(135deg, var(--dark), var(--darker));
            color: var(--cyan-main);
        }
        
        body.light {
            background: linear-gradient(135deg, #e2e8f0 0%, #f1f5f9 100%);
            color: var(--light-text);
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
            z-index: 1001;
            box-shadow: 0 10px 30px rgba(102,126,234,0.4);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px);
        }
        .theme-toggle:hover {
            transform: scale(1.1) rotate(180deg);
            box-shadow: 0 15px 40px rgba(102,126,234,0.6);
        }

        /* Sidebar */
        .sidebar { 
            height: 100vh; 
            position: fixed; 
            width: 260px; 
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body.dark .sidebar { 
            background: rgba(26,26,46,0.98); 
            backdrop-filter: blur(25px); 
            box-shadow: 4px 0 30px rgba(0,0,0,0.4);
        }
        body.light .sidebar {
            background: var(--light-card);
            box-shadow: 4px 0 30px var(--light-shadow);
            border-right: 1px solid var(--light-border);
        }
        
        .sidebar .logo { 
            font-weight: 700;
            font-size: 1.4rem;
            transition: all 0.4s ease;
        }
        body.dark .sidebar .logo { 
            background: var(--primary); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        body.light .sidebar .logo {
            color: var(--light-text);
        }
        
        .nav-link { 
            padding: 14px 24px; 
            border-radius: 16px; 
            margin-bottom: 10px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            border: 1px solid transparent;
            text-decoration: none;
            display: block;
        }
        body.dark .nav-link { color: var(--cyan-muted); }
        body.light .nav-link { color: var(--light-muted); }
        body.dark .nav-link:hover, body.dark .nav-link.active { 
            background: rgba(102,224,255,0.2); 
            transform: translateX(12px);
            color: var(--cyan-main);
            border-color: var(--cyan-light);
        }
        body.light .nav-link:hover, body.light .nav-link.active {
            background: rgba(59,130,246,0.1);
            transform: translateX(12px);
            color: #3b82f6;
            border-color: rgba(59,130,246,0.2);
        }
        
        .main-content { 
            margin-left: 260px; 
            padding: 2.5rem; 
            min-height: 100vh;
            transition: all 0.4s ease;
        }
        
        h1, h2, h3, h5 { 
            transition: all 0.4s ease;
        }
        body.dark h1, body.dark h2, body.dark h3, body.dark h5 { color: var(--cyan-main) !important; }
        body.light h1, body.light h2, body.light h3, body.light h5 { color: var(--light-text) !important; }
        
        /* Page Header */
        .page-header {
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px var(--light-shadow);
            text-align: center;
            transition: all 0.4s ease;
        }
        body.dark .page-header {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass);
        }
        body.light .page-header {
            background: var(--light-card);
            border: 1px solid var(--light-border);
        }
        
        /* Profile Card */
        .profile-card {
            border-radius: 24px;
            padding: 3rem;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 25px 50px var(--light-shadow);
            transition: all 0.4s ease;
        }
        body.dark .profile-card {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass);
        }
        body.light .profile-card {
            background: var(--light-card);
            border: 1px solid var(--light-border);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            box-shadow: 0 15px 35px rgba(0,212,255,0.4);
            animation: pulse 2s infinite;
            transition: all 0.4s ease;
        }
        body.dark .profile-avatar {
            background: linear-gradient(135deg, var(--cyan-main), var(--cyan-light));
        }
        body.light .profile-avatar {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .form-label { 
            font-weight: 600 !important;
            margin-bottom: 0.75rem;
            font-size: 1.05rem;
            transition: all 0.4s ease;
        }
        body.dark .form-label { color: var(--cyan-light) !important; }
        body.light .form-label { color: #475569 !important; }
        
        .form-control {
            border-radius: 16px !important;
            padding: 16px 20px !important;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        body.dark .form-control {
            background: rgba(102,224,255,0.1) !important;
            border: 2px solid rgba(102,224,255,0.3) !important;
            color: var(--cyan-main) !important;
        }
        body.dark .form-control::placeholder { color: var(--cyan-muted) !important; }
        body.dark .form-control:focus {
            background: rgba(102,224,255,0.2) !important;
            border-color: var(--cyan-main) !important;
            box-shadow: 0 0 0 0.3rem rgba(0,212,255,0.3) !important;
            color: var(--cyan-main) !important;
            transform: translateY(-2px);
        }
        body.dark .form-control[readonly] {
            background: rgba(102,224,255,0.05) !important;
            color: var(--cyan-muted) !important;
            cursor: not-allowed;
        }
        
        body.light .form-control {
            background: #f8fafc !important;
            border: 2px solid #e2e8f0 !important;
            color: #1e293b !important;
        }
        body.light .form-control::placeholder { color: #94a3b8 !important; }
        body.light .form-control:focus {
            background: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.3rem rgba(59,130,246,0.15) !important;
            color: #1e293b !important;
            transform: translateY(-2px);
        }
        body.light .form-control[readonly] {
            background: #f1f5f9 !important;
            color: #64748b !important;
            cursor: not-allowed;
        }
        
        .btn-primary {
            border: none !important;
            border-radius: 16px !important;
            padding: 16px 40px !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        body.dark .btn-primary {
            background: var(--primary) !important;
            color: white !important;
            box-shadow: 0 10px 30px rgba(102,126,234,0.4);
        }
        body.dark .btn-primary:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 20px 40px rgba(102,126,234,0.6) !important;
        }
        
        body.light .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%) !important;
            color: white !important;
            box-shadow: 0 10px 30px rgba(59,130,246,0.3);
        }
        body.light .btn-primary:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 20px 40px rgba(59,130,246,0.5) !important;
        }
        
        /* Alerts */
        .alert {
            border-radius: 20px !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid !important;
            margin-bottom: 2rem;
            transition: all 0.4s ease;
        }
        body.dark .alert-success {
            background: rgba(39,174,96,0.25) !important;
            border-color: #27ae60 !important;
            color: var(--cyan-main) !important;
        }
        body.light .alert-success {
            background: #f0fdf4 !important;
            border-color: #10b981 !important;
            color: #166534 !important;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-item {
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        body.dark .stat-item {
            background: rgba(102,224,255,0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(102,224,255,0.3);
        }
        body.light .stat-item {
            background: var(--light-card);
            border: 1px solid var(--light-border);
            box-shadow: 0 10px 30px var(--light-shadow);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            transition: all 0.4s ease;
        }
        body.dark .stat-number { 
            background: linear-gradient(135deg, var(--cyan-main), var(--cyan-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        body.light .stat-number { color: var(--light-text); }

        /* Email stat: light + dark dono mein overflow mat karo */
        .stat-item .stat-number {
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            word-break: break-all;
        } /* long email ke liye recommended pattern hai. [web:25][web:36] */
        
        .user-stats {
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.4s ease;
        }
        body.dark .user-stats {
            background: rgba(102,224,255,0.08);
            border: 1px solid rgba(102,224,255,0.2);
        }
        body.light .user-stats {
            background: #f8fafc;
            border: 1px solid var(--light-border);
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1.5rem; }
            .sidebar { transform: translateX(-100%); }
            .theme-toggle { top: 15px; right: 15px; width: 50px; height: 50px; }
        }
    </style>
</head>
<body class="dark">
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" title="Switch to Light Mode">
        <i class="fas fa-sun"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar p-4">
        <div class="logo mb-5 text-center py-4">
            <h3>Daily Expense Tracker</h3>
        </div>
        <nav class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Dashboard</a>
            <a href="add_expense.php" class="nav-link"><i class="fas fa-plus me-2"></i>Add Expense</a>
            <a href="manage_expenses.php" class="nav-link"><i class="fas fa-list me-2"></i>Manage Expenses</a>
            <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar me-2"></i>Reports</a>
            <a href="profile.php" class="nav-link active"><i class="fas fa-user me-2"></i>Profile</a>
            <a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="page-header">
            <h1><i class="fas fa-user-circle me-3"></i>Profile Settings</h1>
            <p class="chart-text" style="font-size: 1.1rem;">Manage your account information</p>
        </div>

        <!-- User Stats -->
        <div class="user-stats">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= htmlspecialchars($_SESSION['username']) ?></div>
                    <div class="chart-text">Username</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= htmlspecialchars($user['email']) ?></div>
                    <div class="chart-text">Email</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                    <div class="chart-text">Member Since</div>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="profile-card">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-user me-2"></i>Username
                    </label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
                </div>
                
                <div class="mb-5">
                    <label class="form-label">
                        <i class="fas fa-calendar-check me-2"></i>Member Since
                    </label>
                    <input type="text" value="<?= date('M d, Y', strtotime($user['created_at'])) ?>" class="form-control" readonly>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Profile
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // LIGHT/DARK MODE TOGGLE - FULLY SYNCHRONIZED
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const toggleIcon = themeToggle.querySelector('i');
        
        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
            
            if (savedTheme === 'light' || (!savedTheme && prefersLight)) {
                body.classList.remove('dark');
                body.classList.add('light');
                toggleIcon.classList.remove('fa-sun');
                toggleIcon.classList.add('fa-moon');
            }
        }
        
        initTheme();
        
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

        window.matchMedia('(prefers-color-scheme: light)').addListener(() => {
            if (!localStorage.getItem('theme')) {
                initTheme();
            }
        });
    </script>
</body>
</html>
