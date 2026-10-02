<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}
include 'includes/db.php';

if (!isset($_GET['id'])) {
    die("Invalid Purchase ID");
}

$purchaseId = intval($_GET['id']);
$isSupplierSource = isset($_GET['source']) && $_GET['source'] === 'supplier';

$stmt = $conn->prepare("
    SELECT p.total_amount, p.amount_paid, p.created_at, s.name as supplier_name, s.phone as supplier_phone, s.email as supplier_email, u.username as cashier_name
    FROM purchases p 
    JOIN suppliers s ON p.supplier_id = s.id 
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $purchaseId);
$stmt->execute();
$stmt->bind_result($total, $amountPaid, $date, $supplierName, $supplierPhone, $supplierEmail, $cashierName);
if (!$stmt->fetch()) {
    die("Purchase order not found.");
}
$stmt->close();

$items = [];
// If this is a balance settlement ($total == 0), we fetch the recent items from this supplier's active purchases
// so the receipt shows what products they are paying for.
if ($total == 0) {
    $stmt = $conn->prepare("
        SELECT pr.name, pi.quantity, pi.cost_price as price, pr.price as retail_price, pi.unit 
        FROM purchase_items pi 
        JOIN products pr ON pi.product_id = pr.id 
        JOIN purchases p ON pi.purchase_id = p.id
        WHERE p.supplier_id = (SELECT supplier_id FROM purchases WHERE id = ?) 
        AND p.is_cleared = 0 AND p.total_amount > 0
        ORDER BY p.created_at DESC LIMIT 50
    ");
} else {
    $stmt = $conn->prepare("
        SELECT pr.name, pi.quantity, pi.cost_price as price, pr.price as retail_price, pi.unit 
        FROM purchase_items pi 
        JOIN products pr ON pi.product_id = pr.id 
        WHERE pi.purchase_id = ?
    ");
}
$stmt->bind_param("i", $purchaseId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

$settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while($row = $settingsQ->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$orderNo = str_pad($purchaseId, 6, "0", STR_PAD_LEFT);
$discount = 0; // Purchases don't have discount right now
$amountReceived = 0; 
$changeReturned = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $orderNo; ?></title>
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
            overflow: hidden;
            z-index: 1;
        }

        /* Watermark */
        .receipt-container::before {
            content: "";
            position: absolute;
            top: 20%; left: 10%; right: 10%; bottom: 20%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='100' cy='100' r='90' fill='none' stroke='rgba(0,0,0,0.05)' stroke-width='10'/%3E%3Ctext x='100' y='110' text-anchor='middle' font-size='40' font-family='Arial' font-weight='bold' fill='rgba(0,0,0,0.05)'%3E<?php echo urlencode(substr($settings['store_name'], 0, 3)); ?>%3C/text%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            z-index: 0;
            pointer-events: none;
        }

        .receipt-content {
            position: relative;
            z-index: 1;
        }

        .center { text-align: center; }
        .bold { font-weight: 700; }
        
        .header { margin-bottom: 20px; }
        .header h1 { margin: 0 0 5px 0; font-size: 24px; letter-spacing: 1px; border-bottom: 2px solid #000; padding-bottom: 5px; display: inline-block;}
        .header p { margin: 2px 0; font-size: 12px; }

        .line { border-bottom: 1px dashed #666; margin: 15px 0; }
        .line-solid { border-bottom: 1px solid #000; margin: 15px 0; }
        
        .info-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 3px; }
        
        .item-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .item-table th { text-align: left; border-bottom: 1px dashed #666; padding-bottom: 5px; font-size: 13px; }
        .item-table td { padding: 5px 0; vertical-align: top; }
        .item-table .qty { width: 25%; font-size: 12px;}
        .item-table .desc { width: 50%; }
        .item-table .amt { width: 25%; text-align: right; }
        
        .totals { margin-top: 15px; }
        .totals .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .totals .row.grand { font-size: 18px; font-weight: 700; border-top: 1px dashed #666; border-bottom: 1px dashed #666; padding: 10px 0; margin: 10px 0; }
        
        .footer { text-align: center; margin-top: 20px; font-size: 12px; }
        .barcode { margin-top: 15px; }

        .actions { margin-top: 30px; display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; font-size: 14px; cursor: pointer; border-radius: 4px; font-family: inherit; font-weight: bold; }
        .btn-print { background: #000; color: #fff; }
        .btn-close { background: #e2e8f0; color: #333; }

        @media print {
            body { padding: 0; background: white; align-items: flex-start; }
            .receipt-container { width: 100%; box-shadow: none; padding: 0; }
            .actions { display: none; }
            .cloned-receipt { page-break-before: always; }
        }
    </style>
</head>
<body>
    <div class="receipt-container" id="main-receipt">
        <div class="receipt-content">
            <div class="header center">
                <!-- Top Logo -->
                <div style="width: 50px; height: 50px; border: 3px solid #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; margin: 0 auto 10px auto;">
                    <?php echo substr($settings['store_name'], 0, 1); ?>
                </div>
                <h1><?php echo htmlspecialchars(strtoupper($settings['store_name'])); ?></h1>
                <p><?php echo nl2br(htmlspecialchars($settings['store_address'])); ?></p>
            <p>Tel: <?php echo htmlspecialchars($settings['store_phone']); ?></p>
        </div>

        <div class="center" style="margin: 15px 0; border-top: 1px dashed #666; border-bottom: 1px dashed #666; padding: 5px 0; font-weight: bold; font-size: 16px;">
            <?php echo ($total == 0 || $isSupplierSource) ? 'SUPPLIER PAYMENT / STATEMENT' : 'PURCHASE ORDER'; ?>
        </div>

        <div class="info-row">
            <span>Date: <?php echo date('d-M-Y', strtotime($date)); ?></span>
            <span>Time: <?php echo date('H:i:s', strtotime($date)); ?></span>
        </div>
        <div class="info-row">
            <span>PO #: <?php echo $orderNo; ?></span>
            <span>User: <?php echo strtoupper(htmlspecialchars($cashierName ?: 'Admin')); ?></span>
        </div>

        <?php if(!empty($supplierName)): ?>
        <div class="line-solid" style="margin: 10px 0;"></div>
        <div style="font-size: 13px; margin-bottom: 10px; line-height: 1.4;">
            <span class="bold">Supplier:</span><br>
            <?php echo htmlspecialchars($supplierName); ?>
            <?php if(!empty($supplierPhone)): ?>
                <br>Tel: <?php echo htmlspecialchars($supplierPhone); ?>
            <?php endif; ?>
        </div>
        <div class="line-solid" style="margin: 10px 0;"></div>
        <?php endif; ?>

        <table class="item-table">
            <thead>
                <tr>
                    <th class="qty">QTY</th>
                    <th class="desc">ITEM</th>
                    <th class="amt">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                foreach($items as $item): 
                    $itemTotal = $item['quantity'] * $item['price'];
                    $subtotal += $itemTotal;
                ?>
                <tr>
                    <td class="qty"><?php echo floatval($item['quantity']) . ' ' . $item['unit']; ?></td>
                    <td class="desc">
                        <?php echo htmlspecialchars($item['name']); ?><br>
                        <small style="color: #555;">Buy: $<?php echo number_format($item['price'], 2); ?> | Sell: $<?php echo number_format($item['retail_price'], 2); ?></small>
                    </td>
                    <td class="amt">$<?php echo number_format($itemTotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <?php if ($total > 0 && !$isSupplierSource): ?>
                <div class="row">
                    <span>SUBTOTAL</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="row grand">
                    <span>TOTAL AMOUNT</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <?php if ($amountPaid > 0): ?>
                    <div class="row" style="margin-top: 10px; font-size: 13px;">
                        <span>AMOUNT PAID</span>
                        <span>$<?php echo number_format($amountPaid, 2); ?></span>
                    </div>
                    <?php if ($total - $amountPaid > 0): ?>
                    <div class="row" style="font-size: 13px; font-weight: bold;">
                        <span>BALANCE DUE</span>
                        <span>$<?php echo number_format($total - $amountPaid, 2); ?></span>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($total > 0): ?>
                    <div class="row">
                        <span>SUBTOTAL</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="row grand">
                        <span>TOTAL AMOUNT</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="row grand" style="background:#22c55e; color:white; border-radius:8px; padding:15px; margin-top:15px; text-align:center; display:flex; flex-direction:column; border:none;">
                    <span style="font-size:14px; margin-bottom:5px;">PAYMENT PAID TO SUPPLIER</span>
                    <span style="font-size:26px;">$<?php echo number_format($amountPaid, 2); ?></span>
                </div>
                
                <?php if ($total > 0 && $total - $amountPaid > 0): ?>
                <div class="row" style="margin-top: 10px; font-size: 13px; font-weight: bold;">
                    <span>REMAINING BALANCE</span>
                    <span>$<?php echo number_format($total - $amountPaid, 2); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($total == 0): ?>
                <div class="center" style="font-size:12px; color:#555; margin-top:5px;">
                    Thank you for your payment.<br>
                    Above are the active items related to your balance.
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p class="bold" style="font-size: 14px;">INTERNAL RECORD</p>
            <p>Purchase Order and Stock Entry<br>Auto-generated by POS</p>
            
            <!-- Barcode -->
            <div class="barcode" style="margin-top: 15px; text-align: center;">
                <svg id="barcode"></svg>
            </div>
            <p style="margin: 0; font-size: 10px; letter-spacing: 2px;"><?php echo $orderNo; ?></p>
        </div>
        </div> <!-- End receipt-content -->
    </div>
    
    <!-- JSBarcode Library -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <script>
        JsBarcode("#barcode", "<?php echo $orderNo; ?>", {
            format: "CODE128",
            width: 1.5,
            height: 40,
            displayValue: false,
            margin: 0
        });
    </script>
    
    <div class="actions">
        <div style="display: flex; align-items: center; background: white; padding: 0 10px; border-radius: 4px; border: 1px solid #ccc;">
            <label for="print-copies" style="font-size: 14px; font-weight: bold; margin-right: 5px;">Copies:</label>
            <input type="number" id="print-copies" value="1" min="1" max="10" style="width: 50px; border: none; outline: none; font-size: 16px; text-align: center;">
        </div>
        <button class="btn btn-print" onclick="doPrint()">🖨️ PRINT</button>
        <button class="btn" style="background: #059669;" onclick="downloadPDF()">📄 PDF</button>
        <button class="btn btn-close" onclick="window.close()">✖ CLOSE</button>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function doPrint() {
            let copies = parseInt(document.getElementById('print-copies').value) || 1;
            let originalReceipt = document.getElementById('main-receipt');
            
            // Remove any previously cloned receipts
            document.querySelectorAll('.cloned-receipt').forEach(e => e.remove());
            
            // Clone if copies > 1
            for(let i = 1; i < copies; i++) {
                let clone = originalReceipt.cloneNode(true);
                clone.classList.add('cloned-receipt');
                originalReceipt.parentNode.insertBefore(clone, originalReceipt.nextSibling);
            }
            
            window.print();
        }

        function downloadPDF() {
            const element = document.getElementById('main-receipt');
            const opt = {
                margin:       0.2,
                filename:     'Receipt_<?php echo $orderNo; ?>.pdf',
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

        // Auto print dialog on load (1 copy by default)
        window.onload = function() { 
            setTimeout(() => { doPrint(); }, 500);
        }
    </script>
</body>
</html>
