<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include 'includes/db.php';

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'No barcode provided']);
    exit;
}

// First check variations
$vStmt = $conn->prepare("SELECT pv.id as var_id, pv.product_id, pv.variation_name, pv.price as var_price, pv.stock as var_stock, p.name, p.price as base_price, p.unit, p.image FROM product_variations pv JOIN products p ON pv.product_id = p.id WHERE pv.barcode = ? LIMIT 1");
$vStmt->bind_param("s", $code);
$vStmt->execute();
$vRes = $vStmt->get_result();

if ($vRow = $vRes->fetch_assoc()) {
    if (floatval($vRow['var_stock']) < 10) {
        echo json_encode(['success' => false, 'message' => 'Low Stock (Under 10). Cannot add product.']);
        exit();
    }
    
    $retail_price = $vRow['var_price'] ? (float)$vRow['var_price'] : (float)$vRow['base_price'];
    
    echo json_encode(['success' => true, 'product' => [
        'id' => $vRow['product_id'] . '-' . $vRow['var_id'],
        'product_id' => $vRow['product_id'],
        'variation_id' => $vRow['var_id'],
        'name' => $vRow['name'] . ' (' . $vRow['variation_name'] . ')',
        'price' => $retail_price,
        'stock' => $vRow['var_stock'],
        'unit' => $vRow['unit'],
        'image' => $vRow['image']
    ]]);
    exit();
}
$vStmt->close();

$stmt = $conn->prepare("SELECT id, name, price, stock, unit, image FROM products WHERE barcode = ? LIMIT 1");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (floatval($row['stock']) < 10) {
        echo json_encode(['success' => false, 'message' => 'Low Stock (Under 10). Cannot add product.']);
        exit;
    }
    echo json_encode(['success' => true, 'product' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
}
$stmt->close();
?>
