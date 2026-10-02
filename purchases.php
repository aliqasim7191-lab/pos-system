<?php
include 'includes/db.php';
include 'includes/header.php';

// Branch Filters
$bF_WHERE = $isAdmin ? " WHERE tenant_id = {$_SESSION['tenant_id']}" : " WHERE tenant_id = {$_SESSION['tenant_id']} AND branch_id = $current_branch_id";
$bF_AND = $isAdmin ? " AND tenant_id = {$_SESSION['tenant_id']}" : " AND tenant_id = {$_SESSION['tenant_id']} AND branch_id = $current_branch_id";


if (!$isAdmin) {
    echo "<div style='padding: 2rem;'><h2>Access Denied</h2><p>You do not have permission to view this page.</p></div>";
    include 'includes/footer.php';
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_purchase') {
    $supplierId = intval($_POST['supplier_id']);
    $amountPaid = floatval($_POST['amount_paid']);
    $itemNames = $_POST['item_names'] ?? [];
    $costPrices = $_POST['cost_prices'] ?? [];
    $retailPrices = $_POST['retail_prices'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $purchaseUnits = $_POST['purchase_units'] ?? [];
    
    $totalAmount = 0;
    for ($i = 0; $i < count($itemNames); $i++) {
        $totalAmount += floatval($costPrices[$i]) * floatval($quantities[$i]);
    }
    
    $conn->begin_transaction();
    try {
        // Insert Purchase
        $userId = $_SESSION['user_id'] ?? 1;
        $stmt = $conn->prepare("INSERT INTO purchases (supplier_id, user_id, total_amount, amount_paid, status, tenant_id) VALUES (?, ?, ?, ?, 'received', {$_SESSION['tenant_id']})");
        $stmt->bind_param("iidd", $supplierId, $userId, $totalAmount, $amountPaid);
        $stmt->execute();
        $purchaseId = $stmt->insert_id;
        $stmt->close();
        
        // Insert Items and Update Stock
        $stmtItem = $conn->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, cost_price, unit, tenant_id) VALUES (?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
        $stmtUpdateProd = $conn->prepare("UPDATE products SET stock = stock + ?, purchase_price = ?, price = ? WHERE id = ?");
        
        for ($i = 0; $i < count($itemNames); $i++) {
            $name = trim($itemNames[$i]);
            $qty = floatval($quantities[$i]);
            $cost = floatval($costPrices[$i]);
            $retail = isset($retailPrices[$i]) && $retailPrices[$i] !== '' ? floatval($retailPrices[$i]) : ($cost * 1.4);
            $pUnit = isset($purchaseUnits[$i]) ? $purchaseUnits[$i] : 'pcs';
            
            if (empty($name)) continue;

            // Check if product exists
            $checkProd = $conn->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
            $checkProd->bind_param("s", $name);
            $checkProd->execute();
            $resProd = $checkProd->get_result();
            
            if ($rowProd = $resProd->fetch_assoc()) {
                $pId = $rowProd['id'];
                // Update existing stock, cost, and retail price
                $stmtUpdateProd->bind_param("dddi", $qty, $cost, $retail, $pId);
                $stmtUpdateProd->execute();
            } else {
                // Create new product
                $wholesalePrice = $cost * 1.15; // Default 15% margin for wholesale
                
                $insertProd = $conn->prepare("INSERT INTO products (name, purchase_price, price, wholesale_price, stock, unit, category, status, tenant_id) VALUES (?, ?, ?, ?, ?, ?, 'Uncategorized', 'active', {$_SESSION['tenant_id']})");
                $insertProd->bind_param("sdddds", $name, $cost, $retail, $wholesalePrice, $qty, $pUnit);
                $insertProd->execute();
                $pId = $insertProd->insert_id;
                $insertProd->close();
            }
            $checkProd->close();
            
            $stmtItem->bind_param("iidds", $purchaseId, $pId, $qty, $cost, $pUnit);
            $stmtItem->execute();
        }
        $stmtItem->close();
        $stmtUpdateProd->close();
        
        // Update Supplier Payable (Total - Paid)
        $payableIncrease = $totalAmount - $amountPaid;
        if ($payableIncrease != 0) {
            $stmtSupp = $conn->prepare("UPDATE suppliers SET outstanding_payable = outstanding_payable + ? WHERE id = ?");
            $stmtSupp->bind_param("di", $payableIncrease, $supplierId);
            $stmtSupp->execute();
            $stmtSupp->close();
        }
        
        $conn->commit();
        $message = "Purchase Order created and stock updated successfully!";
    } catch(Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive_all') {
    $conn->query("UPDATE purchases SET is_cleared = 1 WHERE is_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
    echo "<script>window.location.href='purchases.php';</script>";
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_purchase') {
    $delId = intval($_POST['id']);
    // Optional: Delete purchase items first (they have tenant_id)
    $conn->query("DELETE FROM purchase_items WHERE purchase_id = $delId AND tenant_id = {$_SESSION['tenant_id']}");
    $conn->query("DELETE FROM purchases WHERE id = $delId AND tenant_id = {$_SESSION['tenant_id']}");
    echo "<script>window.location.href='purchases.php" . (isset($_GET['history']) && $_GET['history'] == 1 ? "?history=1" : "") . "';</script>";
    exit;
}

// Fetch lists for forms
$suppliers = [];
$resSupp = $conn->query("SELECT id, name FROM suppliers WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY name ASC");
if ($resSupp) {
    while($row = $resSupp->fetch_assoc()) $suppliers[] = $row;
}

$products = [];
$resProd = $conn->query("SELECT id, name, price FROM products WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY name ASC");
if ($resProd) {
    while($row = $resProd->fetch_assoc()) $products[] = $row;
}

// Fetch recent purchases
$showHistory = isset($_GET['history']) && $_GET['history'] == '1';

$purchases = [];
if ($showHistory) {
    $resPurch = $conn->query("SELECT p.id, p.total_amount, p.amount_paid, p.created_at, s.name as supplier_name, u.username as cashier_name,
                                     (SELECT GROUP_CONCAT(CONCAT(pi.quantity, ' ', pi.unit, ' ', pr.name) SEPARATOR ', ') 
                                      FROM purchase_items pi JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = p.id) as items_summary
                              FROM purchases p 
                              LEFT JOIN suppliers s ON p.supplier_id = s.id 
                              LEFT JOIN users u ON p.user_id = u.id
                              WHERE p.tenant_id = {$_SESSION['tenant_id']}
                              ORDER BY p.created_at DESC LIMIT 50");
} else {
    $resPurch = $conn->query("SELECT p.id, p.total_amount, p.amount_paid, p.created_at, s.name as supplier_name, u.username as cashier_name,
                                     (SELECT GROUP_CONCAT(CONCAT(pi.quantity, ' ', pi.unit, ' ', pr.name) SEPARATOR ', ') 
                                      FROM purchase_items pi JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = p.id) as items_summary
                              FROM purchases p 
                              LEFT JOIN suppliers s ON p.supplier_id = s.id 
                              LEFT JOIN users u ON p.user_id = u.id
                              WHERE p.is_cleared = 0 AND p.tenant_id = {$_SESSION['tenant_id']}
                              ORDER BY p.created_at DESC LIMIT 50");
}

if ($resPurch) {
    while($row = $resPurch->fetch_assoc()) $purchases[] = $row;
}
?>

<div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2><?php echo $showHistory ? 'All Past Purchase Orders' : 'Active Purchase Orders'; ?></h2>
        <p><?php echo $showHistory ? 'Showing historical supplier payments.' : 'Record stock purchases. Cleared at EOD.'; ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items:center;">
        <?php if($showHistory): ?>
            <a href="purchases.php" class="btn btn-primary" style="text-decoration: none; width: auto !important; border-radius: 6px !important; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; justify-content: center;">&larr; View Active Purchases</a>
        <?php else: ?>
            <a href="purchases.php?history=1" class="btn" style="background: #e2e8f0; color: #334155; text-decoration: none; width: auto !important; border-radius: 6px !important; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#cbd5e1'" onmouseout="this.style.background='#e2e8f0'">📚 View All History</a>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to clear/archive the current purchase list? They will still be visible in History.');">
                <input type="hidden" name="action" value="archive_all">
                <button type="submit" class="btn" style="background: #f59e0b; color: white; width: auto !important; border-radius: 6px !important; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; justify-content: center; border: none; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">📦 Archive List</button>
            </form>
            <button class="btn btn-primary" onclick="document.getElementById('addPurchaseModal').style.display='flex'" style="width: auto !important; border-radius: 6px !important; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; transition: all 0.2s;">+ Create Purchase Order</button>
        <?php endif; ?>
    </div>
</div>

<?php if($message): ?>
    <div style="background: <?php echo strpos($message, 'Error') !== false ? 'var(--danger)' : 'var(--success)'; ?>; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;">Recent Purchases</h3>
        <?php if($showHistory): ?>
        <div class="no-print" style="display: flex; gap: 0.5rem;">
            <button type="button" onclick="window.print()" class="btn" style="background: var(--primary-color); color: white; padding: 0.4rem 1rem; border: none; border-radius: 4px; cursor: pointer;">🖨️ Print List</button>
            <button type="button" onclick="downloadPurchasesPDF()" class="btn" style="background: #059669; color: white; padding: 0.4rem 1rem; border: none; border-radius: 4px; cursor: pointer;">📄 PDF List</button>
        </div>
        <?php endif; ?>
    </div>
    <div class="print-area">
    <table class="data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">PO #</th>
                <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Date</th>
                <th style="padding: 0.65rem 0.5rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Supplier</th>
                <th style="padding: 0.65rem 0.5rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Cashier</th>
                <th style="padding: 0.65rem 0.5rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Items Purchased</th>
                <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Total Amount</th>
                <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Amount Paid</th>
                <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Balance Due</th>
                <th class="no-print" style="padding: 0.65rem 0.4rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($purchases as $p): ?>
                <tr style="border-bottom: 1px solid var(--border-color); <?php if($p['total_amount'] == 0) echo 'background: #f0fdf4;'; ?>">
                    <td style="padding: 0.6rem 0.4rem; text-align: center; white-space: nowrap; font-size: 0.82rem;">
                        <?php if($p['total_amount'] == 0): ?>
                            <span style="background: var(--success); color: white; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.72rem;">PAYMENT</span>
                        <?php else: ?>
                            PO-<?php echo str_pad($p['id'], 5, "0", STR_PAD_LEFT); ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.6rem 0.4rem; text-align: center; white-space: nowrap; font-size: 0.82rem; color: #475569;"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                    <td style="padding: 0.6rem 0.5rem; font-weight: 600; color: #0f172a; white-space: nowrap; font-size: 0.85rem;"><?php echo htmlspecialchars($p['supplier_name'] ?: 'Walk-in'); ?></td>
                    <td style="padding: 0.6rem 0.5rem; color: #475569; white-space: nowrap; font-size: 0.82rem;"><?php echo htmlspecialchars($p['cashier_name'] ?: 'Admin'); ?></td>
                    <td style="padding: 0.6rem 0.5rem; font-size: 0.82rem; color: var(--text-muted); max-width: 170px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($p['items_summary'] ?? ''); ?>">
                        <?php 
                            if($p['total_amount'] == 0) {
                                echo '<span style="color:var(--success); font-weight:500;">Supplier Balance Settlement</span>';
                            } else {
                                echo htmlspecialchars($p['items_summary'] ?: 'No items'); 
                            }
                        ?>
                    </td>
                    <td style="padding: 0.6rem 0.5rem; text-align: right; font-weight: 700; color: #0f172a; white-space: nowrap; font-size: 0.85rem;">
                        <?php echo $p['total_amount'] == 0 ? '-' : '$'.number_format($p['total_amount'], 2); ?>
                    </td>
                    <td style="padding: 0.6rem 0.5rem; text-align: right; color: var(--success); white-space: nowrap; font-size: 0.85rem; font-weight: <?php echo $p['total_amount'] == 0 ? 'bold' : '600'; ?>;">
                        $<?php echo number_format($p['amount_paid'], 2); ?>
                    </td>
                    <td style="padding: 0.6rem 0.5rem; text-align: right; color: var(--danger); white-space: nowrap; font-size: 0.85rem; font-weight: 600;">
                        <?php 
                            if ($p['total_amount'] == 0) {
                                echo '-';
                            } else {
                                echo '$' . number_format($p['total_amount'] - $p['amount_paid'], 2); 
                            }
                        ?>
                    </td>
                    <td class="no-print" style="padding: 0.6rem 0.4rem; text-align: right; white-space: nowrap;">
                        <div style="display:flex; gap:0.35rem; justify-content: flex-end; align-items: center;">
                            <?php if($p['total_amount'] > 0): ?>
                                <a href="purchase_receipt.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn" style="display:inline-flex; align-items:center; gap:0.3rem; background:white; color:#0f172a; border:1px solid #cbd5e1; padding:0.3rem 0.6rem; font-size:0.78rem; font-weight:600; border-radius:6px; text-decoration:none; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:all 0.2s;" onmouseover="this.style.borderColor='#94a3b8'; this.style.backgroundColor='#f8fafc';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.backgroundColor='white';">
                                    <svg style="width:14px; height:14px; color:#475569;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Receipt
                                </a>
                            <?php endif; ?>
                            <form method="POST" onsubmit="return confirm('Delete this purchase? This cannot be undone.');" style="margin:0; display:inline-block;">
                                <input type="hidden" name="action" value="delete_purchase">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn" style="background:#ef4444; color:#fff; padding: 0.3rem 0.5rem; font-size: 0.78rem; border:none; border-radius:6px; cursor:pointer;" title="Delete">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($purchases)): ?>
                <tr><td colspan="9" style="padding: 2rem; text-align:center;">No purchases recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div> <!-- End print-area -->
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .main-nav, .main-header, .dashboard-header { display: none !important; }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPurchasesPDF() {
        const element = document.querySelector('.print-area');
        const opt = {
            margin:       0.5,
            filename:     'Purchase_History.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 4, useCORS: true },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
        };
        
        const btn = document.querySelector('button[onclick="downloadPurchasesPDF()"]');
        if(btn) btn.innerText = "⏳...";
        
        window.scrollTo(0,0);
        
        html2pdf().set(opt).from(element).save().then(() => {
            if(btn) btn.innerText = "📄 PDF List";
        });
    }
</script>

<!-- Create PO Modal -->
<div id="addPurchaseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 700px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Create Purchase Order</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_purchase">
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Select Supplier *</label>
                <select name="supplier_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                    <option value="">-- Choose Supplier --</option>
                    <?php foreach($suppliers as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <h4 style="margin-bottom: 0.5rem;">Purchase Items</h4>
            
            <datalist id="product-list">
                <?php foreach($products as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['name']); ?>">
                <?php endforeach; ?>
            </datalist>

            <div id="po-items-container">
                <div class="po-item-row" style="display:flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;">
                    <input type="text" list="product-list" name="item_names[]" placeholder="Select or Type New Product" required style="flex:2; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                    <input type="number" step="0.01" name="cost_prices[]" placeholder="Cost ($)" required style="flex:1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;" oninput="calcPOTotal()">
                    <input type="number" step="0.01" name="retail_prices[]" placeholder="Sale Price ($)" required style="flex:1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                    <input type="number" step="0.01" name="quantities[]" placeholder="Qty" required style="flex:1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;" oninput="calcPOTotal()">
                    <select name="purchase_units[]" style="flex:1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                        <option value="pcs">Pieces</option>
                        <option value="kg">Kg</option>
                        <option value="liter">Liter</option>
                        <option value="box">Box</option>
                        <option value="packet">Packet</option>
                        <option value="carton">Carton</option>
                    </select>
                    <button type="button" class="btn" style="background:var(--danger); color:white; padding:0.5rem;" onclick="this.parentElement.remove(); calcPOTotal();">X</button>
                </div>
            </div>
            <button type="button" class="btn" style="background:#e2e8f0; margin-bottom: 1.5rem;" onclick="addPORow()">+ Add Another Item</button>
            
            <div style="display:flex; justify-content:space-between; align-items:center; border-top: 2px dashed var(--border-color); padding-top: 1rem; margin-bottom: 1.5rem;">
                <span style="font-weight:bold; font-size: 1.2rem;">Total PO Amount:</span>
                <span id="po-total-display" style="font-weight:bold; font-size: 1.5rem; color: var(--primary-color);">$0.00</span>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Amount Paid to Supplier Now ($) *</label>
                <input type="number" step="0.01" name="amount_paid" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;" value="0">
                <small style="color:var(--text-muted);">Any unpaid amount will be added to the supplier's outstanding payable balance.</small>
            </div>

            <div style="display:flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 2; padding: 1rem; font-size: 1.1rem;">Submit Purchase Order</button>
                <button type="button" class="btn" style="flex: 1; background:#e2e8f0; color:var(--text-main);" onclick="document.getElementById('addPurchaseModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function addPORow() {
    let row = document.querySelector('.po-item-row').cloneNode(true);
    row.querySelectorAll('input').forEach(inp => inp.value = '');
    row.querySelector('select').value = '';
    document.getElementById('po-items-container').appendChild(row);
}

function calcPOTotal() {
    let rows = document.querySelectorAll('.po-item-row');
    let total = 0;
    rows.forEach(row => {
        let cost = parseFloat(row.querySelector('input[name="cost_prices[]"]').value) || 0;
        let qty = parseFloat(row.querySelector('input[name="quantities[]"]').value) || 0;
        total += (cost * qty);
    });
    document.getElementById('po-total-display').innerText = '$' + total.toFixed(2);
}
</script>

<?php include 'includes/footer.php'; ?>
