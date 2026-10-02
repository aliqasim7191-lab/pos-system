<?php
$file = 'C:/xampp/htdocs/point of sale/includes/header.php';
$content = file_get_contents($file);

// 1. Remove the right-side button
$content = preg_replace('/<button onclick="window\.location\.href=\'alerts\.php\';" title="Quick Purchase \/ Stock Update" [^>]+>.*?<\/button>/is', '', $content);

// 2. Change the Purchases link in the nav
$content = preg_replace('/<li><a href="purchases\.php"[^>]*>Purchases<\/a><\/li>/i', '<li><a href="#" onclick="openGlobalPurchaseModal(); return false;" class="<?php echo $currentPage == \'purchases.php\' ? \'active\' : \'\'; ?>">Purchases</a></li>', $content);

// 3. Add the modal HTML at the bottom, just before closing </header>
// We need to fetch products and suppliers.
$modalHTML = '
<!-- Global Quick Purchase Modal -->
<div id="globalPurchaseModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center; text-align:left;">
    <div style="background:white; border-radius:16px; padding:2rem; width:100%; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,0.2); position:relative;">
        <button onclick="document.getElementById(\'globalPurchaseModal\').style.display=\'none\'" style="position:absolute; top:1rem; right:1rem; background:#f1f5f9; border:none; border-radius:50%; width:32px; height:32px; font-size:1.2rem; cursor:pointer; color:#64748b;">✕</button>
        <h3 style="color:#0f172a; margin:0 0 0.3rem 0;">📦 Quick Purchase / Stock Update</h3>
        <p style="color:#64748b; font-size:0.9rem; margin:0 0 1.5rem 0;">Buy stock instantly. <a href="purchases.php" style="color:#2563eb; font-weight:600;">View History &rarr;</a></p>

        <?php
            // Fetch Products and Suppliers for global modal
            $gp_products = $conn->query("SELECT id, name, purchase_price, price FROM products WHERE tenant_id = {$_SESSION[\'tenant_id\']} ORDER BY name");
            $gp_suppliers = $conn->query("SELECT id, name FROM suppliers WHERE tenant_id = {$_SESSION[\'tenant_id\']} ORDER BY name");
        ?>

        <form method="POST" action="alerts.php">
            <input type="hidden" name="quick_purchase" value="1">
            <input type="hidden" name="redirect_back" value="<?php echo htmlspecialchars($_SERVER[\'REQUEST_URI\']); ?>">

            <!-- Product -->
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">🛍️ Product (Konsi item)</label>
                <select name="product_id" id="gpProductId" required style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem;" onchange="updateGPPrice()">
                    <option value="">-- Select Product --</option>
                    <?php if($gp_products) while($p = $gp_products->fetch_assoc()): ?>
                    <option value="<?php echo $p[\'id\']; ?>" data-cost="<?php echo $p[\'purchase_price\']; ?>" data-sell="<?php echo $p[\'price\']; ?>"><?php echo htmlspecialchars($p[\'name\']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Supplier -->
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">🏭 Supplier (Kis say le rahe hain)</label>
                <select name="supplier_id" required style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem;">
                    <option value="0">-- Walk-in / No Supplier --</option>
                    <?php if($gp_suppliers) while($s = $gp_suppliers->fetch_assoc()): ?>
                    <option value="<?php echo $s[\'id\']; ?>"><?php echo htmlspecialchars($s[\'name\']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Qty + Cost Price + Sell Price -->
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">📦 Qty</label>
                    <input type="number" name="qty" id="gpQty" min="1" step="0.01" required placeholder="50" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; box-sizing:border-box;" oninput="calcGPTotal()">
                </div>
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">💰 Buy</label>
                    <input type="number" name="cost_price" id="gpCostPrice" min="0" step="0.01" required placeholder="150" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; box-sizing:border-box;" oninput="calcGPTotal()">
                </div>
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#10b981; display:block; margin-bottom:0.3rem;">🏷️ Sell</label>
                    <input type="number" name="sell_price" id="gpSellPrice" min="0" step="0.01" placeholder="200" style="width:100%; padding:0.6rem; border:1px solid #86efac; border-radius:8px; font-size:0.95rem; box-sizing:border-box; background:#f0fdf4;">
                </div>
            </div>

            <!-- Total display -->
            <div id="gpTotalDisplay" style="background:#f0fdf4; border:1px solid #86efac; padding:0.8rem 1rem; border-radius:8px; margin-bottom:1rem; font-weight:700; color:#15803d; display:none;">
                Total Amount: Rs. <span id="gpTotalAmt">0</span>
            </div>

            <!-- Payment -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">💳 Method</label>
                    <select name="pay_method" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem;">
                        <option value="cash">💵 Cash</option>
                        <option value="bank">🏦 Bank</option>
                        <option value="cheque">📝 Cheque</option>
                        <option value="online">📱 Online</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">💵 Paid Now</label>
                    <input type="number" name="amount_paid" id="gpAmountPaid" min="0" step="0.01" placeholder="0 = Udhaar" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; box-sizing:border-box;">
                </div>
            </div>

            <button type="submit" style="width:100%; background:linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; padding:0.9rem; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer;">
                ✅ Record Purchase & Update Stock
            </button>
        </form>
    </div>
</div>

<script>
function openGlobalPurchaseModal() {
    document.getElementById(\'globalPurchaseModal\').style.display = \'flex\';
}
function updateGPPrice() {
    var sel = document.getElementById(\'gpProductId\');
    var opt = sel.options[sel.selectedIndex];
    if(opt.value) {
        document.getElementById(\'gpCostPrice\').value = opt.getAttribute(\'data-cost\') || \'\';
        document.getElementById(\'gpSellPrice\').value = opt.getAttribute(\'data-sell\') || \'\';
    } else {
        document.getElementById(\'gpCostPrice\').value = \'\';
        document.getElementById(\'gpSellPrice\').value = \'\';
    }
    calcGPTotal();
}
function calcGPTotal() {
    var qty = parseFloat(document.getElementById(\'gpQty\').value) || 0;
    var cost = parseFloat(document.getElementById(\'gpCostPrice\').value) || 0;
    var total = qty * cost;
    if (total > 0) {
        document.getElementById(\'gpTotalAmt\').textContent = total.toLocaleString(\'en-PK\', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById(\'gpTotalDisplay\').style.display = \'block\';
        document.getElementById(\'gpAmountPaid\').placeholder = \'Max: \' + total.toFixed(2);
    } else {
        document.getElementById(\'gpTotalDisplay\').style.display = \'none\';
    }
}
</script>
</header>';

$content = str_replace('</header>', $modalHTML, $content);
file_put_contents($file, $content);
echo "Nav updated.";
?>
