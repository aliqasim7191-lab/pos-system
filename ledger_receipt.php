<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'includes/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$showHistory = isset($_GET['history']) && $_GET['history'] == 1;
$historyFilter = $showHistory ? "" : "AND cl.is_cleared = 0";

$custRes = $conn->query("SELECT * FROM customers WHERE id = $id LIMIT 1");
$customer = $custRes->fetch_assoc();

if (!$customer) {
    die("Customer not found.");
}

$ledger = [];
$res = $conn->query("SELECT cl.*, s.taken_by, s.total_amount as sale_total, (SELECT GROUP_CONCAT(CONCAT(si.quantity, 'x ', p.name) SEPARATOR ', ') FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = cl.sale_id) as items_summary FROM customer_ledger cl LEFT JOIN sales s ON cl.sale_id = s.id WHERE cl.customer_id = $id $historyFilter ORDER BY cl.created_at ASC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $ledger[] = $row;
    }
}

// Get settings
$settings = [
    'store_name' => 'My POS Store',
    'store_address' => '123 Main Street',
    'store_phone' => '555-0123'
];
$settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($settingsQ) {
    while($row = $settingsQ->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger Statement - <?php echo htmlspecialchars($customer['name']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&family=Inter:wght@400;600;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        .receipt-container {
            width: 80mm;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 15px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .header h1 { margin: 5px 0; font-size: 18px; font-weight: 800; }
        .header p { margin: 2px 0; font-size: 12px; }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 3px;
        }
        
        .line-solid { border-top: 1px solid #000; margin: 8px 0; }
        .line-dashed { border-top: 1px dashed #000; margin: 8px 0; }
        
        .table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .table th { text-align: left; padding: 4px 0; border-bottom: 1px solid #000; }
        .table td { padding: 4px 0; vertical-align: top; }
        .table .right { text-align: right; }
        
        .totals {
            margin-top: 10px;
            font-size: 14px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-weight: 600;
        }
        .totals-row.grand {
            font-size: 16px;
            font-weight: 800;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .actions {
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            background: #2563eb; color: #fff; border: none; padding: 10px 15px;
            border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;
            margin: 0 5px;
        }
        @media print {
            body { background: transparent; padding: 0; }
            .receipt-container { width: 100%; box-shadow: none; padding: 0; }
            .actions { display: none; }
            .cloned-receipt { page-break-before: always; }
        }
    </style>
</head>
<body>
    <div class="receipt-container" id="main-receipt">
        <div class="header">
            <h1><?php echo htmlspecialchars(strtoupper($settings['store_name'])); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($settings['store_address'])); ?></p>
            <p>Tel: <?php echo htmlspecialchars($settings['store_phone']); ?></p>
        </div>

        <div style="text-align:center; font-weight:bold; font-size:16px; margin-bottom:10px;">
            STATEMENT OF ACCOUNT
        </div>

        <div class="info-row">
            <span>Customer: <strong><?php echo htmlspecialchars($customer['name']); ?></strong></span>
        </div>
        <div class="info-row">
            <span>Phone: <?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-row">
            <span>Date: <?php echo date('d-M-Y h:i A'); ?></span>
        </div>
        
        <div class="line-solid"></div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Date/Desc</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ledger as $l): ?>
                <tr>
                    <td style="padding-bottom: 8px;">
                        <div><?php echo date('d-m-Y', strtotime($l['created_at'])); ?></div>
                        <div style="font-weight:bold;"><?php echo htmlspecialchars($l['description']); ?></div>
                        <?php if($l['sale_id'] && !empty($l['items_summary'])): ?>
                            <div style="font-size: 10px; color: #444; margin-top:2px; font-family:'Courier Prime', monospace;">
                                <?php echo htmlspecialchars($l['items_summary']); ?>
                                <?php if(!empty($l['taken_by'])): ?>
                                    <br><strong>Taken By:</strong> <?php echo htmlspecialchars($l['taken_by']); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="right" style="padding-bottom: 8px;">
                        <?php if($l['type'] == 'sale'): ?>
                            <span style="color: #000;">+$<?php echo number_format($l['amount'], 2); ?></span>
                        <?php else: ?>
                            <span style="color: #000;">-$<?php echo number_format($l['amount'], 2); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($ledger)): ?>
                <tr>
                    <td colspan="2" style="text-align:center; padding:10px;">No active transactions.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="line-solid"></div>
        
        <div class="totals">
            <div class="totals-row grand">
                <span>Total Due:</span>
                <span>$<?php echo number_format(max(0, $customer['outstanding_balance']), 2); ?></span>
            </div>
            <?php if($customer['outstanding_balance'] < 0): ?>
            <div class="totals-row">
                <span style="font-size:12px;">Advance Deposit:</span>
                <span style="font-size:12px;">$<?php echo number_format(abs($customer['outstanding_balance']), 2); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="header" style="border:none; margin-top:20px; border-top: 1px dashed #000; padding-top:10px;">
            <p>Thank you for your business!</p>
        </div>
    </div>

    <div class="actions">
        <button class="btn" onclick="doPrint()">🖨️ Print</button>
        <button class="btn" style="background: #059669;" onclick="downloadPDF()">📄 PDF</button>
        <button class="btn" style="background: #ef4444;" onclick="closeReceipt()">❌ Close</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function closeReceipt() {
            if (window.opener && !window.opener.closed) {
                window.close();
            } else {
                window.location.href = 'customer_ledger.php?id=<?php echo $id; ?>';
            }
        }
        function doPrint() {
            window.print();
        }
        function downloadPDF() {
            const element = document.getElementById('main-receipt');
            const btn = document.querySelector('button[onclick="downloadPDF()"]');
            btn.innerText = "⏳ Generating...";
            btn.disabled = true;

            const opt = {
                margin:       0,
                filename:     'Statement_<?php echo preg_replace("/[^a-zA-Z0-9]+/", "_", $customer['name']); ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: [3.5, element.scrollHeight * 0.015 + 1.5], orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                if(btn) {
                    btn.innerText = "📄 PDF";
                    btn.disabled = false;
                }
            });
        }
    </script>
</body>
</html>

