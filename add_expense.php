<?php 
require_once 'config.php'; 

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// calculate current month goal/spent for confirmation logic
$currentMonth = date('Y-m');

$stmt = $pdo->prepare("SELECT goal_amount FROM goals WHERE user_id = ? AND month_year = ?");
$stmt->execute([$user_id, $currentMonth]);
$goalRow = $stmt->fetch();
$currentGoal = $goalRow['goal_amount'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(amount) AS spent FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $currentMonth]);
$spentRow = $stmt->fetch();
$currentSpent = $spentRow['spent'] ?? 0;

$warning = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newAmount = floatval($_POST['amount']);
    $confirm = isset($_POST['confirm_overspend']) && $_POST['confirm_overspend'] == '1';

    if ($currentGoal > 0 && ($currentSpent + $newAmount) > $currentGoal && !$confirm) {
        $warning = "Adding this expense will exceed your monthly goal. Please confirm.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category_id, amount, description, expense_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $_POST['category_id'],
            $_POST['amount'],
            $_POST['description'],
            $_POST['expense_date']
        ]);
        header('Location: dashboard.php?success=1');
        exit;
    }
}

// Categories - Add "Others" category if not exists
$stmt = $pdo->query("SELECT * FROM categories WHERE type='expense'");
$categories = $stmt->fetchAll();

// Check if "Others" category exists, if not add it
$hasOthers = false;
foreach ($categories as $cat) {
    if (strtolower(trim($cat['name'])) === 'others') {
        $hasOthers = true;
        break;
    }
}

