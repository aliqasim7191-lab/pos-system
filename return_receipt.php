<?php
include 'includes/db.php';
session_start();

if (!isset($_GET['id'])) {
    die("Invalid Return ID.");
}

$returnId = intval($_GET['id']);

// Fetch return details
$query = "
    SELECT d.*, p.name as product_name, p.price as unit_price, u.username, s.name as supplier_name, s.phone as supplier_phone 
    FROM damaged_stock d 
    JOIN products p ON d.product_id = p.id 
    JOIN users u ON d.user_id = u.id 
    LEFT JOIN suppliers s ON d.supplier_id = s.id 
    WHERE d.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $returnId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Return record not found.");
}
$return = $res->fetch_assoc();

// Store settings
$settings = [
    'store_name' => 'SuperStore POS',
    'store_address' => '123 Main Street, City',
    'store_phone' => '+1 234 567 8900'
];

$storeQ = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($storeQ) {
    while($row = $storeQ->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Receipt #<?php echo $returnId; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f1f5f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .receipt-container {
            background: white;
            width: 300px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: #000;
            font-size: 14px;
            line-height: 1.4;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .header { margin-bottom: 15px; }
        .header h1 { font-size: 18px; margin: 0 0 5px 0; }
        .header p { margin: 2px 0; font-size: 12px; }
        .line-dashed { border-bottom: 1px dashed #000; margin: 10px 0; }
        .line-solid { border-bottom: 1px solid #000; margin: 10px 0; }
        .info-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px; }
        .item-table { width: 100%; font-size: 13px; margin-bottom: 10px; border-collapse: collapse; }
        .item-table th { text-align: left; padding-bottom: 5px; }
        .item-table td { vertical-align: top; padding: 3px 0; }
        .item-table .qty { width: 40px; }
        .item-table .price { width: 60px; text-align: right; }
        .item-table .total { width: 60px; text-align: right; font-weight: bold; }
        
        .totals { margin-top: 15px; font-size: 14px; }
        .totals .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .totals .grand { font-weight: bold; font-size: 16px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px 0; margin-top: 5px; }
        
        .footer { margin-top: 20px; text-align: center; font-size: 12px; }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .no-print button { padding: 8px 15px; font-size: 14px; cursor: pointer; border: none; border-radius: 4px; margin: 0 5px; }
        .btn-print { background: #0f172a; color: white; }
        .btn-pdf { background: #059669; color: white; }
        
        @media print {
            body { background: white; padding: 0; }
            .receipt-container { box-shadow: none; width: 100%; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <div>
        <div class="no-print">
            <button onclick="window.print()" class="btn-print">🖨️ Print Receipt</button>
            <button onclick="downloadPDF()" class="btn-pdf">📄 PDF</button>
        </div>

        <div class="receipt-container" id="main-receipt">
            <div class="header center">
                <!-- Top Logo -->
                <div style="width: 50px; height: 50px; border: 3px solid #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; margin: 0 auto 10px auto;">
                    <?php echo substr($settings['store_name'], 0, 1); ?>
                </div>
                <h1>STOCK RETURN VOUCHER</h1>
                <p class="bold"><?php echo htmlspecialchars(strtoupper($settings['store_name'])); ?></p>
                <p><?php echo htmlspecialchars($settings['store_address']); ?></p>
                <p>Tel: <?php echo htmlspecialchars($settings['store_phone']); ?></p>
            </div>

            <div class="info-row">
                <span>Voucher #:</span>
                <span class="bold">RTN-<?php echo str_pad($returnId, 5, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <span>Date:</span>
                <span><?php echo date('d-M-Y', strtotime($return['logged_at'])); ?></span>
            </div>
            <div class="info-row">
                <span>Time:</span>
                <span><?php echo date('H:i:s', strtotime($return['logged_at'])); ?></span>
            </div>
            <div class="info-row">
                <span>Operator:</span>
                <span><?php echo strtoupper(htmlspecialchars($return['username'])); ?></span>
            </div>

            <div class="line-dashed"></div>
            
            <?php if ($return['supplier_name']): ?>
            <div class="info-row bold" style="font-size: 13px;">
                <span>Return To:</span>
                <span><?php echo htmlspecialchars($return['supplier_name']); ?></span>
            </div>
            <div class="line-dashed"></div>
            <?php endif; ?>

            <div class="info-row" style="margin-bottom: 10px;">
                <span class="bold">Reason:</span>
                <span><?php echo htmlspecialchars($return['reason']); ?></span>
            </div>

            <table class="item-table">
                <thead>
                    <tr class="line-solid" style="border-top: 1px solid #000;">
                        <th>Item</th>
                        <th class="qty">Qty</th>
                        <th class="price">Price</th>
                        <th class="total">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($return['product_name']); ?></td>
                        <td class="qty"><?php echo floatval($return['quantity']); ?></td>
                        <td class="price"><?php echo number_format($return['unit_price'], 2); ?></td>
                        <td class="total"><?php echo number_format($return['loss_amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="totals">
                <div class="row grand">
                    <span>TOTAL LOSS:</span>
                    <span>$<?php echo number_format($return['loss_amount'], 2); ?></span>
                </div>
            </div>

            <div class="footer">
                <p>Authorized Signature</p>
                <div style="border-bottom: 1px solid #000; width: 150px; margin: 30px auto 10px auto;"></div>
            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('main-receipt');
            const opt = {
                margin:       0.2,
                filename:     'Return_Voucher_RTN-<?php echo str_pad($returnId, 5, '0', STR_PAD_LEFT); ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 4, useCORS: true, scrollY: 0 },
                jsPDF:        { unit: 'in', format: [4.0, element.scrollHeight * 0.015 + 1.5], orientation: 'portrait' }
            };
            
            const btn = document.querySelector('.btn-pdf');
            if(btn) btn.innerText = "⏳...";
            
            window.scrollTo(0,0);
            
            html2pdf().set(opt).from(element).save().then(() => {
                if(btn) btn.innerText = "📄 PDF";
            });
        }
    </script>
</body>
</html>

