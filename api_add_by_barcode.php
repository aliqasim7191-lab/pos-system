<?php
session_start();
include 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_GET['barcode']) || empty(trim($_GET['barcode']))) {
    echo json_encode(['success' => false, 'message' => 'No barcode provided.']);
    exit();
}

$barcode = trim($_GET['barcode']);

// First check variations
$vStmt = $conn->prepare("SELECT pv.id as var_id, pv.product_id, pv.variation_name, pv.price as var_price, pv.stock as var_stock, p.name, p.price as base_price, p.wholesale_price, p.unit FROM product_variations pv JOIN products p ON pv.product_id = p.id WHERE pv.barcode = ? AND p.status = 'active' LIMIT 1");
$vStmt->bind_param("s", $barcode);
$vStmt->execute();
$vRes = $vStmt->get_result();

if ($vRow = $vRes->fetch_assoc()) {
    if (floatval($vRow['var_stock']) <= 0) {
        echo json_encode(['success' => false, 'message' => 'Out of Stock. Cannot add product.']);
        exit();
    }
    
    $retail_price = $vRow['var_price'] ? (float)$vRow['var_price'] : (float)$vRow['base_price'];
    
    echo json_encode([
        'success' => true,
        'product' => [
            'id' => $vRow['product_id'] . '-' . $vRow['var_id'],
            'name' => $vRow['name'] . ' (' . $vRow['variation_name'] . ')',
            'retail_price' => $retail_price,
            'wholesale_price' => isset($vRow['wholesale_price']) ? (float)$vRow['wholesale_price'] : $retail_price,
            'unit' => $vRow['unit'] ? $vRow['unit'] : 'pcs',
            'stock' => (float)$vRow['var_stock'],
            'variation_id' => $vRow['var_id']
        ]
    ]);
    exit();
}
$vStmt->close();

// Then check parent products
$tenant_id = $_SESSION['tenant_id'] ?? 1;
$barcodeLower = strtolower($barcode);
$stmt = $conn->prepare("SELECT id, name, price, wholesale_price, unit, stock FROM products WHERE (barcode = ? OR TRIM(barcode) = ? OR LOWER(name) = ?) AND status = 'active' AND tenant_id = ? LIMIT 1");
$stmt->bind_param("sssi", $barcode, $barcode, $barcodeLower, $tenant_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    if (floatval($row['stock']) <= 0) {
        echo json_encode(['success' => false, 'message' => 'Out of Stock. Cannot add product.']);
        exit();
    }
    echo json_encode([
        'success' => true,
        'product' => [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'retail_price' => (float)$row['price'],
            'wholesale_price' => isset($row['wholesale_price']) ? (float)$row['wholesale_price'] : (float)$row['price'],
            'unit' => $row['unit'] ? $row['unit'] : 'pcs',
            'stock' => (float)$row['stock']
        ]
    ]);
} else {
    // Attempt Online Lookup via OpenFoodFacts API
    $url = "https://world.openfoodfacts.org/api/v0/product/" . urlencode($barcode) . ".json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SuperPOS_System/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $apiResponse = curl_exec($ch);
    curl_close($ch);
    
    $onlineData = json_decode($apiResponse, true);
    $onlineName = "";
    
    if (isset($onlineData['status']) && $onlineData['status'] === 1 && !empty($onlineData['product']['product_name'])) {
        $onlineName = trim($onlineData['product']['product_name']);
    } else {
        // Fallback to UPCItemDB
        $url2 = "https://api.upcitemdb.com/prod/trial/lookup?upc=" . urlencode($barcode);
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $url2);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 2);
        $apiResponse2 = curl_exec($ch2);
        curl_close($ch2);
        
        $onlineData2 = json_decode($apiResponse2, true);
        if (isset($onlineData2['items']) && count($onlineData2['items']) > 0 && !empty($onlineData2['items'][0]['title'])) {
            $onlineName = trim($onlineData2['items'][0]['title']);
        }
    }
    
    if (!empty($onlineName)) {
        $tenant_id = $_SESSION['tenant_id'] ?? 1;
        $branch_id = $_SESSION['branch_id'] ?? 1;
        
        // Auto-add to products table
        $insStmt = $conn->prepare("INSERT INTO products (name, barcode, price, purchase_price, stock, unit, status, tenant_id, branch_id) VALUES (?, ?, 0, 0, 100, 'pcs', 'active', ?, ?)");
        $insStmt->bind_param("ssii", $onlineName, $barcode, $tenant_id, $branch_id);
        
        if ($insStmt->execute()) {
            $newId = $insStmt->insert_id;
            echo json_encode([
                'success' => true,
                'is_new_online' => true,
                'product' => [
                    'id' => (int)$newId,
                    'name' => $onlineName,
                    'retail_price' => 0,
                    'wholesale_price' => 0,
                    'unit' => 'pcs',
                    'stock' => 100
                ]
            ]);
            $insStmt->close();
            $stmt->close();
            exit();
        }
    }

    echo json_encode(['success' => false, 'message' => 'Product not found.']);
}
$stmt->close();
?>
