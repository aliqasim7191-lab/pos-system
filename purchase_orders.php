<?php
include 'includes/db.php';
include 'includes/header.php';

if ($user_role === 'cashier') {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_po') {
        $supplier_id = intval($_POST['supplier_id']);
        $expected_date = $conn->real_escape_string($_POST['expected_date']);
        $notes = $conn->real_escape_string($_POST['notes']);
        $products = $_POST['products'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $costs = $_POST['costs'] ?? [];
        
        if ($supplier_id > 0 && !empty($products)) {
            $conn->begin_transaction();
            try {
                $total_amount = 0;
                $stmt = $conn->prepare("INSERT INTO purchase_orders (supplier_id, branch_id, expected_date, notes, tenant_id) VALUES (?, ?, ?, ?, {$_SESSION['tenant_id']})");
                $stmt->bind_param("iiss", $supplier_id, $current_branch_id, $expected_date, $notes);
                $stmt->execute();
                $po_id = $stmt->insert_id;
                $stmt->close();
                
                $itemStmt = $conn->prepare("INSERT INTO purchase_order_items (po_id, product_id, quantity, unit_cost, tenant_id) VALUES (?, ?, ?, ?, {$_SESSION['tenant_id']})");
                
                for ($i=0; $i < count($products); $i++) {
                    $pid = intval($products[$i]);
                    $qty = floatval($quantities[$i]);
                    $cost = floatval($costs[$i]);
                    if ($pid > 0 && $qty > 0) {
                        $total_amount += ($qty * $cost);
                        $itemStmt->bind_param("iidd", $po_id, $pid, $qty, $cost);
                        $itemStmt->execute();
                    }
                }
                $itemStmt->close();
                
                $conn->query("UPDATE purchase_orders SET total_amount = $total_amount WHERE id = $po_id");
                $conn->commit();
                $message = "Purchase Order #$po_id created successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error creating PO: " . $e->getMessage();
            }
        } else {
            $error = "Please select a supplier and add at least one product.";
        }
    } elseif ($action === 'update_status') {
        $po_id = intval($_POST['po_id']);
        $status = $conn->real_escape_string($_POST['status']);
        
        $poQ = $conn->query("SELECT * FROM purchase_orders WHERE id = $po_id AND branch_id = $current_branch_id");
        if ($poQ && $po = $poQ->fetch_assoc()) {
            if ($po['status'] !== 'received' && $status === 'received') {
                // Convert PO to actual Purchase & update stock
                $conn->begin_transaction();
                try {
                    $conn->query("UPDATE purchase_orders SET status = 'received' WHERE id = $po_id");
                    
                    // Create purchase record
                    $pStmt = $conn->prepare("INSERT INTO purchases (supplier_id, branch_id, user_id, total_amount, status, tenant_id) VALUES (?, ?, ?, ?, 'received', {$_SESSION['tenant_id']})");
                    $uid = $_SESSION['user_id'] ?? 1;
                    $pStmt->bind_param("iiid", $po['supplier_id'], $current_branch_id, $uid, $po['total_amount']);
                    $pStmt->execute();
                    $pur_id = $pStmt->insert_id;
                    $pStmt->close();
                    
                    // Move items and update stock
                    $piQ = $conn->query("SELECT * FROM purchase_order_items WHERE po_id = $po_id");
                    $piStmt = $conn->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, cost_price, tenant_id) VALUES (?, ?, ?, ?, {$_SESSION['tenant_id']})");
                    $stkStmt = $conn->prepare("UPDATE products SET stock = stock + ?, purchase_price = ? WHERE id = ?");
                    
                    while ($item = $piQ->fetch_assoc()) {
                        $piStmt->bind_param("iidd", $pur_id, $item['product_id'], $item['quantity'], $item['unit_cost']);
                        $piStmt->execute();
                        
                        $stkStmt->bind_param("ddi", $item['quantity'], $item['unit_cost'], $item['product_id']);
                        $stkStmt->execute();
                    }
                    $piStmt->close();
                    $stkStmt->close();
                    
                    // Update supplier balance
                    $conn->query("UPDATE suppliers SET outstanding_payable = outstanding_payable + ".$po['total_amount']." WHERE id = ".$po['supplier_id']);
                    
                    $conn->commit();
                    $message = "PO #$po_id marked as received. Stock updated and Purchase #$pur_id generated.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error converting PO: " . $e->getMessage();
                }
            } else {
                $conn->query("UPDATE purchase_orders SET status = '$status' WHERE id = $po_id");
                $message = "PO Status updated.";
            }
        }
    }
}
?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h2>Purchase Orders (POs)</h2>
    <p>Manage international/bulk supplier orders.</p>
</div>

<?php if($message): ?>
    <div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $message; ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div style="background: var(--danger); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $error; ?></div>
<?php endif; ?>

