<?php
header('Content-Type: application/json');
require_once 'includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['cart']) || empty($input['tenant_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$tenant_id = (int)$input['tenant_id'];
$c_name = $conn->real_escape_string($input['customer_name']);
$c_phone = $conn->real_escape_string($input['customer_phone']);
$c_addr = $conn->real_escape_string($input['customer_address']);
$cart = $input['cart'];

// Find or Create Customer
$cq = $conn->query("SELECT id FROM customers WHERE phone = '$c_phone' AND tenant_id = $tenant_id");
if ($cq && $cq->num_rows > 0) {
    $c_id = $cq->fetch_assoc()['id'];
} else {
    // Find first branch for tenant
    $bq = $conn->query("SELECT id FROM branches WHERE tenant_id = $tenant_id LIMIT 1");
    $branch_id = $bq->fetch_assoc()['id'] ?? 1;
    
    $conn->query("INSERT INTO customers (name, phone, address, tenant_id, branch_id) VALUES ('$c_name', '$c_phone', '$c_addr', $tenant_id, $branch_id)");
    $c_id = $conn->insert_id;
}

$total = 0;
foreach($cart as $item) {
    $total += ($item['price'] * $item['qty']);
}

// Default branch for online orders
$bq = $conn->query("SELECT id FROM branches WHERE tenant_id = $tenant_id LIMIT 1");
$branch_id = $bq->fetch_assoc()['id'] ?? 1;

// Insert Sale (Unpaid, Online)
$stmt = $conn->prepare("INSERT INTO sales (branch_id, customer_id, user_id, total_amount, payment_method, tenant_id, notes) VALUES (?, ?, NULL, ?, 'online_unpaid', ?, ?)");
$notes = "Online Order. Delivery to: $c_addr";
$stmt->bind_param("iidis", $branch_id, $c_id, $total, $tenant_id, $notes);
if ($stmt->execute()) {
    $sale_id = $stmt->insert_id;
    $stmt->close();
    
    // Insert Items
    $i_stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, variation_id, quantity, price, tenant_id) VALUES (?, ?, ?, ?, ?, ?)");
    $tenant_id = $_SESSION['tenant_id'];
    foreach($cart as $item) {
        // App.js sends id as "product_id-variation_id" if variation is used, but it also sends product_id and variation_id fields!
        // Let's use item['product_id'] and item['variation_id'] if available.
        $p_id = isset($item['product_id']) ? (int)$item['product_id'] : (int)$item['id'];
        $v_id = !empty($item['variation_id']) ? (int)$item['variation_id'] : null;
        $qty = (int)$item['qty'];
        $price = (float)$item['price'];
        
        $i_stmt->bind_param("iiidid", $sale_id, $p_id, $v_id, $qty, $price, $tenant_id);
        $i_stmt->execute();
        
        // Deduct Stock
        if ($v_id) {
            $conn->query("UPDATE product_variations SET stock = stock - $qty WHERE id = $v_id");
            // Also deduct main stock to keep them in sync if desired, but typically variations track their own stock.
        } else {
            $conn->query("UPDATE products SET stock_quantity = stock_quantity - $qty WHERE id = $p_id");
        }
    }
    $i_stmt->close();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>
