<?php
include 'includes/db.php';

// Ensure returns table has required columns
$colCheck = $conn->query("SHOW COLUMNS FROM returns LIKE 'is_cleared'");
if ($colCheck && $colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE returns ADD COLUMN is_cleared TINYINT(1) NOT NULL DEFAULT 0");
}
$colCheck2 = $conn->query("SHOW COLUMNS FROM returns LIKE 'branch_id'");
if ($colCheck2 && $colCheck2->num_rows == 0) {
    $conn->query("ALTER TABLE returns ADD COLUMN branch_id INT NOT NULL DEFAULT 1");
}
$colCheck3 = $conn->query("SHOW COLUMNS FROM returns LIKE 'created_at'");
if ($colCheck3 && $colCheck3->num_rows == 0) {
    $conn->query("ALTER TABLE returns ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

$colCheck4 = $conn->query("SHOW COLUMNS FROM return_items LIKE 'tenant_id'");
if ($colCheck4 && $colCheck4->num_rows == 0) {
    $conn->query("ALTER TABLE return_items ADD COLUMN tenant_id INT NOT NULL DEFAULT 1");
}

include 'includes/header.php';

// Branch Filters
$bF_AND = $isAdmin ? " AND tenant_id = {$_SESSION['tenant_id']}" : " AND tenant_id = {$_SESSION['tenant_id']} AND branch_id = $current_branch_id";
$bF_AND_CL = $isAdmin ? " AND cl.tenant_id = {$_SESSION['tenant_id']}" : " AND cl.tenant_id = {$_SESSION['tenant_id']} AND cl.branch_id = $current_branch_id";
$bF_AND_P = $isAdmin ? " AND p.tenant_id = {$_SESSION['tenant_id']}" : " AND p.tenant_id = {$_SESSION['tenant_id']} AND p.branch_id = $current_branch_id";


// Only Admin
if (!isset($isAdmin) || !$isAdmin) {
    echo "<div class='container'><div style='padding:2rem; text-align:center;'><h2>Access Denied</h2><p>Only administrators can perform End of Day settlement.</p></div></div>";
    include 'includes/footer.php';
    exit;
}

$message = '';
$today = date('Y-m-d');

// Fetch Uncleared (Active) Summary for the Z-Report
$salesQuery = $conn->query("SELECT SUM(total_amount) as s, COUNT(id) as c FROM sales WHERE is_cleared = 0 $bF_AND");
$salesData = $salesQuery->fetch_assoc();
$totalSales = $salesData['s'] ?? 0;
$totalOrders = $salesData['c'] ?? 0;

$creditSalesQuery = $conn->query("SELECT SUM(total_amount) as cs FROM sales WHERE payment_method != 'cash' AND is_cleared = 0 $bF_AND");
$totalCreditSales = $creditSalesQuery->fetch_assoc()['cs'] ?? 0;
$totalCashSalesOnly = $totalSales - $totalCreditSales;

$expenseQuery = $conn->query("SELECT SUM(amount) as e FROM expenses WHERE is_cleared = 0 AND category != 'Return Refund' $bF_AND");
$totalExpenses = $expenseQuery->fetch_assoc()['e'] ?? 0;

$purchasesQuery = $conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE is_cleared = 0 $bF_AND");
$totalPurchases = $purchasesQuery->fetch_assoc()['p'] ?? 0;

// Gross Profit Margin Calculation (Total Sales - COGS)
$bF_AND_S = $isAdmin ? " AND s.tenant_id = {$_SESSION['tenant_id']}" : " AND s.tenant_id = {$_SESSION['tenant_id']} AND s.branch_id = $current_branch_id";
$cogsQuery = $conn->query("SELECT SUM(si.quantity * si.cost_price) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id WHERE s.is_cleared = 0 $bF_AND_S");
$totalCOGS = $cogsQuery->fetch_assoc()['cogs'] ?? 0;

// Fetch Active Returns Summary
$returnsQuery = $conn->query("SELECT SUM(total_refund) as tr, COUNT(id) as c FROM returns WHERE (is_cleared = 0 OR is_cleared IS NULL) $bF_AND");
$returnsData = $returnsQuery ? $returnsQuery->fetch_assoc() : [];
$totalReturnsRefund = floatval($returnsData['tr'] ?? 0);
$totalReturnsCount = intval($returnsData['c'] ?? 0);

$bF_AND_R = $isAdmin ? " AND r.tenant_id = {$_SESSION['tenant_id']}" : " AND r.tenant_id = {$_SESSION['tenant_id']} AND r.branch_id = $current_branch_id";
$returnQtyQuery = $conn->query("SELECT SUM(ri.quantity) as qty FROM return_items ri JOIN returns r ON ri.return_id = r.id WHERE (r.is_cleared = 0 OR r.is_cleared IS NULL) $bF_AND_R");
$totalReturnItemsQty = floatval(($returnQtyQuery ? $returnQtyQuery->fetch_assoc()['qty'] : 0) ?? 0);

$netProfit = $totalSales - $totalExpenses - $totalPurchases - $totalReturnsRefund;
$grossMargin = $totalSales - $totalCOGS;

// Cash Math
$cashQuery = $conn->query("SELECT SUM(total_amount) as net_cash FROM sales WHERE payment_method='cash' AND is_cleared = 0 $bF_AND");
$netCashSales = $cashQuery->fetch_assoc()['net_cash'] ?? 0;

$khataQuery = $conn->query("SELECT SUM(amount) as c FROM customer_ledger WHERE type IN ('payment_received', 'advance_deposit') AND is_cleared = 0 $bF_AND");
$khataPaymentsReceived = $khataQuery->fetch_assoc()['c'] ?? 0;

$openShiftsQuery = $conn->query("SELECT SUM(opening_cash) as oc, SUM(closing_cash) as cc FROM shifts WHERE is_cleared = 0 $bF_AND");
$shiftData = $openShiftsQuery->fetch_assoc();
$totalOpeningCash = $shiftData['oc'] ?? 0;
$totalClosingCash = $shiftData['cc'] ?? 0;

$expectedCash = $totalOpeningCash + $netCashSales + $khataPaymentsReceived - $totalExpenses - $totalPurchases - $totalReturnsRefund;

// If shifts were closed, use their actual closing cash. Otherwise fallback to expected cash.
$finalActualCash = $totalClosingCash > 0 ? $totalClosingCash : $expectedCash;

// Fetch Detailed Arrays
$detailedOrdersArr = [];
$detailedOrders = $conn->query("SELECT id, customer_name, total_amount, payment_method, created_at FROM sales WHERE is_cleared = 0 ORDER BY created_at DESC");
if ($detailedOrders) {
    while($r = $detailedOrders->fetch_assoc()) $detailedOrdersArr[] = $r;
}

$detailedExpensesArr = [];
$detailedExpenses = $conn->query("SELECT category, amount, expense_date FROM expenses WHERE is_cleared = 0 AND category != 'Return Refund' ORDER BY expense_date DESC");
if ($detailedExpenses) {
    while($r = $detailedExpenses->fetch_assoc()) $detailedExpensesArr[] = $r;
}

$detailedPurchasesArr = [];
$detailedPurchases = $conn->query("SELECT s.name as supplier_name, p.amount_paid, p.created_at FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.is_cleared = 0 $bF_AND_P ORDER BY p.created_at DESC");
if ($detailedPurchases) {
    while($r = $detailedPurchases->fetch_assoc()) $detailedPurchasesArr[] = $r;
}

$detailedKhataArr = [];
$detailedKhata = $conn->query("SELECT c.name as customer_name, cl.amount, cl.created_at FROM customer_ledger cl JOIN customers c ON cl.customer_id = c.id WHERE cl.type IN ('payment_received', 'advance_deposit') AND cl.is_cleared = 0 $bF_AND_CL ORDER BY cl.created_at DESC");
if ($detailedKhata) {
    while($r = $detailedKhata->fetch_assoc()) $detailedKhataArr[] = $r;
}

// Fetch Active Returns Summary
$returnsQuery = $conn->query("SELECT SUM(total_refund) as tr, COUNT(id) as c FROM returns WHERE is_cleared = 0 $bF_AND");
$returnsData = $returnsQuery ? $returnsQuery->fetch_assoc() : [];
$totalReturnsRefund = floatval($returnsData['tr'] ?? 0);
$totalReturnsCount = intval($returnsData['c'] ?? 0);

$bF_AND_R = $isAdmin ? " AND r.tenant_id = {$_SESSION['tenant_id']}" : " AND r.tenant_id = {$_SESSION['tenant_id']} AND r.branch_id = $current_branch_id";
$returnQtyQuery = $conn->query("SELECT SUM(ri.quantity) as qty FROM return_items ri JOIN returns r ON ri.return_id = r.id WHERE r.is_cleared = 0 $bF_AND_R");
$totalReturnItemsQty = floatval(($returnQtyQuery ? $returnQtyQuery->fetch_assoc()['qty'] : 0) ?? 0);

$detailedReturnsArr = [];
$detailedReturns = $conn->query("SELECT r.id, r.sale_id, r.total_refund, r.created_at, u.username FROM returns r LEFT JOIN users u ON r.user_id = u.id WHERE r.is_cleared = 0 $bF_AND_R ORDER BY r.created_at DESC");
if ($detailedReturns) {
    while($r = $detailedReturns->fetch_assoc()) $detailedReturnsArr[] = $r;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_eod'])) {
    
    // 1. Force close any open shifts (for all users) assuming the admin checked the cash
    $conn->query("UPDATE shifts SET closed_at = NOW(), closing_cash = opening_cash, status = 'closed' WHERE status = 'open' AND tenant_id = {$_SESSION['tenant_id']}");
    
    // Recalculate total closing cash just in case an open shift was forced closed
    $openShiftsQuery2 = $conn->query("SELECT SUM(closing_cash) as cc FROM shifts WHERE is_cleared = 0 $bF_AND");
    $totalClosingCash2 = $openShiftsQuery2->fetch_assoc()['cc'] ?? 0;
    $finalActualCash = $totalClosingCash2 > 0 ? $totalClosingCash2 : $expectedCash;

    // 2. Save the Snapshot (Only if there is data)
    if ($totalOrders > 0 || $totalExpenses > 0 || $totalPurchases > 0 || $totalOpeningCash > 0 || $totalReturnsCount > 0) {
        $snapshot = [
            'orders' => $detailedOrdersArr,
            'expenses' => $detailedExpensesArr,
            'purchases' => $detailedPurchasesArr,
            'returns' => $detailedReturnsArr,
            'returns_summary' => [
                'count' => $totalReturnsCount,
                'refund' => $totalReturnsRefund,
                'items_qty' => $totalReturnItemsQty
            ]
        ];
        $jsonSnapshot = json_encode($snapshot);
        
        $stmt = $conn->prepare("INSERT INTO z_reports_history (report_date, total_orders, total_sales, total_expenses, total_supplier_payments, net_income, snapshot_data, opening_cash, expected_cash, closing_cash, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt->bind_param("siddddsddd", $today, $totalOrders, $totalSales, $totalExpenses, $totalPurchases, $netProfit, $jsonSnapshot, $totalOpeningCash, $expectedCash, $finalActualCash);
        $stmt->execute();
        $stmt->close();
    }

    // 3. Mark all active data as cleared
    $conn->query("UPDATE sales SET is_cleared = 1 WHERE is_cleared = 0 $bF_AND");
    $conn->query("UPDATE expenses SET is_cleared = 1 WHERE is_cleared = 0 $bF_AND");
    $conn->query("UPDATE purchases SET is_cleared = 1 WHERE is_cleared = 0 $bF_AND");
    $conn->query("UPDATE shifts SET is_cleared = 1 WHERE is_cleared = 0 $bF_AND");
    $conn->query("UPDATE customer_ledger SET is_cleared = 1 WHERE is_cleared = 0 $bF_AND");
    $conn->query("UPDATE returns SET is_cleared = 1 WHERE is_cleared = 0 $bF_AND");

    // Log the action
    include_once 'includes/alert_engine.php';
    logAudit($conn, $_SESSION['user_id'], 'End of Day', "Executed End of Day Z-Report. Cleared session data to history.");

    // Set a flag/message
    $message = "End of Day process completed successfully! Session data cleared to history.";
    
    // Refresh the arrays so the UI shows zero on reload without physical refresh
    $totalOrders = 0; $totalSales = 0; $totalExpenses = 0; $totalPurchases = 0; $netProfit = 0;
    $totalOpeningCash = 0; $netCashSales = 0; $expectedCash = 0;
    $totalReturnsRefund = 0; $totalReturnsCount = 0; $totalReturnItemsQty = 0;
    $detailedOrdersArr = []; $detailedExpensesArr = []; $detailedPurchasesArr = []; $detailedReturnsArr = [];
}
?>

<div style="padding-left: 6rem; padding-right: 6rem; box-sizing: border-box; width: 100%;">
    <div class="animate-fade-in container" style="max-width: 780px; margin: 3rem auto; background: var(--surface-color); padding: 3rem; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
    
    <div style="display: flex; justify-content: flex-end; margin-bottom: -2rem;" class="no-print">
        <a href="historical_z_reports.php" class="btn" style="background: #e2e8f0; color: #0f172a; text-decoration: none; font-size: 0.9rem;">📚 View History</a>
    </div>

    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="color: var(--text-main); font-size: 2.5rem; margin-bottom: 0.5rem;">End of Day Settlement</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem;">Z-Report for <strong><?php echo date('l, F j, Y'); ?></strong></p>
    </div>

    <?php if($message): ?>
        <div style="background: var(--success); color: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; font-size: 1.1rem; text-align: center; font-weight: 500;">
            ✅ <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Total Orders Today</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--text-main);"><?php echo $totalOrders; ?></div>
        </div>
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Total Sales Today</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--primary-color);">$<?php echo number_format($totalSales, 2); ?></div>
            <?php if($totalCreditSales > 0): ?>
            <div style="font-size: 0.9rem; color: #64748b; margin-top: 0.5rem; display: flex; justify-content: space-between;">
                <span>Cash: $<?php echo number_format($totalCashSalesOnly, 2); ?></span>
                <span>Udhaar/Card: $<?php echo number_format($totalCreditSales, 2); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Today Expenses</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--danger);">-$<?php echo number_format($totalExpenses, 2); ?></div>
        </div>
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Supplier Payments</div>
            <div style="font-size: 2rem; font-weight: 800; color: #d97706;">-$<?php echo number_format($totalPurchases, 2); ?></div>
        </div>
        <div style="background: #fdf2f8; padding: 1.5rem; border-radius: 12px; border: 1px solid #fbcfe8;">
            <div style="color: #be185d; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Product Returns</div>
            <div style="font-size: 2rem; font-weight: 800; color: #db2777;">-$<?php echo number_format($totalReturnsRefund, 2); ?></div>
            <div style="font-size: 0.85rem; color: #be185d; margin-top: 0.5rem;"><?php echo $totalReturnsCount; ?> returns processed (<?php echo floatval($totalReturnItemsQty); ?> items)</div>
        </div>
        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 12px; border: 1px solid #bbf7d0;">
            <div style="color: #166534; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Net Daily Cash Generated</div>
            <?php $netCashGenerated = $netCashSales + $khataPaymentsReceived - $totalExpenses - $totalPurchases - $totalReturnsRefund; ?>
            <div style="font-size: 2rem; font-weight: 800; color: #15803d;">$<?php echo number_format($netCashGenerated, 2); ?></div>
        </div>
        <div style="background: <?php echo $grossMargin >= 0 ? '#e0e7ff' : '#fee2e2'; ?>; padding: 1.5rem; border-radius: 12px; border: 1px solid <?php echo $grossMargin >= 0 ? '#a5b4fc' : '#fecaca'; ?>;">
            <div style="color: <?php echo $grossMargin >= 0 ? '#3730a3' : '#991b1b'; ?>; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Actual Profit Margin</div>
            <div style="font-size: 2rem; font-weight: 800; color: <?php echo $grossMargin >= 0 ? '#4338ca' : '#b91c1c'; ?>;"><?php echo $grossMargin >= 0 ? '+' : ''; ?>$<?php echo number_format($grossMargin, 2); ?></div>
            <div style="font-size: 0.85rem; color: <?php echo $grossMargin >= 0 ? '#4f46e5' : '#dc2626'; ?>; margin-top: 0.5rem;">Sales minus Purchase Cost</div>
        </div>
    </div>

    <!-- Cash Drawer Summary -->
    <div style="background: #f1f5f9; border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-bottom: 3rem;">
        <h4 style="margin-bottom: 1rem; color: var(--text-main); font-size: 1.1rem;">Cash Drawer Summary</h4>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1.1rem; font-weight: bold; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">
            <span style="color: var(--text-main);">Opening Cash (Purana Cash):</span>
            <strong style="color: var(--text-main);">$<?php echo number_format($totalOpeningCash, 2); ?></strong>
        </div>

        <div style="margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; font-weight: 600;">Today's Cash Flow (Naya Cash)</div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1rem;">
            <span style="color: var(--text-muted);">+ Net Cash Sales:</span>
            <strong style="color: var(--success);">$<?php echo number_format($netCashSales, 2); ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1rem;">
            <span style="color: var(--text-muted);">+ Khata/Customer Payments:</span>
            <strong style="color: var(--success);">$<?php echo number_format($khataPaymentsReceived, 2); ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1rem;">
            <span style="color: var(--text-muted);">- Today Expenses:</span>
            <strong style="color: var(--danger);">-$<?php echo number_format($totalExpenses, 2); ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1rem;">
            <span style="color: var(--text-muted);">- Supplier Payments:</span>
            <strong style="color: #d97706;">-$<?php echo number_format($totalPurchases, 2); ?></strong>
        </div>
        <?php if ($totalReturnsRefund > 0): ?>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1rem;">
            <span style="color: #db2777; font-weight: 600;">- Product Returns Refund:</span>
            <strong style="color: #db2777;">-$<?php echo number_format($totalReturnsRefund, 2); ?></strong>
        </div>
        <?php endif; ?>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1rem; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1;">
            <?php $netCashGenerated = $netCashSales + $khataPaymentsReceived - $totalExpenses - $totalPurchases - $totalReturnsRefund; ?>
            <span style="color: var(--text-main); font-weight: 500;">Baqi Cash (Net Generated Today):</span>
            <strong style="color: <?php echo $netCashGenerated >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;"><?php echo $netCashGenerated >= 0 ? '+' : ''; ?>$<?php echo number_format($netCashGenerated, 2); ?></strong>
        </div>

        <div style="border-top: 2px solid #cbd5e1; margin: 1rem 0; padding-top: 1rem; display: flex; justify-content: space-between; font-size: 1.2rem;">
            <span style="color: var(--text-muted); font-weight: bold;">System Expected Total Cash:</span>
            <strong style="color: var(--primary-color);">$<?php echo number_format($expectedCash, 2); ?></strong>
        </div>
        
        <div style="background: <?php echo ($totalClosingCash > 0 && $finalActualCash != $expectedCash) ? '#fef2f2' : '#f0fdf4'; ?>; padding: 1rem; border-radius: 8px; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: bold; color: var(--text-main); font-size: 1.2rem;">Actual Cash (From Shifts)</span>
            <div style="text-align: right;">
                <strong style="color: <?php echo ($finalActualCash >= $expectedCash) ? 'var(--success)' : 'var(--danger)'; ?>; font-size: 1.5rem;">
                    $<?php echo number_format($finalActualCash, 2); ?>
                </strong>
                <?php if ($totalClosingCash == 0 && $expectedCash > 0): ?>
                    <div style="font-size: 0.8rem; color: #b45309; margin-top: 0.2rem;">⚠️ Auto-filled. No shifts closed yet.</div>
                <?php elseif ($totalClosingCash > 0): ?>
                    <?php $variance = $finalActualCash - $expectedCash; ?>
                    <div style="font-size: 0.85rem; color: <?php echo $variance < 0 ? 'var(--danger)' : 'var(--success)'; ?>; margin-top: 0.2rem;">
                        Variance: $<?php echo number_format($variance, 2); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
        <h4 style="color: #92400e; margin-bottom: 0.5rem; font-size: 1.1rem;">⚠️ Warning</h4>
        <p style="color: #b45309; font-size: 0.95rem;">Running the End of Day process will finalize the Z-Report using the Actual Cash above. It will force-close any registers that are still open. Are you sure you want to proceed?</p>
    </div>

    <!-- Detailed Breakdowns for Printing -->
    <div class="detailed-reports">
        
        <!-- Today's Orders -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Today's Orders</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Order ID</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Customer</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Method</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Time</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedOrdersArr)): foreach($detailedOrdersArr as $o): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);">#<?php echo $o['id']; ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($o['customer_name'] ?: 'Walk-in'); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-transform: capitalize;"><?php echo $o['payment_method']; ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($o['created_at'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600;">$<?php echo number_format($o['total_amount'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No orders today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Today's Khata Payments -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Khata Payments Received</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Customer Name</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Date</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Amount Received</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedKhataArr)): foreach($detailedKhataArr as $k): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($k['customer_name']); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($k['created_at'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600; color: var(--success);">+$<?php echo number_format($k['amount'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No Khata payments received today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Today's Expenses -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Today's Expenses</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Expense Title</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Date</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedExpensesArr)): foreach($detailedExpensesArr as $e): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($e['category']); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($e['expense_date'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600; color: var(--danger);">-$<?php echo number_format($e['amount'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No expenses recorded today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Today's Supplier Payments -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Supplier Payments</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Supplier Name</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Time</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedPurchasesArr)): foreach($detailedPurchasesArr as $p): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($p['supplier_name'] ?: 'Unknown'); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($p['created_at'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600; color: #eab308;">-$<?php echo number_format($p['amount_paid'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No supplier payments today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Today's Product Returns -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Product Returns</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Return ID</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Receipt #</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Processed By</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Time</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Total Refund</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedReturnsArr)): foreach($detailedReturnsArr as $r): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);">#<?php echo $r['id']; ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);">Sale #<?php echo $r['sale_id']; ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($r['username'] ?: 'Staff'); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($r['created_at'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600; color: #db2777;">-$<?php echo number_format($r['total_refund'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No product returns recorded today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <form method="POST" style="text-align: center; margin-top: 2rem;" class="no-print">
        <button type="submit" name="run_eod" class="btn btn-danger" style="padding: 1.2rem 3rem; font-size: 1.2rem; border-radius: 999px; box-shadow: 0 4px 15px rgba(239,68,68,0.4);" onclick="return confirm('Kya aap waqai aaj ka saara data clear kar ke History mein save karna chahte hain?');" <?php echo ($totalOrders == 0 && $totalExpenses == 0 && $totalPurchases == 0 && $totalOpeningCash == 0 && $totalReturnsCount == 0) ? 'disabled' : ''; ?>>
            🧹 Clear Today's Data & Save to History
        </button>
        <a href="print_z_report.php" target="_blank" class="btn" style="display: inline-block; background: var(--primary-color); color: white; padding: 1.2rem 3rem; font-size: 1.2rem; border-radius: 999px; margin-left: 1rem; box-shadow: 0 4px 15px rgba(30,58,138,0.3); text-decoration: none;">
            🖨️ Print Report
        </a>
    </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.querySelector('main.container') || document.body;
        const opt = {
            margin:       0.5,
            filename:     'end_of_day_report_<?php echo $today; ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { 
                scale: 4,
                useCORS: true,
                onclone: function(clonedDoc) {
                    const noPrints = clonedDoc.querySelectorAll('.no-print, .main-header');
                    noPrints.forEach(el => el.style.display = 'none');
                }
            },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        
        const btn = document.querySelector('button[onclick="downloadPDF()"]');
        if(btn) btn.innerText = "⏳...";
        
        window.scrollTo(0,0);
        
        html2pdf().set(opt).from(element).save().then(() => {
            if(btn) btn.innerText = "📄 Download PDF";
        });
    }
</script>

<style>
    @media print {
        .main-header, .no-print { display: none !important; }
        .container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        body { background: white !important; }
        .detailed-reports { display: block !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
    }
</style>

<?php include 'includes/footer.php'; ?>
