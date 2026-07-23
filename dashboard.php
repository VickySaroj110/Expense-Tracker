<?php 
require_once 'config.php'; 
if (!isLoggedIn()) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(amount) as total_amount FROM expenses WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Monthly goal vs spent (for overspent status)
$currentMonth = date('Y-m');

// Goal table se current month goal
$stmt = $pdo->prepare("SELECT goal_amount FROM goals WHERE user_id = ? AND month_year = ?");
$stmt->execute([$user_id, $currentMonth]);
$goalRow = $stmt->fetch();
$currentGoal = $goalRow['goal_amount'] ?? 0;

// Is month ka spent
$stmt = $pdo->prepare("
    SELECT SUM(amount) AS month_spent
    FROM expenses
    WHERE user_id = ?
      AND DATE_FORMAT(expense_date, '%Y-%m') = ?
");
$stmt->execute([$user_id, $currentMonth]);
$monthRow = $stmt->fetch();
$currentMonthSpent = $monthRow['month_spent'] ?? 0;

$isOverspent = $currentGoal > 0 && $currentMonthSpent > $currentGoal;

// Today's spending
$stmt = $pdo->prepare("SELECT SUM(amount) as today_amount FROM expenses WHERE user_id = ? AND DATE(expense_date) = CURDATE()");
$stmt->execute([$user_id]);
$today = $stmt->fetch();

// Recent expenses
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name, c.color FROM expenses e JOIN categories c ON e.category_id = c.id WHERE e.user_id = ? ORDER BY e.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent = $stmt->fetchAll();

// Categories for chart
$stmt = $pdo->prepare("SELECT c.name, c.color, SUM(e.amount) as total FROM expenses e JOIN categories c ON e.category_id = c.id WHERE e.user_id = ? AND c.type='expense' GROUP BY c.id");
$stmt->execute([$user_id]);
$chart_data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Daily Expense Tracker</title>
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
        
        h1, h2, h3, h5, h6 { 
            transition: all 0.4s ease;
        }
        body.dark h1, body.dark h2, body.dark h3, body.dark h5, body.dark h6 { color: var(--cyan-main) !important; }
        body.light h1, body.light h2, body.light h3, body.light h5, body.light h6 { color: var(--light-text) !important; }
        
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            border-radius: 24px;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        body.dark .stat-card {
            background: rgba(102,224,255,0.12);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(102,224,255,0.3);
        }
        body.light .stat-card {
            background: var(--light-card);
            border: 1px solid var(--light-border);
            box-shadow: 0 15px 35px var(--light-shadow);
        }
        
        body.dark .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--primary);
            border-radius: 24px 24px 0 0;
        }
        body.light .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 24px 24px 0 0;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
        }
        body.dark .stat-card:hover { box-shadow: 0 30px 60px rgba(0,212,255,0.3); }
        body.light .stat-card:hover { box-shadow: 0 30px 60px rgba(59,130,246,0.25); }
        
        .stat-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            transition: all 0.4s ease;
        }
        body.dark .stat-icon {
            background: rgba(102,224,255,0.2);
            border: 2px solid rgba(102,224,255,0.3);
        }
        body.light .stat-icon {
            background: rgba(59,130,246,0.1);
            border: 2px solid rgba(59,130,246,0.2);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            transition: all 0.4s ease;
        }
        body.dark .stat-number {
            background: linear-gradient(135deg, var(--cyan-main), var(--cyan-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        body.light .stat-number { color: var(--light-text); }
        
        .stat-label {
            font-size: 1.1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s ease;
        }
        body.dark .stat-label { color: var(--cyan-muted); }
        body.light .stat-label { color: var(--light-muted); }
        
        /* Chart Section */
        .chart-section {
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 20px 40px var(--light-shadow);
            transition: all 0.4s ease;
        }
        body.dark .chart-section {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass);
        }
        body.light .chart-section {
            background: var(--light-card);
            border: 1px solid var(--light-border);
        }
        
        .chart-container {
            height: 420px;
            position: relative;
            margin-bottom: 2rem;
        }
        
        .recent-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .recent-item {
            padding: 1.5rem 0;
            border-bottom: 1px solid rgba(102,224,255,0.2);
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        body.dark .recent-item { border-bottom-color: rgba(102,224,255,0.2); }
        body.light .recent-item { border-bottom-color: var(--light-border); }
        
        body.dark .recent-item:hover {
            background: rgba(102,224,255,0.1);
            padding-left: 1rem;
            border-radius: 12px;
        }
        body.light .recent-item:hover {
            background: rgba(59,130,246,0.05);
            padding-left: 1rem;
            border-radius: 12px;
        }
        
        .category-badge {
            font-size: 0.85rem;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 20px;
        }
        
        body.dark .recent-desc { color: var(--cyan-muted); }
        body.light .recent-desc { color: var(--light-muted); }
        body.dark .recent-amount { color: var(--cyan-main); }
        body.light .recent-amount { color: var(--light-text); }
        
        .btn-primary {
            border: none !important;
            border-radius: 16px !important;
            padding: 16px 32px !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        body.dark .btn-primary {
            background: var(--primary) !important;
            color: white !important;
            box-shadow: 0 15px 35px rgba(102,126,234,0.4);
        }
        body.dark .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 25px 50px rgba(102,126,234,0.6);
        }
        
        body.light .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%) !important;
            color: white !important;
            box-shadow: 0 15px 35px rgba(59,130,246,0.3);
        }
        body.light .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 25px 50px rgba(59,130,246,0.5);
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1.5rem; }
            .sidebar { transform: translateX(-100%); }
            .stats-grid { grid-template-columns: 1fr; }
            .chart-container { height: 350px; }
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
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-chart-line me-2"></i>Dashboard</a>
            <a href="add_expense.php" class="nav-link"><i class="fas fa-plus me-2"></i>Add Expense</a>
            <a href="set_goal.php" class="nav-link"><i class="fas fa-bullseye me-2"></i>Set Goal</a>
            <a href="manage_expenses.php" class="nav-link"><i class="fas fa-list me-2"></i>Manage Expenses</a>
            <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar me-2"></i>Reports</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user me-2"></i>Profile</a>
            <a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1><i class="fas fa-home me-3"></i>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
                    <p id="welcomeText" style="font-size: 1.2rem;">Here's what's happening with your expenses</p>
                </div>
                <a href="add_expense.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Expense
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Total Expenses -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-receipt" id="totalIcon"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="stat-label">Total Expenses</div>
            </div>
            
            <!-- Total Spent (Overspent aware) -->
            <div class="stat-card">
                <div class="stat-icon" style="<?= $isOverspent ? 'background: rgba(248,113,113,0.15); border-color:#f97373;' : '' ?>">
                    <i class="fas fa-rupee-sign" id="spentIcon" style="<?= $isOverspent ? 'color:#f97373;' : '' ?>"></i>
                </div>

                <div class="stat-number" style="<?= $isOverspent ? 'color:#f97373; -webkit-text-fill-color:#f97373;' : '' ?>">
                    ₹<?= number_format($stats['total_amount'] ?? 0, 2) ?>
                </div>

                <div class="stat-label">
                    <?php if ($isOverspent && $currentGoal > 0): ?>
                        Overspent (Goal: ₹<?= number_format($currentGoal, 2) ?>)
                    <?php else: ?>
                        Total Spent
                    <?php endif; ?>
                </div>

                <?php if ($currentGoal > 0): ?>
                    <?php $progress = $currentGoal > 0 ? min(($currentMonthSpent / $currentGoal) * 100, 200) : 0; ?>
                    <div class="mt-3">
                        <small>
                            This month: ₹<?= number_format($currentMonthSpent, 2) ?> /
                            Goal: ₹<?= number_format($currentGoal, 2) ?>
                        </small>
                        <div class="progress mt-2" style="height:8px;border-radius:999px;">
                            <div class="progress-bar <?= $isOverspent ? 'bg-danger' : 'bg-success' ?>"
                                 role="progressbar"
                                 style="width: <?= min($progress, 100) ?>%;">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Today's Spending -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day" id="todayIcon"></i>
                </div>
                <div class="stat-number">₹<?= number_format($today['today_amount'] ?? 0, 2) ?></div>
                <div class="stat-label">Today's Spending</div>
            </div>
        </div>

        <!-- Charts & Recent Section -->
        <div class="chart-section">
            <div class="row">
                <!-- Main Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="chart-container">
                        <div class="mb-3">
                            <h5 id="chartTitle" style="margin: 0;"><i class="fas fa-chart-pie me-2"></i>Expense Breakdown</h5>
                            <small id="chartSubtitle">Your spending by category</small>
                        </div>
                        <canvas id="expenseChart"></canvas>
                    </div>
                </div>
                
                <!-- Recent Expenses -->
                <div class="col-lg-4">
                    <div class="stat-card p-4 h-100 d-flex flex-column">
                        <h5 id="recentTitle"><i class="fas fa-clock me-2"></i>Recent Activity</h5>
                        <div class="recent-list flex-grow-1">
                            <?php if (empty($recent)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox display-4 mb-3" id="emptyIcon"></i>
                                <p id="emptyText">No expenses yet</p>
                                <a href="add_expense.php" class="btn btn-outline-primary" id="addFirstBtn">
                                    Add first expense
                                </a>
                            </div>
                            <?php else: ?>
                            <?php foreach (array_slice($recent, 0, 5) as $exp): ?>
                            <div class="recent-item">
                                <div>
                                    <div class="category-badge mb-1" style="background: <?= $exp['color'] ?>; color: white;">
                                        <?= htmlspecialchars($exp['category_name']) ?>
                                    </div>
                                    <small class="recent-desc"><?= htmlspecialchars($exp['description'] ?: 'No description') ?></small>
                                </div>
                                <div style="text-align: right;">
                                    <div class="recent-amount" style="font-size: 1.3rem; font-weight: 700;">
                                        ₹<?= number_format($exp['amount'], 2) ?>
                                    </div>
                                    <small class="recent-desc"><?= date('d M', strtotime($exp['expense_date'])) ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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
            
            if (window.dashboardChart) {
                updateDashboardChart();
            }
        });

        // THEME-AWARE CHART
        let dashboardChart;
        
        function createDashboardChart() {
            const ctx = document.getElementById('expenseChart').getContext('2d');
            dashboardChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($chart_data, 'name')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($chart_data, 'total')) ?>,
                        backgroundColor: <?= json_encode(array_column($chart_data, 'color')) ?>,
                        borderWidth: 0,
                        hoverBorderWidth: 4,
                        cutout: '65%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: body.classList.contains('dark') ? 'var(--cyan-light)' : 'var(--light-text)',
                                font: { size: 14, weight: '600' },
                                padding: 25,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            backgroundColor: body.classList.contains('dark') ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                            titleColor: body.classList.contains('dark') ? 'var(--cyan-main)' : 'var(--light-text)',
                            bodyColor: body.classList.contains('dark') ? 'var(--cyan-light)' : 'var(--light-muted)',
                            borderColor: body.classList.contains('dark') ? 'var(--cyan-light)' : 'var(--light-border)',
                            borderWidth: 1,
                            cornerRadius: 12,
                            displayColors: true
                        }
                    }
                }
            });
        }
        
        function updateDashboardChart() {
            if (dashboardChart) {
                dashboardChart.options.plugins.legend.labels.color = 
                    body.classList.contains('dark') ? 'var(--cyan-light)' : 'var(--light-text)';
                dashboardChart.options.plugins.tooltip.backgroundColor = 
                    body.classList.contains('dark') ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)';
                dashboardChart.options.plugins.tooltip.titleColor = 
                    body.classList.contains('dark') ? 'var(--cyan-main)' : 'var(--light-text)';
                dashboardChart.options.plugins.tooltip.bodyColor = 
                    body.classList.contains('dark') ? 'var(--cyan-light)' : 'var(--light-muted)';
                dashboardChart.options.plugins.tooltip.borderColor = 
                    body.classList.contains('dark') ? 'var(--cyan-light)' : 'var(--light-border)';
                dashboardChart.update('none');
            }
        }
        
        createDashboardChart();

        window.matchMedia('(prefers-color-scheme: light)').addListener(() => {
            if (!localStorage.getItem('theme')) {
                initTheme();
            }
        });
    </script>
</body>
</html>