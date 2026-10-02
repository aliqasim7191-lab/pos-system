<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$current_branch_id = $_SESSION['branch_id'] ?? 1;
include 'includes/db.php';
include 'includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_damage'])) {
    $productId = intval($_POST['product_id']);
    $qty = floatval($_POST['quantity']);
    $reason = $_POST['reason'];
    $userId = $_SESSION['user_id'];
    $supplierId = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    
    // Get cost price or retail price to log the loss
    $stmt = $conn->prepare("SELECT price, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $lossAmount = $row['price'] * $qty;
        
        if ($row['stock'] >= $qty) {
            // Deduct stock
            $update = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $update->bind_param("di", $qty, $productId);
            $update->execute();
            $update->close();
            
            // Log damage
            $insert = $conn->prepare("INSERT INTO damaged_stock (product_id, user_id, supplier_id, quantity, loss_amount, reason, tenant_id) VALUES (?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
            $insert->bind_param("iiidss", $productId, $userId, $supplierId, $qty, $lossAmount, $reason);
            $insert->execute();
            $insert->close();
            
            // Deduct from business value / record expense so it balances books
            // If returning to a supplier, we still deduct the stock value, but tag it differently
            $supplierName = "Unknown Supplier";
            if ($supplierId) {
                $sQ = $conn->query("SELECT name FROM suppliers WHERE id = $supplierId");
                if ($sQ && $sData = $sQ->fetch_assoc()) $supplierName = $sData['name'];
            }
            
            $expenseDesc = $supplierId ? "Returned to Supplier ($supplierName): $reason" : "Damaged Stock: $reason";
            $expenseCat = $supplierId ? "Supplier Return" : "Stock Loss";
            
            $todayStr = date('Y-m-d');
            $exp = $conn->prepare("INSERT INTO expenses (notes, amount, category, user_id, expense_date, is_cleared, tenant_id) VALUES (?, ?, ?, ?, ?, 0, {$_SESSION['tenant_id']})");
            $exp->bind_param("sdsis", $expenseDesc, $lossAmount, $expenseCat, $userId, $todayStr);
            $exp->execute();
            $exp->close();
            
            $message = "<div class='alert' style='background:#dcfce7; color:#15803d; padding:1rem; border-radius:8px;'>Stock damaged logged successfully. Inventory updated.</div>";
        } else {
            $message = "<div class='alert' style='background:#fee2e2; color:#b91c1c; padding:1rem; border-radius:8px;'>Error: Not enough stock to log this damage.</div>";
        }
    }
    $stmt->close();
}

if (isset($_GET['delete'])) {
    if (!isset($isAdmin) || !$isAdmin) {
        $message = "<div class='alert' style='background:#fee2e2; color:#b91c1c; padding:1rem; border-radius:8px;'>Error: Only admins can delete records.</div>";
    } else {
        $delId = intval($_GET['delete']);
        $q = $conn->prepare("SELECT product_id, quantity, reason, loss_amount, supplier_id FROM damaged_stock WHERE id = ?");
        $q->bind_param("i", $delId);
        $q->execute();
        $res = $q->get_result();
        if ($row = $res->fetch_assoc()) {
            $qty = $row['quantity'];
            $pid = $row['product_id'];
            
            // Return stock
            $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $pid");
            
            // Remove from damaged_stock
            $conn->query("DELETE FROM damaged_stock WHERE id = $delId");
            
            // Delete associated expense
            $supplierName = "Unknown Supplier";
            if ($row['supplier_id']) {
                $sQ = $conn->query("SELECT name FROM suppliers WHERE id = " . $row['supplier_id']);
                if ($sQ && $sData = $sQ->fetch_assoc()) $supplierName = $sData['name'];
            }
            $expenseDesc = $row['supplier_id'] ? "Returned to Supplier ($supplierName): {$row['reason']}" : "Damaged Stock: {$row['reason']}";
            
            $delExp = $conn->prepare("DELETE FROM expenses WHERE notes = ? AND amount = ? AND is_cleared = 0 LIMIT 1");
            $delExp->bind_param("sd", $expenseDesc, $row['loss_amount']);
            $delExp->execute();
            
            $message = "<div class='alert' style='background:#dcfce7; color:#15803d; padding:1rem; border-radius:8px;'>Entry deleted. Stock restored and expense reversed.</div>";
        }
    }
}

if (isset($_GET['clear'])) {
    if (!isset($isAdmin) || !$isAdmin) {
        $message = "<div class='alert' style='background:#fee2e2; color:#b91c1c; padding:1rem; border-radius:8px;'>Error: Only admins can clear records.</div>";
    } else {
        $clearId = intval($_GET['clear']);
        $conn->query("UPDATE damaged_stock SET is_cleared = 1 WHERE id = $clearId");
        $message = "<div class='alert' style='background:#dcfce7; color:#15803d; padding:1rem; border-radius:8px;'>Entry moved to history.</div>";
    }
}

$productsQ = $conn->query("SELECT id, name, stock FROM products WHERE stock > 0 ORDER BY name ASC");
$suppliersQ = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
$historyQ = $conn->query("SELECT d.*, p.name as product_name, u.username, s.name as supplier_name FROM damaged_stock d JOIN products p ON d.product_id = p.id JOIN users u ON d.user_id = u.id LEFT JOIN suppliers s ON d.supplier_id = s.id WHERE d.is_cleared = 0 ORDER BY d.id DESC LIMIT 50");
?>

<div style="padding: 2rem;">
    <h2>🗑️ Damage & Loss Control</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Log broken, expired, or lost items to keep inventory and accounting accurate.</p>
    
    
    
    <?php echo $message; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        
        <!-- Log Form -->
        <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3>Report Damaged Stock</h3>
            <br>
            <form method="POST">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Select Product</label>
                    <select name="product_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                        <option value="">-- Choose Product --</option>
                        <?php while($p = $productsQ->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (Stock: <?php echo floatval($p['stock']); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Quantity Lost/Damaged</label>
                    <input type="number" step="0.01" name="quantity" min="0.01" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Return To Supplier (Optional)</label>
                    <select name="supplier_id" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                        <option value="">-- No Supplier (Internal Loss) --</option>
                        <?php while($s = $suppliersQ->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Reason (e.g., Expired, Broken)</label>
                    <input type="text" name="reason" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
                
                <button type="submit" name="log_damage" class="btn" style="width: 100%; padding: 1rem; background: var(--danger); color: white; border: none; font-size: 1.1rem; border-radius: 8px;">Log Damage & Remove Stock</button>
            </form>
        </div>
        
        <!-- History Table -->
        <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">Recent Loss & Returns</h3>
                <div class="no-print" style="display: flex; gap: 0.5rem;">
                    <a href="damaged_stock_history.php" class="btn" style="background: #e2e8f0; color: #475569; padding: 0.4rem 1rem; border: none; border-radius: 4px; text-decoration: none;">📚 View History</a>
                    <button type="button" onclick="window.print()" class="btn" style="background: var(--primary-color); color: white; padding: 0.4rem 1rem; border: none; border-radius: 4px; cursor: pointer;">🖨️ Print</button>
                    <button type="button" onclick="downloadPDF()" class="btn" style="background: #059669; color: white; padding: 0.4rem 1rem; border: none; border-radius: 4px; cursor: pointer;">📄 PDF</button>
                </div>
            </div>
            
            <div class="print-area">
                <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Date</th>
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Product</th>
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Supplier</th>
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Qty</th>
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Loss ($)</th>
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Reason</th>
                        <?php if(isset($isAdmin) && $isAdmin): ?>
                        <th class="no-print" style="padding: 0.75rem; text-align: right; border-bottom: 2px solid var(--border-color);">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if($historyQ && $historyQ->num_rows > 0): ?>
                        <?php while($row = $historyQ->fetch_assoc()): ?>
                        <tr>
                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); font-size: 0.85rem;"><?php echo date('M d, Y H:i', strtotime($row['logged_at'])); ?></td>
                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); font-weight: 500;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); color: #0284c7;">
                                <?php echo htmlspecialchars($row['supplier_name'] ?: '—'); ?>
                            </td>
                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); color: var(--danger); font-weight: bold;">-<?php echo floatval($row['quantity']); ?></td>
                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">$<?php echo number_format($row['loss_amount'], 2); ?></td>
                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.9rem;"><?php echo htmlspecialchars($row['reason']); ?></td>
                            <?php if(isset($isAdmin) && $isAdmin): ?>
                            <td class="no-print" style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: right; white-space: nowrap;">
                                <a href="return_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" style="color: #059669; text-decoration: none; font-weight: bold; margin-right: 10px;">📄 Receipt</a>
                                <a href="?clear=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this from the active list? It will be saved in History.')" style="color: var(--danger); text-decoration: none; font-weight: bold; margin-right: 10px;">❌ Delete</a>
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('UNDO MISTAKE: Revert this stock back to inventory and delete expense? (Warning: This completely deletes the record)')" style="color: #f59e0b; text-decoration: none; font-weight: bold;">🔄 Revert</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="padding: 1rem; text-align: center;">No damaged stock recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div> <!-- End Print Area -->
        </div>
        
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .main-nav, .main-header { display: none !important; }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.querySelector('.print-area');
        const opt = {
            margin:       0.5,
            filename:     'Damaged_Stock_Report.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 4, useCORS: true },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        
        const btn = document.querySelector('button[onclick="downloadPDF()"]');
        if(btn) btn.innerText = "⏳...";
        
        window.scrollTo(0,0);
        
        html2pdf().set(opt).from(element).save().then(() => {
            if(btn) btn.innerText = "📄 PDF";
        });
    }
</script>

<?php include 'includes/footer.php'; ?>



