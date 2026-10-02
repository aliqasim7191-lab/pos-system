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

include 'includes/header.php';

// Branch Filters
$bF_WHERE = $isAdmin ? " WHERE tenant_id = " . $_SESSION['tenant_id'] : " WHERE tenant_id = " . $_SESSION['tenant_id'] . " AND branch_id = $current_branch_id";
$bF_AND = $isAdmin ? " AND tenant_id = " . $_SESSION['tenant_id'] : " AND tenant_id = " . $_SESSION['tenant_id'] . " AND branch_id = $current_branch_id";
$bF_WHERE_S = $isAdmin ? " WHERE s.tenant_id = " . $_SESSION['tenant_id'] : " WHERE s.tenant_id = " . $_SESSION['tenant_id'] . " AND s.branch_id = $current_branch_id";
$bF_AND_S = $isAdmin ? " AND s.tenant_id = " . $_SESSION['tenant_id'] : " AND s.tenant_id = " . $_SESSION['tenant_id'] . " AND s.branch_id = $current_branch_id";
$bF_WHERE_P = $isAdmin ? " WHERE p.tenant_id = " . $_SESSION['tenant_id'] : " WHERE p.tenant_id = " . $_SESSION['tenant_id'] . " AND p.branch_id = $current_branch_id";
$bF_AND_P = $isAdmin ? " AND p.tenant_id = " . $_SESSION['tenant_id'] : " AND p.tenant_id = " . $_SESSION['tenant_id'] . " AND p.branch_id = $current_branch_id";


if (!$isAdmin) {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Only administrators can view the dashboard.</p></div>";
    include 'includes/footer.php';
    exit();
}

// 1. Quick Stats (Active Session - Uncleared)
$activeSalesQ = $conn->query("SELECT SUM(total_amount) as total FROM sales WHERE is_cleared = 0 $bF_AND");
$todaySales = $activeSalesQ ? $activeSalesQ->fetch_assoc()['total'] : 0;

$activeCogsQ = $conn->query("SELECT SUM(si.quantity * si.cost_price) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id WHERE s.is_cleared = 0 $bF_AND_S");
$todayCogs = $activeCogsQ ? $activeCogsQ->fetch_assoc()['cogs'] : 0;
$todayProfit = $todaySales - $todayCogs;

$activeShiftsQ = $conn->query("SELECT SUM(opening_cash) as oc FROM shifts WHERE is_cleared = 0 $bF_AND");
$openingCash = $activeShiftsQ ? floatval($activeShiftsQ->fetch_assoc()['oc']) : 0;

$cashSalesQ = $conn->query("SELECT SUM(amount_received - change_returned) as c FROM sales WHERE payment_method = 'cash' AND is_cleared = 0 $bF_AND");
$netCashSales = $cashSalesQ ? floatval($cashSalesQ->fetch_assoc()['c']) : 0;

$expensesQ = $conn->query("SELECT SUM(amount) as e FROM expenses WHERE is_cleared = 0 $bF_AND");
$totalExpenses = $expensesQ ? floatval($expensesQ->fetch_assoc()['e']) : 0;

$supPayQ = $conn->query("SELECT SUM(amount_paid) as total FROM purchases WHERE is_cleared = 0 $bF_AND");
$totalSupplierPayments = $supPayQ ? floatval($supPayQ->fetch_assoc()['total']) : 0;

$expectedCash = $openingCash + $netCashSales - $totalExpenses;

$productsQ = $conn->query("SELECT COUNT(id) as count FROM products $bF_WHERE");
$totalProducts = $productsQ ? $productsQ->fetch_assoc()['count'] : 0;

$lowStockQ = $conn->query("SELECT COUNT(id) as count FROM products WHERE stock <= 10 $bF_AND");
$lowStock = $lowStockQ ? $lowStockQ->fetch_assoc()['count'] : 0;

$g_salesQ = $conn->query("SELECT SUM(total_amount) as total FROM sales WHERE is_cleared = 0 $bF_AND");
$lifetimeTotalSales = $g_salesQ ? floatval($g_salesQ->fetch_assoc()['total']) : 0;

$g_cogsQ = $conn->query("SELECT SUM(si.quantity * si.cost_price) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id $bF_WHERE_S");
$lifetimeTotalCogs = $g_cogsQ ? floatval($g_cogsQ->fetch_assoc()['cogs']) : 0;
$lifetimeTotalProfit = $lifetimeTotalSales - $lifetimeTotalCogs;

$g_ordersQ = $conn->query("SELECT COUNT(id) as count FROM sales WHERE is_cleared = 0 $bF_AND");
$lifetimeTotalOrders = $g_ordersQ ? intval($g_ordersQ->fetch_assoc()['count']) : 0;

