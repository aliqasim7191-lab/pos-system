<?php
include 'includes/db.php';
include 'includes/header.php';

$saleDate  = isset($_GET['sale_date']) ? trim($_GET['sale_date']) : '';
$saleMonth = isset($_GET['sale_month']) ? trim($_GET['sale_month']) : '';
$showHistory = (isset($_GET['history']) && $_GET['history'] == 1) || !empty($saleDate) || !empty($saleMonth);

// Base SQL conditions
$settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings");
$sysSettings = [];
if ($settingsQ) {
    while($r = $settingsQ->fetch_assoc()) {
        $sysSettings[$r['setting_key']] = $r['setting_value'];
    }
}
$storeName = $sysSettings['store_name'] ?? 'SuperStore POS';
$storePhone = $sysSettings['store_phone'] ?? '';
$storeAddress = $sysSettings['store_address'] ?? '';
$taxId = $sysSettings['tax_id'] ?? '';
$currencySymbol = $sysSettings['currency_symbol'] ?? '$';

$whereClauses = ["s.tenant_id = {$_SESSION['tenant_id']}"];
if (!$isAdmin) {
    $whereClauses[] = "s.branch_id = $current_branch_id";
}

if (!empty($saleDate)) {
    $safeDate = $conn->real_escape_string($saleDate);
    $whereClauses[] = "DATE(s.created_at) = '$safeDate'";
    $filterTitle = "Sales on " . date('d M Y', strtotime($saleDate));
    $filterDesc = "Showing all sales recorded on " . date('F j, Y', strtotime($saleDate)) . ".";
} elseif (!empty($saleMonth)) {
    $parts = explode('-', $saleMonth);
    $y = (int)($parts[0] ?? date('Y'));
    $m = (int)($parts[1] ?? date('m'));
    $whereClauses[] = "MONTH(s.created_at) = $m AND YEAR(s.created_at) = $y";
    $filterTitle = "Sales for " . date('F Y', strtotime($saleMonth . '-01'));
    $filterDesc = "Showing all sales recorded in " . date('F Y', strtotime($saleMonth . '-01')) . ".";
} elseif ($showHistory) {
    $filterTitle = "All Historical Sales";
    $filterDesc = "Showing all historical sales records from the database.";
} else {
    $whereClauses[] = "s.is_cleared = 0";
    $filterTitle = "Current Active Sales";
    $filterDesc = "Showing sales from the current open shift. Cleared after End of Day or Shift Close.";
}

$whereSQL = implode(' AND ', $whereClauses);
$query = "SELECT s.*, b.name as branch_name FROM sales s LEFT JOIN branches b ON s.branch_id = b.id WHERE $whereSQL ORDER BY s.created_at DESC";
$result = $conn->query($query);

$sales = [];
$totalSalesRevenue = 0;
$totalSalesDiscount = 0;
$totalCashSales = 0;

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $sales[] = $row;
        $totalSalesRevenue += floatval($row['total_amount'] ?? 0);
        $totalSalesDiscount += floatval($row['discount'] ?? 0);
        if (strtolower($row['payment_method'] ?? 'cash') === 'cash') {
            $totalCashSales += floatval($row['total_amount'] ?? 0);
        }
    }
}

// 1. Calculate COGS & Gross Profit
$totalCOGS = 0;
if (!empty($sales)) {
    $saleIds = array_column($sales, 'id');
    $idsStr = implode(',', array_map('intval', $saleIds));
    if (!empty($idsStr)) {
        // Auto-correct any corrupted/abnormal cost prices in sale_items table
        $conn->query("UPDATE sale_items SET cost_price = price * 0.7 WHERE sale_id IN ($idsStr) AND (cost_price > price * 3 OR cost_price > 1000)");

        $cogsQ = $conn->query("SELECT SUM(quantity * LEAST(cost_price, price)) as cogs FROM sale_items WHERE sale_id IN ($idsStr)");
        if ($cogsQ) {
            $totalCOGS = floatval($cogsQ->fetch_assoc()['cogs'] ?? 0);
        }
    }
}
$totalProfit = $totalSalesRevenue - $totalCOGS;