if (!$hasOthers) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, type, color) VALUES ('Others', 'expense', '#95a5a6')");
    $stmt->execute();

    $stmt = $pdo->query("SELECT * FROM categories WHERE type='expense'");
    $categories = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Daily Expense Tracker</title>
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

        h1, h2, h3, h5 { transition: all 0.4s ease; }
        body.dark h1, body.dark h2, body.dark h3, body.dark h5 { color: var(--cyan-main) !important; }
        body.light h1, body.light h2, body.light h3, body.light h5 { color: var(--light-text) !important; }

        .form-container {
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 25px 50px var(--light-shadow);
            margin-bottom: 2rem;
            transition: all 0.4s ease;
        }

        body.dark .form-container {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass);
        }

        body.light .form-container {
            background: var(--light-card);
            border: 1px solid var(--light-border);
        }

        .form-label {
            font-weight: 600 !important;
            margin-bottom: 0.75rem;
            font-size: 1.05rem;
            transition: all 0.4s ease;
        }

        body.dark .form-label { color: var(--cyan-light) !important; }
        body.light .form-label { color: #475569 !important; }

        .form-control, .form-select {
            border-radius: 18px !important;
            padding: 16px 20px !important;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(15px);
        }

        body.dark .form-control, body.dark .form-select {
            background: rgba(102,224,255,0.12) !important;
            border: 2px solid rgba(102,224,255,0.3) !important;
            color: var(--cyan-main) !important;
        }

        body.dark .form-control::placeholder { color: var(--cyan-muted) !important; }

        body.dark .form-control:focus, body.dark .form-select:focus {
            background: rgba(102,224,255,0.25) !important;
            border-color: var(--cyan-main) !important;
            box-shadow: 0 0 0 0.3rem rgba(0,212,255,0.25), 0 15px 35px rgba(0,212,255,0.3) !important;
            color: var(--cyan-main) !important;
            transform: translateY(-2px);
        }

        body.light .form-control, body.light .form-select {
            background: #f8fafc !important;
            border: 2px solid #e2e8f0 !important;
            color: #1e293b !important;
        }

        body.light .form-control::placeholder { color: #94a3b8 !important; }

        body.light .form-control:focus, body.light .form-select:focus {
            background: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.3rem rgba(59,130,246,0.15), 0 15px 35px rgba(59,130,246,0.2) !important;
            color: #1e293b !important;
            transform: translateY(-2px);
        }

        .preview-card {
            border-radius: 24px;
            padding: 2.5rem 2rem;
            height: 100%;
            box-shadow: 0 20px 40px var(--light-shadow);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        body.dark .preview-card {
            background: linear-gradient(135deg, rgba(102,224,255,0.15), rgba(0,212,255,0.08));
            backdrop-filter: blur(25px);
            border: 1px solid rgba(102,224,255,0.4);
        }

        body.light .preview-card {
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border: 1px solid var(--light-border);
        }

        body.dark .preview-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--cyan-main), var(--cyan-light));
            border-radius: 24px 24px 0 0;
        }

        body.light .preview-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 24px 24px 0 0;
        }

        .preview-card:hover { transform: translateY(-8px); }
        body.dark .preview-card:hover { box-shadow: 0 30px 60px rgba(0,212,255,0.3); }
        body.light .preview-card:hover { box-shadow: 0 30px 60px rgba(59,130,246,0.2); }

        .preview-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        body.dark .preview-amount {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--cyan-main), var(--cyan-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        body.light .preview-amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--light-text);
            margin-bottom: 0.5rem;
        }

        body.dark .preview-category {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--cyan-light);
            margin-bottom: 0.75rem;
        }

        body.light .preview-category {
            font-size: 1.3rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 0.75rem;
        }

        body.dark .preview-desc {
            color: var(--cyan-muted);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        body.light .preview-desc {
            color: var(--light-muted);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        body.dark .preview-date {
            color: var(--cyan-light);
            font-weight: 500;
        }

        body.light .preview-date {
            color: #475569;
            font-weight: 500;
        }

        .btn-submit {
            border: none !important;
            border-radius: 18px !important;
            padding: 18px 40px !important;
            font-weight: 700 !important;
            font-size: 1.2rem !important;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        body.dark .btn-submit {
            background: var(--primary) !important;
            color: white !important;
            box-shadow: 0 15px 35px rgba(102,126,234,0.4);
        }

        body.dark .btn-submit:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 25px 50px rgba(102,126,234,0.6) !important;
        }

        body.light .btn-submit {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%) !important;
            color: white !important;
            box-shadow: 0 15px 35px rgba(59,130,246,0.3);
        }

        body.light .btn-submit:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 25px 50px rgba(59,130,246,0.5) !important;
        }

        body.dark .btn-secondary {
            background: rgba(102,224,255,0.2) !important;
            border: 1px solid rgba(102,224,255,0.3) !important;
            color: var(--cyan-main) !important;
        }

        body.light .btn-secondary {
            background: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            color: #475569 !important;
        }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1.5rem; }
            .sidebar { transform: translateX(-100%); }
            .theme-toggle { top: 15px; right: 15px; width: 50px; height: 50px; }
            .row { flex-direction: column-reverse; }
            .preview-card { margin-top: 2rem; }
        }
    </style>
