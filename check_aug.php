<?php
session_start();
include 'includes/db.php';
echo "<h2>August 2026 Sales Diagnostic</h2>";

// Total sales count August
$q = $conn->query("SELECT COUNT(*) as cnt, SUM(total_amount) as total, MIN(is_monthly_cleared) as minc, MAX(is_monthly_cleared) as maxc FROM sales WHERE MONTH(created_at) = 8 AND YEAR(created_at) = 2026");
$r = $q->fetch_assoc();
echo "<b>Total August Sales: {$r['cnt']} | Total Amount: {$r['total']} | Cleared range: {$r['minc']}-{$r['maxc']}</b><br><br>";

// By is_monthly_cleared
$q2 = $conn->query("SELECT is_monthly_cleared, COUNT(*) as cnt, SUM(total_amount) as total FROM sales WHERE MONTH(created_at) = 8 AND YEAR(created_at) = 2026 GROUP BY is_monthly_cleared");
echo "<h3>By is_monthly_cleared status:</h3>";
while($r = $q2->fetch_assoc()){
    echo "Cleared={$r['is_monthly_cleared']}: {$r['cnt']} sales, Rs. {$r['total']}<br>";
}

// By tenant_id
$q3 = $conn->query("SELECT tenant_id, COUNT(*) as cnt, SUM(total_amount) as total FROM sales WHERE MONTH(created_at) = 8 AND YEAR(created_at) = 2026 GROUP BY tenant_id");
echo "<h3>By tenant_id:</h3>";
while($r = $q3->fetch_assoc()){
    echo "Tenant {$r['tenant_id']}: {$r['cnt']} sales, Rs. {$r['total']}<br>";
}

// Session info
echo "<h3>Session Info:</h3>";
echo "Session tenant_id: " . ($_SESSION['tenant_id'] ?? 'NOT SET') . "<br>";

// Check is_cleared vs is_monthly_cleared
$q4 = $conn->query("SELECT is_cleared, is_monthly_cleared, COUNT(*) as cnt FROM sales WHERE MONTH(created_at) = 8 AND YEAR(created_at) = 2026 GROUP BY is_cleared, is_monthly_cleared");
echo "<h3>is_cleared vs is_monthly_cleared:</h3>";
while($r = $q4->fetch_assoc()){
    echo "is_cleared={$r['is_cleared']}, is_monthly_cleared={$r['is_monthly_cleared']}: {$r['cnt']} sales<br>";
}
?>
