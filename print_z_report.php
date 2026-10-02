<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}
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

// Only Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: Only administrators can view the Z-Report.");
}

$today = date('Y-m-d');
$isAdmin = true;
$current_branch_id = $_SESSION['branch_id'] ?? 1;
$bF_AND = " AND tenant_id = {$_SESSION['tenant_id']}";

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

$bF_AND_S = " AND s.tenant_id = {$_SESSION['tenant_id']}";
$cogsQuery = $conn->query("SELECT SUM(si.quantity * si.cost_price) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id WHERE s.is_cleared = 0 $bF_AND_S");
$totalCOGS = $cogsQuery->fetch_assoc()['cogs'] ?? 0;

// Fetch Active Returns Summary
$returnsQuery = $conn->query("SELECT SUM(total_refund) as tr, COUNT(id) as c FROM returns WHERE (is_cleared = 0 OR is_cleared IS NULL) $bF_AND");
$returnsData = $returnsQuery ? $returnsQuery->fetch_assoc() : [];
$totalReturnsRefund = floatval($returnsData['tr'] ?? 0);
$totalReturnsCount = intval($returnsData['c'] ?? 0);

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
$finalActualCash = $totalClosingCash > 0 ? $totalClosingCash : $expectedCash;

// Fetch Detailed Orders
$detailedOrders = $conn->query("SELECT id, customer_name, total_amount, payment_method, created_at FROM sales WHERE is_cleared = 0 $bF_AND ORDER BY created_at DESC");

// Fetch Detailed Expenses
$detailedExpenses = $conn->query("SELECT category, amount, expense_date FROM expenses WHERE is_cleared = 0 AND category != 'Return Refund' $bF_AND ORDER BY expense_date DESC");

// Fetch Detailed Supplier Payments
$bF_AND_P = " AND p.tenant_id = {$_SESSION['tenant_id']}";
$detailedPurchases = $conn->query("SELECT s.name as supplier_name, p.amount_paid, p.created_at FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.is_cleared = 0 $bF_AND_P ORDER BY p.created_at DESC");

$bF_AND_R = " AND r.tenant_id = {$_SESSION['tenant_id']}";
$detailedReturns = $conn->query("SELECT r.id, r.sale_id, r.total_refund, r.created_at FROM returns r WHERE (r.is_cleared = 0 OR r.is_cleared IS NULL) $bF_AND_R ORDER BY r.created_at DESC");

$settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while($row = $settingsQ->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Z-Report - <?php echo date('d-M-Y'); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap');
        
        body { 
            font-family: 'Courier Prime', Courier, monospace; 
            font-size: 14px; 
            margin: 0; 
            padding: 20px; 
            color: #1a1a1a; 
            background: #f4f4f4;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .receipt-container { 
            width: 320px; /* Standard 80mm thermal paper width */
            background: white;
            padding: 25px 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }

        .center { text-align: center; }
        .bold { font-weight: 700; }
        
        .header { margin-bottom: 20px; }
        .header h1 { margin: 0 0 5px 0; font-size: 22px; letter-spacing: 1px; border-bottom: 2px solid #000; padding-bottom: 5px; display: inline-block;}
        .header p { margin: 2px 0; font-size: 12px; }

        .line { border-bottom: 1px dashed #666; margin: 15px 0; }
        .line-solid { border-bottom: 1px solid #000; margin: 15px 0; }
        
        .info-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 3px; }
        
        .item-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .item-table th { text-align: left; border-bottom: 1px dashed #666; padding-bottom: 5px; font-size: 12px; }
        .item-table td { padding: 5px 0; vertical-align: top; font-size: 12px;}
        
        .totals { margin-top: 15px; }
        .totals .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .totals .row.grand { font-size: 16px; font-weight: 700; border-top: 1px dashed #666; border-bottom: 1px dashed #666; padding: 10px 0; margin: 10px 0; }
        
        .footer { text-align: center; margin-top: 20px; font-size: 12px; }

        .actions { margin-top: 30px; display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; font-size: 14px; cursor: pointer; border-radius: 4px; font-family: inherit; font-weight: bold; }
        .btn-print { background: #000; color: #fff; }
        .btn-close { background: #e2e8f0; color: #333; }

        @media print {
            body { padding: 0; background: white; align-items: flex-start; }
            .receipt-container { width: 100%; box-shadow: none; padding: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container" id="main-receipt">
        <div class="header center">
            <!-- Top Logo -->
            <div style="width: 50px; height: 50px; border: 3px solid #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; margin: 0 auto 10px auto;">
                <?php echo substr($settings['store_name'], 0, 1); ?>
            </div>
            <h1>END OF DAY REPORT</h1>
            <p class="bold"><?php echo htmlspecialchars(strtoupper($settings['store_name'])); ?></p>
        </div>

        <div class="info-row">
            <span>Date: <?php echo date('d-M-Y'); ?></span>
            <span>Time: <?php echo date('H:i:s'); ?></span>
        </div>
        <div class="info-row">
            <span>Admin: <?php echo strtoupper(htmlspecialchars($_SESSION['username'])); ?></span>
            <span>Type: Z-REPORT</span>
        </div>

        <div class="line-solid"></div>
        <div class="center bold" style="margin: 10px 0; font-size: 15px;">FINANCIAL SUMMARY</div>
        <div class="line-solid"></div>

        <div class="totals">
            <div class="row"><span>Total Orders:</span> <span><?php echo $totalOrders; ?></span></div>
            <div class="row"><span>Total Sales (Cash+Udhaar):</span> <span><?php echo number_format($totalSales, 2); ?></span></div>
            <?php if($totalCreditSales > 0): ?>
            <div class="row" style="font-size: 12px; color: #555;"><span>  - Cash Sales:</span> <span><?php echo number_format($totalCashSalesOnly, 2); ?></span></div>
            <div class="row" style="font-size: 12px; color: #555;"><span>  - Udhaar/Card:</span> <span><?php echo number_format($totalCreditSales, 2); ?></span></div>
            <?php endif; ?>
            <?php if($totalReturnsCount > 0): ?>
            <div class="row"><span>Product Returns (<?php echo $totalReturnsCount; ?>):</span> <span>-$<?php echo number_format($totalReturnsRefund, 2); ?></span></div>
            <?php endif; ?>
            <div class="row"><span>Expenses Paid:</span> <span>-<?php echo number_format($totalExpenses, 2); ?></span></div>
            <div class="row"><span>Supplier Paid:</span> <span>-<?php echo number_format($totalPurchases, 2); ?></span></div>
            
            <div class="line" style="margin: 5px 0;"></div>
            <div class="row"><span>Cost of Goods (COGS):</span> <span>-<?php echo number_format($totalCOGS, 2); ?></span></div>
            <div class="row" style="font-weight:bold; font-size: 13px;"><span>Net Profit Margin:</span> <span><?php echo $grossMargin >= 0 ? '+' : ''; ?><?php echo number_format($grossMargin, 2); ?></span></div>
        </div>



        <div class="line-solid"></div>
        <div class="center bold" style="margin: 10px 0; font-size: 15px;">CASH DRAWER SUMMARY</div>
        <div class="line-solid"></div>
        
        <div class="totals">
            <div class="row"><span>Opening Cash (Purana):</span> <span><?php echo number_format($totalOpeningCash, 2); ?></span></div>
            <div class="line" style="margin: 5px 0;"></div>
            
            <div class="center bold" style="font-size: 12px; margin: 5px 0;">Today's Cash Flow</div>
            <div class="row"><span>+ Net Cash Sales:</span> <span><?php echo number_format($netCashSales, 2); ?></span></div>
            <?php if($khataPaymentsReceived > 0.01): ?>
            <div class="row"><span>+ Khata Received:</span> <span><?php echo number_format($khataPaymentsReceived, 2); ?></span></div>
            <?php endif; ?>
            <div class="row"><span>- Expenses Paid:</span> <span>-<?php echo number_format($totalExpenses, 2); ?></span></div>
            <div class="row"><span>- Supplier Paid:</span> <span>-<?php echo number_format($totalPurchases, 2); ?></span></div>
            <?php if($totalReturnsRefund > 0): ?>
            <div class="row"><span>- Returns Refund:</span> <span>-<?php echo number_format($totalReturnsRefund, 2); ?></span></div>
            <?php endif; ?>
            <?php $netCashGenerated = $netCashSales + $khataPaymentsReceived - $totalExpenses - $totalPurchases - $totalReturnsRefund; ?>
            <div class="row" style="margin-top:2px; font-weight:bold;"><span>Baqi Cash (Net):</span> <span><?php echo $netCashGenerated >= 0 ? '+' : ''; ?><?php echo number_format($netCashGenerated, 2); ?></span></div>

            <div class="row" style="margin-top:10px; padding-top:5px; border-top:2px solid #000; font-size: 14px; font-weight: bold;">
                <span>TOTAL CASH IN DRAWER:</span> <span><?php echo number_format($expectedCash, 2); ?></span>
            </div>
            <?php if($totalClosingCash > 0): ?>
            <div class="row" style="font-weight: bold; margin-top:5px; font-size: 13px;">
                <span>Actual Counted Cash:</span> <span><?php echo number_format($finalActualCash, 2); ?></span>
            </div>
            <?php if($finalActualCash != $expectedCash): ?>
            <div class="row" style="font-size: 11px; margin-top:2px;">
                <span>Variance:</span> <span><?php echo number_format($finalActualCash - $expectedCash, 2); ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p class="bold" style="font-size: 14px;">END OF REPORT</p>
            <p style="margin: 5px 0 0 0; font-size: 10px; letter-spacing: 2px;">Generated by SuperStore POS</p>
        </div>
    </div>
    
    <div class="actions">
        <button class="btn btn-print" onclick="window.print()">🖨️ PRINT</button>
        <button class="btn" style="background: #059669;" onclick="downloadPDF()">📄 PDF</button>
        <button class="btn btn-close" onclick="window.close()">✖ CLOSE</button>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('main-receipt');
            const opt = {
                margin:       0.2,
                filename:     'Z-Report-<?php echo date("Y-m-d"); ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 4, useCORS: true, scrollY: 0 },
                jsPDF:        { unit: 'in', format: [4.0, element.scrollHeight * 0.015 + 1.5], orientation: 'portrait' }
            };
            
            const btn = document.querySelector('button[onclick="downloadPDF()"]');
            if(btn) btn.innerText = "⏳...";
            
            window.scrollTo(0,0);
            
            html2pdf().set(opt).from(element).save().then(() => {
                if(btn) btn.innerText = "📄 PDF";
            });
        }

        // Auto print dialog on load
        window.onload = function() { 
            setTimeout(() => { window.print(); }, 500);
        }
    </script>
</body>
</html>

