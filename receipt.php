<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}
include 'includes/db.php';

if (!isset($_GET['id'])) {
    die("Invalid Sale ID");
}

$saleId = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT s.total_amount, s.discount, s.tax_amount, s.payment_method, s.amount_received, s.change_returned, s.created_at, 
           COALESCE(c.name, s.customer_name) as customer_name, COALESCE(c.address, s.customer_address) as customer_address,
           s.taken_by, COALESCE(b.name, 'Main Branch') as branch_name
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    LEFT JOIN branches b ON s.branch_id = b.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $saleId);
$stmt->execute();
$stmt->bind_result($total, $discount, $taxAmount, $paymentMethod, $amountReceived, $changeReturned, $date, $customerName, $customerAddress, $takenBy, $branchName);
if (!$stmt->fetch()) {
    die("Sale not found.");
}
$stmt->close();

$items = [];
$stmt = $conn->prepare("
    SELECT p.name, si.quantity, si.price, p.unit 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt->bind_param("i", $saleId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

$tenantId = intval($_SESSION['tenant_id'] ?? 1);
$settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE tenant_id = $tenantId OR tenant_id IS NULL OR tenant_id = 0");
$settings = [];
if ($settingsQ) {
    while($row = $settingsQ->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$pri_curr = $settings['currency_symbol'] ?? '$';
$sec_curr = $settings['secondary_currency_symbol'] ?? '';
$exch_rate = floatval($settings['exchange_rate'] ?? 1);

$storeFullName = strtoupper(trim($settings['store_name'] ?? 'SUPERSTORE POS'));

$hasLogo = !empty($settings['store_logo']) && file_exists($settings['store_logo']);

if ($hasLogo) {
    $watermarkUrl = $settings['store_logo'];
} else {
    $len = strlen($storeFullName);
    $fontSize = $len > 18 ? 20 : ($len > 12 ? 24 : 28);
    $watermarkUrl = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 300'%3E%3Ccircle cx='150' cy='150' r='135' fill='none' stroke='rgba(0,0,0,0.06)' stroke-width='8'/%3E%3Ctext x='150' y='160' text-anchor='middle' font-size='{$fontSize}' font-family='Courier, Arial, sans-serif' font-weight='bold' fill='rgba(0,0,0,0.06)'%3E" . rawurlencode($storeFullName) . "%3C/text%3E%3C/svg%3E";
}

$orderNo = str_pad($saleId, 6, "0", STR_PAD_LEFT);
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
            top: 22%; left: 6%; right: 6%; bottom: 22%;
            background-image: url("<?php echo htmlspecialchars($watermarkUrl); ?>");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: contain;
            opacity: <?php echo $hasLogo ? '0.09' : '0.85'; ?>;
            mix-blend-mode: multiply;
            filter: <?php echo $hasLogo ? 'grayscale(100%) contrast(160%)' : 'none'; ?>;
            z-index: 0;
            pointer-events: none;
        }

        .receipt-content {
            position: relative;
            z-index: 2;
            color: #000000 !important;
            font-weight: 600;
        }

        .receipt-content td, 
        .receipt-content th, 
        .receipt-content span, 
        .receipt-content p, 
        .receipt-content div,
        .receipt-content h1 {
            color: #000000 !important;
            text-shadow: -1px -1px 0 #ffffff, 1px -1px 0 #ffffff, -1px 1px 0 #ffffff, 1px 1px 0 #ffffff, 0 0 3px #ffffff;
        }

        .item-table th, .item-table td {
            font-weight: 700 !important;
            color: #000000 !important;
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
                <?php if(!empty($settings['store_logo']) && file_exists($settings['store_logo'])): ?>
                    <img src="<?php echo htmlspecialchars($settings['store_logo']); ?>" alt="Logo" style="width: calc(100% + 40px); margin: -25px -20px 12px -20px; max-height: 100px; object-fit: cover; display: block;">
                <?php else: ?>
                    <div style="width: 50px; height: 50px; border: 3px solid #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; margin: 0 auto 10px auto;">
                        <?php echo strtoupper(substr($settings['store_name'] ?? 'D', 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h1><?php echo htmlspecialchars(strtoupper($settings['store_name'] ?? 'DEFAULT COMPANY')); ?></h1>
                <p style="margin: 4px 0 6px 0; font-weight: bold; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #111;">
                    <?php echo htmlspecialchars(!empty($branchName) ? $branchName : 'Main Branch'); ?>
                </p>
                <p><?php echo nl2br(htmlspecialchars($settings['store_address'] ?? '')); ?></p>
                <p>Tel: <?php echo htmlspecialchars($settings['store_phone'] ?? 'N/A'); ?></p>
                <?php if (!empty($settings['tax_id'])): ?>
                    <p style="margin-top: 5px; font-weight: bold;">Tax ID / NTN: <?php echo htmlspecialchars($settings['tax_id']); ?></p>
                <?php endif; ?>
            </div>

            <div class="info-row">
                <span>Date: <?php echo date($settings['date_format'] ?? 'd-m-Y', strtotime($date)); ?></span>
                <span>Time: <?php echo date('H:i', strtotime($date)); ?></span>
        </div>
        <div class="info-row">
            <span>Order #: <?php echo $orderNo; ?></span>
            <span>Cashier: <?php echo strtoupper(htmlspecialchars($_SESSION['username'])); ?></span>
        </div>

        <?php if(!empty($customerName)): ?>
        <div class="line-solid" style="margin: 10px 0;"></div>
        <div style="font-size: 13px; margin-bottom: 10px; line-height: 1.4;">
            <span class="bold">Billed To:</span><br>
            <?php echo htmlspecialchars($customerName); ?>
            <?php if(!empty($customerAddress)): ?>
                <br><?php echo nl2br(htmlspecialchars($customerAddress)); ?>
            <?php endif; ?>
            <?php if($paymentMethod === 'credit' && !empty($takenBy)): ?>
                <div style="margin-top: 5px; padding-top: 5px; border-top: 1px dashed #ccc;">
                    <span class="bold">Taken By:</span> <?php echo htmlspecialchars($takenBy); ?>
                </div>
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
                        <small style="color: #555;">@ <?php echo htmlspecialchars($pri_curr); ?><?php echo number_format($item['price'], 2); ?></small>
                    </td>
                    <td class="amt"><?php echo htmlspecialchars($pri_curr); ?><?php echo number_format($itemTotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <span>SUBTOTAL</span>
                <span><?php echo htmlspecialchars($pri_curr); ?><?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if($discount > 0): ?>
            <div class="row" style="color: #444;">
                <span>DISCOUNT</span>
                <span>-<?php echo htmlspecialchars($pri_curr); ?><?php echo number_format($discount, 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if($taxAmount > 0): ?>
            <div class="row" style="color: #444;">
                <span>TAX</span>
                <span>+<?php echo htmlspecialchars($pri_curr); ?><?php echo number_format($taxAmount, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="row grand">
                <span>TOTAL DUE</span>
                <span><?php echo htmlspecialchars($pri_curr); ?><?php echo number_format($total, 2); ?></span>
            </div>
            <?php /* Secondary currency conversion removed as per request */ ?>
            <div class="row" style="margin-top: 10px; font-size: 13px;">
                <span>PAYMENT (<?php echo strtoupper($paymentMethod); ?>)</span>
                <?php 
                if ($paymentMethod === 'credit') {
                    $displayAmount = $amountReceived; 
                } else {
                    $displayAmount = $amountReceived > 0 ? $amountReceived : $total;
                }
                ?>
                <span><?php echo htmlspecialchars($pri_curr); ?><?php echo number_format($displayAmount, 2); ?></span>
            </div>
            <?php if($changeReturned > 0): ?>
            <div class="row" style="font-size: 13px;">
                <span>CHANGE</span>
                <span><?php echo htmlspecialchars($pri_curr); ?><?php echo number_format($changeReturned, 2); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p class="bold" style="font-size: 14px;">THANK YOU FOR SHOPPING!</p>
            <p>Please keep this receipt for returns<br>within 7 days of purchase.</p>
            
            <!-- Footer -->
            <div class="footer center" style="margin-top: 15px;">
                <?php echo nl2br(htmlspecialchars($settings['receipt_footer'] ?? 'Thank you for shopping with us!')); ?>
                <?php if (!empty($settings['invoice_terms'])): ?>
                    <div style="font-size: 10px; color: #555; margin-top: 10px; text-transform: uppercase;">
                        <?php echo nl2br(htmlspecialchars($settings['invoice_terms'])); ?>
                    </div>
                <?php endif; ?>
                <div style="margin-top: 10px; font-size: 11px;">Powered by SuperStore POS</div>
            </div>
            
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
        <div style="display:flex; gap:0.5rem; margin-top:0.5rem; margin-bottom:0.5rem; width:100%;">
            <input type="text" id="wa-phone" placeholder="WhatsApp Number" style="flex:1; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size:0.9rem;">
            <button class="btn" onclick="sendWhatsApp()" style="background:#25D366; color:white; border:none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; display:flex; align-items:center; gap:0.5rem; font-weight:bold;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 0C5.385 0 0 5.386 0 12.035c0 2.127.553 4.195 1.603 6.02L.036 23.951l6.04-1.583c1.765.952 3.754 1.455 5.95 1.455 6.647 0 12.035-5.385 12.035-12.035C24.062 5.385 18.678 0 12.031 0zm0 21.84c-1.802 0-3.568-.485-5.116-1.402l-.367-.218-3.799.996.996-3.704-.239-.38C2.476 15.352 1.94 13.722 1.94 12.035c0-5.568 4.53-10.098 10.096-10.098 5.564 0 10.095 4.53 10.095 10.098 0 5.566-4.531 10.095-10.095 10.095zm5.541-7.56c-.304-.152-1.796-.887-2.073-.988-.278-.101-.48-.152-.682.152-.202.304-.783.988-.959 1.19-.176.202-.353.228-.656.076-.304-.152-1.284-.473-2.445-1.506-.906-.807-1.517-1.802-1.693-2.106-.176-.304-.019-.468.133-.62.136-.137.304-.354.455-.531.152-.177.202-.304.304-.506.101-.202.05-.38-.026-.531-.076-.152-.682-1.644-.934-2.253-.245-.592-.495-.512-.682-.522-.176-.01-.379-.01-.581-.01-.202 0-.53.076-.808.38-.278.304-1.06 1.037-1.06 2.53 0 1.492 1.085 2.935 1.237 3.137.152.202 2.14 3.266 5.184 4.577.724.312 1.288.498 1.728.638.726.231 1.386.198 1.905.12.583-.087 1.796-.733 2.049-1.442.253-.708.253-1.315.176-1.442-.075-.126-.277-.202-.581-.353z"/></svg>
                WhatsApp
            </button>
        </div>
        <button class="btn btn-close" onclick="closeReceipt()">✖ CLOSE</button>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function closeReceipt() {
            // If it's a popup, window.close() will work. Otherwise redirect to POS.
            if (window.opener && !window.opener.closed) {
                window.close();
            } else {
                window.location.href = 'index.php';
            }
        }
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
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ generating...';
            btn.disabled = true;

            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        function sendWhatsApp() {
            let phone = document.getElementById('wa-phone').value.trim();
            if(!phone) {
                alert("Please enter a WhatsApp number first.");
                return;
            }
            // Basic cleanup (remove spaces, dashes)
            phone = phone.replace(/[\s\-\+]/g, '');
            // Auto convert local Pakistani format (03xx) to international format (923xx)
            if (phone.startsWith('03') && phone.length === 11) {
                phone = '92' + phone.substring(1);
            }
            
            // Build Receipt Text
            let storeName = "<?php echo addslashes($settings['store_name'] ?? 'SuperStore POS'); ?>";
            let storePhone = "<?php echo addslashes($settings['store_phone'] ?? ''); ?>";
            let receiptNo = "<?php echo addslashes($orderNo); ?>";
            let date = "<?php echo addslashes($date ?? ''); ?>";
            let customerName = "<?php echo addslashes($customerName ?? ''); ?>";
            let paymentMethod = "<?php echo strtoupper($paymentMethod); ?>";
            let subtotal = "<?php echo number_format((float)($subtotal ?? 0), 2); ?>";
            let discount = "<?php echo number_format((float)($discount ?? 0), 2); ?>";
            let tax = "<?php echo number_format((float)($taxAmount ?? 0), 2); ?>";
            let total = "<?php echo number_format((float)($total ?? 0), 2); ?>";
            let received = "<?php echo number_format((float)($displayAmount ?? 0), 2); ?>";
            let change = "<?php echo number_format((float)($changeReturned ?? 0), 2); ?>";
            
            let text = "*" + storeName + "*\n";
            if(storePhone) text += "📱 " + storePhone + "\n";
            text += "------------------------\n";
            text += "🧾 *Receipt #: " + receiptNo + "*\n";
            text += "📅 Date: " + date + "\n";
            if (customerName) text += "👤 Customer: " + customerName + "\n";
            text += "------------------------\n";
            text += "*ITEMS:*\n";
            
            <?php
            foreach($items as $item) {
                  $itemName = addslashes($item['name']);
                  $qty = floatval($item['quantity']);
                  $sub = number_format($qty * $item['price'], 2);
                  echo "text += '• ' + '{$itemName}' + '\\n  ' + '{$qty} x {$pri_curr}{$sub}\\n';\n";
              }
              ?>
            
            text += "------------------------\n";
            text += "Subtotal: <?php echo $pri_curr; ?>" + subtotal + "\n";
            if (parseFloat(discount) > 0) text += "Discount: -<?php echo $pri_curr; ?>" + discount + "\n";
            if (parseFloat(tax) > 0) text += "Tax: +<?php echo $pri_curr; ?>" + tax + "\n";
            text += "------------------------\n";
            text += "💰 *TOTAL DUE: <?php echo $pri_curr; ?>" + total + "*\n";
            text += "------------------------\n";
            text += "Paid by: " + paymentMethod + "\n";
            text += "Amount Received: <?php echo $pri_curr; ?>" + received + "\n";
            if (parseFloat(change) > 0) text += "Change: <?php echo $pri_curr; ?>" + change + "\n";
            text += "------------------------\n";
            text += "Thank you for shopping! 🌟";
            
            let encoded = encodeURIComponent(text);
            let url = "https://wa.me/" + phone + "?text=" + encoded;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>