<div style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <!-- Create PO Form -->
    <div style="flex: 1; min-width: 350px; background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem;">Create New PO</h3>
        <form method="POST" id="poForm">
            <input type="hidden" name="action" value="create_po">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight:500;">Supplier</label>
                <select name="supplier_id" required style="width:100%; padding:0.8rem; border-radius:6px; border:1px solid #cbd5e1;">
                    <option value="">-- Select Supplier --</option>
                    <?php
                    $sq = $conn->query("SELECT id, name FROM suppliers WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY name");
                    while($s = $sq->fetch_assoc()) {
                        echo "<option value='{$s['id']}'>{$s['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight:500;">Expected Delivery</label>
                <input type="date" name="expected_date" required style="width:100%; padding:0.8rem; border-radius:6px; border:1px solid #cbd5e1;">
            </div>
            
            <h4 style="margin: 1.5rem 0 0.5rem 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem;">Order Items</h4>
            <div id="po-items-container">
                <div class="po-item" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items:center;">
                    <select name="products[]" required style="flex:2; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e1;">
                        <option value="">Product</option>
                        <?php
                        $pq = $conn->query("SELECT id, name, purchase_price FROM products WHERE branch_id = $current_branch_id ORDER BY name");
                        $prods = [];
                        while($p = $pq->fetch_assoc()) {
                            $prods[] = $p;
                            echo "<option value='{$p['id']}' data-cost='{$p['purchase_price']}'>{$p['name']}</option>";
                        }
                        ?>
                    </select>
                    <input type="number" name="quantities[]" step="0.01" placeholder="Qty" required style="flex:1; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e1;">
                    <input type="number" name="costs[]" step="0.01" placeholder="Cost" required style="flex:1; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e1;">
                </div>
            </div>
            <button type="button" class="btn" onclick="addPoItem()" style="background:#e2e8f0; color:#475569; width:100%; margin-bottom:1rem;">+ Add Another Item</button>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight:500;">Notes (Terms, Instructions)</label>
                <textarea name="notes" rows="3" style="width:100%; padding:0.8rem; border-radius:6px; border:1px solid #cbd5e1;"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem; border-radius:6px;">Generate Purchase Order</button>
        </form>
    </div>

    <!-- PO List -->
    <div style="flex: 2; min-width: 400px; background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem;">Recent Purchase Orders</h3>
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 0.75rem;">PO #</th>
                    <th style="padding: 0.75rem;">Supplier</th>
                    <th style="padding: 0.75rem;">Expected</th>
                    <th style="padding: 0.75rem;">Total</th>
                    <th style="padding: 0.75rem;">Status</th>
                    <th style="padding: 0.75rem; text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pos = $conn->query("
                    SELECT po.*, s.name as supplier_name 
                    FROM purchase_orders po
                    JOIN suppliers s ON po.supplier_id = s.id
                    WHERE po.tenant_id = {$_SESSION['tenant_id']} AND po.branch_id = $current_branch_id
                    ORDER BY po.po_date DESC LIMIT 30
                ");
                if ($pos && $pos->num_rows > 0) {
                    while($po = $pos->fetch_assoc()) {
                        echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                        echo "<td style='padding: 0.75rem; font-weight:bold;'>PO-".str_pad($po['id'], 4, '0', STR_PAD_LEFT)."</td>";
                        echo "<td style='padding: 0.75rem;'>".htmlspecialchars($po['supplier_name'])."</td>";
                        echo "<td style='padding: 0.75rem;'>".($po['expected_date'] ? date('d M Y', strtotime($po['expected_date'])) : '-')."</td>";
                        echo "<td style='padding: 0.75rem; font-weight:bold;'>$".number_format($po['total_amount'], 2)."</td>";
                        
                        $badgeBg = $po['status'] == 'received' ? '#dcfce7' : ($po['status'] == 'draft' ? '#f1f5f9' : ($po['status'] == 'sent' ? '#dbeafe' : '#fef9c3'));
                        $badgeCo = $po['status'] == 'received' ? '#166534' : ($po['status'] == 'draft' ? '#475569' : ($po['status'] == 'sent' ? '#1e40af' : '#a16207'));
                        echo "<td style='padding: 0.75rem;'><span style='background:{$badgeBg};color:{$badgeCo};padding:2px 6px;border-radius:4px;font-size:0.8rem;'>".strtoupper($po['status'])."</span></td>";
                        
                        echo "<td style='padding: 0.75rem; text-align:right;'>";
                        if ($po['status'] !== 'received' && $po['status'] !== 'cancelled') {
                            echo "<form method='POST' style='display:inline;' onsubmit=\"return confirm('Mark as RECEIVED? This will generate a Purchase record and add stock.');\">
                                    <input type='hidden' name='action' value='update_status'>
                                    <input type='hidden' name='po_id' value='{$po['id']}'>
                                    <input type='hidden' name='status' value='received'>
                                    <button type='submit' style='background:#10b981; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer; font-size:0.8rem;'>Mark Received</button>
                                  </form>";
                        }
                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='padding: 1rem; text-align:center;'>No Purchase Orders found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const productsOptions = `<?php
    foreach($prods as $p) {
        echo "<option value='{$p['id']}' data-cost='{$p['purchase_price']}'>".addslashes($p['name'])."</option>";
    }
?>`;

function addPoItem() {
    const container = document.getElementById('po-items-container');
    const div = document.createElement('div');
    div.className = 'po-item';
    div.style.cssText = 'display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items:center;';
    div.innerHTML = `
        <select name="products[]" required style="flex:2; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e1;">
            <option value="">Product</option>
            ${productsOptions}
        </select>
        <input type="number" name="quantities[]" step="0.01" placeholder="Qty" required style="flex:1; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e1;">
        <input type="number" name="costs[]" step="0.01" placeholder="Cost" required style="flex:1; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e1;">
        <button type="button" onclick="this.parentElement.remove()" style="background:#ef4444; color:white; border:none; border-radius:4px; padding:0.5rem; cursor:pointer;">X</button>
    `;
    container.appendChild(div);
}

// Auto-fill cost when product selected
document.getElementById('po-items-container').addEventListener('change', function(e) {
    if (e.target.name === 'products[]') {
        const option = e.target.options[e.target.selectedIndex];
        const costInput = e.target.parentElement.querySelector('input[name="costs[]"]');
        if (option && option.dataset.cost) {
            costInput.value = option.dataset.cost;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