</head>
<body class="dark">
    <button class="theme-toggle" id="themeToggle" title="Switch to Light Mode">
        <i class="fas fa-sun"></i>
    </button>

    <div class="sidebar p-4">
        <div class="logo mb-5 text-center py-4">
            <h3>Daily Expense Tracker</h3>
        </div>
        <nav class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Dashboard</a>
            <a href="add_expense.php" class="nav-link active"><i class="fas fa-plus me-2"></i>Add Expense</a>
            <a href="manage_expenses.php" class="nav-link"><i class="fas fa-list me-2"></i>Manage Expenses</a>
            <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar me-2"></i>Reports</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user me-2"></i>Profile</a>
            <a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="row">
            <div class="col-lg-8">
                <div class="form-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-plus-circle me-2"></i>Add New Expense</h2>
                        <a href="dashboard.php" class="btn btn-secondary" id="backBtn" style="border-radius: 12px; padding: 10px 20px;">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <form method="POST" id="expenseForm">
                        <?php if (!empty($warning)): ?>
                            <div class="alert alert-warning"><?= htmlspecialchars($warning) ?></div>
                        <?php endif; ?>
                        <input type="hidden" name="confirm_overspend" id="confirmOverspend" value="0">

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-tags me-2"></i>Category</label>
                                <select name="category_id" class="form-select" id="category" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" data-color="<?= $cat['color'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id']==$cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-rupee-sign me-2"></i>Amount (₹)</label>
                                <input type="number" name="amount" step="0.01" min="0.01" class="form-control" id="amount" placeholder="500.00" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-calendar me-2"></i>Date</label>
                                <input type="date" name="expense_date" class="form-control" id="date" value="<?= htmlspecialchars($_POST['expense_date'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-align-left me-2"></i>Description</label>
                                <input type="text" name="description" class="form-control" id="desc" placeholder="Grocery shopping, office lunch, etc." maxlength="255" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-submit text-white">
                            <i class="fas fa-save me-2"></i>Add Expense
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="preview-card">
                    <h5 class="mb-4" id="previewTitle"><i class="fas fa-eye me-2"></i>Live Preview</h5>
                    <div id="preview" class="text-center">
                        <i class="fas fa-receipt preview-icon" id="previewIcon"></i>
                        <div class="preview-amount" id="previewAmount">₹0.00</div>
                        <div class="preview-category" id="previewCategory">-</div>
                        <div class="preview-desc" id="previewDesc">-</div>
                        <div class="preview-date" id="previewDate">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        const preview = {
            category: document.getElementById('category'),
            amount: document.getElementById('amount'),
            desc: document.getElementById('desc'),
            date: document.getElementById('date'),
            previewAmount: document.getElementById('previewAmount'),
            previewCategory: document.getElementById('previewCategory'),
            previewDesc: document.getElementById('previewDesc'),
            previewDate: document.getElementById('previewDate'),
            previewIcon: document.getElementById('previewIcon')
        };

        function updatePreview() {
            const amount = parseFloat(preview.amount.value) || 0;
            preview.previewAmount.textContent = '₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2});

            const selectedOption = preview.category.options[preview.category.selectedIndex];
            preview.previewCategory.textContent = selectedOption.text;
            preview.previewDesc.textContent = preview.desc.value || 'No description';

            if (preview.date.value) {
                const date = new Date(preview.date.value);
                preview.previewDate.textContent = date.toLocaleDateString('en-IN', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            } else {
                preview.previewDate.textContent = '-';
            }

            const color = selectedOption.dataset.color;
            preview.previewIcon.style.color = color;
            document.querySelector('.preview-card').style.borderLeft = `5px solid ${color}`;

            if (amount > 1000) {
                preview.previewAmount.style.color = body.classList.contains('dark') ? '#f39c12' : '#d97706';
            } else if (amount > 500) {
                preview.previewAmount.style.color = body.classList.contains('dark') ? 'var(--cyan-main)' : '#3b82f6';
            } else {
                preview.previewAmount.style.color = body.classList.contains('dark') ? 'var(--cyan-light)' : '#1d4ed8';
            }
        }

        [preview.category, preview.amount, preview.desc, preview.date].forEach(el => {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        });

        if (!document.getElementById('date').value) {
            document.getElementById('date').valueAsDate = new Date();
        }

        updatePreview();

        const currentGoal = <?= json_encode($currentGoal) ?>;
        const currentSpent = <?= json_encode($currentSpent) ?>;
        const form = document.getElementById('expenseForm');

        form.addEventListener('submit', function(e) {
            const amountVal = parseFloat(preview.amount.value) || 0;

            if (currentGoal > 0 && (currentSpent + amountVal) > currentGoal) {
                const ok = confirm('This expense will exceed your monthly goal. Are you sure you want to add it?');
                if (!ok) {
                    e.preventDefault();
                } else {
                    document.getElementById('confirmOverspend').value = '1';
                }
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