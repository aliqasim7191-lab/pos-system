<?php
// Smart Alert Engine
// Generates alerts based on business conditions

function generateSystemAlerts($conn) {
    // 1. Low Stock Alerts (Stock <= 10)
    $lowStockQ = $conn->query("SELECT id, name, stock FROM products WHERE stock <= 10 AND tenant_id = {$_SESSION['tenant_id']}");
    if ($lowStockQ && $lowStockQ->num_rows > 0) {
        $count = $lowStockQ->num_rows;
        // Check if we already have an unread Low Stock alert today to avoid spamming
        $today = date('Y-m-d');
        $checkQ = $conn->query("SELECT id FROM alerts WHERE type = 'Low Stock' AND DATE(created_at) = '$today' AND tenant_id = {$_SESSION['tenant_id']}");
        if ($checkQ && $checkQ->num_rows == 0) {
            $msg = "$count products are currently low in stock or out of stock. Please review inventory.";
            $stmt = $conn->prepare("INSERT INTO alerts (type, severity, message, action_link, tenant_id) VALUES ('Low Stock', 'warning', ?, 'dashboard.php#low-stock-list', {$_SESSION['tenant_id']})");
            $stmt->bind_param("s", $msg);
            $stmt->execute();
        }
    }

    // 2. Supplier Payment Due Alerts (Outstanding > 0)
    $supplierQ = $conn->query("SELECT id, name, outstanding_payable FROM suppliers WHERE outstanding_payable > 0 AND tenant_id = {$_SESSION['tenant_id']}");
    if ($supplierQ && $supplierQ->num_rows > 0) {
        $count = $supplierQ->num_rows;
        $today = date('Y-m-d');
        $checkQ = $conn->query("SELECT id FROM alerts WHERE type = 'Payment Overdue' AND DATE(created_at) = '$today' AND tenant_id = {$_SESSION['tenant_id']}");
        if ($checkQ && $checkQ->num_rows == 0) {
            $msg = "$count suppliers have outstanding payables. Please review supplier balances.";
            $stmt = $conn->prepare("INSERT INTO alerts (type, severity, message, action_link, tenant_id) VALUES ('Payment Overdue', 'info', ?, 'suppliers.php', {$_SESSION['tenant_id']})");
            $stmt->bind_param("s", $msg);
            $stmt->execute();
        }
    }
}

// Helper for logging audit trails
function logAudit($conn, $userId, $action, $details) {
    $tenant_id = $_SESSION['tenant_id'] ?? 1;
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action_type, description, tenant_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $userId, $action, $details, $tenant_id);
    $stmt->execute();
}
?>
