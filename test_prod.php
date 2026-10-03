<?php
include 'includes/db.php';
$current_branch_id = 1;
$taxQ = $conn->query("SELECT setting_value FROM settings WHERE setting_key='tax_rate'");
$global_tax_rate = ($taxQ && $taxQ->num_rows > 0) ? floatval($taxQ->fetch_assoc()['setting_value']) : 0;

$result = $conn->query("SELECT p.*, t.rate as custom_tax_rate FROM products p LEFT JOIN tax_classes t ON p.tax_class_id = t.id WHERE p.status='active' AND p.branch_id = $current_branch_id");
$products = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['tax_rate'] = $row['custom_tax_rate'] !== null ? floatval($row['custom_tax_rate']) : $global_tax_rate;
        $row['variations'] = [];
        $products[$row['id']] = $row;
    }
}
echo json_encode($products);
?>
