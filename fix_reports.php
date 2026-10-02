<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/reports.php");
// Add tenant_id to queries
$f = str_replace(
    'SELECT SUM(total_amount) FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND is_monthly_cleared = 0',
    'SELECT SUM(total_amount) FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND is_monthly_cleared = 0 AND tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);
$f = str_replace(
    'SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ? AND is_monthly_cleared = 0',
    'SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ? AND is_monthly_cleared = 0 AND tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);
$f = str_replace(
    'SELECT SUM(amount_paid) FROM purchases WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND is_monthly_cleared = 0',
    'SELECT SUM(amount_paid) FROM purchases WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND is_monthly_cleared = 0 AND tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);
$f = str_replace(
    'INSERT INTO monthly_reports_history (report_month, total_sales, total_expenses, total_supplier_payments, net_income, snapshot_data)',
    'INSERT INTO monthly_reports_history (report_month, total_sales, total_expenses, total_supplier_payments, net_income, snapshot_data, tenant_id)',
    $f
);
$f = str_replace(
    'VALUES (?, ?, ?, ?, ?, ?)',
    'VALUES (?, ?, ?, ?, ?, ?, ' . "\$_SESSION['tenant_id']" . ')',
    $f
);
$f = str_replace(
    'UPDATE sales SET is_monthly_cleared = 1 WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?',
    'UPDATE sales SET is_monthly_cleared = 1 WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);
$f = str_replace(
    'UPDATE expenses SET is_monthly_cleared = 1 WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?',
    'UPDATE expenses SET is_monthly_cleared = 1 WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ? AND tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);
$f = str_replace(
    'UPDATE purchases SET is_monthly_cleared = 1 WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?',
    'UPDATE purchases SET is_monthly_cleared = 1 WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);
$f = str_replace(
    'SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE MONTH(s.created_at) = ? AND YEAR(s.created_at) = ?',
    'SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE MONTH(s.created_at) = ? AND YEAR(s.created_at) = ? AND s.tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);
$f = str_replace(
    'SELECT * FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?',
    'SELECT * FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ? AND tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);
$f = str_replace(
    'SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE MONTH(p.created_at) = ? AND YEAR(p.created_at) = ?',
    'SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE MONTH(p.created_at) = ? AND YEAR(p.created_at) = ? AND p.tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/reports.php", $f);
echo "Reports.php isolated!";
?>
