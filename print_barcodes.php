<?php
include 'includes/db.php';

$productQ = $conn->query("SELECT id, name, barcode, price FROM products WHERE barcode IS NOT NULL AND barcode != '' ORDER BY name ASC");
$products = [];
while ($row = $productQ->fetch_assoc()) {
    $products[] = $row;
}

$printList = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['print'])) {
    $selections = $_POST['print_qty'] ?? [];
    foreach ($selections as $id => $qty) {
        $qty = intval($qty);
        if ($qty > 0) {
            // Find product
            foreach ($products as $p) {
                if ($p['id'] == $id) {
                    for ($i = 0; $i < $qty; $i++) {
                        $printList[] = $p;
                    }
                    break;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcodes</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .no-print { display: block; }
        .print-only { display: none; }
        
        .setup-container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn { padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 500; }
        
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .print-only { display: block; }
            .barcode-grid { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-start; }
            .barcode-item { width: 150px; height: 90px; border: 1px dashed #ccc; padding: 5px; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; box-sizing: border-box; }
            .barcode-item p { margin: 2px 0; font-size: 10px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; }
            .barcode-item svg { height: 45px !important; max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="no-print setup-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2>🖨️ Print Barcode Labels</h2>
            <a href="products.php" style="color: #64748b; text-decoration: none;">Back to Inventory</a>
        </div>
        
        <form method="POST">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead>
                    <tr style="background: #f1f5f9;">
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #e2e8f0;">Product</th>
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #e2e8f0;">Barcode</th>
                        <th style="padding: 0.75rem; text-align: center; border-bottom: 2px solid #e2e8f0;">Copies to Print</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $p): ?>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #e2e8f0;"><?php echo htmlspecialchars($p['name']); ?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #e2e8f0; font-family: monospace;"><?php echo htmlspecialchars($p['barcode']); ?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #e2e8f0; text-align: center;">
                            <input type="number" name="print_qty[<?php echo $p['id']; ?>]" min="0" max="100" value="0" style="width: 80px; padding: 0.5rem; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="print" class="btn" style="width: 100%;">Generate Preview</button>
        </form>
    </div>

    <?php if(!empty($printList)): ?>
    <div class="no-print setup-container" style="margin-top: 2rem; text-align: center;">
        <p>Generated <?php echo count($printList); ?> labels.</p>
        <button onclick="window.print()" class="btn" style="background: #10b981;">🖨️ Print Now</button>
    </div>

    <div class="print-only barcode-grid">
        <?php foreach($printList as $index => $item): ?>
            <div class="barcode-item">
                <p><?php echo substr(htmlspecialchars($item['name']), 0, 20); ?></p>
                <svg id="barcode-<?php echo $index; ?>"></svg>
                <p>$<?php echo number_format($item['price'], 2); ?></p>
                <script>
                    JsBarcode("#barcode-<?php echo $index; ?>", "<?php echo htmlspecialchars($item['barcode']); ?>", {
                        format: "CODE128",
                        width: 1.5,
                        height: 40,
                        displayValue: true,
                        fontSize: 12,
                        margin: 0
                    });
                </script>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</body>
</html>
