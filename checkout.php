<?php
include 'includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['cart']) || empty($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit;
}

$cart = $data['cart'];
$discount = isset($data['discount']) ? floatval($data['discount']) : 0;
$customerId = !empty($data['customer_id']) ? intval($data['customer_id']) : null;
$customerName = isset($data['customer_name']) ? trim($data['customer_name']) : null;
$customerAddress = isset($data['customer_address']) ? trim($data['customer_address']) : null;
$takenBy = isset($data['taken_by']) ? trim($data['taken_by']) : null;
$paymentMethod = isset($data['payment_method']) ? $data['payment_method'] : 'cash';
$amountReceived = isset($data['amount_received']) ? floatval($data['amount_received']) : 0;
$changeReturned = isset($data['change_returned']) ? floatval($data['change_returned']) : 0;
$pointsUsed = isset($data['points_used']) ? intval($data['points_used']) : 0;
$totalAmount = 0;

// If a customer is selected, ALWAYS treat the sale as Khata (Credit)
if ($customerId) {
    $paymentMethod = 'credit';
}

// Security check: calculate server-side
$taxQ = $conn->query("SELECT setting_value FROM settings WHERE setting_key='tax_rate'");
$global_tax_rate = ($taxQ && $taxQ->num_rows > 0) ? floatval($taxQ->fetch_assoc()['setting_value']) : 0;

foreach($cart as &$item) {
    $totalAmount += $item['price'] * $item['quantity'];
    
    // Fetch product tax rate
    $prodId = isset($item['product_id']) ? intval($item['product_id']) : intval($item['id']);
    $item['tax_rate'] = $global_tax_rate; // default
    $ptQ = $conn->query("SELECT t.rate FROM products p LEFT JOIN tax_classes t ON p.tax_class_id = t.id WHERE p.id = $prodId");
    if ($ptQ && $r = $ptQ->fetch_assoc()) {
        if ($r['rate'] !== null) {
            $item['tax_rate'] = floatval($r['rate']);
        }
    }
}

$tax_amount = 0;
foreach($cart as $item) {
    $itemDiscRatio = $totalAmount > 0 ? (($item['price'] * $item['quantity']) / $totalAmount) : 0;
    $itemDiscount = $discount * $itemDiscRatio;
    $itemTotalAfterDisc = ($item['price'] * $item['quantity']) - $itemDiscount;
    if ($itemTotalAfterDisc > 0) {
        $tax_amount += $itemTotalAfterDisc * ($item['tax_rate'] / 100);
    }
}

$finalTotal = $totalAmount + $tax_amount - $discount;

if ($finalTotal < 0) $finalTotal = 0;

// For credit sales, amount received is usually 0 initially
if ($paymentMethod === 'credit') {
    $amountReceived = 0;
    $changeReturned = 0;
}