$g_udhaarQ = $conn->query("SELECT SUM(total_amount) as ub FROM sales WHERE payment_method = 'credit' AND is_cleared = 0 $bF_AND");
$lifetimeTotalUdhaar = $g_udhaarQ ? floatval($g_udhaarQ->fetch_assoc()['ub']) : 0;

$g_cashQ = $conn->query("SELECT SUM(amount_received - change_returned) as c FROM sales WHERE payment_method = 'cash' AND is_cleared = 0 $bF_AND");
$lifetimeTotalCash = $g_cashQ ? floatval($g_cashQ->fetch_assoc()['c']) : 0;

// Returns calculation
$returnsTotalRefund = 0;
$returnsCount = 0;
$returnsCheckCol = $conn->query("SHOW COLUMNS FROM returns LIKE 'is_cleared'");
if ($returnsCheckCol && $returnsCheckCol->num_rows > 0) {
    $rQ = $conn->query("SELECT SUM(total_refund) as total_refund, COUNT(id) as cnt FROM returns WHERE is_cleared = 0 $bF_AND");
} else {
    $rQ = $conn->query("SELECT SUM(total_refund) as total_refund, COUNT(id) as cnt FROM returns $bF_WHERE");
}
if ($rQ && $rRow = $rQ->fetch_assoc()) {
    $returnsTotalRefund = floatval($rRow['total_refund'] ?? 0);
    $returnsCount = intval($rRow['cnt'] ?? 0);
}

// 2. Weekly Sales & Profit Data for Chart
$weeklySalesData = [];
$weeklyProfitData = [];
$weeklyLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weeklyLabels[] = date('l', strtotime($date)); // Full day name
    $q = $conn->query("SELECT SUM(total_amount) as total FROM sales WHERE DATE(created_at) = '$date' $bF_AND");
    $rev = ($q && $row = $q->fetch_assoc()) ? floatval($row['total']) : 0;
    
    // COGS
    $qc = $conn->query("SELECT SUM(si.quantity * si.cost_price) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id WHERE DATE(s.created_at) = '$date' $bF_AND_S");
    $cogs = ($qc && $row = $qc->fetch_assoc()) ? floatval($row['cogs']) : 0;
    
    $weeklySalesData[] = $rev;
    $weeklyProfitData[] = $rev - $cogs;
}

