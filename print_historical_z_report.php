<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}
include 'includes/db.php';

// Only Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: Only administrators can view the Z-Report.");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM z_reports_history WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
    die("Report not found.");
}

$snapshot = json_decode($report['snapshot_data'], true);
$detailedOrdersArr = $snapshot['orders'] ?? [];
$detailedExpensesArr = $snapshot['expenses'] ?? [];
$detailedPurchasesArr = $snapshot['purchases'] ?? [];
$detailedReturnsArr = $snapshot['returns'] ?? [];
$returnsSummary = $snapshot['returns_summary'] ?? [];

$totalSales = $report['total_sales'];
$totalOrders = $report['total_orders'];
$totalExpenses = $report['total_expenses'];
$totalPurchases = $report['total_supplier_payments'];
$netProfit = $report['net_income'];
$reportDate = $report['report_date'];

$totalCreditSales = 0;
foreach($detailedOrdersArr as $o) {
    if (isset($o['payment_method']) && $o['payment_method'] !== 'cash') {
        $totalCreditSales += floatval($o['total_amount']);
    }
}
$totalCashSalesOnly = $totalSales - $totalCreditSales;
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
    <title>Z-Report - <?php echo date('d-M-Y', strtotime($reportDate)); ?></title>
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
            <span>Date: <?php echo date('d-M-Y', strtotime($reportDate)); ?></span>
            <span>Time: <?php echo date('H:i:s', strtotime($report['created_at'])); ?></span>
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
            <div class="row"><span>Expenses Paid:</span> <span>-<?php echo number_format($totalExpenses, 2); ?></span></div>
            <div class="row"><span>Supplier Paid:</span> <span>-<?php echo number_format($totalPurchases, 2); ?></span></div>
        </div>

        <?php
        $totalOpeningCash = $report['opening_cash'] ?? 0;
        $expectedCash = $report['expected_cash'] ?? 0;
        $finalActualCash = $report['closing_cash'] ?? 0;
        $totalClosingCash = $finalActualCash; // for variance display later
        
        $netCashSales = 0;
        foreach($detailedOrdersArr as $o) {
            if (!isset($o['payment_method']) || $o['payment_method'] === 'cash') {
                $netCashSales += floatval($o['total_amount']);
            }
        }
        $khataPaymentsReceived = $expectedCash - $totalOpeningCash - $netCashSales + $totalExpenses + $totalPurchases;
        ?>

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
            <div class="row"><span>- Total Payouts:</span> <span>-<?php echo number_format($totalExpenses + $totalPurchases, 2); ?></span></div>
            <?php $netCashGenerated = $netCashSales + $khataPaymentsReceived - $totalExpenses - $totalPurchases; ?>
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