// 2. Calculate Opening Cash & Total Expected Cash safely (handling shifts and z_reports_history column names)
$openingCash = 0;
if (!empty($saleDate)) {
    $sDateSafe = $conn->real_escape_string($saleDate);
    $zhQ = $conn->query("SELECT SUM(opening_cash) as oc FROM z_reports_history WHERE (DATE(report_date) = '$sDateSafe' OR DATE(created_at) = '$sDateSafe') AND tenant_id = {$_SESSION['tenant_id']}");
    $openingCash = floatval($zhQ ? ($zhQ->fetch_assoc()['oc'] ?? 0) : 0);
    if ($openingCash == 0) {
        $shQ = $conn->query("SELECT SUM(opening_cash) as oc FROM shifts WHERE (DATE(opened_at) = '$sDateSafe' OR DATE(closed_at) = '$sDateSafe') AND tenant_id = {$_SESSION['tenant_id']}");
        $openingCash = floatval($shQ ? ($shQ->fetch_assoc()['oc'] ?? 0) : 0);
    }
} elseif (!empty($saleMonth)) {
    $parts = explode('-', $saleMonth);
    $y = (int)($parts[0] ?? date('Y'));
    $m = (int)($parts[1] ?? date('m'));
    $zhQ = $conn->query("SELECT SUM(opening_cash) as oc FROM z_reports_history WHERE (MONTH(COALESCE(report_date, created_at)) = $m AND YEAR(COALESCE(report_date, created_at)) = $y) AND tenant_id = {$_SESSION['tenant_id']}");
    $openingCash = floatval($zhQ ? ($zhQ->fetch_assoc()['oc'] ?? 0) : 0);
    if ($openingCash == 0) {
        $shQ = $conn->query("SELECT SUM(opening_cash) as oc FROM shifts WHERE (MONTH(opened_at) = $m AND YEAR(opened_at) = $y) AND tenant_id = {$_SESSION['tenant_id']}");
        $openingCash = floatval($shQ ? ($shQ->fetch_assoc()['oc'] ?? 0) : 0);
    }
} elseif (!$showHistory) {
    $shQ = $conn->query("SELECT SUM(opening_cash) as oc FROM shifts WHERE is_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
    $openingCash = floatval($shQ ? ($shQ->fetch_assoc()['oc'] ?? 0) : 0);
} else {
    $zhQ = $conn->query("SELECT SUM(opening_cash) as oc FROM z_reports_history WHERE tenant_id = {$_SESSION['tenant_id']}");
    $openingCash = floatval($zhQ ? ($zhQ->fetch_assoc()['oc'] ?? 0) : 0);
    if ($openingCash == 0) {
        $shQ = $conn->query("SELECT SUM(opening_cash) as oc FROM shifts WHERE tenant_id = {$_SESSION['tenant_id']}");
        $openingCash = floatval($shQ ? ($shQ->fetch_assoc()['oc'] ?? 0) : 0);
    }
}

// 3. Calculate Supplier Payments for the active filter view
$totalSupplierPayments = 0;
if (!empty($saleDate)) {
    $sDateSafe = $conn->real_escape_string($saleDate);
    $pQ = $conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE DATE(created_at) = '$sDateSafe' AND tenant_id = {$_SESSION['tenant_id']}");
    $totalSupplierPayments = floatval($pQ ? ($pQ->fetch_assoc()['p'] ?? 0) : 0);
} elseif (!empty($saleMonth)) {
    $parts = explode('-', $saleMonth);
    $y = (int)($parts[0] ?? date('Y'));
    $m = (int)($parts[1] ?? date('m'));
    $pQ = $conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE MONTH(created_at) = $m AND YEAR(created_at) = $y AND tenant_id = {$_SESSION['tenant_id']}");
    $totalSupplierPayments = floatval($pQ ? ($pQ->fetch_assoc()['p'] ?? 0) : 0);
} elseif (!$showHistory) {
    $pQ = $conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE is_cleared = 0 AND tenant_id = {$_SESSION['tenant_id']}");
    $totalSupplierPayments = floatval($pQ ? ($pQ->fetch_assoc()['p'] ?? 0) : 0);
} else {
    $pQ = $conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE tenant_id = {$_SESSION['tenant_id']}");
    $totalSupplierPayments = floatval($pQ ? ($pQ->fetch_assoc()['p'] ?? 0) : 0);
}

// Net Total Cash (Cash Sales minus Supplier Payments)
$totalCashOnly = $totalCashSales - $totalSupplierPayments;
$totalExpectedCash = $openingCash + $totalCashSales - $totalSupplierPayments;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_sale') {
    $delId = intval($_POST['id']);
    $conn->query("DELETE FROM sale_items WHERE sale_id = $delId");
    $conn->query("DELETE FROM sales WHERE id = $delId");
    $redirectURL = "sales.php";
    $params = [];
    if(!empty($saleDate)) $params['sale_date'] = $saleDate;
    if(!empty($saleMonth)) $params['sale_month'] = $saleMonth;
    if($showHistory && empty($saleDate) && empty($saleMonth)) $params['history'] = 1;
    if(!empty($params)) $redirectURL .= "?" . http_build_query($params);
    echo "<script>window.location.href='$redirectURL';</script>";
    exit;
}
?>

