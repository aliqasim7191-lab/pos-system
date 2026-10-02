<?php
include 'includes/db.php';

// Total all sales
$q = $conn->query("SELECT COUNT(*) as cnt, MIN(created_at) as first_sale, MAX(created_at) as last_sale FROM sales");
$r = $q->fetch_assoc();
echo "Total Sales in DB: " . $r['cnt'] . "\n";
echo "First Sale: " . $r['first_sale'] . "\n";
echo "Last Sale: " . $r['last_sale'] . "\n\n";

// Sales by month/year
$q2 = $conn->query("SELECT YEAR(created_at) as yr, MONTH(created_at) as mo, COUNT(*) as cnt, SUM(total_amount) as total FROM sales GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY yr DESC, mo DESC LIMIT 20");
echo "Sales by Month:\n";
while($r = $q2->fetch_assoc()){
    echo $r['yr'] . "-" . str_pad($r['mo'],2,'0',STR_PAD_LEFT) . ": " . $r['cnt'] . " sales, Rs. " . $r['total'] . "\n";
}

// Check is_cleared column
echo "\nChecking is_cleared vs is_monthly_cleared columns:\n";
$q3 = $conn->query("SELECT is_cleared, is_monthly_cleared, COUNT(*) as cnt FROM sales GROUP BY is_cleared, is_monthly_cleared");
while($r = $q3->fetch_assoc()){
    echo "is_cleared={$r['is_cleared']}, is_monthly_cleared={$r['is_monthly_cleared']}: {$r['cnt']} sales\n";
}
?>
