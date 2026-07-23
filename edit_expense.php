<?php
require_once 'config.php';
if (!isLoggedIn() || !isset($_POST['expense_id'])) {
    header('Location: manage_expenses.php'); 
    exit;
}

// Validate all required fields
if (empty($_POST['category_id']) || empty($_POST['amount']) || empty($_POST['expense_date'])) {
    header('Location: manage_expenses.php?error=missing_fields');
    exit;
}

// Sanitize inputs
$expense_id = (int)$_POST['expense_id'];
$user_id = $_SESSION['user_id'];
$category_id = (int)$_POST['category_id'];
$amount = floatval($_POST['amount']);
$description = trim($_POST['description'] ?? '');
$expense_date = $_POST['expense_date'];

// Update query - COMPLETE with category_id
$stmt = $pdo->prepare("
    UPDATE expenses 
    SET category_id = ?, amount = ?, description = ?, expense_date = ? 
    WHERE id = ? AND user_id = ?
");

$result = $stmt->execute([$category_id, $amount, $description, $expense_date, $expense_id, $user_id]);

if ($result && $stmt->rowCount() > 0) {
    // Success
    header('Location: manage_expenses.php?success=1');
} else {
    // Failed
    header('Location: manage_expenses.php?error=update_failed');
}
exit;
?>