<div style="padding-left: 2.75rem; padding-right: 1.5rem; box-sizing: border-box; width: 100%;">
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom: 1.25rem; width: 100%;">
    <div>
        <h2 style="font-size: 1.75rem; color: #0f172a; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($filterTitle); ?></h2>
        <p style="color: #64748b; font-size: 0.9rem; margin:0;">
            <?php echo htmlspecialchars($filterDesc); ?>
        </p>
    </div>

    <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; margin-left: auto; justify-content: flex-end;">
        <!-- Reprint Receipt Form -->
        <form method="GET" action="receipt.php" target="_blank" style="display:flex; gap:0.4rem; align-items:center; background: #ffffff; padding: 0.35rem 0.6rem; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <input type="number" name="id" placeholder="Enter Sale ID..." required style="padding: 0.45rem 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1; width: 160px; font-size:0.92rem; outline:none;">
            <button type="submit" class="btn btn-primary" style="padding: 0.45rem 1.1rem; border-radius: 6px; font-weight: 600; font-size:0.92rem; cursor:pointer;" title="Reprint Receipt by ID">🖨️ Reprint</button>
        </form>

        <!-- Date & Month Filter Form -->
        <form method="GET" action="sales.php" style="display: flex; gap: 0.4rem; align-items: center; background: #ffffff; padding: 0.35rem 0.55rem; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 0.25rem;">
                <label style="font-size: 0.78rem; font-weight: 600; color: #475569;">Date:</label>
                <input type="date" name="sale_date" value="<?php echo htmlspecialchars($saleDate); ?>" style="padding: 0.3rem 0.45rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.82rem; outline:none;" onchange="if(this.value) this.form.sale_month.value='';">
            </div>

            <div style="display: flex; align-items: center; gap: 0.25rem;">
                <label style="font-size: 0.78rem; font-weight: 600; color: #475569;">Month:</label>
                <input type="month" name="sale_month" value="<?php echo htmlspecialchars($saleMonth); ?>" style="padding: 0.3rem 0.45rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.82rem; outline:none;" onchange="if(this.value) this.form.sale_date.value='';">
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 0.35rem 0.85rem; font-size: 0.82rem; font-weight: 600; border-radius: 6px; border: none; cursor: pointer;">
                🔍 Filter
            </button>

            <?php if(!empty($saleDate) || !empty($saleMonth) || $showHistory): ?>
                <a href="sales.php" class="btn" style="background: #f1f5f9; color: #334155; padding: 0.35rem 0.7rem; font-size: 0.82rem; font-weight: 600; border-radius: 6px; text-decoration: none; border: 1px solid #cbd5e1;">Active Sales</a>
            <?php endif; ?>

            <?php if(!$showHistory && empty($saleDate) && empty($saleMonth)): ?>
                <a href="sales.php?history=1" class="btn" style="background: #ffffff; color: #475569; padding: 0.35rem 0.7rem; font-size: 0.82rem; border-radius: 6px; font-weight: 600; text-decoration: none; border: 1px solid #cbd5e1;">📚 All Sales</a>
            <?php endif; ?>

            <button type="button" onclick="openSalesPrintModal()" class="btn" style="background: #10b981; color: white; padding: 0.35rem 0.8rem; font-size: 0.82rem; border-radius: 6px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;" title="Print & PDF Sales Statement">
                🖨️ Print
            </button>
        </form>
    </div>
</div>

