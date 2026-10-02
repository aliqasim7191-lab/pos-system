<?php
include 'includes/db.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$q = $conn->query("
    SELECT t.*, p.name as product_name, b1.name as from_name, b2.name as to_name 
    FROM stock_transfers t
    JOIN products p ON t.product_id = p.id
    JOIN branches b1 ON t.from_branch = b1.id
    JOIN branches b2 ON t.to_branch = b2.id
    WHERE t.id = $id
");
if (!$q || $q->num_rows == 0) {
    die("Transfer not found.");
}
$t = $q->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transfer #<?php echo $t['id']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f8fafc; }
        .receipt { max-width: 450px; margin: 0 auto; background: white; border: 1px solid #e2e8f0; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; }
        .header h2 { margin: 0 0 10px 0; color: #0f172a; }
        .header p { margin: 5px 0; color: #64748b; font-size: 0.9rem; }
        .row { display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px; font-size: 0.95rem; }
        .row span { color: #475569; }
        .row strong { color: #0f172a; }
    </style>
    <!-- Include html2pdf.js for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <div class="receipt" id="receipt">
        <div class="header">
            <h2>Stock Transfer Bill</h2>
            <p>Transfer ID: <strong>#<?php echo $t['id']; ?></strong></p>
            <p>Date: <?php echo date('d M Y h:i A', strtotime($t['transfer_date'])); ?></p>
        </div>
        <div class="row"><span>Status:</span> <strong><?php echo strtoupper($t['status']); ?></strong></div>
        <div class="row"><span>From Branch:</span> <strong><?php echo htmlspecialchars($t['from_name']); ?></strong></div>
        <div class="row"><span>To Branch:</span> <strong><?php echo htmlspecialchars($t['to_name']); ?></strong></div>
        <div class="row"><span>Product:</span> <strong><?php echo htmlspecialchars($t['product_name']); ?></strong></div>
        <div class="row"><span>Quantity:</span> <strong style="font-size: 1.1rem; color: #2563eb;"><?php echo $t['quantity']; ?></strong></div>
        
        <?php if ($t['is_deleted']): ?>
        <div style="margin-top: 20px; text-align: center; color: #ef4444; border: 2px dashed #ef4444; padding: 10px; font-weight: bold; border-radius: 6px;">
            DELETED / REVERSED
        </div>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px; display: flex; justify-content: center; gap: 1rem;" class="no-print">
        <button onclick="window.print()" style="padding: 12px 24px; cursor: pointer; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 1rem;">🖨️ Print Bill</button>
        <button onclick="downloadPDF()" style="padding: 12px 24px; cursor: pointer; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 1rem;">📄 Save PDF</button>
        <button onclick="window.close()" style="padding: 12px 24px; cursor: pointer; background: #64748b; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 1rem;">Close</button>
    </div>
    
    <script>
    function downloadPDF() {
        const element = document.getElementById('receipt');
        const opt = {
            margin:       0.5,
            filename:     'Transfer_Bill_#<?php echo $t['id']; ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>
    <style>
    @media print { 
        body { background: white; padding: 0; }
        .receipt { box-shadow: none; border: none; max-width: 100%; }
        .no-print { display: none !important; } 
    }
    </style>
</body>
</html>
