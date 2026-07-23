<?php 
require_once 'config.php'; 
if (!isLoggedIn()) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Total expenses count
$stmt = $pdo->prepare("SELECT COUNT(*) as total_expenses, SUM(amount) as total_spent FROM expenses WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Monthly report (last 6 months)
$stmt = $pdo->prepare("SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total FROM expenses WHERE user_id = ? GROUP BY month ORDER BY month DESC LIMIT 6");
$stmt->execute([$user_id]);
$monthly = $stmt->fetchAll();

// Category wise (only expenses)
$stmt = $pdo->prepare("SELECT c.name, c.color, SUM(e.amount) as total FROM expenses e JOIN categories c ON e.category_id = c.id WHERE e.user_id = ? AND c.type='expense' GROUP BY c.id ORDER BY total DESC");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();

// Recent expenses summary
$stmt = $pdo->prepare("SELECT DATE(expense_date) as date, SUM(amount) as daily_total FROM expenses WHERE user_id = ? GROUP BY date ORDER BY date DESC LIMIT 7");
$stmt->execute([$user_id]);
$daily = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Daily Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Page Header */
        .page-header {
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 20px 40px var(--light-shadow);
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
        
        h1, h2, h3, h5, h6 { 
            transition: all 0.4s ease;
        }
        body.dark h1, body.dark h2, body.dark h3, body.dark h5, body.dark h6 { color: var(--cyan-main) !important; }
        body.light h1, body.light h2, body.light h3, body.light h5, body.light h6 { color: var(--light-text) !important; }
        
        /* Chart Container */
        .chart-container {
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            transition: all 0.4s ease;
        }
        body.dark .chart-container {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        body.light .chart-container {
            background: var(--light-card);
            border: 1px solid var(--light-border);
            box-shadow: 0 20px 40px var(--light-shadow);
        }
        
        .chart-wrapper {
            position: relative;
            height: 320px;
        }
        
        /* Stat Cards */
        .stat-card {
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        body.dark .stat-card {
            background: rgba(102,224,255,0.12);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(102,224,255,0.3);
        }
        body.light .stat-card {
            background: var(--light-card);
            border: 1px solid var(--light-border);
            box-shadow: 0 10px 30px var(--light-shadow);
        }
        body.dark .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,212,255,0.3);
        }
        body.light .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(59,130,246,0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            transition: all 0.4s ease;
        }
        body.dark .stat-number { color: var(--cyan-main); }
        body.light .stat-number { color: var(--light-text); }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        /* Category Items – GRID */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* responsive wrap */  /* [web:25][web:35] */
            gap: 1.5rem;
        }
        
        .category-item {
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(102,224,255,0.2);
            transition: all 0.3s ease;
        }
        body.dark .category-item {
            background: rgba(102,224,255,0.1);
        }
        body.light .category-item {
            background: var(--light-card);
            border-color: var(--light-border);
        }
        
        .category-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .chart-text { font-size: 0.95rem; }
        body.dark .chart-text { color: var(--cyan-muted) !important; }
        body.light .chart-text { color: var(--light-muted) !important; }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1.5rem; }
            .sidebar { transform: translateX(-100%); }
            .chart-container { padding: 1.5rem; }
            .chart-wrapper { height: 260px; }
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
            <a href="reports.php" class="nav-link active"><i class="fas fa-chart-bar me-2"></i>Reports</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user me-2"></i>Profile</a>
            <a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar me-3"></i>Reports & Analytics</h1>
            <p class="chart-text">Visualize your spending patterns</p>
        </div>

        <!-- Stats Cards -->
        <div class="summary-grid">
           <div class="stat-card">
    <i class="fas fa-receipt display-4" style="color: var(--cyan-light);"></i>
    <div class="stat-number"><?= number_format($stats['total_expenses']) ?></div>
    <div class="chart-text">Total Expenses</div>
</div>

<div class="stat-card">
    <i class="fas fa-rupee-sign display-4" style="color: #f39c12;"></i>
    <div class="stat-number">₹<?= number_format($stats['total_spent'], 2) ?></div>
    <div class="chart-text">Total Spent</div>
