<?php
include 'includes/db.php';
// Check purchases table
$q = $conn->query("SHOW COLUMNS FROM purchases");
echo "=== purchases table ===\n";
while($r = $q->fetch_assoc()) echo $r['Field'] . " | " . $r['Type'] . "\n";

echo "\n=== purchase_items table ===\n";
$q2 = $conn->query("SHOW COLUMNS FROM purchase_items");
while($r = $q2->fetch_assoc()) echo $r['Field'] . " | " . $r['Type'] . "\n";

echo "\n=== suppliers table ===\n";
$q3 = $conn->query("SHOW COLUMNS FROM suppliers");
while($r = $q3->fetch_assoc()) echo $r['Field'] . " | " . $r['Type'] . "\n";

echo "\nSample suppliers:\n";
$q4 = $conn->query("SELECT id, name FROM suppliers LIMIT 5");
while($r = $q4->fetch_assoc()) echo "ID:{$r['id']} | {$r['name']}\n";
?>
