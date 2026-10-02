<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}
$product_id = intval($_GET['id']);

$pQ = $conn->query("SELECT * FROM products WHERE id = $product_id AND tenant_id = {$_SESSION['tenant_id']}");
if (!$pQ || $pQ->num_rows === 0) {
    die("<div style='padding:2rem;'>Product not found.</div>");
}
$product = $pQ->fetch_assoc();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $v_name = $conn->real_escape_string($_POST['variation_name']);
        $v_sku = $conn->real_escape_string($_POST['barcode']);
        $v_stock = floatval($_POST['stock']);
        $v_price = !empty($_POST['price']) ? floatval($_POST['price']) : null;
        $v_purchase = !empty($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : 0;
        
        $priceVal = $v_price === null ? "NULL" : $v_price;
        $conn->query("INSERT INTO product_variations (product_id, variation_name, barcode, stock, purchase_price, price, tenant_id) VALUES ($product_id, '$v_name', '$v_sku', $v_stock, $v_purchase, $priceVal, {$_SESSION['tenant_id']})");
        $message = "Variation added successfully!";
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $vid = intval($_POST['variation_id']);
        $conn->query("DELETE FROM product_variations WHERE id = $vid AND product_id = $product_id");
        $message = "Variation deleted!";
    }
}

$vars = $conn->query("SELECT * FROM product_variations WHERE product_id = $product_id");
?>

<div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2>Manage Variations: <?php echo htmlspecialchars($product['name']); ?></h2>
        <p>Add sizes, colors, or flavors for this product.</p>
    </div>
    <a href="products.php" class="btn" style="background:#e2e8f0; color:#475569;">&larr; Back to Products</a>
</div>

<?php if($message) echo "<div style='background:#10b981;color:white;padding:1rem;border-radius:6px;margin-bottom:1rem;'>$message</div>"; ?>

<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="flex:1; min-width:300px; background:white; padding:1.5rem; border-radius:12px; border:1px solid #e2e8f0;">
        <h3>Add Variation</h3>
        <form method="POST" style="margin-top:1rem;">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom:1rem;">
                <label>Variation Name (e.g. Red - Large)</label>
                <input type="text" name="variation_name" required style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px; margin-top:0.3rem;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Barcode / SKU (Optional)</label>
                <input type="text" name="barcode" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px; margin-top:0.3rem;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Stock</label>
                <input type="number" step="0.01" name="stock" value="0" required style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px; margin-top:0.3rem;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Purchase Price (Buy Price) ($)</label>
                <input type="number" step="0.01" name="purchase_price" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px; margin-top:0.3rem;" placeholder="<?php echo number_format($product['purchase_price'] ?? 0, 2); ?>">
                <small style="color:#64748b; font-size: 0.8rem;">Leave blank to inherit parent purchase price</small>
            </div>
            <div style="margin-bottom:1rem;">
                <label>Sale Price Override ($)</label>
                <input type="number" step="0.01" name="price" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px; margin-top:0.3rem;" placeholder="<?php echo number_format($product['price'], 2); ?>">
                <small style="color:#64748b; font-size: 0.8rem;">Leave blank to inherit parent sale price</small>
            </div>
            <button class="btn btn-primary" style="width:100%; border-radius:6px;">Add Variation</button>
        </form>
    </div>
    
    <div style="flex:2; min-width:400px; background:white; padding:1.5rem; border-radius:12px; border:1px solid #e2e8f0;">
        <h3>Current Variations</h3>
        <table class="data-table" style="width:100%; margin-top:1rem; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0; text-align:left;">
                    <th style="padding:0.75rem;">Name</th>
                    <th style="padding:0.75rem;">Barcode</th>
                    <th style="padding:0.75rem;">Stock</th>
                    <th style="padding:0.75rem;">Buy Price</th>
                    <th style="padding:0.75rem;">Sell Price</th>
                    <th style="padding:0.75rem;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($vars->num_rows > 0): ?>
                    <?php while($v = $vars->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:0.75rem; font-weight:600;"><?php echo htmlspecialchars($v['variation_name']); ?></td>
                        <td style="padding:0.75rem; color:#64748b;"><?php echo htmlspecialchars($v['barcode'] ?: '-'); ?></td>
                        <td style="padding:0.75rem; font-weight:bold; color:<?php echo $v['stock']<=0?'red':'#0f172a'; ?>"><?php echo floatval($v['stock']); ?></td>
                        <td style="padding:0.75rem; color: #b45309;">
                            <?php echo ($v['purchase_price'] > 0) ? '$'.number_format($v['purchase_price'], 2) : '<span style="color:#94a3b8;font-size:0.8rem;">(Inherited) $'.number_format($product['purchase_price']??0,2).'</span>'; ?>
                        </td>
                        <td style="padding:0.75rem; color: var(--primary-color); font-weight: 500;">
                            <?php echo $v['price'] ? '$'.number_format($v['price'], 2) : '<span style="color:#94a3b8;font-size:0.8rem; font-weight:normal;">(Inherited) $'.number_format($product['price'],2).'</span>'; ?>
                        </td>
                        <td style="padding:0.75rem;">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this variation?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="variation_id" value="<?php echo $v['id']; ?>">
                                <button type="submit" style="background:#ef4444; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="padding:1rem; text-align:center; color:#64748b;">No variations added yet. Product will act as a standard single-item product.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