<!-- Compact Summary Cards Bar (6 Cards) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; margin-bottom: 1.5rem;">
    <!-- 1. Total Orders -->
    <div style="background: #ffffff; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #2563eb; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.02em;">Total Orders</div>
        <div style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-top: 0.15rem;"><?php echo count($sales); ?></div>
    </div>
    
    <!-- 2. Total Sales Revenue -->
    <div style="background: #ffffff; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.02em;">Total Revenue</div>
        <div style="font-size: 1.25rem; font-weight: 800; color: #059669; margin-top: 0.15rem;">$<?php echo number_format($totalSalesRevenue, 2); ?></div>
    </div>

    <!-- 3. Total Profit -->
    <div style="background: #ffffff; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #0d9488; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.02em;">Total Profit</div>
        <div style="font-size: 1.25rem; font-weight: 800; color: <?php echo $totalProfit >= 0 ? '#0d9488' : '#e11d48'; ?>; margin-top: 0.15rem;">
            $<?php echo number_format($totalProfit, 2); ?>
        </div>
    </div>

    <!-- 4. Supplier Payments -->
    <div style="background: #ffffff; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #eab308; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.02em;">Supplier Payments</div>
        <div style="font-size: 1.25rem; font-weight: 800; color: #ca8a04; margin-top: 0.15rem;">-$<?php echo number_format($totalSupplierPayments, 2); ?></div>
    </div>

    <!-- 5. Total Opening Cash -->
    <div style="background: #ffffff; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #d97706; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.02em;">Opening Cash</div>
        <div style="font-size: 1.25rem; font-weight: 800; color: #d97706; margin-top: 0.15rem;">$<?php echo number_format($openingCash, 2); ?></div>
    </div>

    <!-- 6. Total Cash Sales -->
    <div style="background: #ffffff; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #7c3aed; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.02em;">Total Cash</div>
        <div style="font-size: 1.25rem; font-weight: 800; color: #7c3aed; margin-top: 0.15rem;">$<?php echo number_format($totalCashOnly, 2); ?></div>
    </div>
</div>

<div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; background: #ffffff;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
            <tr>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Sale ID</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Date / Time</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Customer</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Payment Method</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Discount</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Total Paid</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $pmIcons  = ['cash'=>'💵','card'=>'💳','bank'=>'🏦','udhaar'=>'📒','online'=>'📱','cheque'=>'📝'];
            $pmLabels = ['cash'=>'Cash','card'=>'Card','bank'=>'Bank','udhaar'=>'Udhaar','online'=>'Online','cheque'=>'Cheque'];
            $pmStyles = ['cash'  =>'background:#f0fdf4;color:#065f46;border:1px solid #10b981;',
                         'card'  =>'background:#eff6ff;color:#1e40af;border:1px solid #3b82f6;',
                         'bank'  =>'background:#faf5ff;color:#5b21b6;border:1px solid #8b5cf6;',
                         'udhaar'=>'background:#fff7ed;color:#92400e;border:1px solid #f59e0b;',
                         'online'=>'background:#ecfdf5;color:#065f46;border:1px solid #34d399;',
                         'cheque'=>'background:#f1f5f9;color:#1e293b;border:1px solid #64748b;'];
            foreach($sales as $sale):
                $pm = strtolower($sale['payment_method'] ?? 'cash');
                $pmIcon  = $pmIcons[$pm]  ?? '💰';
                $pmLabel = $pmLabels[$pm] ?? ucfirst($pm);
                $pmStyle = $pmStyles[$pm] ?? 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;';
            ?>
            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                <td style="padding: 1rem; color: #64748b; font-weight: 500;">#<?php echo $sale['id']; ?></td>
                <td style="padding: 1rem; color: #0f172a;"><?php echo date('M d, Y h:i A', strtotime($sale['created_at'])); ?></td>
                <td style="padding: 1rem;">
                    <span style="background: #f1f5f9; color: #475569; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500;">
                        <?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?>
                    </span>
                </td>
                <td style="padding: 1rem;">
                    <span style="<?php echo $pmStyle; ?> padding: 0.3rem 0.7rem; border-radius: 999px; font-size: 0.82rem; font-weight: 700; display:inline-flex; align-items:center; gap:0.3rem; white-space:nowrap;">
                        <?php echo $pmIcon; ?> <?php echo $pmLabel; ?>
                    </span>
                </td>
                <td style="padding: 1rem; color: #ef4444; font-weight: 500;">-$<?php echo number_format($sale['discount'] ?? 0, 2); ?></td>
                <td style="padding: 1rem; font-weight: 700; color: #0f172a;">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                <td style="padding: 0.75rem 1rem; vertical-align: middle;">
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button onclick="viewDetails(<?php echo $sale['id']; ?>)" style="background: #2563eb; color: #ffffff; border: none; padding: 0 0.9rem; height: 36px; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 2px 4px rgba(37,99,235,0.2);" onmouseover="this.style.background='#059669'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(5,150,105,0.4)';" onmouseout="this.style.background='#2563eb'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(37,99,235,0.2)';">
                            View Details
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this sale? This cannot be undone.');" style="margin:0; display:inline-block;">
                            <input type="hidden" name="action" value="delete_sale">
                            <input type="hidden" name="id" value="<?php echo $sale['id']; ?>">
                            <button type="submit" style="background: #ef4444; color: #ffffff; border: none; width: 36px; height: 36px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 2px 4px rgba(239,68,68,0.2);" onmouseover="this.style.background='#991b1b'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(153,27,27,0.4)';" onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(239,68,68,0.2)';" title="Delete Sale">
                                🗑️
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($sales)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 4rem; color: #64748b;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🧾</div>
                    <h3 style="margin: 0; color: #0f172a; font-size: 1.2rem;">No Sales Found</h3>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem;">No sales records found for the selected filter.</p>
                    <a href="sales.php" class="btn btn-primary" style="margin-top: 1.5rem; text-decoration:none; display:inline-block;">View Active Sales</a>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Sale Details Modal -->
