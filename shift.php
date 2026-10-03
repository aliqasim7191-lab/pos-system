<?php
include 'includes/db.php';
include 'includes/header.php';

$userId = $_SESSION['user_id'];

// Check if there is ANY uncleared shift globally
$stmt = $conn->prepare("SELECT id, opened_at, opening_cash, status FROM shifts WHERE is_cleared = 0 AND branch_id = $current_branch_id LIMIT 1");
$stmt->execute();
$stmt->bind_result($shiftId, $openedAt, $openingCash, $shiftStatus);
$hasUnclearedShift = $stmt->fetch();
$hasOpenShift = ($hasUnclearedShift && $shiftStatus === 'open');
$stmt->close();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'open' && !$hasUnclearedShift) {
        $startingCash = floatval($_POST['starting_cash']);
        if ($startingCash < 0) $startingCash = 0;
        
        $stmt = $conn->prepare("INSERT INTO shifts (user_id, opening_cash, branch_id, tenant_id) VALUES (?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt->bind_param("idi", $userId, $startingCash, $current_branch_id);
        if ($stmt->execute()) {
            $message = "Shift opened successfully.";
            $hasUnclearedShift = true;
            $hasOpenShift = true;
            $shiftId = $stmt->insert_id;
            $openedAt = date('Y-m-d H:i:s');
            $openingCash = $startingCash;
        } else {
            $error = "Error opening shift.";
        }
    }
    elseif ($action === 'close' && $hasUnclearedShift) {
        $actualCash = floatval($_POST['actual_cash']);
        
        $stmt = $conn->prepare("SELECT SUM(amount_received) FROM sales WHERE created_at >= ? AND payment_method = 'cash' AND branch_id = $current_branch_id");
        $stmt->bind_param("s", $openedAt);
        $stmt->execute();
        $stmt->bind_result($cashSales);
        $stmt->fetch();
        $stmt->close();
        $cashSales = $cashSales ?: 0;
        
        $stmt = $conn->prepare("SELECT SUM(change_returned) FROM sales WHERE created_at >= ? AND payment_method = 'cash' AND branch_id = $current_branch_id");
        $stmt->bind_param("s", $openedAt);
        $stmt->execute();
        $stmt->bind_result($changeReturned);
        $stmt->fetch();
        $stmt->close();
        $changeReturned = $changeReturned ?: 0;
        
        $stmt = $conn->prepare("SELECT SUM(amount) FROM expenses WHERE created_at >= ? AND branch_id = $current_branch_id");
        $stmt->bind_param("s", $openedAt);
        $stmt->execute();
        $stmt->bind_result($exp);
        $stmt->fetch();
        $stmt->close();
        $exp = $exp ?: 0;

        $stmt = $conn->prepare("SELECT SUM(amount_paid) FROM purchases WHERE created_at >= ? AND branch_id = $current_branch_id");
        $stmt->bind_param("s", $openedAt);
        $stmt->execute();
        $stmt->bind_result($pur);
        $stmt->fetch();
        $stmt->close();
        $pur = $pur ?: 0;

        $stmt = $conn->prepare("SELECT SUM(amount) FROM customer_ledger WHERE created_at >= ? AND branch_id = $current_branch_id AND type IN ('payment_received', 'advance_deposit')");
        $stmt->bind_param("s", $openedAt);
        $stmt->execute();
        $stmt->bind_result($khataIn);
        $stmt->fetch();
        $stmt->close();
        $khataIn = $khataIn ?: 0;


        $expectedCash = $openingCash + $cashSales + $khataIn - $changeReturned - $exp - $pur;
        
        $stmt = $conn->prepare("UPDATE shifts SET closed_at = NOW(), closing_cash = ?, expected_cash = ?, status = 'closed' WHERE id = ?");
        $stmt->bind_param("ddi", $actualCash, $expectedCash, $shiftId);
        if ($stmt->execute()) {
            $hasOpenShift = false;
            $variance = $actualCash - $expectedCash;
            $message = "Shift closed. Expected: $" . number_format($expectedCash, 2) . " | Actual: $" . number_format($actualCash, 2) . " | Variance: $" . number_format($variance, 2);
        } else {
            $error = "Error closing shift.";
        }
        $stmt->close();
    }
}
?>

<div style="padding-left: 2.75rem; padding-right: 1.5rem; box-sizing: border-box; width: 100%;">
<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h2>Register & Shift Management</h2>
    <p>Manage your cash drawer and daily shifts.</p>
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

