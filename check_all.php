<?php
include 'includes/db.php';

echo "=== ALL SALES IN DATABASE ===\n";
$q = $conn->query("SELECT id, total_amount, is_cleared, is_monthly_cleared, created_at, tenant_id FROM sales ORDER BY created_at DESC");
echo "Total: " . $q->num_rows . " sales\n\n";
while($r = $q->fetch_assoc()){
    echo "ID:{$r['id']} | Rs.{$r['total_amount']} | cleared:{$r['is_cleared']} | monthly:{$r['is_monthly_cleared']} | {$r['created_at']} | tenant:{$r['tenant_id']}\n";
}

echo "\n=== ALL TABLES THAT MIGHT HAVE SALES ===\n";
$tables = ['sales', 'held_sales', 'z_reports_history', 'monthly_reports_history'];
foreach($tables as $t){
    $q2 = $conn->query("SELECT COUNT(*) as cnt FROM $t");
    $r = $q2->fetch_assoc();
    echo "$t: {$r['cnt']} rows\n";
}

echo "\n=== Z-REPORTS HISTORY (Past Day Closings) ===\n";
$q3 = $conn->query("SELECT * FROM z_reports_history ORDER BY report_date DESC");
while($r = $q3->fetch_assoc()){
    echo "Date:{$r['report_date']} | Sales:{$r['total_sales']} | Orders:{$r['total_orders']} | Net:{$r['net_income']}\n";
}

echo "\n=== MONTHLY REPORTS HISTORY ===\n";
$q4 = $conn->query("SELECT * FROM monthly_reports_history ORDER BY report_month DESC");
while($r = $q4->fetch_assoc()){
    echo "Month:{$r['report_month']} | Sales:{$r['total_sales']} | Expenses:{$r['total_expenses']} | Net:{$r['net_income']}\n";
}
?>
