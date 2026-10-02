<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["success" => false, "message" => "Unauthorized"]));
}
include 'includes/db.php';

header('Content-Type: application/json');

$query = $conn->query("SELECT id, name, customer_type, credit_limit, outstanding_balance, points FROM customers ORDER BY name ASC");
$customers = [];
while ($row = $query->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode(["success" => true, "customers" => $customers]);
?>