<div id="saleDetailsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius:16px; width:500px; max-width:90%; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <h2 style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Sale Details <span id="modalSaleId" style="color: var(--primary-color);"></span></h2>
        
        <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-muted);">
            <div><strong>Date:</strong> <span id="modalSaleDate"></span></div>
            <div><strong>Customer:</strong> <span id="modalSaleCustomer"></span></div>
        </div>

        <div style="max-height: 250px; overflow-y: auto; margin-bottom: 1.5rem; border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
            <table style="width: 100%; font-size: 0.95rem;">
                <thead style="border-bottom: 2px solid var(--border-color);">
                    <tr>
                        <th style="padding-bottom:0.5rem;">Item</th>
                        <th style="padding-bottom:0.5rem; text-align:center;">Qty</th>
                        <th style="padding-bottom:0.5rem; text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="modalSaleItemsList">
                    <!-- Items injected here -->
                </tbody>
            </table>
        </div>

        <div style="text-align: right; margin-bottom: 1.5rem;">
            <div style="font-size: 0.95rem; color: var(--danger);">Discount: <span id="modalSaleDiscount"></span></div>
            <div style="font-size: 1.2rem; font-weight: bold; color: var(--primary-color);">Total Paid: <span id="modalSaleTotal"></span></div>
        </div>

        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
            <a id="modalPrintReceiptBtn" href="#" target="_blank" style="padding: 0.6rem 1.3rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.45rem; background: #2563eb; color: #ffffff; font-size: 0.9rem; box-shadow: 0 2px 6px rgba(37,99,235,0.25);">🖨️ Print Receipt</a>
            <button onclick="closeDetailsModal()" style="padding: 0.6rem 1.3rem; border-radius: 8px; font-weight: 600; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 0.9rem; cursor: pointer;">Close</button>
        </div>
    </div>
</div>