<div style="display: flex; gap: 2rem;">
    <!-- Shift Status Panel -->
    <div style="flex: 1; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); text-align: center;">
        <?php if($hasOpenShift): 
            // Calculate Current Shift Stats
            // 1. Cash Sales
            $stmt = $conn->prepare("SELECT SUM(amount_received), SUM(change_returned) FROM sales WHERE created_at >= ? AND payment_method = 'cash' AND branch_id = $current_branch_id");
            $stmt->bind_param("s", $openedAt);
            $stmt->execute();
            $stmt->bind_result($shiftCashReceived, $shiftChangeReturned);
            $stmt->fetch();
            $stmt->close();
            $shiftNetCashSales = ($shiftCashReceived ?: 0) - ($shiftChangeReturned ?: 0);
            
            // 2. Other Sales (Card/Bank)
            $stmt = $conn->prepare("SELECT SUM(total_amount) FROM sales WHERE created_at >= ? AND payment_method != 'cash'");
            $stmt->bind_param("s", $openedAt);
            $stmt->execute();
            $stmt->bind_result($shiftOtherSales);
            $stmt->fetch();
            $stmt->close();
            $shiftOtherSales = $shiftOtherSales ?: 0;

            $stmt = $conn->prepare("SELECT SUM(amount) FROM customer_ledger WHERE created_at >= ? AND branch_id = $current_branch_id AND type IN ('payment_received', 'advance_deposit')");
            $stmt->bind_param("s", $openedAt);
            $stmt->execute();
            $stmt->bind_result($shiftKhataIn);
            $stmt->fetch();
            $stmt->close();
            $shiftKhataIn = $shiftKhataIn ?: 0;


            // 3. Expenses
            $stmt = $conn->prepare("SELECT SUM(amount) FROM expenses WHERE created_at >= ? AND branch_id = $current_branch_id");
            $stmt->bind_param("s", $openedAt);
            $stmt->execute();
            $stmt->bind_result($shiftExpenses);
            $stmt->fetch();
            $stmt->close();
            $shiftExpenses = $shiftExpenses ?: 0;

            // 4. Supplier Payments (Purchases)
            $stmt = $conn->prepare("SELECT SUM(amount_paid) FROM purchases WHERE created_at >= ? AND branch_id = $current_branch_id");
            $stmt->bind_param("s", $openedAt);
            $stmt->execute();
            $stmt->bind_result($shiftPurchases);
            $stmt->fetch();
            $stmt->close();
            $shiftPurchases = $shiftPurchases ?: 0;

            // Removed Held Sales Logic
            
            $expectedCashNow = $openingCash + $shiftNetCashSales + $shiftKhataIn - $shiftExpenses - $shiftPurchases;
        ?>
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">🔓</div>
            <h3 style="color: var(--success); margin-bottom: 0.5rem;">Register is OPEN</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Opened at: <strong><?php echo date('h:i A', strtotime($openedAt)); ?></strong></p>
            
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; text-align: left; margin-bottom: 1.5rem; font-size: 0.95rem;">
                <h4 style="margin-bottom: 0.8rem; color: var(--text-main); border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">Shift Summary</h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Opening Cash:</span>
                    <strong style="color: var(--text-main);">$<?php echo number_format($openingCash, 2); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">+ Cash Sales (Net):</span>
                    <strong style="color: var(--success);">$<?php echo number_format($shiftNetCashSales, 2); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">+ Khata Payments Received:</span>
                    <strong style="color: var(--success);">+$<?php echo number_format($shiftKhataIn, 2); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">- Expenses Paid:</span>
                    <strong style="color: var(--danger);">-$<?php echo number_format($shiftExpenses, 2); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">- Supplier Payments:</span>
                    <strong style="color: var(--danger);">-$<?php echo number_format($shiftPurchases, 2); ?></strong>
                </div>
                <div style="border-top: 1px dashed #cbd5e1; margin: 0.5rem 0; padding-top: 0.5rem; display: flex; justify-content: space-between;">
                    <span style="color: var(--text-main); font-weight: bold;">Expected Cash in Drawer:</span>
                    <strong style="color: var(--primary-color); font-size: 1.1rem;">$<?php echo number_format($expectedCashNow, 2); ?></strong>
                </div>
                
                <div style="margin-top: 1rem; font-size: 0.85rem; color: #64748b;">
                    Other info: Card/Bank Sales ($<?php echo number_format($shiftOtherSales, 2); ?>)
                </div>
            </div>
            
            <p style="color: #059669; font-size: 1.2rem; font-weight: bold; margin-bottom: 1.5rem; background:#ecfdf5; padding:15px; border-radius:8px;">?3 Shift is currently OPEN and recording sales.</p>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Your shift will automatically be closed when the Admin runs the End of Day (Z-Report).</p>
            <a href="reports.php" style="width: 100%; display:block; padding: 1.2rem; font-size: 1.2rem; font-weight: bold; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; border: none; border-radius: 12px; cursor: pointer; text-decoration:none; box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 25px rgba(14, 165, 233, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 20px rgba(14, 165, 233, 0.3)';"><i class="fa fa-chart-line" style="margin-right:8px;"></i> View End of Day Reports</a>
        <?php elseif(isset($hasUnclearedShift) && $hasUnclearedShift && $shiftStatus === 'closed'): ?>
            <div style="font-size: 4rem; margin-bottom: 1rem;">⏳</div>
            <h3 style="color: #eab308; margin-bottom: 0.5rem;">Waiting for End of Day</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">The cash has been closed but the day hasn't ended. You must run End of Day before opening a new session.</p>
            <a href="end_of_day.php" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: bold; background: linear-gradient(135deg, #eab308, #ca8a04); color: white; border: none; border-radius: 12px; cursor: pointer; text-decoration: none; display: block; box-shadow: 0 10px 20px rgba(234, 179, 8, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 25px rgba(234, 179, 8, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 20px rgba(234, 179, 8, 0.3)';"><i class="fa fa-moon" style="margin-right:8px;"></i> Go to End of Day</a>
        <?php else: ?>
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔒</div>
            <h3 style="color: var(--danger); margin-bottom: 0.5rem;">Register is CLOSED</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">You must open the register to start making sales.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="open">
                <div style="text-align: left; margin-bottom: 1rem;">
                    <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Starting Cash ($)</label>
                    <input type="number" step="0.01" name="starting_cash" value="" placeholder="Enter opening cash..." required min="0" style="width: 100%; padding: 1.2rem; border: 2px solid #cbd5e1; border-radius: 12px; font-size: 1.5rem; text-align: center; color: #1e293b; outline: none; transition: 0.3s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 4px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                </div>
                <button type="submit" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: bold; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; border: none; border-radius: 12px; cursor: pointer; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 25px rgba(59, 130, 246, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 20px rgba(59, 130, 246, 0.3)';"><i class="fa fa-door-open" style="margin-right:8px;"></i> Open Register</button>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Shift History -->
    <div style="flex: 2; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Your Recent Shifts</h3>
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 0.5rem;">Date</th>
                    <th style="padding: 0.5rem;">Opened</th>
                    <th style="padding: 0.5rem;">Closed</th>
                    <th style="padding: 0.5rem;">Expected</th>
                    <th style="padding: 0.5rem;">Actual</th>
                    <th style="padding: 0.5rem;">Variance</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $histQ = $conn->prepare("SELECT opened_at, closed_at, expected_cash, closing_cash FROM shifts WHERE user_id = ? AND branch_id = $current_branch_id AND status = 'closed' ORDER BY closed_at DESC LIMIT 5");
                $histQ->bind_param("i", $userId);
                $histQ->execute();
                $histRes = $histQ->get_result();
                if ($histRes->num_rows > 0) {
                    while ($r = $histRes->fetch_assoc()) {
                        $variance = $r['closing_cash'] - $r['expected_cash'];
                        $vColor = $variance < 0 ? 'var(--danger)' : ($variance > 0 ? 'var(--success)' : 'inherit');
                        echo "<tr style='border-bottom: 1px solid var(--border-color); font-size: 0.9rem;'>";
                        echo "<td style='padding: 0.5rem;'>" . date('d M Y', strtotime($r['opened_at'])) . "</td>";
                        echo "<td style='padding: 0.5rem;'>" . date('h:i A', strtotime($r['opened_at'])) . "</td>";
                        echo "<td style='padding: 0.5rem;'>" . date('h:i A', strtotime($r['closed_at'])) . "</td>";
                        echo "<td style='padding: 0.5rem;'>$" . number_format($r['expected_cash'], 2) . "</td>";
                        echo "<td style='padding: 0.5rem;'>$" . number_format($r['closing_cash'], 2) . "</td>";
                        echo "<td style='padding: 0.5rem; color: $vColor; font-weight: bold;'>$" . number_format($variance, 2) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='padding: 1rem; text-align: center; color: var(--text-muted);'>No past shifts found.</td></tr>";
                }
                $histQ->close();
                ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
