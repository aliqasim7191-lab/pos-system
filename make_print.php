<?php
include 'includes/db.php';
// Create print_transfer.php
\ = <<<EOD
<?php
include 'includes/db.php';
\ = isset(\['id']) ? intval(\['id']) : 0;
\ = \->query("
    SELECT t.*, p.name as product_name, b1.name as from_name, b2.name as to_name 
    FROM stock_transfers t
    JOIN products p ON t.product_id = p.id
    JOIN branches b1 ON t.from_branch = b1.id
    JOIN branches b2 ON t.to_branch = b2.id
    WHERE t.id = \
");
if (!\ || \->num_rows == 0) {
    die("Transfer not found.");
}
\ = \->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transfer #<?php echo \['id']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .receipt { max-width: 400px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px dashed #eee; padding-bottom: 5px; }
    </style>
</head>
<body>
    <div class="receipt" id="receipt">
        <div class="header">
            <h2>Stock Transfer Bill</h2>
            <p>Transfer ID: #<?php echo \['id']; ?></p>
            <p>Date: <?php echo date('d M Y h:i A', strtotime(\['transfer_date'])); ?></p>
        </div>
        <div class="row"><span>Status:</span> <strong><?php echo strtoupper(\['status']); ?></strong></div>
        <div class="row"><span>From Branch:</span> <strong><?php echo htmlspecialchars(\['from_name']); ?></strong></div>
        <div class="row"><span>To Branch:</span> <strong><?php echo htmlspecialchars(\['to_name']); ?></strong></div>
        <div class="row"><span>Product:</span> <strong><?php echo htmlspecialchars(\['product_name']); ?></strong></div>
        <div class="row"><span>Quantity:</span> <strong><?php echo \['quantity']; ?></strong></div>
        
        <?php if (\['is_deleted']): ?>
        <h3 style="color:red; text-align:center; border: 2px solid red; padding: 5px;">DELETED</h3>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 20px;" class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #3b82f6; color: white; border: none; border-radius: 5px;">Print Bill</button>
    </div>
    <style>
    @media print { .no-print { display: none; } }
    </style>
</body>
</html>
EOD;
file_put_contents('print_transfer.php', \);
echo "Created print_transfer.php\n";
?>