// 3. Top 5 Selling Products (Horizontal Bar - Active Session)
$topProductsQ = $conn->query("
    SELECT p.name, SUM(si.quantity) as qty_sold 
    FROM sales s
    JOIN sale_items si ON s.id = si.sale_id 
    JOIN products p ON si.product_id = p.id 
    WHERE s.is_cleared = 0
     $bF_AND_P GROUP BY p.id 
    ORDER BY qty_sold DESC 
    LIMIT 5
");
$topLabels = [];
$topData = [];
if ($topProductsQ) {
    while ($row = $topProductsQ->fetch_assoc()) {
        $topLabels[] = substr($row['name'], 0, 20) . '...';
        $topData[] = floatval($row['qty_sold']);
    }
}

// 4. Low Stock Items List
$lowStockItemsQ = $conn->query("SELECT id, name, stock, unit FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT 5");
$lowStockItems = [];
if ($lowStockItemsQ) {
    while($r = $lowStockItemsQ->fetch_assoc()) $lowStockItems[] = $r;
}

// 5. Recent Transactions (Active Session)
$recentTxQ = $conn->query("SELECT id, customer_name, total_amount, created_at FROM sales WHERE is_cleared = 0 ORDER BY created_at DESC LIMIT 5");
$recentTx = [];
if ($recentTxQ) {
    while($r = $recentTxQ->fetch_assoc()) $recentTx[] = $r;
}

// 6. Trending Products (25+ Sales Active Session)
$trendingQ = $conn->query("
    SELECT p.id, p.name, SUM(si.quantity) as qty_sold 
    FROM sales s 
    JOIN sale_items si ON s.id = si.sale_id 
    JOIN products p ON si.product_id = p.id 
    WHERE s.is_cleared = 0
     $bF_AND_P GROUP BY p.id 
    HAVING qty_sold >= 25 
    ORDER BY qty_sold DESC
");

$trendingProducts = [];
if ($trendingQ) {
    while($r = $trendingQ->fetch_assoc()) $trendingProducts[] = $r;
}

// 7. Business Health Score Calculation (Pro Feature)
$healthScore = 100;
$aiActions = [];

if ($lowStock > 0) {
    $healthScore -= min(15, $lowStock * 2); // -2 per low stock, max -15
    $aiActions[] = "📦 Reorder $lowStock low-stock products to prevent lost sales.";
}
if ($totalProducts == 0) {
    $healthScore -= 30;
    $aiActions[] = "➕ Add your first products to start selling.";
}
if ($todaySales == 0) {
    $healthScore -= 10;
    $aiActions[] = "🚀 Make your first sale today to boost revenue.";
}
if ($totalExpenses > ($todayProfit > 0 ? $todayProfit : 1)) {
    $healthScore -= 15;
    $aiActions[] = "💸 Expenses are higher than today's estimated profit. Review spending.";
}

// Check uncollected credit (Udhaar) - if customer ledger has high balances
$uncollectedQ = $conn->query("SELECT COUNT(id) as c, SUM(outstanding_balance) as total FROM customers WHERE outstanding_balance > 0 $bF_AND");
if ($uncollectedQ) {
    $uc = $uncollectedQ->fetch_assoc();
    if ($uc['c'] > 0) {
        $healthScore -= 5;
        $aiActions[] = "💳 Collect $" . number_format($uc['total'], 2) . " in outstanding balances from " . $uc['c'] . " customers.";
    }
}

// Check unpaid suppliers
$unpaidSuppQ = $conn->query("SELECT COUNT(id) as c, SUM(outstanding_payable) as total FROM suppliers WHERE outstanding_payable > 0 $bF_AND");
if ($unpaidSuppQ) {
    $us = $unpaidSuppQ->fetch_assoc();
    if ($us['c'] > 0) {
        $healthScore -= 5;
        $aiActions[] = "🚚 You owe $" . number_format($us['total'], 2) . " to " . $us['c'] . " suppliers. Plan payments to maintain good credit.";
    }
}

$healthScore = max(0, min(100, $healthScore));

if (empty($aiActions)) {
    $aiActions[] = "✨ Your business is perfectly optimized today. Keep up the great work!";
}

$healthColor = $healthScore >= 80 ? '#10b981' : ($healthScore >= 50 ? '#f59e0b' : '#ef4444');
$healthText = $healthScore >= 80 ? 'Excellent' : ($healthScore >= 50 ? 'Needs Attention' : 'Critical Action Needed');
?>
<style>
.stat-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 0.85rem 1.25rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    border: 1px solid #e2e8f0;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}
</style>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem; padding-left: 4.5rem; padding-right: 3rem; box-sizing: border-box; width: 100%;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px;">Executive Dashboard</h2>
            <p style="margin: 0.3rem 0 0 0; color: #64748b; font-size: 0.95rem;">Overview of daily performance and transactions</p>
        </div>
        <div style="background: white; padding: 0.65rem 1.25rem; border-radius: 999px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 0.9rem;">
            📅 <?php echo date('l, F j, Y'); ?>
        </div>
    </div>

    <!-- PRO FEATURES: Health Score & AI Assistant -->
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Health Score -->
        <div class="stat-card" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border: none; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h4 style="margin: 0 0 0.5rem 0; font-weight: 600; font-size: 0.9rem; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.5px;">Business Health Score</h4>
                <div style="font-size: 2.8rem; font-weight: 800; color: <?php echo $healthColor; ?>;">
                    <?php echo $healthScore; ?><span style="font-size: 1.4rem; color: #64748b;">/100</span>
                </div>
                <div style="margin-top: 0.4rem; font-size: 0.9rem; font-weight: 500; color: #e2e8f0;">
                    Status: <strong style="color: <?php echo $healthColor; ?>;"><?php echo $healthText; ?></strong>
                </div>
            </div>
            <div style="font-size: 3.5rem; opacity: 0.85; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));">
                <?php echo $healthScore >= 80 ? '🌟' : ($healthScore >= 50 ? '⚠️' : '🚨'); ?>
            </div>
        </div>

        <!-- AI Assistant Actions -->
        <div class="stat-card" style="border-left: 5px solid #8b5cf6;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; font-weight: 700; font-size: 1.05rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: #8b5cf6; color: white; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem;">AI</span> 
                    What should I do today?
                </h4>
                <a href="#" onclick="openAiModal(); return false;" class="btn" style="margin-left: auto; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; text-decoration: none; font-size: 0.85rem; font-weight: 700; padding: 0.35rem 0.85rem; border-radius: 999px; transition: all 0.2s ease;">Ask AI 💬</a>
            </div>
            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach(array_slice($aiActions, 0, 3) as $action): ?>
                    <li style="padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; color: #334155; display: flex; align-items: center; gap: 0.5rem;">
                        <?php echo htmlspecialchars($action); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <!-- Executive Dashboard Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); row-gap: 0.6rem; column-gap: 1rem; margin-bottom: 2rem;">
        
        <!-- Today's Sales Card -->
        <div class="stat-card" style="border-left: 5px solid #0066ff;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">💵</span> TODAY'S SALES
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #0066ff; line-height: 1.2;">$<?php echo number_format($lifetimeTotalSales ?? 0, 2); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Net completed sales</div>
        </div>

        <!-- Today's Orders Card -->
        <div class="stat-card" style="border-left: 5px solid #00c49f;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">📦</span> TODAY'S ORDERS
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #00c49f; line-height: 1.2;"><?php echo number_format($lifetimeTotalOrders ?? 0); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Total orders placed</div>
        </div>

        <!-- Opening Cash Card -->
        <div class="stat-card" style="border-left: 5px solid #ffbb28;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">🏦</span> OPENING CASH
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #eab308; line-height: 1.2;">$<?php echo number_format($openingCash ?? 0, 2); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Shift initial cash</div>
        </div>

        <!-- Today's Expenses Card -->
        <div class="stat-card" style="border-left: 5px solid #ff4d4f;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">💸</span> TODAY'S EXPENSES
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #ff4d4f; line-height: 1.2;">$<?php echo number_format($totalExpenses ?? 0, 2); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Operating costs</div>
        </div>

        <!-- Today's Net Profit Card -->
        <div class="stat-card" style="border-left: 5px solid #00a854;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">📈</span> TODAY'S NET PROFIT
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #00a854; line-height: 1.2;">$<?php echo number_format((($todayProfit ?? 0) >= 0) ? $todayProfit : 0, 2); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Sales minus expenses</div>
        </div>

        <!-- Active Est. Loss Card -->
        <div class="stat-card" style="border-left: 5px solid #ff4d4f;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">📉</span> ACTIVE EST. LOSS
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #ff4d4f; line-height: 1.2;">$<?php echo number_format((($todayProfit ?? 0) < 0) ? abs($todayProfit) : 0, 2); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Loss on current shift</div>
        </div>

        <!-- Supplier Payments Card -->
        <div class="stat-card" style="border-left: 5px solid #6366f1;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">🚚</span> SUPPLIER PAYMENTS
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #6366f1; line-height: 1.2;">$<?php echo number_format($totalSupplierPayments ?? 0, 2); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Total paid to suppliers</div>
        </div>

        <!-- Total Udhaar Card -->
        <div class="stat-card" style="border-left: 5px solid #ffbb28;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">📓</span> TOTAL UDHAAR
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #eab308; line-height: 1.2;">$<?php echo number_format($lifetimeTotalUdhaar ?? 0, 2); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Credit sales this shift</div>
        </div>

        <!-- Product Returns Card -->
        <div class="stat-card" style="border-left: 5px solid #ec4899;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">↩️</span> PRODUCT RETURNS
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #ec4899; line-height: 1.2;">$<?php echo number_format($returnsTotalRefund ?? 0, 2); ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;"><?php echo intval($returnsCount ?? 0); ?> returns processed</div>
        </div>

        <!-- Total Products Card -->
        <div class="stat-card" style="border-left: 5px solid #8b5cf6;">
            <h4 style="margin: 0 0 0.5rem 0; font-weight: 700; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.4rem;">
                <span style="font-size: 1rem;">📦</span> TOTAL PRODUCTS
            </h4>
            <div style="font-size: 2.2rem; font-weight: 800; color: #8b5cf6; line-height: 1.2;"><?php echo $totalProducts; ?></div>
            <div style="margin-top: 0.3rem; font-size: 0.85rem; color: #64748b; font-weight: 400;">Items in catalog</div>
        </div>
    </div>

    <!-- Main Analytics Section -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Main Area Line Chart: Revenue Overview -->
        <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.35rem; font-weight: 700; color: #0f172a;">Revenue Overview</h3>
                    <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">Weekly sales performance</p>
                </div>
                <div style="font-size: 0.85rem; color: #64748b; font-weight: 600; background: #f8fafc; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    Last 7 Days
                </div>
            </div>
            <div style="height: 350px;">
                <canvas id="weeklyBarChart"></canvas>
            </div>
        </div>
        
        <!-- Top Products List / Horizontal Bar -->
        <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; display: flex; flex-direction: column;">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 0.25rem 0; font-size: 1.35rem; font-weight: 700; color: #0f172a;">Top Performers</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Your best-selling items this session.</p>
            </div>
            <div style="flex-grow: 1; min-height: 250px;">
                <canvas id="topProductsBarChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Data Tables Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Trending Products -->
        <div style="background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.2rem; color: #1e293b;">Trending Today</h3>
                <span style="background: #fef08a; color: #b45309; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: bold;">25+ Sales</span>
            </div>
            
            <?php if(empty($trendingProducts)): ?>
                <div style="text-align: center; padding: 2rem 0; color: #64748b;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💤</div>
                    <strong>No hits yet</strong>
                    <div style="font-size: 0.9rem;">No product has reached 25 sales this session.</div>
                </div>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach($trendingProducts as $item): ?>
                    <li style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <div style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($item['name']); ?></div>
                        </div>
                        <div style="background: #fef9c3; color: #b45309; padding: 0.4rem 0.8rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem;">
                            🔥 <?php echo floatval($item['qty_sold']); ?> sold
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <!-- Recent Transactions -->
        <div style="background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.2rem; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.75rem;">Recent Sales</h3>
            <?php if(empty($recentTx)): ?>
                <p style="color: #94a3b8; text-align: center; padding: 2rem 0;">No sales yet.</p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach($recentTx as $tx): ?>
                    <li style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <div style="font-weight: 600; color: #334155;">Sale #<?php echo $tx['id']; ?></div>
                            <div style="font-size: 0.85rem; color: #64748b;"><?php echo $tx['customer_name'] ? htmlspecialchars($tx['customer_name']) : 'Walk-in Customer'; ?> • <?php echo date('h:i A', strtotime($tx['created_at'])); ?></div>
                        </div>
                        <div style="font-weight: 700; color: #10b981; font-size: 1.1rem;">
                            +$<?php echo number_format($tx['total_amount'], 2); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Low Stock Items -->
        <div style="background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.2rem; color: #1e293b;">Attention Required</h3>
                <span style="background: #fee2e2; color: #ef4444; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: bold;"><?php echo $lowStock; ?> Items Low Stock</span>
            </div>
            
            <?php if(empty($lowStockItems)): ?>
                <div style="text-align: center; padding: 2rem 0; color: #10b981;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">✅</div>
                    <strong>All Good!</strong>
                    <div style="font-size: 0.9rem; color: #64748b;">No items are critically low on stock.</div>
                </div>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach($lowStockItems as $item): ?>
                    <li style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <a href="products.php?edit=<?php echo $item['id']; ?>" style="font-weight: 600; color: #3b82f6; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                                <?php echo htmlspecialchars($item['name']); ?>
                                <span style="font-size: 0.8rem;">↗</span>
                            </a>
                        </div>
                        <div style="background: #fef2f2; color: #ef4444; padding: 0.4rem 0.8rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem;">
                            <?php echo floatval($item['stock']) . ' ' . $item['unit']; ?> left
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Premium Smooth Curved Area Chart: Revenue Overview & Net Profit
    const canvasBar = document.getElementById('weeklyBarChart');
    if (canvasBar) {
        const ctxBar = canvasBar.getContext('2d');

        // Blue Gradient for Revenue
        const blueGradient = ctxBar.createLinearGradient(0, 0, 0, 320);
        blueGradient.addColorStop(0, 'rgba(0, 102, 255, 0.22)');
        blueGradient.addColorStop(1, 'rgba(0, 102, 255, 0.0)');

        // Purple Gradient for Net Profit
        const purpleGradient = ctxBar.createLinearGradient(0, 0, 0, 320);
        purpleGradient.addColorStop(0, 'rgba(139, 92, 246, 0.22)');
        purpleGradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

        new Chart(ctxBar, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weeklyLabels); ?>,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: <?php echo json_encode($weeklySalesData); ?>,
                        borderColor: '#0066ff',
                        borderWidth: 3.5,
                        backgroundColor: blueGradient,
                        fill: true,
                        tension: 0.42,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#0066ff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 9,
                        pointHoverBorderWidth: 4,
                        pointHoverBackgroundColor: '#ffffff'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            font: { weight: '600', size: 12 },
                            color: '#475569'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        titleFont: { size: 14, weight: '700' },
                        bodyFont: { size: 13, weight: '600' },
                        cornerRadius: 10,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += '$' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: '600', size: 12 }, color: '#64748b' }
                    },
                    y: {
                        border: { display: false },
                        grid: { color: 'rgba(241, 245, 249, 0.8)' },
                        ticks: {
                            font: { weight: '500', size: 12 },
                            color: '#64748b',
                            callback: function(val) {
                                return '$' + val.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Horizontal Bar Chart: Top Products
    const canvasTop = document.getElementById('topProductsBarChart');
    if (canvasTop) {
        const ctxTop = canvasTop.getContext('2d');
        new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($topLabels); ?>,
                datasets: [{
                    label: 'Quantity Sold',
                    data: <?php echo json_encode($topData); ?>,
                    backgroundColor: [
                        '#0066ff',
                        '#10b981',
                        '#8b5cf6',
                        '#f59e0b',
                        '#ec4899',
                        '#06b6d4'
                    ],
                    borderRadius: 6,
                    barThickness: 18
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart',
                    delay: (context) => context.dataIndex * 150
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { display: false }
                    },
                    y: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { weight: '600' }, color: '#334155' }
                    }
                }
            }
        });
    }
});
</script>
<!-- POS Smart AI Assistant Modal -->
<div id="aiAssistantModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 1rem; box-sizing: border-box;">
    <div style="background: #ffffff; width: 100%; max-width: 580px; height: 620px; max-height: 90vh; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column; overflow: hidden; border: 1px solid rgba(255,255,255,0.4); animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Modal Header -->
        <div style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); padding: 1.1rem 1.5rem; color: white; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(124,58,237,0.25);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2.5L14.5 5.5H9.5L12 2.5Z" fill="#38BDF8"/>
                        <path d="M4.5 8C4.5 6.62 5.62 5.5 7 5.5H17C18.38 5.5 19.5 6.62 19.5 8V14C19.5 17.04 16.8 19.5 13.5 19.5H10.5C7.2 19.5 4.5 17.04 4.5 14V8Z" fill="url(#profAgentGrad1)" stroke="#FFFFFF" stroke-width="1.2"/>
                        <rect x="2.5" y="10.5" width="2" height="5" rx="1" fill="#38BDF8"/>
                        <rect x="19.5" y="10.5" width="2" height="5" rx="1" fill="#38BDF8"/>
                        <path d="M6.5 9.2H17.5L16.5 13.2H7.5L6.5 9.2Z" fill="#0F172A" stroke="#38BDF8" stroke-width="0.8"/>
                        <path d="M8.5 11.2H15.5" stroke="#38BDF8" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="9.5" cy="11.2" r="0.8" fill="#FFFFFF"/>
                        <circle cx="14.5" cy="11.2" r="0.8" fill="#FFFFFF"/>
                        <path d="M10 16.2H14" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                        <path d="M11 17.7H13" stroke="#38BDF8" stroke-width="1" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="profAgentGrad1" x1="4.5" y1="5.5" x2="19.5" y2="19.5" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#312E81"/>
                                <stop offset="0.5" stop-color="#4F46E5"/>
                                <stop offset="1" stop-color="#7C3AED"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #ffffff;">POS Smart AI Assistant</h3>
                    <div style="font-size: 0.75rem; color: #ddd6fe; display: flex; align-items: center; gap: 0.4rem; margin-top: 0.1rem;">
                        <span style="width: 7px; height: 7px; background: #34d399; border-radius: 50%; display: inline-block;"></span> Live Store Analytics Engine
                    </div>
                </div>
            </div>
            <button onclick="closeAiModal()" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)';" onmouseout="this.style.background='rgba(255,255,255,0.15)';">✕</button>
        </div>

        <!-- Chat Output Body -->
        <div id="aiChatBody" style="flex: 1; padding: 1.25rem; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 1rem;">
            
            <!-- Welcome AI Message -->
            <div style="display: flex; gap: 0.75rem; max-width: 88%;">
                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #1e1b4b, #312e81); border: 1px solid rgba(99,102,241,0.5); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 3px 10px rgba(99,102,241,0.35);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2.5L14.5 5.5H9.5L12 2.5Z" fill="#38BDF8"/>
                        <path d="M4.5 8C4.5 6.62 5.62 5.5 7 5.5H17C18.38 5.5 19.5 6.62 19.5 8V14C19.5 17.04 16.8 19.5 13.5 19.5H10.5C7.2 19.5 4.5 17.04 4.5 14V8Z" fill="url(#profAgentGrad_w)" stroke="#818CF8" stroke-width="1.2"/>
                        <rect x="2.5" y="10.5" width="2" height="5" rx="1" fill="#38BDF8"/>
                        <rect x="19.5" y="10.5" width="2" height="5" rx="1" fill="#38BDF8"/>
                        <path d="M6.5 9.2H17.5L16.5 13.2H7.5L6.5 9.2Z" fill="#0F172A" stroke="#38BDF8" stroke-width="0.8"/>
                        <path d="M8.5 11.2H15.5" stroke="#38BDF8" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="9.5" cy="11.2" r="0.8" fill="#FFFFFF"/>
                        <circle cx="14.5" cy="11.2" r="0.8" fill="#FFFFFF"/>
                        <path d="M10 16.2H14" stroke="#818CF8" stroke-width="1.2" stroke-linecap="round"/>
                        <path d="M11 17.7H13" stroke="#38BDF8" stroke-width="1" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="profAgentGrad_w" x1="4.5" y1="5.5" x2="19.5" y2="19.5" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#312E81"/>
                                <stop offset="0.5" stop-color="#4F46E5"/>
                                <stop offset="1" stop-color="#7C3AED"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div style="background: #ffffff; padding: 1rem 1.15rem; border-radius: 16px; border-top-left-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; color: #1e293b; font-size: 0.95rem; line-height: 1.5;">
                    Assalam-o-Alaikum! 👋 Main aap ka <strong>POS Store AI Assistant</strong> hoon.<br><br>
                    Aap mujh se apne store ki sales, stock, top items, profit ya udhaar ke baare mein koi bhi sawal pooch sakte hain.
                </div>
            </div>

            <!-- Quick Suggestion Chips -->
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-left: 2.5rem; margin-top: -0.25rem;">
                <button onclick="sendAiQuick('Aaj ki sales kitni hui?')" style="background: #ffffff; border: 1px solid #ddd6fe; color: #6d28d9; padding: 0.4rem 0.85rem; border-radius: 99px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.background='#f5f3ff';" onmouseout="this.style.background='#ffffff';">📊 Aaj ki sales?</button>
                <button onclick="sendAiQuick('Konse items low stock par hain?')" style="background: #ffffff; border: 1px solid #fed7aa; color: #c2410c; padding: 0.4rem 0.85rem; border-radius: 99px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.background='#fff7ed';" onmouseout="this.style.background='#ffffff';">📦 Low stock items?</button>
                <button onclick="sendAiQuick('Top selling product konsa hai?')" style="background: #ffffff; border: 1px solid #bbf7d0; color: #15803d; padding: 0.4rem 0.85rem; border-radius: 99px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.background='#f0fdf4';" onmouseout="this.style.background='#ffffff';">🔥 Top selling product?</button>
                <button onclick="sendAiQuick('Kitna udhaar baqi hai?')" style="background: #ffffff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 0.4rem 0.85rem; border-radius: 99px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.background='#eff6ff';" onmouseout="this.style.background='#ffffff';">💳 Customer Udhaar?</button>
                <button onclick="sendAiQuick('Aaj opening cash kitna tha?')" style="background: #ffffff; border: 1px solid #fde68a; color: #b45309; padding: 0.4rem 0.85rem; border-radius: 99px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.background='#fffbeb';" onmouseout="this.style.background='#ffffff';">💵 Opening Cash?</button>
                <button onclick="sendAiQuick('Inventory valuation kitni hai?')" style="background: #ffffff; border: 1px solid #e9d5ff; color: #7e22ce; padding: 0.4rem 0.85rem; border-radius: 99px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.background='#faf5ff';" onmouseout="this.style.background='#ffffff';">💰 Inventory value?</button>
                <button onclick="sendAiQuick('System ke tamam features batao')" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; padding: 0.4rem 0.85rem; border-radius: 99px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='#ffffff';">🖥️ System Features?</button>
            </div>

        </div>

        <!-- Modal Input Footer -->
        <div style="padding: 1rem 1.25rem; background: #ffffff; border-top: 1px solid #e2e8f0;">
            <form onsubmit="submitAiQuestion(event)" style="display: flex; gap: 0.6rem; align-items: center;">
                <input type="text" id="aiQuestionInput" placeholder="Aap kya poochna chahte hain?..." autocomplete="off" style="flex: 1; padding: 0.75rem 1.1rem; border-radius: 12px; border: 1.5px solid #cbd5e1; font-size: 0.95rem; outline: none; transition: border-color 0.2s ease; background: #f8fafc;" onfocus="this.style.borderColor='#7c3aed'; this.style.background='#ffffff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                <button type="submit" id="aiSendBtn" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; border: none; padding: 0.75rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; box-shadow: 0 4px 12px rgba(124,58,237,0.3); transition: all 0.2s ease;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                    Send 🚀
                </button>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes modalPop {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes typingDot {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.35; }
    30% { transform: translateY(-5px); opacity: 1; scale: 1.15; }
}
.typing-dot-bounce {
    width: 7px;
    height: 7px;
    background-color: #7c3aed;
    border-radius: 50%;
    display: inline-block;
    animation: typingDot 1.4s infinite ease-in-out;
}
.typing-dot-bounce:nth-child(1) { animation-delay: 0s; }
.typing-dot-bounce:nth-child(2) { animation-delay: 0.2s; }
.typing-dot-bounce:nth-child(3) { animation-delay: 0.4s; }
</style>

