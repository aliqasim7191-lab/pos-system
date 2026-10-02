<?php
include 'includes/db.php';
// Check payment methods in sales
$q = $conn->query("SELECT payment_method, COUNT(*) as cnt, SUM(total_amount) as total FROM sales GROUP BY payment_method ORDER BY cnt DESC");
echo "Payment Methods in Database:\n";
while($r = $q->fetch_assoc()){
    echo "Method: '" . $r['payment_method'] . "' | Count: {$r['cnt']} | Total: Rs.{$r['total']}\n";
}
// Check split payments table if exists
$q2 = $conn->query("SHOW TABLES LIKE 'sale_payments'");
echo "\nsale_payments table: " . ($q2->num_rows > 0 ? "EXISTS" : "NOT FOUND") . "\n";

$q3 = $conn->query("SHOW TABLES LIKE 'payments'");
echo "payments table: " . ($q3->num_rows > 0 ? "EXISTS" : "NOT FOUND") . "\n";

// Check sales table columns for payment
$q4 = $conn->query("SHOW COLUMNS FROM sales LIKE '%pay%'");
echo "\nPayment columns in sales:\n";
while($r = $q4->fetch_assoc()) echo $r['Field'] . "\n";
?>
