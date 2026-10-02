<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'includes/db.php';
$isAdmin = ($_SESSION['role'] === 'admin');

// Handle Quick Purchase (Full Purchase Order from Alert)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_purchase'])) {
    $prod_id     = intval($_POST['product_id']);
    $supplier_id = intval($_POST['supplier_id']);
    $qty         = floatval($_POST['qty']);
    $cost_price  = floatval($_POST['cost_price']);
    $sell_price  = floatval($_POST['sell_price']);
    $amount_paid = floatval($_POST['amount_paid']);
    $pay_method  = $conn->real_escape_string($_POST['pay_method']);
    $tid         = intval($_SESSION['tenant_id']);
    $uid         = intval($_SESSION['user_id']);
    $bid         = intval($_SESSION['branch_id'] ?? 1);
    $total       = $qty * $cost_price;
    $status      = 'received';

    if ($prod_id > 0 && $qty > 0 && $cost_price > 0) {
        // 1. Create purchase record
        $supp_val = ($supplier_id > 0) ? $supplier_id : "NULL";
        $conn->query("INSERT INTO purchases (supplier_id, user_id, total_amount, amount_paid, status, branch_id, tenant_id) 
                      VALUES ($supp_val, $uid, $total, $amount_paid, '$status', $bid, $tid)");
        $purchase_id = $conn->insert_id;

        // 2. Create purchase item
        $conn->query("INSERT INTO purchase_items (purchase_id, product_id, quantity, cost_price, tenant_id) 
                      VALUES ($purchase_id, $prod_id, $qty, $cost_price, $tid)");

        // 3. Update product stock + buy price + sell price (if provided)
        if ($sell_price > 0) {
            $conn->query("UPDATE products SET stock = stock + $qty, purchase_price = $cost_price, price = $sell_price WHERE id = $prod_id AND tenant_id = $tid");
        } else {
            $conn->query("UPDATE products SET stock = stock + $qty, purchase_price = $cost_price WHERE id = $prod_id AND tenant_id = $tid");
        }

        // 4. Update supplier outstanding payable if not fully paid
        $remaining = $total - $amount_paid;
        if ($supplier_id > 0 && $remaining > 0) {
            $conn->query("UPDATE suppliers SET outstanding_payable = outstanding_payable + $remaining WHERE id = $supplier_id AND tenant_id = $tid");
        }

        // 5. Get product name for success msg
        $pname = $conn->query("SELECT name, stock, price FROM products WHERE id = $prod_id")->fetch_assoc();
        
        $redirect = "alerts.php";
        if(!empty($_POST['redirect_back'])) {
            $redirect = $_POST['redirect_back'];
            // Remove existing success messages from URL
            $redirect = preg_replace('/([&?])purchase_done=[^&]*&?/', '$1', $redirect);
            $redirect = preg_replace('/([&?])pname=[^&]*&?/', '$1', $redirect);
            $redirect = preg_replace('/([&?])newstock=[^&]*&?/', '$1', $redirect);
            $redirect = preg_replace('/([&?])newprice=[^&]*&?/', '$1', $redirect);
            $redirect = rtrim($redirect, '?&');
            $separator = strpos($redirect, '?') !== false ? '&' : '?';
        } else {
            $separator = '?';
        }

        header("Location: " . $redirect . $separator . "purchase_done=1&pname=" . urlencode($pname['name']) . "&newstock=" . $pname['stock'] . "&newprice=" . $pname['price']);
        exit();
    }
}

// Handle dismiss
if (isset($_GET['dismiss'])) {
    $id = $_GET['dismiss'];
    if (strpos($id, 'db_') === 0) {
        $dbId = (int)str_replace('db_', '', $id);
        $conn->query("UPDATE alerts SET is_read = 1 WHERE id = $dbId");
    } else {
        if (!isset($_SESSION['dismissed_alerts'])) $_SESSION['dismissed_alerts'] = [];
        $_SESSION['dismissed_alerts'][] = $id;
    }
    header("Location: alerts.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Alerts - Point of Sale</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: #f8fafc;">
    <?php include 'includes/header.php'; ?>
    
    <div class="container" style="max-width: 1000px; margin-top: 2rem;">
        <h1 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            🔔 System Alerts
        </h1>

        <?php if(isset($_GET['purchase_done'])): ?>
        <div style="background:#f0fdf4; border:1px solid #86efac; color:#15803d; padding:1rem 1.5rem; border-radius:10px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
            ✅ Purchase Recorded! <strong><?php echo htmlspecialchars($_GET['pname'] ?? ''); ?></strong> — Stock updated to <strong><?php echo intval($_GET['newstock'] ?? 0); ?> units</strong>. Purchase order saved & supplier account updated!
        </div>
        <?php endif; ?>
        <?php if(isset($_GET['updated'])): ?>
        <div style="background:#f0fdf4; border:1px solid #86efac; color:#15803d; padding:1rem 1.5rem; border-radius:10px; margin-bottom:1.5rem; font-weight:600;">
            ✅ Stock successfully updated!
        </div>
        <?php endif; ?>

        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 1rem; font-weight: 600; color: #475569;">Type</th>
                        <th style="padding: 1rem; font-weight: 600; color: #475569;">Details</th>
                        <th style="padding: 1rem; font-weight: 600; color: #475569; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hasAlerts = false;
                    $dismissed = isset($_SESSION['dismissed_alerts']) ? $_SESSION['dismissed_alerts'] : [];

                    // DB Alerts
                    $dbAlerts = $conn->query("SELECT * FROM alerts WHERE is_read = 0 AND tenant_id = {$_SESSION['tenant_id']} ORDER BY created_at DESC");
                    if($dbAlerts) {
                        while($r = $dbAlerts->fetch_assoc()) {
                            $hasAlerts = true;
                            echo '<tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'white\'">';
                            echo '<td style="padding: 1rem;"><span style="background: #e0f2fe; color: #0284c7; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.8rem; font-weight: 600;">System</span></td>';
                            echo '<td style="padding: 1rem; color: #334155;">'.htmlspecialchars($r['message']).'</td>';
                            echo '<td style="padding: 1rem; text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">';
                            if(!empty($r['action_link'])) echo '<a href="'.htmlspecialchars($r['action_link']).'" class="btn" style="background:#eff6ff; color:#2563eb; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">Take Action</a>';
                            echo '<a href="alerts.php?dismiss=db_'.$r['id'].'" class="btn" style="background:#fee2e2; color:#ef4444; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">Dismiss</a>';
                            echo '</td></tr>';
                        }
                    }

                    // Low Stock
                    $suppliers = $conn->query("SELECT id, name FROM suppliers WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY name");
                    $supplierList = [];
                    if ($suppliers) while($s = $suppliers->fetch_assoc()) $supplierList[] = $s;

                    $stockQ = $conn->query("SELECT id, name, stock, purchase_price, price FROM products WHERE stock <= 10 AND tenant_id = {$_SESSION['tenant_id']}");
                    if ($stockQ) {
                        while($r = $stockQ->fetch_assoc()) {
                            if (!in_array('ls_' . $r['id'], $dismissed)) {
                                $hasAlerts = true;
                                $stockColor = $r['stock'] <= 0 ? '#dc2626' : ($r['stock'] <= 5 ? '#ea580c' : '#ca8a04');
                                $modalId = 'modal_' . $r['id'];
                                echo '<tr style="border-bottom: 1px solid #f1f5f9;" onmouseover="this.style.background=\'#fff7ed\'" onmouseout="this.style.background=\'white\'">';
                                echo '<td style="padding: 1rem; vertical-align:middle;"><span style="background:#fee2e2; color:#ef4444; padding:0.2rem 0.6rem; border-radius:99px; font-size:0.8rem; font-weight:700;">⚠️ Low Stock</span></td>';
                                echo '<td style="padding: 1rem; color:#334155; vertical-align:middle;">';
                                echo '<strong>' . htmlspecialchars($r['name']) . '</strong>';
                                echo ' &mdash; <span style="color:' . $stockColor . '; font-weight:700;">' . $r['stock'] . ' units left</span>';
                                echo ' &mdash; <span style="color:#64748b; font-size:0.85rem;">Buy: Rs.' . number_format($r['purchase_price'],2) . ' | Sell: Rs.' . number_format($r['price'],2) . '</span>';
                                echo '</td>';
                                echo '<td style="padding:1rem; text-align:right; white-space:nowrap; vertical-align:middle;">';
                                echo '<button onclick="openModal(\'' . $modalId . '\', ' . $r['id'] . ', \'' . addslashes($r['name']) . '\', ' . ($r['purchase_price'] ?? 0) . ', ' . ($r['price'] ?? 0) . ')" style="background:#16a34a; color:white; border:none; padding:0.5rem 1rem; border-radius:8px; font-size:0.85rem; font-weight:700; cursor:pointer; margin-right:0.5rem;">📦 Quick Purchase</button>';
                                echo '<a href="alerts.php?dismiss=ls_' . $r['id'] . '" class="btn" style="background:#f1f5f9; color:#64748b; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">Dismiss</a>';
                                echo '</td></tr>';
                            }
                        }
                    }

                    // Supplier Payables
                    $suppQ = $conn->query("SELECT id, name, outstanding_payable FROM suppliers WHERE outstanding_payable > 0 AND tenant_id = {$_SESSION['tenant_id']}");
                    if ($suppQ) {
                        while($r = $suppQ->fetch_assoc()) {
                            if (!in_array('supp_' . $r['id'], $dismissed)) {
                                $hasAlerts = true;
                                echo '<tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'white\'">';
                                echo '<td style="padding: 1rem;"><span style="background: #fef3c7; color: #d97706; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.8rem; font-weight: 600;">Payable</span></td>';
                                echo '<td style="padding: 1rem; color: #334155;">Payment due to <strong>'.htmlspecialchars($r['name']).'</strong>: $'.number_format($r['outstanding_payable'],2).'</td>';
                                echo '<td style="padding: 1rem; text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">';
                                echo '<a href="suppliers.php" class="btn" style="background:#eff6ff; color:#2563eb; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">Pay Now</a>';
                                echo '<a href="alerts.php?dismiss=supp_'.$r['id'].'" class="btn" style="background:#f1f5f9; color:#64748b; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">Dismiss</a>';
                                echo '</td></tr>';
                            }
                        }
                    }

                    // Customer Receivables
                    $custQ = $conn->query("SELECT id, name, outstanding_balance FROM customers WHERE outstanding_balance > 0 AND tenant_id = {$_SESSION['tenant_id']}");
                    if ($custQ) {
                        while($r = $custQ->fetch_assoc()) {
                            if (!in_array('cust_' . $r['id'], $dismissed)) {
                                $hasAlerts = true;
                                echo '<tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'white\'">';
                                echo '<td style="padding: 1rem;"><span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.8rem; font-weight: 600;">Receivable</span></td>';
                                echo '<td style="padding: 1rem; color: #334155;">Collect from <strong>'.htmlspecialchars($r['name']).'</strong>: $'.number_format($r['outstanding_balance'],2).'</td>';
                                echo '<td style="padding: 1rem; text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">';
                                echo '<a href="customers.php" class="btn" style="background:#eff6ff; color:#2563eb; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">View Khata</a>';
                                echo '<a href="alerts.php?dismiss=cust_'.$r['id'].'" class="btn" style="background:#f1f5f9; color:#64748b; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">Dismiss</a>';
                                echo '</td></tr>';
                            }
                        }
                    }

                    // Today's Expenses
                    $expQ = $conn->query("SELECT id, amount, category FROM expenses WHERE DATE(created_at) = CURDATE() AND tenant_id = {$_SESSION['tenant_id']}");
                    if ($expQ) {
                        while($r = $expQ->fetch_assoc()) {
                            if (!in_array('exp_' . $r['id'], $dismissed)) {
                                $hasAlerts = true;
                                echo '<tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'white\'">';
                                echo '<td style="padding: 1rem;"><span style="background: #f3e8ff; color: #7e22ce; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.8rem; font-weight: 600;">Expense</span></td>';
                                echo '<td style="padding: 1rem; color: #334155;">Today\'s Expense: '.htmlspecialchars($r['category'] ?? 'N/A').' ($'.number_format($r['amount'],2).')</td>';
                                echo '<td style="padding: 1rem; text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">';
                                echo '<a href="expenses.php" class="btn" style="background:#eff6ff; color:#2563eb; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">View Expenses</a>';
                                echo '<a href="alerts.php?dismiss=exp_'.$r['id'].'" class="btn" style="background:#f1f5f9; color:#64748b; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:600;">Dismiss</a>';
                                echo '</td></tr>';
                            }
                        }
                    }

                    if(!$hasAlerts) {
                        echo '<tr><td colspan="3" style="padding: 2rem; text-align: center; color: #64748b; font-size: 1.1rem;">No alerts to display. Everything is up to date! 🎉</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

<!-- Quick Purchase Modal -->
<div id="quickPurchaseModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:16px; padding:2rem; width:100%; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,0.2); position:relative;">
        <button onclick="closeModal()" style="position:absolute; top:1rem; right:1rem; background:#f1f5f9; border:none; border-radius:50%; width:32px; height:32px; font-size:1.2rem; cursor:pointer; color:#64748b;">✕</button>
        <h3 style="color:#0f172a; margin:0 0 0.3rem 0;">📦 Quick Purchase Order</h3>
        <p id="modalProductName" style="color:#64748b; font-size:0.9rem; margin:0 0 1.5rem 0;"></p>

        <form method="POST">
            <input type="hidden" name="quick_purchase" value="1">
            <input type="hidden" name="product_id" id="modalProductId">

            <!-- Supplier -->
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">🏭 Supplier (Kin say khareed rahe hain)</label>
                <select name="supplier_id" required style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem;">
                    <option value="0">-- Walk-in / No Supplier --</option>
                    <?php foreach($supplierList ?? [] as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Qty + Cost Price + Sell Price -->
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">📦 Quantity</label>
                    <input type="number" name="qty" id="modalQty" min="1" step="0.01" required placeholder="e.g. 50" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">💰 Buy Price (Cost)</label>
                    <input type="number" name="cost_price" id="modalCostPrice" min="0" step="0.01" required placeholder="e.g. 150" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; box-sizing:border-box;" oninput="calcTotal()">
                </div>
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#10b981; display:block; margin-bottom:0.3rem;">🏷️ Sell Price (Retail)</label>
                    <input type="number" name="sell_price" id="modalSellPrice" min="0" step="0.01" placeholder="e.g. 200 (Optional)" style="width:100%; padding:0.6rem; border:1px solid #86efac; border-radius:8px; font-size:0.95rem; box-sizing:border-box; background:#f0fdf4;">
                </div>
            </div>

            <!-- Total display -->
            <div id="totalDisplay" style="background:#f0fdf4; border:1px solid #86efac; padding:0.8rem 1rem; border-radius:8px; margin-bottom:1rem; font-weight:700; color:#15803d; display:none;">
                Total Amount: Rs. <span id="totalAmt">0</span>
            </div>

            <!-- Payment -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">💳 Payment Method</label>
                    <select name="pay_method" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem;">
                        <option value="cash">💵 Cash</option>
                        <option value="bank">🏦 Bank Transfer</option>
                        <option value="cheque">📝 Cheque</option>
                        <option value="online">📱 Online</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">💵 Amount Paid (Abhi kitna diya)</label>
                    <input type="number" name="amount_paid" id="modalAmountPaid" min="0" step="0.01" placeholder="0 = Udhaar" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; box-sizing:border-box;">
                </div>
            </div>
            <p style="font-size:0.8rem; color:#94a3b8; margin:0 0 1rem 0;">ℹ️ Agar Amount Paid Total se kam ho, baaki raqam supplier ke account mein Udhaar (Payable) add ho jayegi.</p>

            <button type="submit" style="width:100%; background:linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; padding:0.9rem; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer;">
                ✅ Record Purchase & Update Stock
            </button>
        </form>
    </div>
</div>

<script>
function openModal(modalId, productId, productName, costPrice, sellPrice) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('modalProductName').textContent = 'Product: ' + productName;
    document.getElementById('modalCostPrice').value = costPrice || '';
    document.getElementById('modalSellPrice').value = sellPrice || '';
    document.getElementById('modalQty').value = '';
    document.getElementById('modalAmountPaid').value = '';
    document.getElementById('totalDisplay').style.display = 'none';
    document.getElementById('quickPurchaseModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('quickPurchaseModal').style.display = 'none';
}
function calcTotal() {
    var qty = parseFloat(document.getElementById('modalQty').value) || 0;
    var cost = parseFloat(document.getElementById('modalCostPrice').value) || 0;
    var total = qty * cost;
    if (total > 0) {
        document.getElementById('totalAmt').textContent = total.toLocaleString('en-PK', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('totalDisplay').style.display = 'block';
        document.getElementById('modalAmountPaid').placeholder = 'Max: ' + total.toFixed(2);
    }
}
document.getElementById('modalQty').addEventListener('input', calcTotal);
// Close modal if clicked outside
document.getElementById('quickPurchaseModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</html>