// -------------------------------------------------------------
// STOCK VALIDATION: Prevent sale if stock falls below 10
// -------------------------------------------------------------
foreach($cart as $item) {
    $prodId = isset($item['product_id']) ? intval($item['product_id']) : intval($item['id']);
    $varId = !empty($item['variation_id']) ? intval($item['variation_id']) : null;
    $qty = floatval($item['quantity']);
    
    if ($varId) {
        $stockQ = $conn->query("SELECT stock, variation_name as name FROM product_variations WHERE id = $varId LIMIT 1");
    } else {
        $stockQ = $conn->query("SELECT stock, name FROM products WHERE id = $prodId LIMIT 1");
    }
    
    if ($stockQ && $row = $stockQ->fetch_assoc()) {
        $currentStock = floatval($row['stock']);
        
        if ($currentStock < $qty) {
            echo json_encode(['success' => false, 'message' => "Cannot sell '{$row['name']}'. Not enough stock (Current: $currentStock, Requested: $qty)."]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Product/Variation not found."]);
        exit;
    }
}

$conn->begin_transaction();

try {
    // 1. Insert sale
    $branch_id = $_SESSION['branch_id'] ?? 1;

    $stmt = $conn->prepare("INSERT INTO sales (total_amount, discount, tax_amount, customer_id, customer_name, customer_address, taken_by, payment_method, amount_received, change_returned, branch_id, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
    $stmt->bind_param("dddissssddi", $finalTotal, $discount, $tax_amount, $customerId, $customerName, $customerAddress, $takenBy, $paymentMethod, $amountReceived, $changeReturned, $branch_id);
    $stmt->execute();
    $saleId = $stmt->insert_id;
    $stmt->close();

    // 2. Insert sale items and update stock
    $stmtItem = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, variation_id, quantity, price, cost_price, branch_id, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
    $stmtUpdateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    $stmtUpdateVarStock = $conn->prepare("UPDATE product_variations SET stock = stock - ? WHERE id = ?");

    foreach($cart as $item) {
        $cost = 0;
        $prodId = isset($item['product_id']) ? intval($item['product_id']) : intval($item['id']);
        $varId = !empty($item['variation_id']) ? intval($item['variation_id']) : null;
        
        $costQ = $conn->query("SELECT purchase_price FROM products WHERE id = $prodId LIMIT 1");
        if ($costQ && $row = $costQ->fetch_assoc()) {
            $cost = floatval($row['purchase_price']);
        }
        
        $stmtItem->bind_param("iiidddi", $saleId, $prodId, $varId, $item['quantity'], $item['price'], $cost, $branch_id);
        $stmtItem->execute();
        
        if ($varId) {
            $stmtUpdateVarStock->bind_param("di", $item['quantity'], $varId);
            $stmtUpdateVarStock->execute();
        } else {
            $stmtUpdateStock->bind_param("di", $item['quantity'], $prodId);
            $stmtUpdateStock->execute();
        }
    }
    
    $stmtItem->close();
    $stmtUpdateStock->close();
    $stmtUpdateVarStock->close();

    // 3. Update customer outstanding balance if it's a credit sale, and add loyalty points
    if ($customerId) {
        $loyaltyPointsEarned = floor($finalTotal / 10);
        
        if ($paymentMethod === 'credit') {
            // Update balance
            $stmtCust = $conn->prepare("UPDATE customers SET outstanding_balance = outstanding_balance + ?, points = points + ? - ? WHERE id = ?");
            $stmtCust->bind_param("diii", $finalTotal, $loyaltyPointsEarned, $pointsUsed, $customerId);
            $stmtCust->execute();
            $stmtCust->close();
            
            // Get new balance
            $newBalQ = $conn->query("SELECT outstanding_balance FROM customers WHERE id = $customerId LIMIT 1");
            $newBal = $newBalQ->fetch_assoc()['outstanding_balance'];
            
            // Insert into ledger (Udhaar)
            $desc = "Credit Sale - Invoice #$saleId";
            $stmtLedger = $conn->prepare("INSERT INTO customer_ledger (customer_id, type, sale_id, amount, balance_after, description, tenant_id) VALUES (?, 'sale', ?, ?, ?, ?, {$_SESSION['tenant_id']})");
            $stmtLedger->bind_param("iidds", $customerId, $saleId, $finalTotal, $newBal, $desc);
            $stmtLedger->execute();
            $stmtLedger->close();
        } else {
            // Cash/Card sale (Balance unchanged, but we record history in ledger)
            $stmtCust = $conn->prepare("UPDATE customers SET points = points + ? - ? WHERE id = ?");
            $stmtCust->bind_param("iii", $loyaltyPointsEarned, $pointsUsed, $customerId);
            $stmtCust->execute();
            $stmtCust->close();
            
            $newBalQ = $conn->query("SELECT outstanding_balance FROM customers WHERE id = $customerId LIMIT 1");
            $newBal = $newBalQ->fetch_assoc()['outstanding_balance'];
            
            $desc = ucfirst($paymentMethod) . " Sale - Invoice #$saleId";
            $stmtLedger = $conn->prepare("INSERT INTO customer_ledger (customer_id, type, sale_id, amount, balance_after, description, tenant_id) VALUES (?, 'cash_sale', ?, ?, ?, ?, {$_SESSION['tenant_id']})");

            $stmtLedger->bind_param("iidds", $customerId, $saleId, $finalTotal, $newBal, $desc);
            $stmtLedger->execute();
            $stmtLedger->close();
        }
    }

    $conn->commit();
    
    // Attempt to send email/SMS receipt
    include_once 'includes/mail_sms_helper.php';
    send_receipt($saleId, $conn);
    
    echo json_encode(['success' => true, 'sale_id' => $saleId]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
