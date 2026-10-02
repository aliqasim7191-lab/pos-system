<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad_line = 'AND tenant_id = {$_SESSION[\'tenant_id\']}");' . "\n" . '-? <span id="customer-points-val">0</span> Pts</div>';

// Need to do a dynamic regex in case it's slightly different
$f = preg_replace('/AND tenant_id = \{\$_SESSION\[\'tenant_id\'\]\}\"\);.*?<span id="customer-points-val">0<\/span> Pts<\/div>/s', '%%REPLACE%%', $f);

$good_code = <<<PHP
AND tenant_id = {\$_SESSION['tenant_id']}");
\$products = [];
if (\$result) {
    while(\$row = \$result->fetch_assoc()) {
        \$products[] = \$row;
    }
}
?>

<div class="container pos-layout">
    <div class="pos-left">
        <!-- Search and Categories -->
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <input type="text" id="barcodeInput" placeholder="Scan Barcode (F4)" style="flex: 1; padding: 0.75rem; border: 2px solid #3b82f6; border-radius: 8px; font-size: 1.1rem; outline: none;">
            <input type="text" id="searchInput" placeholder="Search product... (F2)" style="flex: 2; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1.1rem;">
        </div>
        
        <div class="category-filters" id="categoryFilters" style="display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.5rem; margin-bottom: 1rem;">
            <button class="btn btn-primary" style="padding: 0.4rem 1.5rem; border-radius: 50px;" onclick="filterCategory('all')">All</button>
            <?php
            \$catRes = \$conn->query("SELECT DISTINCT category FROM products WHERE status='active' AND tenant_id = {\$_SESSION['tenant_id']} AND category != ''");
            if (\$catRes) {
                while(\$cat = \$catRes->fetch_assoc()) {
                    echo "<button class='btn' style='background: white; border: 1px solid #cbd5e1; padding: 0.4rem 1.5rem; border-radius: 50px; color: #475569; font-weight: 600;' onclick=\"filterCategory('".htmlspecialchars(\$cat['category'])."')\">" . htmlspecialchars(strtoupper(\$cat['category'])) . "</button>";
                }
            }
            ?>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; overflow-y: auto; max-height: calc(100vh - 250px); padding-right: 0.5rem;">
            <!-- Products injected via JS -->
        </div>
    </div>
    
    <div class="pos-right" style="display: flex; flex-direction: column; height: calc(100vh - 80px);">
        <div style="background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%; border: 1px solid #e2e8f0;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="margin: 0; font-size: 1.25rem; color: #0f172a; font-weight: 800;">Current Order</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn" style="background: #fee2e2; color: #ef4444; padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="clearCart()">Clear</button>
                    <button class="btn" style="background: #f1f5f9; color: #64748b; padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="holdSale()">Hold</button>
                    <button class="btn" style="background: #eff6ff; color: #3b82f6; padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="window.location.href='held_sales.php'">Saved</button>
                    <button class="btn" style="background: #f5f3ff; color: #8b5cf6; padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="promptReprint()">Reprint</button>
                </div>
            </div>

            <div style="margin-bottom: 1rem; position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.25rem;">
                    <label style="font-weight: 600; font-size: 0.8rem; color: #64748b; display:block; text-transform: uppercase;">Customer</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <div id="customer-balance-display" style="display:none; font-size: 0.75rem; font-weight: bold; color: #ef4444; background: #fee2e2; padding: 2px 6px; border-radius: 4px;">Outstanding: $<span id="customer-balance-val">0</span></div>
                        <div id="customer-points-display" style="display:none; font-size: 0.75rem; font-weight: bold; color: #10b981; background: #dcfce7; padding: 2px 6px; border-radius: 4px;">⭐ <span id="customer-points-val">0</span> Pts</div>
PHP;

$f = str_replace('%%REPLACE%%', $good_code, $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Restored!";
?>
