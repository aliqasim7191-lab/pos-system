<?php
header("Content-Type: application/json");
include 'includes/db.php';

if (!isset($_GET['code'])) {
    echo json_encode(["status" => "error", "message" => "No code provided"]);
    exit();
}

$code = $conn->real_escape_string($_GET['code']);
$total = isset($_GET['total']) ? floatval($_GET['total']) : 0;

$q = $conn->query("SELECT * FROM promo_codes WHERE code = '$code' LIMIT 1");
if (!$q || $q->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Invalid promo code"]);
    exit();
}

$promo = $q->fetch_assoc();

if (!$promo['is_active']) {
    echo json_encode(["status" => "error", "message" => "Promo code is inactive"]);
    exit();
}

if ($promo['expiry_date'] && strtotime($promo['expiry_date']) < strtotime('today')) {
    echo json_encode(["status" => "error", "message" => "Promo code has expired"]);
    exit();
}

if ($promo['min_order_value'] > 0 && $total < $promo['min_order_value']) {
    echo json_encode(["status" => "error", "message" => "Minimum order value is $" . $promo['min_order_value']]);
    exit();
}

echo json_encode([
    "status" => "success",
    "type" => $promo['discount_type'],
    "value" => $promo['discount_value']
]);
?>
