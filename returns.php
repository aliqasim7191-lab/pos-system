<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$current_branch_id = $_SESSION['branch_id'] ?? 1;
include 'includes/db.php';

// Ensure is_cleared and branch_id exist in returns table
$colCheck = $conn->query("SHOW COLUMNS FROM returns LIKE 'is_cleared'");
if ($colCheck && $colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE returns ADD COLUMN is_cleared TINYINT(1) NOT NULL DEFAULT 0");
}
$colCheck2 = $conn->query("SHOW COLUMNS FROM returns LIKE 'branch_id'");
if ($colCheck2 && $colCheck2->num_rows == 0) {
    $conn->query("ALTER TABLE returns ADD COLUMN branch_id INT NOT NULL DEFAULT 1");
}

$sale = null;
$saleItems = [];
$message = '';

if (isset($_POST['search_sale'])) {
    $saleId = intval($_POST['sale_id']);
    $saleQ = $conn->query("SELECT * FROM sales WHERE id = $saleId");
    if ($saleQ && $saleQ->num_rows > 0) {
        $sale = $saleQ->fetch_assoc();
        
        $itemsQ = $conn->query("SELECT si.*, p.name, p.stock FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = $saleId");
        while ($row = $itemsQ->fetch_assoc()) {
            $saleItems[] = $row;
        }
    } else {
        $message = "<div class='alert' style='background:#fee2e2; color:#b91c1c; padding:1rem; border-radius:8px;'>Receipt #$saleId not found.</div>";
    }
}

if (isset($_POST['process_return'])) {
    $saleId = intval($_POST['sale_id']);
    $returnQtys = $_POST['return_qty'] ?? [];
    
    $totalRefund = 0;
    $returnedItems = [];
    
    foreach ($returnQtys as $itemId => $qty) {
        $qty = floatval($qty);
        if ($qty > 0) {
            // Get item price and product ID
            $stmt = $conn->prepare("SELECT product_id, price FROM sale_items WHERE id = ?");
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $refund = $row['price'] * $qty;
                $totalRefund += $refund;
                $returnedItems[] = [
                    'product_id' => $row['product_id'],
                    'qty' => $qty,
                    'refund' => $refund
                ];
            }
            $stmt->close();
        }
    }
    
    if ($totalRefund > 0) {
        $userId = $_SESSION['user_id'];
        $tenantId = $_SESSION['tenant_id'] ?? 1;
        $branchId = $_SESSION['branch_id'] ?? 1;
        
        // Create return record
        $stmt = $conn->prepare("INSERT INTO returns (sale_id, user_id, total_refund, is_cleared, branch_id, tenant_id) VALUES (?, ?, ?, 0, ?, ?)");
        $stmt->bind_param("iidii", $saleId, $userId, $totalRefund, $branchId, $tenantId);
        $stmt->execute();
        $returnId = $stmt->insert_id;
        $stmt->close();
        
        // Process items
        foreach ($returnedItems as $item) {
            // Log return item
            $stmt = $conn->prepare("INSERT INTO return_items (return_id, product_id, quantity, refund_amount, tenant_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddi", $returnId, $item['product_id'], $item['qty'], $item['refund'], $tenantId);
            $stmt->execute();
            $stmt->close();
            
            // Restore stock
            $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->bind_param("di", $item['qty'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Log the return in expenses so cash drawer expected cash decreases
        $expenseNotes = "Customer Return for Receipt #$saleId";
        $expenseCat = "Return Refund";
        $expenseDate = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, expense_date, notes, is_cleared, branch_id, tenant_id) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
        $stmt->bind_param("isdssii", $userId, $expenseCat, $totalRefund, $expenseDate, $expenseNotes, $branchId, $tenantId);
        $stmt->execute();
        $stmt->close();
        
        $message = "<div class='alert' style='background:#dcfce7; color:#15803d; padding:1rem; border-radius:8px;'>Return Processed! $" . number_format($totalRefund, 2) . " refunded and stock updated.</div>";
    }
}

include 'includes/header.php';
?>

<div style="padding: 2rem;">
    <h2>↩️ Process Return / Exchange</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Scan or enter the receipt number to find the sale and process returned items.</p>
    
    <?php echo $message; ?>
    
    <!-- Professional Search Card -->
    <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 2rem;">
        <form method="POST" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1e293b;"><i class="fa fa-receipt text-muted"></i> Receipt Number</label>
                <input type="number" name="sale_id" required value="<?php echo isset($_POST['sale_id']) ? htmlspecialchars($_POST['sale_id']) : ''; ?>" placeholder="e.g. 1042" style="width: 100%; padding: 0.8rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1.1rem; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <button type="submit" name="search_sale" style="padding: 0.9rem 2rem; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fa fa-search"></i> Find Receipt
            </button>
        </form>
    </div>

    <?php if ($sale): ?>
    <!-- Professional Returns Card -->
    <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #e2e8f0; padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
            <div>
                <h3 style="margin: 0; color: #0f172a; font-size: 1.5rem;"><i class="fa fa-shopping-bag text-primary"></i> Sale #<?php echo $sale['id']; ?></h3>
                <p style="color: #64748b; margin: 0.5rem 0 0 0;"><i class="fa fa-calendar-alt"></i> <?php echo date('M d, Y h:i A', strtotime($sale['created_at'])); ?></p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; color: #64748b; font-size: 0.9rem; text-transform: uppercase; font-weight: bold;">Total Amount</p>
                <h2 style="margin: 0; color: #10b981; font-size: 2rem;">$<?php echo number_format($sale['total_amount'], 2); ?></h2>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="sale_id" value="<?php echo $sale['id']; ?>">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 2rem;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #cbd5e1; border-top-left-radius: 8px; color: #475569; font-weight: 600;">Product Name</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 2px solid #cbd5e1; color: #475569; font-weight: 600;">Price Sold At</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 2px solid #cbd5e1; color: #475569; font-weight: 600;">Qty Sold</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 2px solid #cbd5e1; border-top-right-radius: 8px; color: #ef4444; font-weight: 600;">Qty to Return</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($saleItems as $item): ?>
                        <tr style="transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem; border-bottom: 1px solid #e2e8f0; font-weight: 500; color: #1e293b;">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e2e8f0; text-align: center; color: #64748b; font-weight: 500;">
                                $<?php echo number_format($item['price'], 2); ?>
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: 700; color: #334155; font-size: 1.1rem;">
                                <?php echo floatval($item['quantity']); ?>
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e2e8f0; text-align: center;">
                                <input type="number" name="return_qty[<?php echo $item['id']; ?>]" min="0" max="<?php echo floatval($item['quantity']); ?>" step="0.01" value="0" style="width: 90px; padding: 0.6rem; text-align: center; border: 2px solid #cbd5e1; border-radius: 6px; font-weight: bold; font-size: 1.1rem; color: #ef4444; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#ef4444'" onblur="this.style.borderColor='#cbd5e1'">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="background: #fff1f2; border: 1px solid #fecdd3; padding: 1.5rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #be123c;"><i class="fa fa-info-circle"></i> Return Policy</h4>
                    <p style="margin: 0; color: #9f1239; font-size: 0.9rem;">Processing this return will automatically restore product stock and log a cash deduction.</p>
                </div>
                <button type="submit" name="process_return" style="padding: 1rem 2.5rem; font-size: 1.1rem; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(239, 68, 68, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.4)';" onclick="return confirm('Confirm Return: Are you sure you want to refund this amount and restore the stock?');">
                    <i class="fa fa-undo"></i> Process Return & Refund
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>