<script>
const AI_AVATAR_ICON = `<div style="width: 32px; height: 32px; background: linear-gradient(135deg, #1e1b4b, #312e81); border: 1px solid rgba(99,102,241,0.5); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 3px 10px rgba(99,102,241,0.35);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.5L14.5 5.5H9.5L12 2.5Z" fill="#38BDF8"/><path d="M4.5 8C4.5 6.62 5.62 5.5 7 5.5H17C18.38 5.5 19.5 6.62 19.5 8V14C19.5 17.04 16.8 19.5 13.5 19.5H10.5C7.2 19.5 4.5 17.04 4.5 14V8Z" fill="url(#profAgentGrad_m)" stroke="#818CF8" stroke-width="1.2"/><rect x="2.5" y="10.5" width="2" height="5" rx="1" fill="#38BDF8"/><rect x="19.5" y="10.5" width="2" height="5" rx="1" fill="#38BDF8"/><path d="M6.5 9.2H17.5L16.5 13.2H7.5L6.5 9.2Z" fill="#0F172A" stroke="#38BDF8" stroke-width="0.8"/><path d="M8.5 11.2H15.5" stroke="#38BDF8" stroke-width="2" stroke-linecap="round"/><circle cx="9.5" cy="11.2" r="0.8" fill="#FFFFFF"/><circle cx="14.5" cy="11.2" r="0.8" fill="#FFFFFF"/><path d="M10 16.2H14" stroke="#818CF8" stroke-width="1.2" stroke-linecap="round"/><path d="M11 17.7H13" stroke="#38BDF8" stroke-width="1" stroke-linecap="round"/><defs><linearGradient id="profAgentGrad_m" x1="4.5" y1="5.5" x2="19.5" y2="19.5" gradientUnits="userSpaceOnUse"><stop stop-color="#312E81"/><stop offset="0.5" stop-color="#4F46E5"/><stop offset="1" stop-color="#7C3AED"/></linearGradient></defs></svg></div>`;

