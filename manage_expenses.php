<?php 
require_once 'config.php'; 
if (!isLoggedIn()) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// AJAX Delete
if (isset($_POST['ajax_delete'])) {
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    echo $stmt->execute([$_POST['expense_id'], $user_id]) ? json_encode(['success' => true]) : json_encode(['success' => false]);
    exit;
}

// All expenses with categories
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name, c.color, c.id as category_id FROM expenses e JOIN categories c ON e.category_id = c.id WHERE e.user_id = ? ORDER BY e.created_at DESC");
$stmt->execute([$user_id]);
$expenses = $stmt->fetchAll();

// All categories for edit dropdown
$stmt = $pdo->query("SELECT * FROM categories WHERE type='expense'");
$all_categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Expenses - Daily Expense Tracker</title>
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
        
        /* Table Container */
        .table-container {
            border-radius: 24px;
            border: 1px solid rgba(102,224,255,0.25);
            overflow: hidden;
            box-shadow: 0 20px 40px var(--light-shadow);
            transition: all 0.4s ease;
        }
        body.dark .table-container {
            background: rgba(26,26,46,0.92) !important;
            backdrop-filter: blur(25px) !important;
        }
        body.light .table-container {
            background: var(--light-card) !important;
            border-color: var(--light-border) !important;
        }
        
        .table { margin: 0; }
        .table th { 
            border: none; 
            padding: 1.5rem 1.5rem; 
            font-weight: 600; 
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s ease;
        }
        body.dark .table th { 
            color: var(--cyan-light) !important;
            background: rgba(26,26,46,0.95) !important;
        }
        body.light .table th {
            color: #475569 !important;
            background: #f8fafc !important;
        }
        
        .table td { 
            padding: 1.5rem 1.5rem; 
            border-color: rgba(102,224,255,0.15);
            vertical-align: middle;
            transition: all 0.3s ease;
        }
        body.dark .table td { background: transparent !important; }
        body.light .table td { background: transparent !important; }
        
        /* Table Content */
        body.dark .date-cell strong { 
            color: var(--cyan-main) !important; 
            font-size: 1.1rem; 
            font-weight: 700;
            display: block;
            text-shadow: 0 1px 3px rgba(0,0,0,0.7);
        }
        body.light .date-cell strong { 
            color: var(--light-text) !important; 
            font-size: 1.1rem; 
            font-weight: 700;
            display: block;
        }
        
        body.dark .date-cell small { 
            color: var(--cyan-muted) !important; 
            font-size: 0.85rem;
            font-weight: 500;
        }
        body.light .date-cell small { 
            color: var(--light-muted) !important; 
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        body.dark .desc-cell { 
            color: var(--cyan-light) !important; 
            max-width: 280px;
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0,0,0,0.7);
            line-height: 1.4;
        }
        body.light .desc-cell { 
            color: var(--light-text) !important; 
            max-width: 280px;
            font-weight: 500;
            line-height: 1.4;
        }
        
        body.dark .table tbody tr { 
            color: var(--cyan-light) !important; 
            background: transparent !important;
        }
        body.light .table tbody tr { 
            color: var(--light-text) !important; 
            background: transparent !important;
        }
        
        body.dark .table-hover tbody tr:hover { 
            background: rgba(102,224,255,0.15) !important;
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(0,212,255,0.3);
        }
        body.light .table-hover tbody tr:hover { 
            background: rgba(59,130,246,0.05) !important;
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(59,130,246,0.15);
        }
        
        /* Buttons */
        .btn { 
            border-radius: 12px; 
            padding: 10px 18px; 
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid;
            font-size: 0.85rem;
        }
        .btn:hover { transform: translateY(-3px); }
        
        body.dark .btn-outline-warning { 
            color: #f39c12 !important; 
            border-color: #f39c12; 
            background: rgba(243,156,18,0.1); 
        }
        body.light .btn-outline-warning { 
            color: #d97706 !important; 
            border-color: #d97706; 
            background: rgba(251,191,36,0.1); 
        }
        
        body.dark .btn-outline-danger { 
            color: #e74c3c !important; 
            border-color: #e74c3c; 
            background: rgba(231,76,60,0.1); 
        }
        body.light .btn-outline-danger { 
            color: #dc2626 !important; 
            border-color: #dc2626; 
            background: rgba(248,113,113,0.1); 
        }
        
        /* Modal */
        body.dark .modal-content {
            background: rgba(26,26,46,0.98) !important;
            border: 1px solid rgba(102,224,255,0.3) !important;
            border-radius: 24px !important;
            backdrop-filter: blur(25px) !important;
            color: var(--cyan-main) !important;
        }
        body.light .modal-content {
            background: var(--light-card) !important;
            border: 1px solid var(--light-border) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 60px var(--light-shadow);
        }
        
        body.dark .modal-header, body.dark .modal-footer {
            border-color: rgba(102,224,255,0.2) !important;
            background: rgba(102,224,255,0.08) !important;
            color: var(--cyan-main) !important;
        }
        body.light .modal-header, body.light .modal-footer {
            border-color: var(--light-border) !important;
            background: #f8fafc !important;
            color: var(--light-text) !important;
        }
        
        .form-label { 
            font-weight: 600 !important; 
            transition: all 0.4s ease;
        }
        body.dark .form-label { color: var(--cyan-light) !important; }
        body.light .form-label { color: #475569 !important; }
        
        .form-control, .form-select {
            border-radius: 16px !important;
            padding: 14px 20px !important;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        body.dark .form-control, body.dark .form-select {
            background: rgba(102,224,255,0.1) !important;
            border: 1px solid rgba(102,224,255,0.4) !important;
            color: var(--cyan-main) !important;
        }
        body.dark .form-control::placeholder, body.dark .form-select option { color: var(--cyan-muted) !important; }
        body.dark .form-control:focus, body.dark .form-select:focus {
            background: rgba(102,224,255,0.15) !important;
            border-color: var(--cyan-main) !important;
            box-shadow: 0 0 0 0.25rem rgba(0,212,255,0.3) !important;
            color: var(--cyan-main) !important;
        }
        
        body.light .form-control, body.light .form-select {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            color: #1e293b !important;
        }
        body.light .form-control::placeholder { color: #94a3b8 !important; }
        body.light .form-control:focus, body.light .form-select:focus {
            background: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.25rem rgba(59,130,246,0.15) !important;
            color: #1e293b !important;
        }
        
        /* Empty State */
        .empty-state {
            border-radius: 24px;
            padding: 5rem 3rem;
            text-align: center;
            box-shadow: 0 20px 40px var(--light-shadow);
            transition: all 0.4s ease;
        }
        body.dark .empty-state {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass);
            color: var(--cyan-main);
        }
        body.light .empty-state {
            background: var(--light-card);
            border: 1px solid var(--light-border);
            color: var(--light-text);
        }
        
        /* Badge & Alerts */
        .badge {
            font-size: 0.85rem !important;
            padding: 8px 16px !important;
            font-weight: 600 !important;
        }
        body.light .text-warning { color: #d97706 !important; }
        
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
        body.dark .alert-danger { 
            background: rgba(231,76,60,0.25) !important; 
            border-color: #e74c3c !important; 
            color: var(--cyan-main) !important;
        }
        body.light .alert-danger { 
            background: #fef2f2 !important; 
            border-color: #dc2626 !important; 
            color: #dc2626 !important;
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
            <a href="manage_expenses.php" class="nav-link active"><i class="fas fa-list me-2"></i>Manage Expenses</a>
            <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar me-2"></i>Reports</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user me-2"></i>Profile</a>
            <a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- SUCCESS/ERROR ALERTS -->
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
            <i class="fas fa-check-circle me-2"></i>✅ Expense updated successfully!
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i>❌ Update failed! Please try again.
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="mb-1">Manage Expenses</h1>
                <small id="expenseCount">Total: <?= count($expenses) ?> expenses tracked</small>
            </div>
            <a href="add_expense.php" class="btn" id="addBtn">
                <i class="fas fa-plus me-2"></i>Add New Expense
            </a>
        </div>

        <?php if (empty($expenses)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox display-1 mb-4" id="emptyIcon"></i>
            <h2 class="mb-3">No expenses found</h2>
            <p class="mb-5" id="emptyText">Start tracking your daily expenses by adding your first entry.</p>
            <a href="add_expense.php" class="btn" id="firstExpenseBtn" style="padding: 16px 40px; font-size: 1.1rem;">
                <i class="fas fa-plus-circle me-2"></i>Add Your First Expense
            </a>
        </div>
        <?php else: ?>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar me-2"></i>Date</th>
                            <th><i class="fas fa-tags me-2"></i>Category</th>
                            <th><i class="fas fa-align-left me-2"></i>Description</th>
                            <th><i class="fas fa-rupee-sign me-2"></i>Amount</th>
                            <th><i class="fas fa-cogs me-2"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTable">
                        <?php foreach ($expenses as $exp): ?>
                        <tr data-id="<?= $exp['id'] ?>">
                            <td class="date-cell">
                                <strong><?= date('d M Y', strtotime($exp['expense_date'])) ?></strong>
                                <br><small><?= date('H:i', strtotime($exp['created_at'])) ?></small>
                            </td>
                            <td>
                                <span class="badge fs-6 px-4 py-2" style="background: <?= $exp['color'] ?> !important; color: white !important;">
                                    <?= htmlspecialchars($exp['category_name']) ?>
                                </span>
                            </td>
                            <td class="desc-cell">
                                <?= htmlspecialchars($exp['description'] ?: 'No description') ?>
                            </td>
                            <td>
                                <h5 class="mb-0 text-warning">₹<?= number_format($exp['amount'], 2) ?></h5>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-warning edit-btn me-1" 
                                            data-id="<?= $exp['id'] ?>" 
                                            data-category="<?= $exp['category_id'] ?>"
                                            data-amount="<?= $exp['amount'] ?>" 
                                            data-desc="<?= htmlspecialchars($exp['description']) ?>" 
                                            data-date="<?= $exp['expense_date'] ?>"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                            data-id="<?= $exp['id'] ?>"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title"><i class="fas fa-edit me-2 text-warning"></i>Edit Expense</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="edit_expense.php">
                        <div class="modal-body">
                            <input type="hidden" name="expense_id" id="edit_id">
                            
                            <div class="mb-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" id="edit_category_select" class="form-select" required>
                                    <?php foreach ($all_categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="expense_date" id="edit_date" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Amount (₹)</label>
                                    <input type="number" name="amount" id="edit_amount" step="0.01" min="0.01" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" id="edit_desc" class="form-control" maxlength="255">
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn" id="updateBtn">
                                <i class="fas fa-save me-2"></i>Update Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 🌙 LIGHT/DARK MODE TOGGLE - FULLY SYNCHRONIZED
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

        // Delete with smooth animation
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-btn')) {
                const btn = e.target.closest('.delete-btn');
                if (!confirm('Delete this expense permanently?')) return;
                
                const row = btn.closest('tr');
                const id = btn.dataset.id;
                const btnIcon = btn.querySelector('i');
                
                btn.disabled = true;
                btnIcon.className = 'fas fa-spinner fa-spin';
                
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `ajax_delete=1&expense_id=${id}`
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        row.style.transition = 'all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-100px) scale(0.95)';
                        row.style.maxHeight = '0';
                        
                        setTimeout(() => {
                            row.remove();
                            updateCount();
                        }, 500);
                    } else {
                        btn.disabled = false;
                        btnIcon.className = 'fas fa-trash';
                        alert('Delete failed! Try again.');
                    }
                }).catch(() => {
                    btn.disabled = false;
                    btnIcon.className = 'fas fa-trash';
                });
            }
        });

        // Edit modal population
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-btn')) {
                const btn = e.target.closest('.edit-btn');
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_category_select').value = btn.dataset.category;
                document.getElementById('edit_date').value = btn.dataset.date;
                document.getElementById('edit_amount').value = parseFloat(btn.dataset.amount).toFixed(2);
                document.getElementById('edit_desc').value = btn.dataset.desc || '';
                
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }
        });

        function updateCount() {
            const count = document.querySelectorAll('#expensesTable tr').length;
            const header = document.getElementById('expenseCount');
            if (header && count > 0) {
                header.textContent = `Total: ${count} expenses tracked`;
            }
            
            // Show/hide empty state
            const tableContainer = document.querySelector('.table-container');
            const emptyState = document.querySelector('.empty-state');
            if (count === 0 && tableContainer) {
                tableContainer.style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
            }
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: light)').addListener(() => {
            if (!localStorage.getItem('theme')) {
                initTheme();
            }
        });
    </script>
</body>
</html>
