<?php
include 'includes/db.php';
include 'includes/header.php';

// Handle Month Filtering safely without strtotime overflow issues
$selectedMonthStr = isset($_GET['report_month']) && !empty($_GET['report_month']) ? $_GET['report_month'] : date('Y-m');
$parts = explode('-', $selectedMonthStr);
if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
    $year = (int)$parts[0];
    $month = (int)$parts[1];
} else {
    $year = (int)date('Y');
    $month = (int)date('m');
    $selectedMonthStr = date('Y-m');
}

$message = '';

// Handle End of Month Closing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_eom'])) {
    $eom_month = $_POST['eom_month']; // e.g., '2026-08'
    $eParts = explode('-', $eom_month);
    $y = (int)($eParts[0] ?? date('Y'));
    $m = (int)($eParts[1] ?? date('m'));
    
    // Fetch snapshot of uncleared monthly data
    $qS = $conn->prepare("SELECT SUM(total_amount) FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
    $qS->bind_param("ii", $m, $y); $qS->execute(); $qS->bind_result($sTotal); $qS->fetch(); $qS->close();
    
    $qE = $conn->prepare("SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ? AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
    $qE->bind_param("ii", $m, $y); $qE->execute(); $qE->bind_result($eTotal); $qE->fetch(); $qE->close();
    
    $qP = $conn->prepare("SELECT SUM(amount_paid) FROM purchases WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
    $qP->bind_param("ii", $m, $y); $qP->execute(); $qP->bind_result($pTotal); $qP->fetch(); $qP->close();
    
    $sTotal = $sTotal ?: 0;
    $eTotal = $eTotal ?: 0;
    $pTotal = $pTotal ?: 0;
    $net = $sTotal - $eTotal - $pTotal;
    
    if ($sTotal > 0 || $eTotal > 0 || $pTotal > 0) {
        $snap = json_encode(['info' => 'Monthly snapshot taken before clearing']);
        $rDate = "$y-$m-01";
        
        $stmt = $conn->prepare("INSERT INTO monthly_reports_history (report_month, total_sales, total_expenses, total_supplier_payments, net_income, snapshot_data, tenant_id) VALUES (?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt->bind_param("sdddds", $rDate, $sTotal, $eTotal, $pTotal, $net, $snap);
        $stmt->execute();
        $stmt->close();
        
        // Mark as cleared
        $uS = $conn->prepare("UPDATE sales SET is_monthly_cleared = 1 WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']} AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
        $uS->bind_param("ii", $m, $y); $uS->execute(); $uS->close();
        
        $uE = $conn->prepare("UPDATE expenses SET is_monthly_cleared = 1 WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ? AND tenant_id = {$_SESSION['tenant_id']} AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
        $uE->bind_param("ii", $m, $y); $uE->execute(); $uE->close();
        
        $uP = $conn->prepare("UPDATE purchases SET is_monthly_cleared = 1 WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']} AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
        $uP->bind_param("ii", $m, $y); $uP->execute(); $uP->close();
        
        include_once 'includes/alert_engine.php';
        logAudit($conn, $_SESSION['user_id'], 'End of Month', "Executed End of Month closing for $eom_month.");
        $message = "Monthly data for $eom_month has been cleared and saved to history.";
    } else {
        $message = "No pending data to clear for $eom_month.";
    }
}

// 1. Total Sales (Revenue) - ALL DATA for selected month
$salesMonth = 0;
$stmt = $conn->prepare("SELECT SUM(total_amount) FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']}");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$stmt->bind_result($salesMonth);
$stmt->fetch();
$stmt->close();
if (!$salesMonth) $salesMonth = 0;

// 1b. Total Orders Count for selected month
$ordersMonth = 0;
$stmt = $conn->prepare("SELECT COUNT(id) FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']}");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$stmt->bind_result($ordersMonth);
$stmt->fetch();
$stmt->close();
$ordersMonth = $ordersMonth ?: 0;

// 2. Total Paid to Suppliers - ALL DATA for selected month
$supplierPaidMonth = 0;
$stmt = $conn->prepare("SELECT SUM(amount_paid) FROM purchases WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']}");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$stmt->bind_result($supplierPaidMonth);
$stmt->fetch();
$stmt->close();
if (!$supplierPaidMonth) $supplierPaidMonth = 0;

// Calculate Cost of Goods Sold (COGS) for this month
$cogsMonth = 0;
$stmt = $conn->prepare("SELECT SUM(si.quantity * LEAST(si.cost_price, si.price)) FROM sales s JOIN sale_items si ON s.id = si.sale_id WHERE MONTH(s.created_at) = ? AND YEAR(s.created_at) = ? AND s.tenant_id = {$_SESSION['tenant_id']}");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$stmt->bind_result($cogsMonth);
$stmt->fetch();
$stmt->close();
if (!$cogsMonth) $cogsMonth = 0;

$grossProfit = $salesMonth - $cogsMonth;

// 3. Total Expenses - ALL DATA for selected month
$expensesMonth = 0;
$stmt = $conn->prepare("SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ? AND tenant_id = {$_SESSION['tenant_id']}");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$stmt->bind_result($expensesMonth);
$stmt->fetch();
$stmt->close();
if (!$expensesMonth) $expensesMonth = 0;

$netCashFlow = $salesMonth - $supplierPaidMonth - $expensesMonth;

// 4. Monthly Cash Drawer Stats from Z-Reports (Historical)
$monthlyOpeningCash = 0;
$monthlyExpectedCash = 0;
$monthlyClosingCash = 0;
$stmt = $conn->prepare("SELECT SUM(opening_cash), SUM(expected_cash), SUM(closing_cash) FROM z_reports_history WHERE MONTH(report_date) = ? AND YEAR(report_date) = ? AND tenant_id = {$_SESSION['tenant_id']}");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$stmt->bind_result($monthlyOpeningCash, $monthlyExpectedCash, $monthlyClosingCash);
$stmt->fetch();
$stmt->close();
$monthlyOpeningCash = $monthlyOpeningCash ?: 0;
$monthlyExpectedCash = $monthlyExpectedCash ?: 0;
$monthlyClosingCash = $monthlyClosingCash ?: 0;
$monthlyCashDifference = $monthlyClosingCash - $monthlyOpeningCash;

// 4. Held sales logic removed

// Calculate Net Cash Flow (Money In - Money Out)
$netCashFlow = $salesMonth - $supplierPaidMonth - $expensesMonth;

// 5. Payment Method Breakdown for selected month
$paymentBreakdown = [];
$pmStmt = $conn->prepare("SELECT payment_method, COUNT(*) as cnt, SUM(total_amount) as total FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']} GROUP BY payment_method ORDER BY total DESC");
$pmStmt->bind_param("ii", $month, $year);
$pmStmt->execute();
$pmResult = $pmStmt->get_result();
$pmStmt->close();
$paymentIcons  = ['cash'=>'💵','card'=>'💳','bank'=>'🏦','udhaar'=>'📒','online'=>'📱','cheque'=>'📝'];
$paymentLabels = ['cash'=>'Cash','card'=>'Credit/Debit Card','bank'=>'Bank Transfer','udhaar'=>'Udhaar (Credit)','online'=>'Online Payment','cheque'=>'Cheque'];
while($pm = $pmResult->fetch_assoc()){ $paymentBreakdown[] = $pm; }

// Fetch Monthly Data for the entire year (ALL DATA - cleared + uncleared)
$monthlyData = [];
for ($m = 1; $m <= 12; $m++) {
    $monthlyData[$m] = ['sales' => 0, 'purchases' => 0, 'expenses' => 0, 'net' => 0];
}

$stmt = $conn->prepare("SELECT MONTH(created_at) as m, SUM(total_amount) as t FROM sales WHERE YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']} GROUP BY MONTH(created_at)");
$stmt->bind_param("i", $year);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){ $monthlyData[(int)$row['m']]['sales'] = $row['t']; }
$stmt->close();

$stmt = $conn->prepare("SELECT MONTH(created_at) as m, SUM(amount_paid) as t FROM purchases WHERE YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']} GROUP BY MONTH(created_at)");
$stmt->bind_param("i", $year);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){ $monthlyData[(int)$row['m']]['purchases'] = $row['t']; }
$stmt->close();

$stmt = $conn->prepare("SELECT MONTH(expense_date) as m, SUM(amount) as t FROM expenses WHERE YEAR(expense_date) = ? AND tenant_id = {$_SESSION['tenant_id']} GROUP BY MONTH(expense_date)");
$stmt->bind_param("i", $year);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){ $monthlyData[(int)$row['m']]['expenses'] = $row['t']; }
$stmt->close();

foreach ($monthlyData as $m => $data) {
    $monthlyData[$m]['net'] = $data['sales'] - $data['purchases'] - $data['expenses'];
}

// Fetch daily sales for Chart
$chartLabels = [];
$chartData = [];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$stmt = $conn->prepare("
    SELECT DAY(created_at) as sale_day, SUM(total_amount) as daily_total 
    FROM sales 
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND tenant_id = {$_SESSION['tenant_id']}
    GROUP BY DAY(created_at) 
    ORDER BY DAY(created_at) ASC
");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$salesMap = [];
while ($row = $result->fetch_assoc()) {
    $salesMap[$row['sale_day']] = $row['daily_total'];
}
$stmt->close();

for ($i = 1; $i <= $daysInMonth; $i++) {
    $chartLabels[] = "$i " . date('M', mktime(0,0,0,$month,1,$year));
    $chartData[] = isset($salesMap[$i]) ? floatval($salesMap[$i]) : 0;
}

// Top Selling Products for selected month (ALL DATA)
$stmt = $conn->prepare("
    SELECT p.name, p.image, SUM(si.quantity) as qty, SUM(si.quantity * si.price) as rev
    FROM sale_items si 
    JOIN sales s ON si.sale_id = s.id 
    JOIN products p ON si.product_id = p.id
    WHERE MONTH(s.created_at) = ? AND YEAR(s.created_at) = ? AND s.tenant_id = {$_SESSION['tenant_id']}
    GROUP BY p.id 
    ORDER BY qty DESC LIMIT 5
");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$topSelling = $stmt->get_result();
?>

<?php if($message): ?>
<div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
    ✅ <?php echo $message; ?>
</div>
<?php endif; ?>

<?php
// Calculate Current Active Session (Today's Uncleared Data)
$activeSales = 0; $activeOrders = 0; $activePurchases = 0; $activeExpenses = 0;

$sQ = $conn->query("SELECT SUM(total_amount) as s, COUNT(id) as c FROM sales WHERE is_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
$sD = $sQ->fetch_assoc();
$activeSales = $sD['s'] ?? 0;
$activeOrders = $sD['c'] ?? 0;

$pQ = $conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE is_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
$activePurchases = $pQ->fetch_assoc()['p'] ?? 0;

$eQ = $conn->query("SELECT SUM(amount) as e FROM expenses WHERE is_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
$activeExpenses = $eQ->fetch_assoc()['e'] ?? 0;

$activeNet = $activeSales - $activePurchases - $activeExpenses;

// Fetch Cash Sales specifically for Net Cash Generated calculation
$cQ = $conn->query("SELECT SUM(amount_received - change_returned) as c FROM sales WHERE payment_method = 'cash' AND is_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
$activeCashSales = $cQ->fetch_assoc()['c'] ?? 0;

// Fetch Active Shifts Data (Opening / Closing Cash)
$shQ = $conn->query("SELECT SUM(opening_cash) as oc, SUM(closing_cash) as cc FROM shifts WHERE is_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
$shD = $shQ->fetch_assoc();
$activeOpeningCash = $shD['oc'] ?? 0;
$activeClosingCash = $shD['cc'] ?? 0;

$activeExpectedCash = $activeOpeningCash + $activeCashSales - $activeExpenses - $activePurchases;
$activeActualCash = $activeClosingCash > 0 ? $activeClosingCash : $activeExpectedCash;
?>

<div class="animate-fade-in" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2>Sale Report</h2>
        <p style="color: var(--text-muted);">View your active session and monthly analytics.</p>
    </div>
    
    <form method="GET" style="display: flex; gap: 0.5rem; align-items: center; background: var(--surface-color); padding: 0.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <label style="font-weight: 500; font-size: 0.9rem;">Select Month:</label>
        <input type="month" name="report_month" value="<?php echo $selectedMonthStr; ?>" style="padding: 0.4rem; border: 1px solid var(--border-color); border-radius: 4px; outline: none;">
        <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1.2rem; width: auto !important; font-size: 0.9rem; border-radius: 6px !important; display: inline-flex; align-items: center; justify-content: center; font-weight: 600;">Filter</button>
        <button type="button" onclick="downloadReportPDF()" class="btn" style="background: #10b981; color: white; padding: 0.4rem 1.2rem; width: auto !important; font-size: 0.9rem; margin-left: 0.5rem; border: none; border-radius: 6px !important; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">📄 PDF</button>
    </form>
</div>

<!-- Active Session (Today's Uncleared Data) -->
<div class="animate-slide-up" style="background: #eef2ff; border: 1px solid #c7d2fe; padding: 2rem; border-radius: 12px; margin-bottom: 3rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h3 style="color: #3730a3; margin: 0 0 0.5rem 0;">🌟 Current Active Session (Today)</h3>
            <p style="color: #4f46e5; font-size: 0.9rem; margin: 0;">This data will automatically reset to 0 when you run "End of Day".</p>
        </div>
        <a href="end_of_day.php" class="btn btn-danger" style="padding: 0.6rem 1.5rem; font-size: 0.95rem; font-weight: 600; width: auto !important; border-radius: 6px !important; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">Go to End of Day &rarr;</a>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Active Orders</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-main);"><?php echo $activeOrders; ?></div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Active Sales</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary-color);">$<?php echo number_format($activeSales, 2); ?></div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Expenses & Purchases</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--danger);">-$<?php echo number_format($activeExpenses + $activePurchases, 2); ?></div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Net Income</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: <?php echo $activeNet >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">$<?php echo number_format($activeNet, 2); ?></div>
        </div>
    </div>

    <!-- Active Cash Drawer Summary -->
    <div style="background: white; border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">💵 Today's Cash Drawer (Active Shifts)</h3>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1.1rem; font-weight: bold; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">
            <span style="color: var(--text-main);">Opening Cash (Purana Cash):</span>
            <strong style="color: var(--text-main);">$<?php echo number_format($activeOpeningCash, 2); ?></strong>
        </div>

        <div style="margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; font-weight: 600;">Today's Cash Flow Summary (Naya Cash)</div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1rem; padding-top: 0.5rem;">
            <?php $activeNetCashGenerated = $activeExpectedCash - $activeOpeningCash; ?>
            <span style="color: var(--text-main); font-weight: 500;">Baqi Cash (Net Generated):</span>
            <strong style="color: <?php echo $activeNetCashGenerated >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;"><?php echo $activeNetCashGenerated >= 0 ? '+' : ''; ?>$<?php echo number_format($activeNetCashGenerated, 2); ?></strong>
        </div>

        <div style="border-top: 2px solid #cbd5e1; margin: 1rem 0; padding-top: 1rem; display: flex; justify-content: space-between; font-size: 1.2rem;">
            <span style="color: var(--text-muted); font-weight: bold;">System Expected Total Cash:</span>
            <strong style="color: var(--primary-color);">$<?php echo number_format($activeExpectedCash, 2); ?></strong>
        </div>
        
        <div style="background: <?php echo ($activeClosingCash > 0 && $activeClosingCash != $activeExpectedCash) ? '#fef2f2' : '#f0fdf4'; ?>; padding: 1rem; border-radius: 8px; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: bold; color: var(--text-main); font-size: 1.2rem;">Actual Cash (From Closed Shifts)</span>
            <div style="text-align: right;">
                <strong style="color: <?php echo ($activeActualCash >= $activeExpectedCash) ? 'var(--success)' : 'var(--danger)'; ?>; font-size: 1.5rem;">
                    $<?php echo number_format($activeActualCash, 2); ?>
                </strong>
                <?php if ($activeClosingCash == 0 && $activeExpectedCash > 0): ?>
                    <div style="font-size: 0.85rem; color: #b45309; margin-top: 0.2rem;">⚠️ Auto-filled. No shifts closed yet.</div>
                <?php elseif ($activeClosingCash > 0): ?>
                    <?php $variance = $activeActualCash - $activeExpectedCash; ?>
                    <div style="font-size: 0.85rem; color: <?php echo $variance < 0 ? 'var(--danger)' : 'var(--success)'; ?>; margin-top: 0.2rem;">
                        Variance: $<?php echo number_format($variance, 2); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid var(--border-color); padding-top: 2rem; margin-bottom: 1.5rem;">
    <h3 style="color: var(--text-main); margin: 0;">Monthly Analytics (<?php echo date('F Y', strtotime($selectedMonthStr)); ?>)</h3>
    <form method="POST" onsubmit="return confirm('Kya aap waqai is maheenay ka data clear kar ke History mein save karna chahte hain?');">
        <input type="hidden" name="eom_month" value="<?php echo htmlspecialchars($selectedMonthStr); ?>">
        <button type="submit" name="run_eom" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.9rem; border-radius: 999px;">
            🔒 End of Month Close
        </button>
    </form>
</div>

<!-- Dashboard Summary Cards (Compact 7 Cards Grid) -->
<div class="animate-slide-up" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; margin-bottom: 2rem;">
    <!-- Card 1: Total Orders -->
    <div style="background: var(--surface-color); padding: 0.75rem 0.9rem; border-radius: 10px; border-left: 4px solid #2563eb; box-shadow: 0 2px 4px rgba(0,0,0,0.04); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <h4 style="color: var(--text-muted); margin: 0 0 0.2rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Total Orders</h4>
        <div style="font-size: 1.35rem; font-weight: 800; color: var(--text-main);"><?php echo $ordersMonth; ?></div>
        <small style="color: var(--text-muted); font-size: 0.7rem;">Total orders in month</small>
    </div>

    <!-- Card 2: Total Sales Revenue -->
    <div style="background: var(--surface-color); padding: 0.75rem 0.9rem; border-radius: 10px; border-left: 4px solid var(--primary-color); box-shadow: 0 2px 4px rgba(0,0,0,0.04); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <h4 style="color: var(--text-muted); margin: 0 0 0.2rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Sales Revenue (In)</h4>
        <div style="font-size: 1.35rem; font-weight: 800; color: #059669;">$<?php echo number_format($salesMonth, 2); ?></div>
        <small style="color: var(--text-muted); font-size: 0.7rem;">Total money from orders</small>
    </div>
    
    <!-- Card 3: Supplier Payments -->
    <div style="background: var(--surface-color); padding: 0.75rem 0.9rem; border-radius: 10px; border-left: 4px solid #eab308; box-shadow: 0 2px 4px rgba(0,0,0,0.04); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <h4 style="color: var(--text-muted); margin: 0 0 0.2rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Supplier Payments</h4>
        <div style="font-size: 1.35rem; font-weight: 800; color: #ca8a04;">-$<?php echo number_format($supplierPaidMonth, 2); ?></div>
        <small style="color: var(--text-muted); font-size: 0.7rem;">Money paid to suppliers</small>
    </div>
    
    <!-- Card 4: Expenses -->
    <div style="background: var(--surface-color); padding: 0.75rem 0.9rem; border-radius: 10px; border-left: 4px solid var(--danger); box-shadow: 0 2px 4px rgba(0,0,0,0.04); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <h4 style="color: var(--text-muted); margin: 0 0 0.2rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Expenses (Out)</h4>
        <div style="font-size: 1.35rem; font-weight: 800; color: #dc2626;">-$<?php echo number_format($expensesMonth, 2); ?></div>
        <small style="color: var(--text-muted); font-size: 0.7rem;">Shop overheads/bills</small>
    </div>

    <!-- Card 5: Gross Profit -->
    <div style="background: var(--surface-color); padding: 0.75rem 0.9rem; border-radius: 10px; border-left: 4px solid <?php echo $grossProfit >= 0 ? '#0d9488' : '#dc2626'; ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.04); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <h4 style="color: var(--text-muted); margin: 0 0 0.2rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Gross Profit</h4>
        <div style="font-size: 1.35rem; font-weight: 800; color: <?php echo $grossProfit >= 0 ? '#0d9488' : '#dc2626'; ?>;">
            $<?php echo number_format($grossProfit, 2); ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.7rem;">Sales - Product Costs</small>
    </div>

    <!-- Card 6: Total Opening Cash -->
    <div style="background: var(--surface-color); padding: 0.75rem 0.9rem; border-radius: 10px; border-left: 4px solid #d97706; box-shadow: 0 2px 4px rgba(0,0,0,0.04); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <h4 style="color: var(--text-muted); margin: 0 0 0.2rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Total Opening Cash</h4>
        <div style="font-size: 1.35rem; font-weight: 800; color: #d97706;">$<?php echo number_format($monthlyOpeningCash, 2); ?></div>
        <small style="color: var(--text-muted); font-size: 0.7rem;">Purana cash from Z-Reports</small>
    </div>

    <!-- Card 7: Net Cash Flow -->
    <div style="background: var(--surface-color); padding: 0.75rem 0.9rem; border-radius: 10px; border-left: 4px solid <?php echo $netCashFlow >= 0 ? '#7c3aed' : '#dc2626'; ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.04); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <h4 style="color: var(--text-muted); margin: 0 0 0.2rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Net Cash Flow</h4>
        <div style="font-size: 1.35rem; font-weight: 800; color: <?php echo $netCashFlow >= 0 ? '#7c3aed' : '#dc2626'; ?>;">
            $<?php echo number_format($netCashFlow, 2); ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.7rem;">Sales - Suppliers - Expenses</small>
    </div>
</div>

<!-- Monthly Cash Drawer Summary -->
<div class="animate-slide-up" style="background: #f1f5f9; border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">💵 Monthly Cash Drawer Summary (From Z-Reports)</h3>
    
    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1.1rem; font-weight: bold; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">
        <span style="color: var(--text-main);">Total Opening Cash (Purana Cash):</span>
        <strong style="color: var(--text-main);">$<?php echo number_format($monthlyOpeningCash, 2); ?></strong>
    </div>

    <div style="margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; font-weight: 600;">Monthly Cash Flow Summary (Naya Cash)</div>
    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1rem; padding-top: 0.5rem;">
        <?php $netCashGenerated = $monthlyExpectedCash - $monthlyOpeningCash; ?>
        <span style="color: var(--text-main); font-weight: 500;">Baqi Cash (Net Generated):</span>
        <strong style="color: <?php echo $netCashGenerated >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;"><?php echo $netCashGenerated >= 0 ? '+' : ''; ?>$<?php echo number_format($netCashGenerated, 2); ?></strong>
    </div>

    <div style="border-top: 2px solid #cbd5e1; margin: 1rem 0; padding-top: 1rem; display: flex; justify-content: space-between; font-size: 1.2rem;">
        <span style="color: var(--text-muted); font-weight: bold;">System Expected Total Cash:</span>
        <strong style="color: var(--primary-color);">$<?php echo number_format($monthlyExpectedCash, 2); ?></strong>
    </div>
    
    <div style="background: <?php echo ($monthlyClosingCash >= $monthlyExpectedCash) ? '#f0fdf4' : '#fef2f2'; ?>; padding: 1rem; border-radius: 8px; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: bold; color: var(--text-main); font-size: 1.2rem;">Total Actual Cash Closed</span>
        <div style="text-align: right;">
            <strong style="color: <?php echo ($monthlyClosingCash >= $monthlyExpectedCash) ? 'var(--success)' : 'var(--danger)'; ?>; font-size: 1.5rem;">
                $<?php echo number_format($monthlyClosingCash, 2); ?>
            </strong>
            <?php $variance = $monthlyClosingCash - $monthlyExpectedCash; if($variance != 0): ?>
                <div style="font-size: 0.85rem; color: <?php echo $variance < 0 ? 'var(--danger)' : 'var(--success)'; ?>; margin-top: 0.2rem;">
                    Variance: $<?php echo number_format($variance, 2); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== PAYMENT METHOD BREAKDOWN ===== -->
<?php if (!empty($paymentBreakdown)): ?>
<div class="animate-slide-up" style="background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem; border-top: 4px solid #6366f1;">
    <h3 style="color: var(--text-main); margin: 0 0 1.5rem 0; display:flex; align-items:center; gap:0.5rem;">
        💳 Payment Method Breakdown — <?php echo date('F Y', strtotime($selectedMonthStr)); ?>
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.2rem; margin-bottom: 1.5rem;">
        <?php
        $pmColors = ['cash'=>['bg'=>'#f0fdf4','border'=>'#10b981','text'=>'#065f46'],
                     'card'=>['bg'=>'#eff6ff','border'=>'#3b82f6','text'=>'#1e40af'],
                     'bank'=>['bg'=>'#faf5ff','border'=>'#8b5cf6','text'=>'#5b21b6'],
                     'udhaar'=>['bg'=>'#fff7ed','border'=>'#f59e0b','text'=>'#92400e'],
                     'online'=>['bg'=>'#ecfdf5','border'=>'#34d399','text'=>'#065f46'],
                     'cheque'=>['bg'=>'#f1f5f9','border'=>'#64748b','text'=>'#1e293b']];
        foreach($paymentBreakdown as $pb):
            $pm = strtolower($pb['payment_method'] ?? 'cash');
            $icon  = $paymentIcons[$pm]  ?? '💰';
            $label = $paymentLabels[$pm] ?? ucfirst($pm);
            $color = $pmColors[$pm] ?? ['bg'=>'#f8fafc','border'=>'#94a3b8','text'=>'#0f172a'];
            $pct   = $salesMonth > 0 ? round(($pb['total'] / $salesMonth) * 100, 1) : 0;
        ?>
        <div style="background:<?php echo $color['bg']; ?>; border:1px solid <?php echo $color['border']; ?>; padding:1.4rem; border-radius:10px; border-left:5px solid <?php echo $color['border']; ?>;">
            <div style="font-size:1.8rem; margin-bottom:0.3rem;"><?php echo $icon; ?></div>
            <div style="color:<?php echo $color['text']; ?>; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;"><?php echo $label; ?></div>
            <div style="font-size:1.8rem; font-weight:800; color:<?php echo $color['text']; ?>; margin-top:0.3rem;">Rs.<?php echo number_format($pb['total'], 2); ?></div>
            <div style="display:flex; justify-content:space-between; margin-top:0.5rem; font-size:0.85rem; color:<?php echo $color['text']; ?>; opacity:0.8;">
                <span><?php echo $pb['cnt']; ?> Orders</span>
                <span><?php echo $pct; ?>% of total</span>
            </div>
            <!-- Progress Bar -->
            <div style="background:rgba(0,0,0,0.1); border-radius:999px; height:5px; margin-top:0.8rem;">
                <div style="background:<?php echo $color['border']; ?>; width:<?php echo $pct; ?>%; height:5px; border-radius:999px; transition:width 1s;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Summary Table -->
    <table style="width:100%; border-collapse:collapse; font-size:0.95rem;">
        <thead>
            <tr style="background:#f1f5f9; border-radius:8px;">
                <th style="padding:0.75rem 1rem; text-align:left; color:var(--text-muted); font-weight:600;">Payment Method</th>
                <th style="padding:0.75rem 1rem; text-align:center; color:var(--text-muted); font-weight:600;">Orders</th>
                <th style="padding:0.75rem 1rem; text-align:right; color:var(--text-muted); font-weight:600;">Total Amount</th>
                <th style="padding:0.75rem 1rem; text-align:right; color:var(--text-muted); font-weight:600;">% of Sales</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($paymentBreakdown as $pb):
                $pm = strtolower($pb['payment_method'] ?? 'cash');
                $icon = $paymentIcons[$pm] ?? '💰';
                $label = $paymentLabels[$pm] ?? ucfirst($pm);
                $pct = $salesMonth > 0 ? round(($pb['total'] / $salesMonth) * 100, 1) : 0;
            ?>
            <tr style="border-bottom:1px solid var(--border-color);">
                <td style="padding:0.75rem 1rem; font-weight:600;"><?php echo $icon; ?> <?php echo $label; ?></td>
                <td style="padding:0.75rem 1rem; text-align:center;"><?php echo $pb['cnt']; ?></td>
                <td style="padding:0.75rem 1rem; text-align:right; font-weight:700; color:var(--primary-color);">Rs.<?php echo number_format($pb['total'], 2); ?></td>
                <td style="padding:0.75rem 1rem; text-align:right; color:var(--text-muted);"><?php echo $pct; ?>%</td>
            </tr>
            <?php endforeach; ?>
            <tr style="background:#f8fafc; font-weight:700; border-top:2px solid var(--border-color);">
                <td style="padding:0.75rem 1rem;">TOTAL</td>
                <td style="padding:0.75rem 1rem; text-align:center;"><?php echo array_sum(array_column($paymentBreakdown,'cnt')); ?></td>
                <td style="padding:0.75rem 1rem; text-align:right; color:var(--success);">Rs.<?php echo number_format($salesMonth, 2); ?></td>
                <td style="padding:0.75rem 1rem; text-align:right;">100%</td>
            </tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Chart Card -->
    <div class="animate-slide-up delay-100" style="background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem; color: var(--text-main);">Daily Revenue Trend</h3>
        <canvas id="salesChart" width="400" height="150"></canvas>
    </div>

    <!-- Top Selling Products -->
    <div class="animate-slide-up delay-200" style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
        <h3 style="margin-bottom: 1.5rem; color: var(--text-main);">🔥 Top Selling Products</h3>
        
        <?php if($topSelling && $topSelling->num_rows > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php $rank = 1; while($product = $topSelling->fetch_assoc()): ?>
                    <div style="display: flex; align-items: center; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                        <div style="font-weight: 800; font-size: 1.2rem; color: var(--text-muted); width: 20px;"><?php echo $rank++; ?></div>
                        <?php if($product['image']): ?>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            <div style="width: 48px; height: 48px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #64748b;">N/A</div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.95rem;"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Sold: <?php echo $product['qty']; ?> items</div>
                        </div>
                        <div style="font-weight: 700; color: var(--primary-color);">
                            $<?php echo number_format($product['rev'], 2); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted); text-align: center; margin-top: 2rem;">No sales data available for this month.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Yearly Summary Table -->
<div class="animate-slide-up delay-300" style="background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem;">
    <h3 style="margin-bottom: 1.5rem; color: var(--text-main);">Month-by-Month Summary (<?php echo $year; ?>)</h3>
    <div class="table-wrapper" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Month</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Sales Revenue</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Supplier Payments</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Expenses</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Net Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $monthNames = [1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June', 7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December'];
                foreach($monthlyData as $m => $data): 
                    $isCurrent = ($m == (int)date('m') && $year == date('Y'));
                ?>
                <tr style="border-bottom: 1px solid var(--border-color); <?php echo $isCurrent ? 'background: #f8fafc;' : ''; ?> transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='<?php echo $isCurrent ? '#f8fafc' : 'transparent'; ?>'">
                    <td style="padding: 1rem; font-weight: 600; color: var(--text-main);">
                        <?php echo $monthNames[$m]; ?>
                        <?php if($isCurrent) echo "<span style='margin-left:8px; font-size:0.7rem; background:var(--primary-color); color:white; padding:2px 6px; border-radius:4px;'>Current</span>"; ?>
                    </td>
                    <td style="padding: 1rem; color: var(--text-main); font-weight: 500;">$<?php echo number_format($data['sales'], 2); ?></td>
                    <td style="padding: 1rem; color: #eab308; font-weight: 500;">$<?php echo number_format($data['purchases'], 2); ?></td>
                    <td style="padding: 1rem; color: var(--danger); font-weight: 500;">$<?php echo number_format($data['expenses'], 2); ?></td>
                    <td style="padding: 1rem; font-weight: 700; color: <?php echo $data['net'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                        $<?php echo number_format($data['net'], 2); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Historical Z-Reports (Session Snapshots) for Selected Month -->
<div class="animate-slide-up delay-400" style="background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="color: var(--text-main); margin: 0;">Session Snapshots (<?php echo date('F Y', strtotime($selectedMonthStr)); ?>)</h3>
        <a href="historical_z_reports.php" class="btn btn-primary" style="text-decoration: none; font-size: 0.9rem; padding: 0.5rem 1rem;">View All History</a>
    </div>
    <div class="table-wrapper" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Date & Time</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Orders</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Net Income</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Opening Cash</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Expected Cash</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Closing Cash</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stmt = $conn->prepare("SELECT * FROM z_reports_history WHERE MONTH(COALESCE(report_date, created_at)) = ? AND YEAR(COALESCE(report_date, created_at)) = ? AND tenant_id = {$_SESSION['tenant_id']} ORDER BY created_at DESC");
                $stmt->bind_param("ii", $month, $year);
                $stmt->execute();
                $zReports = $stmt->get_result();
                $stmt->close();
                
                if ($zReports && $zReports->num_rows > 0):
                    while($z = $zReports->fetch_assoc()):
                ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 1rem; font-weight: 500; color: var(--text-main);">
                        <?php echo date('d M Y, h:i A', strtotime($z['created_at'])); ?>
                    </td>
                    <td style="padding: 1rem;"><?php echo $z['total_orders']; ?></td>
                    <td style="padding: 1rem; font-weight: 700; color: <?php echo $z['net_income'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                        $<?php echo number_format($z['net_income'], 2); ?>
                    </td>
                    <td style="padding: 1rem; font-weight: 600;">$<?php echo number_format($z['opening_cash'] ?? 0, 2); ?></td>
                    <td style="padding: 1rem; color: var(--primary-color); font-weight: 600;">$<?php echo number_format($z['expected_cash'], 2); ?></td>
                    <td style="padding: 1rem; font-weight: 600; color: <?php echo ($z['closing_cash'] >= $z['expected_cash']) ? 'var(--success)' : 'var(--danger)'; ?>;">
                        $<?php echo number_format($z['closing_cash'], 2); ?>
                    </td>
                    <td style="padding: 1rem;">
                        <a href="view_z_report.php?id=<?php echo $z['id']; ?>" class="btn action-btn btn-edit" style="text-decoration:none;">📄 View Details</a>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-muted);">No session snapshots found for this month.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== PAST MONTHLY HISTORY (Cleared/Archived Data) ===== -->
<?php
$histQ = $conn->prepare("SELECT * FROM monthly_reports_history WHERE MONTH(COALESCE(report_month, created_at)) = ? AND YEAR(COALESCE(report_month, created_at)) = ? AND tenant_id = {$_SESSION['tenant_id']} ORDER BY id DESC");
$histQ->bind_param("ii", $month, $year);
$histQ->execute();
$histResult = $histQ->get_result();
$histQ->close();

if ($histResult && $histResult->num_rows > 0):
    $allHist = $histResult->fetch_all(MYSQLI_ASSOC);
    $histTotalSales = array_sum(array_column($allHist, 'total_sales'));
    $histTotalExp = array_sum(array_column($allHist, 'total_expenses'));
    $histTotalPurch = array_sum(array_column($allHist, 'total_supplier_payments'));
    $histNet = $histTotalSales - $histTotalExp - $histTotalPurch;
?>
<div class="animate-slide-up" style="background: #fffbeb; border: 2px solid #f59e0b; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
            <h3 style="color: #92400e; margin:0;">📦 Archived Monthly History — <?php echo date('F Y', strtotime($selectedMonthStr)); ?></h3>
            <p style="color:#b45309; margin:0.3rem 0 0 0; font-size:0.9rem;">Yeh data End-of-Month ke waqt archive ho gaya tha. Yeh aapki poori history hai.</p>
        </div>
        <span style="background:#f59e0b; color:white; padding:0.3rem 0.8rem; border-radius:999px; font-size:0.8rem; font-weight:700;">ARCHIVED</span>
    </div>

    <!-- Summary Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem;">
        <div style="background:white; padding:1.2rem; border-radius:8px; border-left:4px solid #10b981;">
            <div style="color:#64748b; font-size:0.8rem; font-weight:600; text-transform:uppercase;">Total Sales</div>
            <div style="font-size:1.8rem; font-weight:800; color:#10b981;">Rs.<?php echo number_format($histTotalSales, 2); ?></div>
        </div>
        <div style="background:white; padding:1.2rem; border-radius:8px; border-left:4px solid #ef4444;">
            <div style="color:#64748b; font-size:0.8rem; font-weight:600; text-transform:uppercase;">Total Expenses</div>
            <div style="font-size:1.8rem; font-weight:800; color:#ef4444;">Rs.<?php echo number_format($histTotalExp, 2); ?></div>
        </div>
        <div style="background:white; padding:1.2rem; border-radius:8px; border-left:4px solid #f59e0b;">
            <div style="color:#64748b; font-size:0.8rem; font-weight:600; text-transform:uppercase;">Supplier Payments</div>
            <div style="font-size:1.8rem; font-weight:800; color:#f59e0b;">Rs.<?php echo number_format($histTotalPurch, 2); ?></div>
        </div>
        <div style="background:white; padding:1.2rem; border-radius:8px; border-left:4px solid <?php echo $histNet >= 0 ? '#3b82f6' : '#ef4444'; ?>;">
            <div style="color:#64748b; font-size:0.8rem; font-weight:600; text-transform:uppercase;">Net Income</div>
            <div style="font-size:1.8rem; font-weight:800; color:<?php echo $histNet >= 0 ? '#3b82f6' : '#ef4444'; ?>;">Rs.<?php echo number_format($histNet, 2); ?></div>
        </div>
    </div>

    <!-- Detail Table -->
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#fef3c7;">
                <th style="padding:0.75rem; text-align:left; color:#92400e; font-size:0.85rem;">#</th>
                <th style="padding:0.75rem; text-align:left; color:#92400e; font-size:0.85rem;">Archived On</th>
                <th style="padding:0.75rem; text-align:right; color:#92400e; font-size:0.85rem;">Sales</th>
                <th style="padding:0.75rem; text-align:right; color:#92400e; font-size:0.85rem;">Expenses</th>
                <th style="padding:0.75rem; text-align:right; color:#92400e; font-size:0.85rem;">Purchases</th>
                <th style="padding:0.75rem; text-align:right; color:#92400e; font-size:0.85rem;">Net</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($allHist as $i => $h): $hNet = $h['total_sales'] - $h['total_expenses'] - $h['total_supplier_payments']; ?>
            <tr style="border-bottom:1px solid #fde68a;">
                <td style="padding:0.75rem; color:#64748b;"><?php echo $i+1; ?></td>
                <td style="padding:0.75rem; font-weight:500;"><?php echo date('d M Y', strtotime($h['report_month'] ?? $h['created_at'] ?? 'now')); ?></td>
                <td style="padding:0.75rem; text-align:right; color:#10b981; font-weight:700;">Rs.<?php echo number_format($h['total_sales'], 2); ?></td>
                <td style="padding:0.75rem; text-align:right; color:#ef4444;">Rs.<?php echo number_format($h['total_expenses'], 2); ?></td>
                <td style="padding:0.75rem; text-align:right; color:#f59e0b;">Rs.<?php echo number_format($h['total_supplier_payments'], 2); ?></td>
                <td style="padding:0.75rem; text-align:right; font-weight:700; color:<?php echo $hNet >= 0 ? '#3b82f6' : '#ef4444'; ?>;">Rs.<?php echo number_format($hNet, 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Total Sales ($)',
                data: <?php echo json_encode($chartData); ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: {
                        callback: function(value) { return '$' + value; },
                        font: { family: "'Poppins', sans-serif" }
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: "'Poppins', sans-serif" } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 12,
                    titleFont: { size: 13, family: "'Poppins', sans-serif" },
                    bodyFont: { size: 14, weight: 'bold', family: "'Poppins', sans-serif" }
                }
            }
        }
    });

    function downloadReportPDF() {
        const element = document.querySelector('main.container') || document.body;
        const opt = {
            margin:       0.3,
            filename:     'monthly_report_<?php echo $selectedMonthStr; ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { 
                scale: 4, 
                useCORS: true,
                onclone: function(clonedDoc) {
                    // Hide any elements if needed here, without breaking active DOM
                }
            },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        
        const btn = document.querySelector('button[onclick="downloadReportPDF()"]');
        if(btn) btn.innerText = "⏳...";
        
        window.scrollTo(0,0);
        
        html2pdf().set(opt).from(element).save().then(() => {
            if(btn) btn.innerText = "📄 PDF";
        });
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