</div>
            <?php if (!empty($daily)): ?>
            <div class="stat-card">
                <i class="fas fa-calendar-day display-4" style="color: var(--accent);"></i>
                <div class="stat-number">₹<?= number_format(end($daily)['daily_total'], 2) ?></div>
                <div class="chart-text">Today's Spending</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-calendar me-2"></i>Monthly Spending Trend</h5>
                        <small class="chart-text">Last 6 months</small>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="chart-container">
                    <div class="mb-4">
                        <h5><i class="fas fa-tags me-2"></i>Category Breakdown</h5>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Summary -->
        <?php if (!empty($categories)): ?>
        <div class="chart-container">
            <h5 class="mb-4"><i class="fas fa-list me-2"></i>Category Summary</h5>
            <div class="category-grid">
                <?php foreach ($categories as $cat): ?>
                <div class="category-item">
                    <div style="color: <?= $cat['color'] ?>; font-size: 1.3rem; margin-bottom: 0.5rem;">
                        <span class="category-color" style="background: <?= $cat['color'] ?>;"></span>
                        <?= htmlspecialchars($cat['name']) ?>
                    </div>
                    <div class="stat-number">₹<?= number_format($cat['total'], 2) ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar"
                             style="width: <?= min(($cat['total'] / ($stats['total_spent'] ?: 1)) * 100, 100) ?>%; background: <?= $cat['color'] ?>;"
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // LIGHT/DARK MODE TOGGLE - SYNCHRONIZED
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
            updateCharts();
        });

        // Charts
        let monthlyChart, categoryChart;
        
        function createCharts() {
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            monthlyChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($monthly, 'month')) ?>,
                    datasets: [{
                        label: 'Spending',
                        data: <?= json_encode(array_column($monthly, 'total')) ?>,
                        borderColor: body.classList.contains('dark') ? 'rgba(102,126,234,1)' : 'rgba(59,130,246,1)',
                        backgroundColor: body.classList.contains('dark') ? 'rgba(102,126,234,0.15)' : 'rgba(59,130,246,0.1)',
                        borderWidth: 4,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: body.classList.contains('dark') ? 'rgba(102,126,234,1)' : 'rgba(59,130,246,1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: body.classList.contains('dark') ? 'rgba(102,224,255,0.1)' : 'rgba(0,0,0,0.05)' },
                            ticks: { color: body.classList.contains('dark') ? 'var(--cyan-muted)' : 'var(--light-muted)' }
                        },
                        x: {
                            grid: { color: body.classList.contains('dark') ? 'rgba(102,224,255,0.1)' : 'rgba(0,0,0,0.05)' },
                            ticks: { color: body.classList.contains('dark') ? 'var(--cyan-muted)' : 'var(--light-muted)' }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });

            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($categories, 'name')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($categories, 'total')) ?>,
                        backgroundColor: <?= json_encode(array_column($categories, 'color')) ?>,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: body.classList.contains('dark') ? 'var(--cyan-light)' : 'var(--light-text)',
                                font: { size: 13, weight: '500' },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    }
                }
            });
        }
        
        function updateCharts() {
            if (monthlyChart) {
                monthlyChart.data.datasets[0].borderColor = body.classList.contains('dark') ? 'rgba(102,126,234,1)' : 'rgba(59,130,246,1)';
                monthlyChart.data.datasets[0].backgroundColor = body.classList.contains('dark') ? 'rgba(102,126,234,0.15)' : 'rgba(59,130,246,0.1)';
                monthlyChart.data.datasets[0].pointBackgroundColor = body.classList.contains('dark') ? 'rgba(102,126,234,1)' : 'rgba(59,130,246,1)';
                monthlyChart.options.scales.y.grid.color = body.classList.contains('dark') ? 'rgba(102,224,255,0.1)' : 'rgba(0,0,0,0.05)';
                monthlyChart.options.scales.x.grid.color = body.classList.contains('dark') ? 'rgba(102,224,255,0.1)' : 'rgba(0,0,0,0.05)';
                monthlyChart.options.scales.y.ticks.color = body.classList.contains('dark') ? 'var(--cyan-muted)' : 'var(--light-muted)';
                monthlyChart.options.scales.x.ticks.color = body.classList.contains('dark') ? 'var(--cyan-muted)' : 'var(--light-muted)';
                monthlyChart.update('none');
            }
            if (categoryChart) {
                categoryChart.options.plugins.legend.labels.color = body.classList.contains('dark') ? 'var(--cyan-light)' : 'var(--light-text)';
                categoryChart.update('none');
            }
        }
        
        createCharts();

        window.matchMedia('(prefers-color-scheme: light)').addListener(() => {
            if (!localStorage.getItem('theme')) {
                initTheme();
                updateCharts();
            }
        });
    </script>
</body>
</html>