function openAiModal() {
    document.getElementById('aiAssistantModal').style.display = 'flex';
    setTimeout(() => {
        document.getElementById('aiQuestionInput').focus();
    }, 100);
}

function closeAiModal() {
    document.getElementById('aiAssistantModal').style.display = 'none';
}

// Close on Esc key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAiModal();
    }
});

function formatMarkdown(text) {
    if (!text) return '';
    let html = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    html = html.replace(/\n/g, '<br>');
    return html;
}

function appendUserMessage(text) {
    const chatBody = document.getElementById('aiChatBody');
    const msgDiv = document.createElement('div');
    msgDiv.style.cssText = 'display: flex; justify-content: flex-end; max-width: 88%; margin-left: auto;';
    msgDiv.innerHTML = `
        <div style="background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; padding: 0.85rem 1.15rem; border-radius: 16px; border-top-right-radius: 4px; box-shadow: 0 2px 8px rgba(124,58,237,0.25); font-size: 0.95rem; line-height: 1.5;">
            ${text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")}
        </div>
    `;
    chatBody.appendChild(msgDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function appendAiMessage(rawText) {
    const chatBody = document.getElementById('aiChatBody');
    const msgDiv = document.createElement('div');
    msgDiv.style.cssText = 'display: flex; gap: 0.75rem; max-width: 88%;';
    msgDiv.innerHTML = `
        ${AI_AVATAR_ICON}
        <div style="background: #ffffff; padding: 1rem 1.15rem; border-radius: 16px; border-top-left-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; color: #1e293b; font-size: 0.95rem; line-height: 1.5;">
            ${formatMarkdown(rawText)}
        </div>
    `;
    chatBody.appendChild(msgDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function showAiTyping() {
    const chatBody = document.getElementById('aiChatBody');
    const msgDiv = document.createElement('div');
    msgDiv.id = 'aiTypingIndicator';
    msgDiv.style.cssText = 'display: flex; gap: 0.75rem; max-width: 88%; align-items: center;';
    msgDiv.innerHTML = `
        ${AI_AVATAR_ICON}
        <div style="background: #ffffff; padding: 0.75rem 1.25rem; border-radius: 16px; border-top-left-radius: 4px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 0.45rem; min-width: 48px;">
            <span class="typing-dot-bounce"></span>
            <span class="typing-dot-bounce"></span>
            <span class="typing-dot-bounce"></span>
        </div>
    `;
    chatBody.appendChild(msgDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function removeAiTyping() {
    const el = document.getElementById('aiTypingIndicator');
    if (el) el.remove();
}

function sendAiQuick(question) {
    document.getElementById('aiQuestionInput').value = question;
    submitAiQuestion(new Event('submit'));
}

function submitAiQuestion(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('aiQuestionInput');
    const question = input.value.trim();
    if (!question) return;

    appendUserMessage(question);
    input.value = '';
    showAiTyping();

    const startTime = Date.now();
    const minTypingDelayMs = 2300; // 2.3 seconds typing delay like Meta AI / WhatsApp

    fetch('api_ai_assistant.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ question: question })
    })
    .then(res => res.json())
    .then(data => {
        const elapsedTime = Date.now() - startTime;
        const remainingDelay = Math.max(0, minTypingDelayMs - elapsedTime);

        setTimeout(() => {
            removeAiTyping();
            if (data.success) {
                appendAiMessage(data.reply);
            } else {
                appendAiMessage('⚠️ ' + (data.message || 'Error communicating with AI assistant.'));
            }
        }, remainingDelay);
    })
    .catch(err => {
        const elapsedTime = Date.now() - startTime;
        const remainingDelay = Math.max(0, minTypingDelayMs - elapsedTime);

        setTimeout(() => {
            removeAiTyping();
            appendAiMessage('⚠️ Error connecting to AI Assistant service.');
            console.error(err);
        }, remainingDelay);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
