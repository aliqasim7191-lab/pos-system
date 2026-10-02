<?php
include 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete data older than 1st of current month
    $firstOfCurrentMonth = date('Y-m-01');
    
    $conn->query("DELETE FROM z_reports_history WHERE created_at < '$firstOfCurrentMonth'");
    
    $conn->query("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE created_at < '$firstOfCurrentMonth')");
    $conn->query("DELETE FROM sales WHERE created_at < '$firstOfCurrentMonth'");
    
    $conn->query("DELETE FROM return_items WHERE return_id IN (SELECT id FROM returns WHERE created_at < '$firstOfCurrentMonth')");
    $conn->query("DELETE FROM returns WHERE created_at < '$firstOfCurrentMonth'");
    
    $conn->query("DELETE FROM expenses WHERE created_at < '$firstOfCurrentMonth'");
    
    $conn->query("DELETE FROM purchase_items WHERE purchase_id IN (SELECT id FROM purchases WHERE created_at < '$firstOfCurrentMonth')");
    $conn->query("DELETE FROM purchases WHERE created_at < '$firstOfCurrentMonth'");
    
    $conn->query("DELETE FROM customer_ledger WHERE created_at < '$firstOfCurrentMonth'");
    $conn->query("DELETE FROM damaged_stock WHERE created_at < '$firstOfCurrentMonth'");
    
    header("Location: index.php?cleared=1");
    exit;
}
?>
