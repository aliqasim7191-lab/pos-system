<?php
include 'includes/db.php';
include 'includes/header.php';

// Only admins or managers can transfer stock
if ($user_role === 'cashier') {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Cashiers cannot transfer stock.</p></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $del_id = intval($_POST['transfer_id']);
    $conn->query("UPDATE stock_transfers SET is_deleted = 1 WHERE id = $del_id");
    $message = "Transfer moved to history (soft deleted).";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'transfer') {
    $product_id = intval($_POST['product_id']);
    $to_branch = intval($_POST['to_branch']);
    $qty = floatval($_POST['quantity']);

    if ($to_branch === $current_branch_id) {
        $error = "Cannot transfer to the same branch.";
    } elseif ($qty <= 0) {
        $error = "Invalid quantity.";
    } else {
        // Check source product
        $srcQ = $conn->query("SELECT * FROM products WHERE id = $product_id AND branch_id = $current_branch_id");
        if ($srcQ && $srcQ->num_rows > 0) {
            $srcP = $srcQ->fetch_assoc();
            if ($srcP['stock'] >= $qty) {
                
                // Deduct from source
                $conn->query("UPDATE products SET stock = stock - $qty WHERE id = $product_id");
                
                // Check if target branch has this product (match by barcode or name)
                $barcode = $conn->real_escape_string($srcP['barcode']);
                $name = $conn->real_escape_string($srcP['name']);
                
                $tgtQ = $conn->query("SELECT id FROM products WHERE branch_id = $to_branch AND (barcode = '$barcode' OR name = '$name') LIMIT 1");
                
                if ($tgtQ && $tgtQ->num_rows > 0) {
                    $tgtP = $tgtQ->fetch_assoc();
                    $tgt_id = $tgtP['id'];
                    $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $tgt_id");
                } else {
                    // Create product in target branch
                    $pp = $srcP['purchase_price'];
                    $p = $srcP['price'];
                    $wp = $srcP['wholesale_price'];
                    $img = $conn->real_escape_string($srcP['image']);
                    $unit = $conn->real_escape_string($srcP['unit']);
                    $cat = $conn->real_escape_string($srcP['category']);
                    $exp = $srcP['expiry_date'] ? "'".$srcP['expiry_date']."'" : 'NULL';
                    
                    $conn->query("INSERT INTO products (name, purchase_price, price, wholesale_price, stock, image, unit, barcode, category, branch_id, expiry_date, tenant_id) VALUES ('$name', $pp, $p, $wp, $qty, '$img', '$unit', '$barcode', '$cat', $to_branch, $exp, {$_SESSION['tenant_id']})");
                }
                
                // Log transfer
                $conn->query("INSERT INTO stock_transfers (product_id, from_branch, to_branch, quantity, tenant_id) VALUES ($product_id, $current_branch_id, $to_branch, $qty, {$_SESSION['tenant_id']})");
                
                $message = "Successfully transferred $qty of $name to destination branch.";
            } else {
                $error = "Not enough stock in your branch.";
            }
        } else {
            $error = "Product not found.";
        }
    }
}
?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h2>Stock Transfers</h2>
    <p>Move inventory between branches.</p>
</div>

