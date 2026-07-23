<?php
require_once 'config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// Current month key
$currentMonth = date('Y-m');

// Handle form submit
if ($_POST) {
    $goal = floatval($_POST['goal_amount']);
    if ($goal > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO goals (user_id, month_year, goal_amount)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE goal_amount = VALUES(goal_amount)
        ");
        $stmt->execute([$user_id, $currentMonth, $goal]);
        $success = "Goal updated successfully!";
    } else {
        $error = "Please enter a valid goal amount.";
    }
}

// Get goal
$stmt = $pdo->prepare("SELECT goal_amount FROM goals WHERE user_id = ? AND month_year = ?");
$stmt->execute([$user_id, $currentMonth]);
$goalRow = $stmt->fetch();
$currentGoal = $goalRow['goal_amount'] ?? 0;

// Get spending
$stmt = $pdo->prepare("
    SELECT SUM(amount) AS spent
    FROM expenses
    WHERE user_id = ?
      AND DATE_FORMAT(expense_date, '%Y-%m') = ?
");
$stmt->execute([$user_id, $currentMonth]);
$spentRow = $stmt->fetch();
$currentSpent = $spentRow['spent'] ?? 0;

// Progress logic
$progress = $currentGoal > 0 ? ($currentSpent / $currentGoal) * 100 : 0;
$progressClamped = min($progress, 100);
$isOverspent = $currentGoal > 0 && $currentSpent > $currentGoal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Set Goal - Daily Expense Tracker</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    :root {
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

        --light-bg: #f8fafc;
        --light-card: #ffffff;
        --light-text: #1e293b;
        --light-border: #e2e8f0;
        --light-shadow: rgba(0,0,0,0.1);
        --light-muted: #64748b;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

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

    .dashboard-link {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 18px;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 700;
        transition: all 0.3s ease;
        backdrop-filter: blur(18px);
    }

    body.dark .dashboard-link {
        background: rgba(255,255,255,0.08);
        color: var(--cyan-main);
        border: 1px solid rgba(102,224,255,0.25);
    }

    body.light .dashboard-link {
        background: rgba(255,255,255,0.9);
        color: var(--light-text);
        border: 1px solid var(--light-border);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .dashboard-link:hover {
        transform: translateY(-2px);
        text-decoration: none;
    }

    .goal-card {
        max-width: 700px;
        margin: auto;
        padding: 2.5rem;
        border-radius: 24px;
        transition: all 0.4s ease;
        box-shadow: 0 20px 40px var(--light-shadow);
    }

    body.dark .goal-card {
        background: var(--card-bg);
        backdrop-filter: blur(25px);
        border: 1px solid var(--glass);
    }

    body.light .goal-card {
        background: var(--light-card);
        border: 1px solid var(--light-border);
        box-shadow: 0 20px 40px var(--light-shadow);
    }

    h1, h2, h3, h4, h5, h6 {
        transition: all 0.4s ease;
    }

    body.dark h1, body.dark h2, body.dark h3, body.dark h4, body.dark h5, body.dark h6 {
        color: var(--cyan-main) !important;
    }

    body.light h1, body.light h2, body.light h3, body.light h4, body.light h5, body.light h6 {
        color: var(--light-text) !important;
    }

    .overspend-card {
        background: linear-gradient(135deg, #3a0f14, #7a1a22);
        color: #ffb3b3;
        border-radius: 18px;
        padding: 1.5rem;
        text-align: center;
        font-weight: 600;
        margin-bottom: 1.5rem;
        animation: pulseRed 1.4s infinite;
    }

    @keyframes pulseRed {
        0% { box-shadow: 0 0 10px rgba(255,0,0,0.4); }
        50% { box-shadow: 0 0 28px rgba(255,0,0,0.8); }
        100% { box-shadow: 0 0 10px rgba(255,0,0,0.4); }
    }

    .progress {
        height: 16px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(255,255,255,0.12);
    }

    body.light .progress {
        background: #e5e7eb;
    }

    .progress-bar {
        font-weight: 700;
    }

    .form-label {
        font-weight: 600;
        margin-bottom: 0.6rem;
    }

    body.dark .form-label {
        color: var(--cyan-muted);
    }

    body.light .form-label {
        color: var(--light-text);
    }

    .form-control {
        border-radius: 16px;
        padding: 14px 16px;
        transition: all 0.3s ease;
    }

    body.dark .form-control {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(102,224,255,0.25);
        color: white;
    }

    body.dark .form-control:focus {
        background: rgba(255,255,255,0.12);
        border-color: var(--cyan-light);
        box-shadow: 0 0 0 0.2rem rgba(0,212,255,0.15);
        color: white;
    }

    body.light .form-control {
        background: #fff;
        border: 1px solid var(--light-border);
        color: var(--light-text);
    }

    body.light .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.15);
        color: var(--light-text);
    }

    body.dark .form-control::placeholder {
        color: rgba(255,255,255,0.5);
    }

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

    .alert {
        border-radius: 16px;
        border: none;
        padding: 1rem 1.25rem;
    }

    body.dark .alert-success {
        background: rgba(39,174,96,0.15);
        color: #7dffb2;
    }

    body.dark .alert-danger {
        background: rgba(248,113,113,0.15);
        color: #ffb3b3;
    }

    body.light .alert-success {
        background: #dcfce7;
        color: #166534;
    }

    body.light .alert-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    hr {
        border-color: rgba(102,224,255,0.15);
        opacity: 1;
        margin: 1.5rem 0;
    }

    body.light hr {
        border-color: var(--light-border);
    }

    p {
        margin-bottom: 0.8rem;
    }

    body.dark p {
        color: var(--cyan-muted);
    }

    body.light p {
        color: var(--light-text);
    }

    .text-success {
        color: var(--success) !important;
    }

    .text-danger {
        color: #f87171 !important;
    }

    @media (max-width: 768px) {
        .goal-card {
            margin: 1rem;
            padding: 1.5rem;
        }

        .theme-toggle {
            top: 15px;
            right: 15px;
            width: 50px;
            height: 50px;
        }

        .dashboard-link {
            top: 15px;
            left: 15px;
            padding: 10px 14px;
            font-size: 0.9rem;
        }
    }
</style>
</head>

<body class="dark">
    <a href="dashboard.php" class="dashboard-link" title="Back to Dashboard">
        <i class="fas fa-house"></i>
        <span>Dashboard</span>
    </a>

    <button class="theme-toggle" id="themeToggle" title="Switch to Light Mode">
        <i class="fas fa-sun"></i>
    </button>

    <div class="container py-5">
        <div class="goal-card">
            <h3 class="mb-4 text-center">
                <i class="fas fa-bullseye me-2"></i>Monthly Goal
            </h3>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <p><strong>Month:</strong> <?= date('F Y') ?></p>

            <?php if ($currentGoal > 0): ?>
                <?php if ($isOverspent): ?>
                    <div class="overspend-card">
                        <i class="fas fa-triangle-exclamation me-2"></i>
                        Budget Overloaded! <br>
                        You exceeded your goal by
                        <strong>₹<?= number_format($currentSpent - $currentGoal, 2) ?></strong>
                    </div>
                <?php endif; ?>

                <p><strong>Goal:</strong> ₹<?= number_format($currentGoal, 2) ?></p>
                <p>
                    <strong>Spent:</strong> ₹<?= number_format($currentSpent, 2) ?>
                    <?php if ($isOverspent): ?>
                        <span class="text-danger ms-2">(Overspent)</span>
                    <?php endif; ?>
                </p>

                <div class="progress mb-3">
                    <div class="progress-bar 
                        <?= $isOverspent ? 'bg-danger' : ($progress < 70 ? 'bg-success' : 'bg-warning') ?>"
                        style="width: <?= $progressClamped ?>%;">
                        <?= round($progress) ?>%
                    </div>
                </div>

                <?php if (!$isOverspent): ?>
                    <p class="text-success">
                        Remaining: ₹<?= number_format($currentGoal - $currentSpent, 2) ?>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p>No goal set for this month.</p>
            <?php endif; ?>

            <hr>

            <form method="POST">
                <label class="form-label">Set Monthly Goal (₹)</label>
                <input type="number"
                       name="goal_amount"
                       step="0.01"
                       min="0.01"
                       class="form-control mb-3"
                       value="<?= $currentGoal ?: '' ?>"
                       placeholder="Enter your monthly spending goal"
                       required>

                <button class="btn btn-primary w-100">
                    <i class="fas fa-save me-2"></i>Save Goal
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const themeToggle = document.getElementById('themeToggle');
        const bodyEl = document.body;
        const toggleIcon = themeToggle.querySelector('i');

        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;

            if (savedTheme === 'light' || (!savedTheme && prefersLight)) {
                bodyEl.classList.remove('dark');
                bodyEl.classList.add('light');
                toggleIcon.classList.remove('fa-sun');
                toggleIcon.classList.add('fa-moon');
            }
        }

        initTheme();

        themeToggle.addEventListener('click', function() {
            bodyEl.classList.toggle('dark');
            bodyEl.classList.toggle('light');

            if (bodyEl.classList.contains('light')) {
                toggleIcon.classList.remove('fa-sun');
                toggleIcon.classList.add('fa-moon');
                localStorage.setItem('theme', 'light');
            } else {
                toggleIcon.classList.remove('fa-moon');
                toggleIcon.classList.add('fa-sun');
                localStorage.setItem('theme', 'dark');
            }
        });

        window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => {
            if (!localStorage.getItem('theme')) {
                initTheme();
            }
        });
    </script>
</body>
</html>