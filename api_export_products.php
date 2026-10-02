<?php
include 'includes/db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Name', 'Price', 'Wholesale_Price', 'Stock', 'Unit', 'Barcode'));

$query = "SELECT id, name, price, wholesale_price, stock, unit, barcode FROM products ORDER BY id ASC";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}
fclose($output);
exit();
?>