<?php if($message): ?>
    <div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div style="background: var(--danger); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <!-- Transfer Form -->
    <div style="flex: 1; min-width: 300px; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem;">New Transfer</h3>
        <form method="POST">
            <input type="hidden" name="action" value="transfer">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Select Product (Your Branch)</label>
                <select name="product_id" required style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <option value="">-- Select Product --</option>
                    <?php
                    $pq = $conn->query("SELECT id, name, stock FROM products WHERE branch_id = $current_branch_id AND stock > 0 ORDER BY name ASC");
                    while($pr = $pq->fetch_assoc()) {
                        echo "<option value='{$pr['id']}'>{$pr['name']} (Stock: {$pr['stock']})</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Destination Branch</label>
                <select name="to_branch" required style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <option value="">-- Select Branch --</option>
                    <?php
                    $bq = $conn->query("SELECT id, name FROM branches WHERE id != $current_branch_id ORDER BY name ASC");
                    while($br = $bq->fetch_assoc()) {
                        echo "<option value='{$br['id']}'>{$br['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Transfer Quantity</label>
                <input type="number" step="0.01" name="quantity" required min="0.01" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="Amount to send">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; border-radius: 8px;">Transfer Stock</button>
        </form>
    </div>
    
    <!-- Transfer History -->
    <div style="flex: 2; min-width: 400px; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Transfer History</h3>
            <div>
                <a href="transfers.php" class="btn" style="background: <?php echo !isset($_GET['history']) ? 'var(--primary-color)' : '#e2e8f0'; ?>; color: <?php echo !isset($_GET['history']) ? 'white' : '#475569'; ?>; padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">Active</a>
                <a href="transfers.php?history=1" class="btn" style="background: <?php echo isset($_GET['history']) ? 'var(--primary-color)' : '#e2e8f0'; ?>; color: <?php echo isset($_GET['history']) ? 'white' : '#475569'; ?>; padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px; font-size: 0.9rem; margin-left: 0.5rem;">Deleted History</a>
            </div>
        </div>
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 0.75rem;">Date</th>
                    <th style="padding: 0.75rem;">Product</th>
                    <th style="padding: 0.75rem;">Direction</th>
                    <th style="padding: 0.75rem;">Qty</th>
                    <th style="padding: 0.75rem;">Status</th>
                    <th style="padding: 0.75rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Show transfers IN or OUT of current branch
                $view_history = isset($_GET['history']) ? 1 : 0;
                $hq = $conn->query("
                    SELECT t.*, p.name as product_name, b1.name as from_name, b2.name as to_name 
                    FROM stock_transfers t
                    JOIN products p ON t.product_id = p.id
                    JOIN branches b1 ON t.from_branch = b1.id
                    JOIN branches b2 ON t.to_branch = b2.id
                    WHERE (t.from_branch = $current_branch_id OR t.to_branch = $current_branch_id)
                    AND t.is_deleted = $view_history
                    ORDER BY t.transfer_date DESC LIMIT 50
                ");
                if ($hq && $hq->num_rows > 0) {
                    while($hr = $hq->fetch_assoc()) {
                        $isOut = ($hr['from_branch'] == $current_branch_id);
                        $direction = $isOut ? "<span style='color:var(--danger);'>OUT ➔ {$hr['to_name']}</span>" : "<span style='color:var(--success);'>IN ⬅ {$hr['from_name']}</span>";
                        
                        $actions = "<a href='print_transfer.php?id=" . $hr['id'] . "' target='_blank' class='btn' style='background:#3b82f6; color:white; padding: 4px 8px; border-radius: 4px; text-decoration:none; font-size: 0.8rem; display:inline-block;'>Print / PDF</a>";
                        if (!$view_history) {
                            $actions .= "<form method='POST' style='display:inline; margin-left: 5px;' onsubmit=\"return confirm('Are you sure you want to delete this transfer? It will be moved to history.');\">
                                            <input type='hidden' name='action' value='delete'>
                                            <input type='hidden' name='transfer_id' value='".$hr['id']."'>
                                            <button type='submit' class='btn' style='background:#ef4444; color:white; padding: 4px 8px; border-radius: 4px; border:none; cursor:pointer; font-size: 0.8rem;'>Delete</button>
                                         </form>";
                        }
                        
                        echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                        echo "<td style='padding: 0.75rem;'>" . date('d M Y h:i A', strtotime($hr['transfer_date'])) . "</td>";
                        echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($hr['product_name']) . "</td>";
                        echo "<td style='padding: 0.75rem;'>" . $direction . "</td>";
                        echo "<td style='padding: 0.75rem; font-weight:bold;'>" . $hr['quantity'] . "</td>";
                        echo "<td style='padding: 0.75rem;'><span style='background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:0.8rem;'>" . strtoupper($hr['status']) . "</span></td>";
                        echo "<td style='padding: 0.75rem; text-align: right;'>" . $actions . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='padding: 1rem; text-align:center; color:var(--text-muted);'>No transfers found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