<script>
function viewDetails(saleId) {
    fetch('api_sale_details.php?id=' + saleId)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('modalSaleId').innerText = '#' + data.sale.id;
                document.getElementById('modalSaleDate').innerText = new Date(data.sale.created_at).toLocaleString();
                document.getElementById('modalSaleCustomer').innerText = data.sale.customer_name ? data.sale.customer_name : 'Walk-in';
                document.getElementById('modalPrintReceiptBtn').href = 'receipt.php?id=' + data.sale.id;
                
                let tbody = document.getElementById('modalSaleItemsList');
                tbody.innerHTML = '';
                
                data.items.forEach(item => {
                    let subtotal = parseFloat(item.price) * parseFloat(item.quantity);
                    tbody.innerHTML += `
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.5rem 0;">${item.name}</td>
                            <td style="padding: 0.5rem 0; text-align:center;">${item.quantity} ${item.unit}</td>
                            <td style="padding: 0.5rem 0; text-align:right;">$${subtotal.toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                document.getElementById('modalSaleDiscount').innerText = '-$' + parseFloat(data.sale.discount || 0).toFixed(2);
                document.getElementById('modalSaleTotal').innerText = '$' + parseFloat(data.sale.total_amount).toFixed(2);
                
                document.getElementById('saleDetailsModal').style.display = 'flex';
            } else {
                alert(data.message);
            }
        })
        .catch(err => console.error(err));
}

function closeDetailsModal() {
    document.getElementById('saleDetailsModal').style.display = 'none';
}
</script>

<!-- Sales Bill & Report Statement Modal -->
<div id="salesPrintModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.65); align-items:center; justify-content:center; z-index:9999; backdrop-filter:blur(4px); padding: 1rem;">
    <div style="background:#ffffff; border-radius:16px; width:780px; max-width:95%; max-height:92vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border: 1px solid #e2e8f0;">
        
        <!-- Modal Action Header -->
        <div style="padding: 1rem 1.5rem; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #334155;">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <span style="font-size: 1.3rem;">🧾</span>
                <h3 style="margin:0; font-size: 1.05rem; font-weight: 700; color: #ffffff;">Sales Bill & Report Statement</h3>
            </div>
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <button type="button" onclick="doPrintReport()" style="background: #2563eb; color: #ffffff; border: none; padding: 0.45rem 1rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 2px 4px rgba(37,99,235,0.3);">
                    🖨️ PRINT
                </button>
                <button type="button" id="pdfDownloadBtn" onclick="downloadReportPDF()" style="background: #059669; color: #ffffff; border: none; padding: 0.45rem 1rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 2px 4px rgba(5,150,105,0.3);">
                    📄 PDF
                </button>
                <button type="button" onclick="closeSalesPrintModal()" style="background: #334155; color: #cbd5e1; border: none; padding: 0.45rem 0.85rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                    ✖ Close
                </button>
            </div>
        </div>

        <!-- Printable Bill Content -->
        <div style="flex: 1; overflow-y: auto; padding: 1.75rem; background: #f8fafc;">
            <div id="bill-statement-printable" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); color: #0f172a; font-family: system-ui, -apple-system, sans-serif;">
                
                <!-- Store Info & Logo -->
                <div style="text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                    <div style="width: 50px; height: 50px; background: #0f172a; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem auto;">
                        <?php echo htmlspecialchars(substr($storeName, 0, 1)); ?>
                    </div>
                    <h2 style="margin: 0 0 0.2rem 0; font-size: 1.5rem; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; color: #0f172a;">
                        <?php echo htmlspecialchars($storeName); ?>
                    </h2>
                    <?php if(!empty($storeAddress)): ?>
                        <p style="margin: 0.15rem 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($storeAddress); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($storePhone)): ?>
                        <p style="margin: 0.15rem 0; font-size: 0.85rem; color: #475569;">Tel: <?php echo htmlspecialchars($storePhone); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($taxId)): ?>
                        <p style="margin: 0.15rem 0; font-size: 0.82rem; font-weight: 600; color: #475569;">NTN / Tax ID: <?php echo htmlspecialchars($taxId); ?></p>
                    <?php endif; ?>

                    <div style="margin-top: 0.85rem; display: inline-block; background: #f1f5f9; padding: 0.35rem 1rem; border-radius: 999px; border: 1px solid #e2e8f0;">
                        <span style="font-weight: 800; font-size: 0.88rem; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                            🧾 OFFICIAL SALES STATEMENT
                        </span>
                    </div>
                </div>

                <!-- Bill Header Info -->
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.83rem; color: #475569; margin-bottom: 1.25rem; background: #f8fafc; padding: 0.65rem 0.9rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div><strong>Report Period:</strong> <?php echo htmlspecialchars($filterTitle); ?></div>
                    <div><strong>Printed On:</strong> <?php echo date('d M Y, h:i A'); ?></div>
                </div>

                <!-- Summary Breakdown Table -->
                <div style="margin-bottom: 1.25rem;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Summary Metrics</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.55rem 0.75rem; font-weight: 600; color: #475569;">Total Orders:</td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 800; text-align: right; color: #0f172a;"><?php echo count($sales); ?></td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 600; color: #475569; border-left: 1px solid #e2e8f0;">Total Revenue:</td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 800; text-align: right; color: #2563eb;"><?php echo $currencySymbol . number_format($totalSalesRevenue, 2); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.55rem 0.75rem; font-weight: 600; color: #475569;">Total Profit:</td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 800; text-align: right; color: <?php echo $totalProfit >= 0 ? '#0d9488' : '#e11d48'; ?>;"><?php echo $currencySymbol . number_format($totalProfit, 2); ?></td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 600; color: #475569; border-left: 1px solid #e2e8f0;">Supplier Payments:</td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 800; text-align: right; color: #ca8a04;">-<?php echo $currencySymbol . number_format($totalSupplierPayments, 2); ?></td>
                        </tr>
                        <tr style="background: #f8fafc;">
                            <td style="padding: 0.55rem 0.75rem; font-weight: 600; color: #475569;">Opening Cash:</td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 800; text-align: right; color: #d97706;"><?php echo $currencySymbol . number_format($openingCash, 2); ?></td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 700; color: #0f172a; border-left: 1px solid #e2e8f0;">Total Cash:</td>
                            <td style="padding: 0.55rem 0.75rem; font-weight: 900; text-align: right; color: #7c3aed; font-size: 0.95rem;"><?php echo $currencySymbol . number_format($totalCashOnly, 2); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Detailed Sales Table -->
                <div style="margin-bottom: 1.25rem;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Sales Records</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #cbd5e1; background: #f1f5f9;">
                                <th style="padding: 0.55rem; text-align: left; color: #334155;">Sale ID</th>
                                <th style="padding: 0.55rem; text-align: left; color: #334155;">Date / Time</th>
                                <th style="padding: 0.55rem; text-align: left; color: #334155;">Customer</th>
                                <th style="padding: 0.55rem; text-align: left; color: #334155;">Method</th>
                                <th style="padding: 0.55rem; text-align: right; color: #334155;">Discount</th>
                                <th style="padding: 0.55rem; text-align: right; color: #334155;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($sales as $sItem): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 0.45rem 0.55rem; color: #64748b; font-weight: 600;">#<?php echo $sItem['id']; ?></td>
                                <td style="padding: 0.45rem 0.55rem;"><?php echo date('M d, Y h:i A', strtotime($sItem['created_at'])); ?></td>
                                <td style="padding: 0.45rem 0.55rem;"><?php echo htmlspecialchars($sItem['customer_name'] ?? 'Walk-in'); ?></td>
                                <td style="padding: 0.45rem 0.55rem; font-weight: 600; text-transform: capitalize;"><?php echo htmlspecialchars($sItem['payment_method'] ?? 'Cash'); ?></td>
                                <td style="padding: 0.45rem 0.55rem; text-align: right; color: #ef4444;">-<?php echo $currencySymbol . number_format($sItem['discount'] ?? 0, 2); ?></td>
                                <td style="padding: 0.45rem 0.55rem; text-align: right; font-weight: 700; color: #0f172a;"><?php echo $currencySymbol . number_format($sItem['total_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($sales)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 1.5rem; color: #64748b;">No sales records found for this period.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div style="border-top: 2px dashed #cbd5e1; padding-top: 0.85rem; text-align: center; font-size: 0.78rem; color: #64748b; margin-top: 1.5rem;">
                    <p style="margin: 0.15rem 0; font-weight: 600; color: #334155;"><?php echo htmlspecialchars($storeName); ?> POS • Official Sales Statement</p>
                    <p style="margin: 0;">Cashier: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function openSalesPrintModal() {
    document.getElementById('salesPrintModal').style.display = 'flex';
}

function closeSalesPrintModal() {
    document.getElementById('salesPrintModal').style.display = 'none';
}

function doPrintReport() {
    window.print();
}

function downloadReportPDF() {
    const element = document.getElementById('bill-statement-printable');
    const opt = {
        margin:       0.3,
        filename:     'Sales_Bill_Statement_<?php echo !empty($saleDate) ? $saleDate : (!empty($saleMonth) ? $saleMonth : date('Y-m-d')); ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    
    const btn = document.getElementById('pdfDownloadBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Generating...';
    btn.disabled = true;

    html2pdf().set(opt).from(element).save().then(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }).catch(err => {
        console.error(err);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>

<style>
    @media print {
        header, .main-header, .no-print, form, nav, .sidebar, button, .btn, #kebabDropdown, .table-wrapper { display: none !important; }
        body, .container { background: white !important; box-shadow: none !important; margin: 0 !important; padding-left: 0 !important; }
        
        #salesPrintModal {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: auto !important;
            background: white !important;
            padding: 0 !important;
            display: block !important;
            z-index: 99999 !important;
        }
        #salesPrintModal > div {
            width: 100% !important;
            max-width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            background: white !important;
        }
        #salesPrintModal > div > div:first-child {
            display: none !important;
        }
        #bill-statement-printable {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
    }
</style>
</div>
<?php include 'includes/footer.php'; ?>
