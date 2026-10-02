<?php
include 'includes/db.php';
include 'includes/header.php';

// --- STRICT SHIFT BLOCKER UI ---
$shiftCheck = $conn->query("SELECT id FROM shifts WHERE is_cleared = 0 AND status = 'open' AND branch_id = $current_branch_id AND tenant_id = {$_SESSION['tenant_id']} LIMIT 1");
$isShiftClosed = ($shiftCheck->num_rows == 0);
// -------------------------------

$taxQ = $conn->query("SELECT setting_value FROM settings WHERE setting_key='tax_rate'");
$global_tax_rate = ($taxQ && $taxQ->num_rows > 0) ? floatval($taxQ->fetch_assoc()['setting_value']) : 0;

$settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('currency_symbol', 'secondary_currency_symbol', 'exchange_rate')");
$sysConfig = [];
if ($settingsQ) {
    while($r = $settingsQ->fetch_assoc()) {
        $sysConfig[$r['setting_key']] = $r['setting_value'];
    }
}
$pri_curr = $sysConfig['currency_symbol'] ?? '$';
$sec_curr = $sysConfig['secondary_currency_symbol'] ?? '';
$exch_rate = floatval($sysConfig['exchange_rate'] ?? 1);

// Fetch all active products for the current branch
$result = $conn->query("SELECT p.*, t.rate as custom_tax_rate FROM products p LEFT JOIN tax_classes t ON p.tax_class_id = t.id WHERE p.status='active' AND p.tenant_id = {$_SESSION['tenant_id']}");
$products = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $row['variations'] = [];
        $pid = $row['id'];
        $vQ = $conn->query("SELECT * FROM product_variations WHERE product_id = $pid");
        if ($vQ) {
            while($v = $vQ->fetch_assoc()) {
                $row['variations'][] = $v;
            }
        }
        $products[$row['id']] = $row;
    }
}
?>

<div class="pos-layout">

<?php if ($isShiftClosed): ?>
<style>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes pulseLock {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(245, 158, 11, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}
.shift-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.15); /* Very light dark overlay */
    backdrop-filter: none; /* No blur, perfectly clear background */
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.shift-modal {
    background: rgba(255, 255, 255, 0.98);
    padding: 3rem;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
    text-align: center;
    width: 440px;
    animation: slideDown 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    position: relative;
    overflow: hidden;
}
.shift-modal::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 6px;
    background: linear-gradient(90deg, #f59e0b, #ef4444);
}
.lock-icon-container {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #fef3c7, #fef08a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem auto;
    border: 4px solid #fff;
    animation: pulseLock 2s infinite;
}
.lock-icon-container i {
    font-size: 2.8rem;
    color: #d97706;
}
.modal-title {
    font-size: 2rem;
    color: #0f172a;
    font-weight: 800;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
}
.modal-subtitle {
    color: #64748b;
    font-size: 1.05rem;
    line-height: 1.5;
    margin-bottom: 2rem;
    padding: 0 1rem;
}
.shift-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 1.2rem;
    font-size: 1.2rem;
    font-weight: bold;
    color: white;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
}
.shift-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 25px -5px rgba(16, 185, 129, 0.6);
}
</style>
<div class="shift-overlay">
    <div class="shift-modal">
        <div class="lock-icon-container" style="font-size: 3rem; line-height: 1;">
            🔒
        </div>
        <h2 class="modal-title">Register is Locked</h2>
        <p class="modal-subtitle">For security reasons, your shift must be open to process transactions.</p>
        
        <a href="shift.php" class="shift-btn">
            <span class="btn-text">Open Register Now</span>
            <i class="fa fa-arrow-right"></i>
        </a>
    </div>
</div><?php endif; ?>


    <div class="products-section" style="flex: 1; min-width: 0;">
        <!-- Search and Categories -->
        <div style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem; align-items: center;">
            <input type="text" id="barcodeScanner" placeholder="Scan Barcode (F4)" style="flex: 1; height: 52px; padding: 0 1rem; border: 2px solid var(--primary-color); border-radius: 10px; font-size: 1rem; box-shadow: 0 4px 6px -1px rgba(37,99,235,0.1); box-sizing: border-box;" autofocus autocomplete="off">
            <input type="text" id="productSearch" placeholder="Search product... (F2)" style="flex: 1; height: 52px; padding: 0 1rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 1rem; box-sizing: border-box;">
        </div>
        
        <div class="category-filters" id="categoryFilters" style="display: flex; gap: 0.4rem; overflow-x: auto; padding-bottom: 0.4rem; margin-bottom: 0.75rem;">
            <button class="btn btn-primary" style="padding: 0.35rem 1.1rem; border-radius: 50px; font-size: 0.85rem;" onclick="filterCategory('all')">All</button>
            <?php
            $catRes = $conn->query("SELECT DISTINCT category FROM products WHERE status='active' AND tenant_id = {$_SESSION['tenant_id']} AND category != ''");
            if ($catRes) {
                while($cat = $catRes->fetch_assoc()) {
                    echo "<button class='btn' style='background: white; border: 1px solid #cbd5e1; padding: 0.35rem 1.1rem; border-radius: 50px; color: #475569; font-weight: 600; font-size: 0.85rem;' onclick=\"filterCategory('".htmlspecialchars($cat['category'])."')\">" . htmlspecialchars(strtoupper($cat['category'])) . "</button>";
                }
            }
            ?>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.85rem; overflow-y: auto; max-height: calc(100vh - 220px); padding-right: 0.4rem;">
            <?php
            if(!empty($products)){
                foreach($products as $p){
                    $img = empty($p['image']) ? 'assets/images/placeholder.jpg' : htmlspecialchars($p['image']);
                    $imgPath = (strpos($img, 'uploads/') === 0 || strpos($img, 'assets/') === 0) ? $img : 'uploads/' . $img;
                    
                    $sku = htmlspecialchars($p['barcode'] ?? '');
                    $cat = htmlspecialchars($p['category'] ?? '');
                    $name = htmlspecialchars($p['name']);
                    $stock = floatval($p['stock'] ?? 0);
                    $price = floatval($p['price'] ?? 0);
                    $id = intval($p['id']);
                    
                    $minStock = floatval($p['min_stock'] ?? 10);
                    if ($minStock <= 0) $minStock = 10;
                    $isLowStock = ($stock <= $minStock);
                    $stockColor = $isLowStock ? '#ef4444' : '#10b981';
                    $stockIcon = $isLowStock ? '🔴' : '🟢';
            ?>
                    <div class="product-card" data-category="<?= $cat ?>" data-sku="<?= $sku ?>" onclick="openProductSelectionModal(<?= $id ?>)">
                        <div class="product-image-wrapper">
                            <img class="product-image" src="<?= $imgPath ?>" alt="Product">
                        </div>
                        <div class="product-info-wrapper">
                            <h3 style="font-size: 0.85rem; margin-bottom: 0.15rem; line-height: 1.2;"><?= $name ?></h3>
                            <div style="font-size: 0.75rem; margin-top: 0.15rem; color: <?= $stockColor ?>; font-weight: 700;">
                                <?= $stockIcon ?> <?= $stock ?> in stock <?= $isLowStock ? '<span style="font-size:0.65rem; background:#fee2e2; color:#ef4444; padding:1px 4px; border-radius:3px; font-weight:800; margin-left:3px;">LOW</span>' : '' ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.35rem; padding-top: 0.35rem; border-top: 1px solid #f1f5f9;">
                                <div class="product-price" style="font-weight: 800; font-size: 0.95rem; color: #0f172a;"><?= htmlspecialchars($pri_curr) ?><?= number_format($price, 2) ?></div>
                                <button class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.25rem 0.5rem; font-size: 0.78rem; border-radius: 4px; font-weight: 600;" onclick="event.stopPropagation(); openProductSelectionModal(<?= $id ?>)">+ Add</button>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo "<p style='grid-column: 1/-1; text-align:center; padding:2rem; color:#64748b;'>No products found.</p>";
            }
            ?>
        </div>
    </div>
    
    <!-- Cart Section -->
    <div class="cart-panel" style="min-width: 0; display: flex; flex-direction: column; padding: 1.1rem;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h2 style="margin: 0; font-size: 1.25rem; color: #0f172a; font-weight: 800;">Current Order</h2>
                <div style="display: flex; gap: 0.4rem;">
                    <button class="btn" style="background: #fee2e2; color: #ef4444; padding: 0.35rem 0.7rem; font-size: 0.8rem;" onclick="clearCart()">Clear</button>
                    <button class="btn" style="background: #f1f5f9; color: #64748b; padding: 0.35rem 0.7rem; font-size: 0.8rem;" onclick="holdSalePrompt()">Hold</button>
                    <button class="btn" style="background: #eff6ff; color: #3b82f6; padding: 0.35rem 0.7rem; font-size: 0.8rem;" onclick="openHeldSales()">Saved</button>
                    <button class="btn" style="background: #f5f3ff; color: #8b5cf6; padding: 0.35rem 0.7rem; font-size: 0.8rem;" onclick="promptReprint()">Reprint</button>
                </div>
            </div>

            <div style="margin-bottom: 0.75rem; position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.25rem;">
                    <label style="font-weight: 600; font-size: 0.8rem; color: #64748b; display:block; text-transform: uppercase;">Customer</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <div id="customer-balance-display" style="display:none; font-size: 0.75rem; font-weight: bold; color: #ef4444; background: #fee2e2; padding: 2px 6px; border-radius: 4px;">Outstanding: $<span id="customer-balance-val">0</span></div>
                        <div id="customer-points-display" style="display:none; font-size: 0.75rem; font-weight: bold; color: #10b981; background: #dcfce7; padding: 2px 6px; border-radius: 4px;">⭐ <span id="customer-points-val">0</span> Pts</div>
                    </div>
                </div>
            <select id="customerSelect" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 500; color: #0f172a; outline: none; background: #f8fafc;" onchange="handleCustomerChange()">
                <option value="" data-type="walk_in" data-points="0">Walk-in Customer <i class="fa fa-user"></i></option>
                <?php
                $custRes = $conn->query("SELECT id, name, customer_type, points, outstanding_balance FROM customers WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY name ASC");
                if ($custRes && $custRes->num_rows > 0) {
                    while($c = $custRes->fetch_assoc()) {
                        $pts = intval($c['points']);
                        $bal = floatval($c['outstanding_balance']);
                        echo "<option value='{$c['id']}' data-type='{$c['customer_type']}' data-points='{$pts}' data-balance='{$bal}'>" . htmlspecialchars($c['name']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="cart-items" id="cart-items" style="flex: 1; min-height: 180px; overflow-y: auto; margin-bottom: 0.5rem; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 0.4rem 0.2rem;">
            <div style="text-align:center; padding: 2rem 1rem; color: #94a3b8;">
                <div style="font-size: 2.2rem; margin-bottom: 0.3rem;">🛒</div>
                <h3 style="color: #0f172a; font-size: 1rem; margin-bottom: 0.2rem;">Your order is empty</h3>
                <p style="font-size:0.8rem;">Scan a barcode or select a product to begin.</p>
            </div>
        </div>

        <div class="cart-summary" style="padding-bottom: 0.25rem; margin-top: auto;">
            <div class="summary-row" style="display:flex; justify-content:space-between; margin-bottom: 0.3rem; font-size: 0.9rem; color: #475569;">
                <span>Subtotal</span>
                <span id="cart-subtotal" style="color: #0f172a; font-weight: 600;">$0.00</span>
            </div>
            <div class="summary-row" style="display:flex; justify-content:space-between; margin-bottom: 0.3rem; font-size: 0.9rem; color: #475569;">
                <span>Discount</span>
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <span>-$</span><input type="number" step="0.01" id="cart-discount" value="0" style="width: 60px; text-align:right; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 4px; font-size: 0.85rem;" oninput="renderCart(false)">
                </div>
            </div>
            <div class="summary-row" style="display:flex; justify-content:space-between; margin-bottom: 0.3rem; font-size: 0.9rem; color: #475569;">
                <span>Tax (0%)</span>
                <span id="cart-tax" style="color: #0f172a; font-weight: 600;">$0.00</span>
            </div>
            <div class="summary-row" style="display:flex; justify-content:space-between; align-items:flex-end; padding-top: 0.4rem; margin-top: 0.3rem; font-size: 1.35rem; font-weight: 800; color: #0f172a; border-top: 2px dashed #e2e8f0;">
                <span>TOTAL</span>
                <div style="text-align: right;">
                    <div id="cart-total">$0.00</div>
                </div>
            </div>
        </div>

        <div style="display: none; margin-bottom: 1rem;">
            <label style="display:block; font-weight: 600; font-size: 0.8rem; color: #64748b; margin-bottom: 0.25rem; text-transform: uppercase;">Order Notes</label>
            <textarea id="order-notes" placeholder="Add notes for this order..." style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: #0f172a; outline: none; resize: none;" rows="2"></textarea>
        </div>

        <div style="display: none; gap: 0.5rem; margin-bottom: 0.75rem;">
            <div style="flex: 1;">
                <select id="orderModeSelect" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: #475569; outline: none;">
                    <option value="sale">Sale</option>
                </select>
            </div>
            <button class="btn" style="background: #fef3c7; color: #d97706; width: auto; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;" onclick="document.getElementById('holdSaleModal').style.display='flex'">Hold</button>
            <button class="btn" style="background: #fee2e2; color: #ef4444; width: auto; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;" onclick="clearCart()" title="Clear cart">Clear</button>
        </div>
        
        <button class="btn btn-primary" id="checkoutMainBtn" style="width: 100%; padding: 1rem; margin-top: 0.4rem; font-size: 1.2rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(37,99,235,0.2);" onclick="checkout()">
            <span>CHECKOUT</span>
            <span id="checkout-total-btn">$0.00</span>
        </button>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding: 1rem;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 450px; max-width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 style="margin-bottom: 1rem; font-size: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; position: sticky; top: -2rem; background: var(--surface-color); z-index: 10;">Complete Payment</h3>
        
        <!-- Total Amount Payable Box -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem 1rem; text-align: center; margin-bottom: 1rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 0.25rem;">TOTAL AMOUNT PAYABLE</div>
            <div id="modal-total" style="font-size: 2.2rem; font-weight: 800; color: #2563eb; line-height: 1;">$0.00</div>
        </div>
        
        <!-- Cart Items Preview -->
        <div id="checkout-cart-items" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.5rem 0.85rem; max-height: 160px; overflow-y: auto; margin-bottom: 1rem;">
            <!-- dynamically populated -->
        </div>
        
        <div id="modal-points-section" style="display:none; background:#f0fdf4; border:1px solid #bbf7d0; padding:0.75rem; border-radius:8px; margin-bottom:1rem; align-items:center; justify-content:space-between;">
            <div>
                <strong style="color:#166534; font-size:0.9rem;">⭐ Available Points: <span id="modal-available-points">0</span></strong>
                <div style="font-size:0.75rem; color:#15803d;">10 Points = $1.00 Discount</div>
            </div>
            <div>
                <input type="number" id="modal-points-to-use" value="0" min="0" step="10" style="width: 80px; padding: 0.25rem; border: 1px solid #86efac; border-radius: 4px;" oninput="recalculateModal()">
            </div>
        </div>

        <div style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <label style="display:flex; align-items:center; gap:0.4rem; font-size: 0.9rem; font-weight: 600; color: #0f172a; margin-bottom: 0.5rem;">
                <span style="color: #2563eb;">👤</span> Customer Name & Contact
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <input type="text" id="modal-customer-name" placeholder="Customer Name (e.g. Ali Qasim)" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; outline: none;">
                <input type="text" id="modal-customer-address" placeholder="Phone Number / Address" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; outline: none;">
            </div>
        </div>

        <div id="payment-method-section" style="margin-bottom: 1.25rem;">
            <label style="display:block; font-weight: 600; margin-bottom: 0.5rem; color: #0f172a; font-size: 0.95rem;">Payment Method</label>
            <select id="paymentMethodSelect" onchange="toggleCashInput()" style="width: 100%; padding: 0.85rem 1rem; border: 2px solid #3b82f6; border-radius: 10px; font-size: 1.05rem; font-weight: 600; color: #0f172a; background: #ffffff; outline: none; cursor: pointer; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="mobile">Mobile Wallet</option>
                <option value="bank">Bank Transfer</option>
                <option value="credit" id="khata-option" style="display:none;">Khata / Account Balance</option>
            </select>
        </div>
        
        <div id="takenByDiv" style="display:none; margin-bottom: 1rem; border: 1px dashed var(--primary-color); padding: 0.75rem; border-radius: 8px; background: #f8fafc;">
            <label style="display:block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--primary-color);">Taken By (Name / Signature)</label>
            <input type="text" id="modal-taken-by" placeholder="Who is taking these products?" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px;">
            <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">For Khata Accounts only, to record the receiver's name.</div>
        </div>
        
        <div id="khata-notice" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; padding:1.25rem 1rem; border-radius:10px; text-align:center; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(37,99,235,0.05);">
            <strong style="color:#1d4ed8; font-size: 1.15rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <span>📖</span> Khata / Account Balance Transaction
            </strong>
            <p style="color:#2563eb; font-size: 0.95rem; margin-top:0.4rem; font-weight: 600; margin-bottom: 0;">
                Yeh bill customer ke Khata / Udhar account mein add ho jayega (Khata Sale).
            </p>
        </div>

        <input type="hidden" id="applied-promo-discount" value="0">
<div id="cashInputDiv" style="margin-bottom: 1.5rem;">
            <label style="display:block; font-weight: 600; margin-bottom: 0.5rem; color: #0f172a;">Amount Received ($)</label>
            <input type="number" step="0.01" id="modal-received" style="width: 100%; padding: 1rem; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 1.25rem; outline: none; font-weight: bold; color: #0f172a;" oninput="calculateChange()">
            


            <div style="display:flex; justify-content:space-between; align-items: center; margin-top: 1rem; font-size: 1.25rem; font-weight: 800; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
                <span style="color: #64748b;">Change:</span>
                <span id="modal-change" style="color: #10b981;">$0.00</span>
            </div>
        </div>

        <div style="display:flex; gap: 1rem;">
            <button class="btn btn-primary" id="confirmBtn" style="flex: 2; padding: 1rem; font-size: 1.1rem;" onclick="confirmCheckout()">Confirm Payment</button>
            <button class="btn" style="flex: 1; background:#e2e8f0; color:var(--text-main);" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
    const PRODUCTS_DATA = <?php echo json_encode($products); ?>;
    const PRI_CURR = '<?php echo addslashes($pri_curr); ?>';
    const SEC_CURR = '<?php echo addslashes($sec_curr); ?>';
    const EXCH_RATE = <?php echo floatval($exch_rate); ?>;
    const globalTaxRate = <?php echo floatval($global_tax_rate); ?>;
</script>
<script src="assets/js/app.js?v=<?= time() ?>"></script>

<!-- Hold Sale Modal -->
<div id="holdSaleModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Hold Current Sale</h3>
        
        <div style="margin-bottom: 1rem;">
            <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Reference / Table No. *</label>
            <input type="text" id="hold-ref" required placeholder="e.g., Table 4 or Order 12" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Customer Name (Walk-in)</label>
            <input type="text" id="hold-walkin" placeholder="Optional" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Taken By (Staff/Rider)</label>
            <input type="text" id="hold-takenby" placeholder="e.g., Ali (Waiter)" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
        </div>

        <div style="display:flex; gap: 1rem;">
            <button class="btn btn-primary" style="flex: 1;" onclick="submitHoldSale()">Save to Hold</button>
            <button class="btn" style="flex: 1; background:#e2e8f0; color:var(--text-main);" onclick="document.getElementById('holdSaleModal').style.display='none'">Cancel</button>
        </div>
    </div>
</div>

<!-- Held Sales Modal -->
<div id="heldSalesModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 600px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); max-height: 80vh; display: flex; flex-direction: column;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            <h3 style="font-size: 1.5rem;">Held Sales</h3>
            <button onclick="document.getElementById('heldSalesModal').style.display='none'" style="background:none; border:none; font-size: 1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div id="heldSalesContainer" style="overflow-y: auto; flex: 1;">
            <p style="text-align:center; padding:2rem;">Loading...</p>
        </div>
    </div>
</div>

<!-- Product Selection Modal (Appears when clicking a product in the grid) -->
<div id="productSelectionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 id="ps-name-display" style="margin-bottom: 0.5rem; font-size: 1.4rem; color: var(--primary-color);">Product Name</h3>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            <div style="color: var(--text-muted); font-size: 0.9rem;">Price: <strong id="ps-price-display" style="color: var(--text-main);">$0.00</strong></div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">Stock: <strong id="ps-stock-display" style="color: var(--text-main);">0</strong></div>
        </div>
        
        <input type="hidden" id="ps-id">
        <input type="hidden" id="ps-name">
        <input type="hidden" id="ps-retail">
        <input type="hidden" id="ps-wholesale">
        <input type="hidden" id="ps-unit">

        <div id="ps-variations-container" style="display:none; margin-bottom:1rem;">
            <label style="display:block; font-weight: 600; margin-bottom: 0.5rem;">Select Variation</label>
            <select id="ps-variation" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; font-size:1rem;" onchange="handleVariationChange()">
            </select>
        </div>

        <label style="display:block; font-weight: 600; margin-bottom: 0.5rem;">Select Quantity (<span id="ps-unit-display">pcs</span>)</label>
        
        <div style="display:flex; align-items:center; gap: 0.5rem; margin-bottom: 1rem;">
            <button class="btn" style="background:#e2e8f0; font-size: 1.5rem; padding: 0.5rem 1.2rem; border-radius: 8px;" onclick="adjustPsQty(-1)">-</button>
            <input type="number" id="ps-qty" value="1" step="0.01" style="flex:1; text-align:center; padding: 1rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1.5rem; font-weight: bold;">
            <button class="btn" style="background:#e2e8f0; font-size: 1.5rem; padding: 0.5rem 1.2rem; border-radius: 8px;" onclick="adjustPsQty(1)">+</button>
        </div>

        <div style="display:flex; gap: 0.5rem; margin-bottom: 1.5rem;">
            <button class="btn" style="flex:1; background:#f1f5f9; color: var(--text-main);" onclick="adjustPsQty(5)">+5</button>
            <button class="btn" style="flex:1; background:#f1f5f9; color: var(--text-main);" onclick="adjustPsQty(10)">+10</button>
            <button class="btn" style="flex:1; background:#f1f5f9; color: var(--text-main);" onclick="adjustPsQty(50)">+50</button>
        </div>

        <div style="display:flex; gap: 1rem;">
            <button class="btn btn-primary" style="flex: 2; padding: 1rem; font-size: 1.1rem;" onclick="confirmProductSelection()">Add to Cart</button>
            <button class="btn" style="flex: 1; background:#e2e8f0; color:var(--text-main);" onclick="closeProductSelectionModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- Quantity Edit Modal for Cart -->
<div id="quantityEditModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 id="qe-name-display" style="margin-bottom: 0.5rem; font-size: 1.4rem; color: var(--primary-color);">Product Name</h3>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; align-items: center;">
            <div style="color: var(--text-muted); font-size: 0.9rem;">Price: </div>
            <input type="number" id="qe-price-input" step="0.01" style="width: 100px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 1rem; font-weight: bold; text-align: right;">
        </div>
        
        <input type="hidden" id="qe-id">

        <label style="display:block; font-weight: 600; margin-bottom: 0.5rem;">Update Quantity (<span id="qe-unit-display">pcs</span>)</label>
        
        <div style="display:flex; align-items:center; gap: 0.5rem; margin-bottom: 1rem;">
            <button class="btn" style="background:#e2e8f0; font-size: 1.5rem; padding: 0.5rem 1.2rem; border-radius: 8px;" onclick="adjustQeQty(-1)">-</button>
            <input type="number" id="qe-qty" value="1" step="0.01" style="flex:1; text-align:center; padding: 1rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1.5rem; font-weight: bold;">
            <button class="btn" style="background:#e2e8f0; font-size: 1.5rem; padding: 0.5rem 1.2rem; border-radius: 8px;" onclick="adjustQeQty(1)">+</button>
        </div>

        <div style="display:flex; gap: 0.5rem; margin-bottom: 1.5rem;">
            <button class="btn" style="flex:1; background:#f1f5f9; color: var(--text-main);" onclick="adjustQeQty(5)">+5</button>
            <button class="btn" style="flex:1; background:#f1f5f9; color: var(--text-main);" onclick="adjustQeQty(10)">+10</button>
            <button class="btn" style="flex:1; background:#f1f5f9; color: var(--text-main);" onclick="adjustQeQty(50)">+50</button>
        </div>

        <div style="display:flex; gap: 1rem;">
            <button class="btn btn-primary" style="flex: 2; padding: 1rem; font-size: 1.1rem;" onclick="confirmQeSelection()">Update Cart</button>
            <button class="btn" style="flex: 1; background:#e2e8f0; color:var(--text-main);" onclick="closeQeModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
function promptReprint() {
    let id = prompt("Enter Sale ID to reprint:");
    if (id) {
        window.open('receipt.php?id=' + id, '_blank');
    }
}

window.filterProductsGrid = function(query) {
    query = query.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-card');
    
    cards.forEach(card => {
        const name = (card.querySelector('h3') ? card.querySelector('h3').innerText : '').toLowerCase();
        const sku = (card.getAttribute('data-sku') || '').toLowerCase();
        
        if (name.includes(query) || sku.includes(query)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
};

function filterCategory(cat, btnElement) {
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        if (cat === 'All' || card.getAttribute('data-category') === cat) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update active state
    if (btnElement) {
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.style.background = '#f8fafc';
            btn.style.color = '#475569';
            btn.style.border = '1px solid #cbd5e1';
        });
        btnElement.style.background = '#eff6ff';
        btnElement.style.color = 'var(--primary-color)';
        btnElement.style.border = '1px solid var(--primary-color)';
    }
}

// Barcode Scanner Logic
document.addEventListener('DOMContentLoaded', () => {
    const barcodeInput = document.getElementById('barcodeScanner');
    let isProcessingBarcode = false;
    let autoScanTimer = null;

    function processBarcodeScan(barcode) {
        barcode = (barcode || '').trim();
        if (!barcode || isProcessingBarcode) return;
        isProcessingBarcode = true;
        
        fetch(`api_add_by_barcode.php?barcode=${encodeURIComponent(barcode)}`)
        .then(res => res.json())
        .then(data => {
            isProcessingBarcode = false;
            if (data.success && data.product) {
                addToCart(
                    data.product.id, 
                    data.product.name, 
                    data.product.retail_price, 
                    data.product.wholesale_price, 
                    data.product.unit
                );
                
                if (data.is_new_online) {
                    showToast("Online Product Found!", "Product added from internet.", "info");
                } else {
                    showToast("Added", data.product.name + " added to cart", "success");
                }
                
                if (barcodeInput) {
                    barcodeInput.style.backgroundColor = '#dcfce7';
                    setTimeout(() => barcodeInput.style.backgroundColor = '', 200);
                    barcodeInput.value = '';
                    const searchInput = document.getElementById('productSearch');
                    if (searchInput) searchInput.value = '';
                    if (typeof filterProductsGrid === 'function') filterProductsGrid('');
                    barcodeInput.focus();
                }
            } else {
                showToast("Error", data.message || ("Product not found for barcode: " + barcode), "error");
                if (barcodeInput) {
                    barcodeInput.style.backgroundColor = '#fee2e2';
                    setTimeout(() => barcodeInput.style.backgroundColor = '', 200);
                    barcodeInput.value = '';
                    const searchInput = document.getElementById('productSearch');
                    if (searchInput) searchInput.value = '';
                    if (typeof filterProductsGrid === 'function') filterProductsGrid('');
                    barcodeInput.focus();
                }
            }
        })
        .catch(err => {
            console.error('Error scanning barcode:', err);
            isProcessingBarcode = false;
            if (barcodeInput) {
                barcodeInput.value = '';
                barcodeInput.focus();
            }
        });
    }

    window.clearAutoScanTimer = function() {
        if (autoScanTimer) {
            clearTimeout(autoScanTimer);
            autoScanTimer = null;
        }
    };

    if (barcodeInput) {
        // Auto-Scan without pressing Enter when typing/scanning into field
        barcodeInput.addEventListener('input', function(e) {
            const val = this.value.trim();
            if (typeof window.filterProductsGrid === 'function') window.filterProductsGrid(val);
            
            clearTimeout(autoScanTimer);
            if (val.length >= 3) {
                // Hardware scanners scan full barcode string in under 50ms.
                // Wait 120ms after typing stops to automatically add to cart!
                autoScanTimer = setTimeout(() => {
                    if (barcodeInput && barcodeInput.value.trim().length >= 3) {
                        processBarcodeScan(barcodeInput.value.trim());
                    }
                }, 120);
            }
        });

        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(autoScanTimer);
                processBarcodeScan(this.value.trim());
            }
        });
    }

    // Global Barcode Scanner Intercept (for hardware scanners when focus is elsewhere)
    let barcodeBuffer = '';
    let barcodeTimer = null;

    document.addEventListener('keypress', function(e) {
        if (e.target.tagName === 'INPUT' && e.target.id !== 'barcodeScanner' && e.target.type === 'text') {
            return; 
        }
        
        if (e.key === 'Enter') {
            if (barcodeBuffer.trim().length > 0) {
                e.preventDefault();
                const code = barcodeBuffer.trim();
                barcodeBuffer = '';
                processBarcodeScan(code);
                return;
            }
        }

        if (e.key.length === 1) {
            barcodeBuffer += e.key;
            clearTimeout(barcodeTimer);
            barcodeTimer = setTimeout(() => {
                if (barcodeBuffer.trim().length >= 3) {
                    const code = barcodeBuffer.trim();
                    barcodeBuffer = '';
                    processBarcodeScan(code);
                } else {
                    barcodeBuffer = '';
                }
            }, 120); 
        }
    });

    // Global Pro Hotkeys
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F1') {
            e.preventDefault();
            openCheckoutModal();
        } else if (e.key === 'F2') {
            e.preventDefault();
            const searchInput = document.getElementById('productSearch');
            if (searchInput) searchInput.focus();
        } else if (e.key === 'F3') {
            e.preventDefault();
            document.getElementById('holdSaleModal').style.display = 'flex';
        } else if (e.key === 'F4') {
            e.preventDefault();
            if (barcodeInput) barcodeInput.focus();
        }
    });

    // Live Product Search Logic (by Name or Barcode)
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            if (typeof window.clearAutoScanTimer === 'function') window.clearAutoScanTimer();
            if (typeof window.filterProductsGrid === 'function') window.filterProductsGrid(this.value);
        });
    }

    // Monthly Cleanup Prompt Logic
    const currentMonth = new Date().getMonth().toString() + "-" + new Date().getFullYear().toString();
    const lastPrompted = localStorage.getItem('last_cleanup_prompt_month');
    
    if (lastPrompted !== currentMonth) {
        localStorage.setItem('last_cleanup_prompt_month', currentMonth);
        // Ask user if they want to clear previous month's history
        setTimeout(() => {
            if (confirm("New month started! Do you want to delete all historical data (Sales, Z-Reports, Expenses) from the previous months to save space?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'clear_history.php';
                document.body.appendChild(form);
                form.submit();
            }
        }, 1500);
    }
});

// OFFLINE-FIRST POS SYSTEM
let isOffline = !navigator.onLine;
let offlineSales = JSON.parse(localStorage.getItem("offline_sales")) || [];

function updateNetworkStatus() {
    isOffline = !navigator.onLine;
    let header = document.querySelector(".header-actions > div:first-child");
    let existingBadge = document.getElementById("offline-badge");
    
    if (isOffline) {
        if (!existingBadge && header) {
            header.innerHTML += '<span id="offline-badge" style="background: #ef4444; color: white; padding: 2px 10px; border-radius: 99px; font-size: 0.8rem; margin-left: 10px; font-weight: bold;">⚡ OFFLINE MODE</span>';
        }
    } else {
        if (existingBadge) existingBadge.remove();
        syncOfflineSales();
    }
}

window.addEventListener("online", updateNetworkStatus);
window.addEventListener("offline", updateNetworkStatus);
setTimeout(updateNetworkStatus, 1000);

function syncOfflineSales() {
    if (offlineSales.length > 0 && !isOffline) {
        let badge = document.getElementById("offline-badge");
        if(badge) badge.innerText = "🔄 SYNCING (" + offlineSales.length + ")...";
        
        let sale = offlineSales[0];
        fetch('checkout.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(sale)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                offlineSales.shift();
                localStorage.setItem("offline_sales", JSON.stringify(offlineSales));
                syncOfflineSales(); // sync next
            }
        })
        .catch(err => console.error("Sync failed:", err));
    }
}

// Modify confirmCheckout to support offline fallback
window.originalConfirmCheckout = window.originalConfirmCheckout || confirmCheckout;
window.confirmCheckout = function() {
    if (isOffline) {
        let finalTotal = currentTotal;
        
        let saleData = {
            cart: cart,
            discount: parseFloat(document.getElementById("modal-discount") ? document.getElementById("modal-discount").value : 0) || 0,
            amount_received: parseFloat(document.getElementById("modal-received") ? document.getElementById("modal-received").value : finalTotal) || finalTotal,
            change_returned: (parseFloat(document.getElementById("modal-received") ? document.getElementById("modal-received").value : finalTotal) || finalTotal) - finalTotal,
            payment_method: document.getElementById("paymentMethodSelect") ? document.getElementById("paymentMethodSelect").value : 'cash',
            customer_id: document.getElementById("customerSelect") ? document.getElementById("customerSelect").value : '',
            customer_name: document.getElementById("modal-customer-name") ? document.getElementById("modal-customer-name").value : '',
            customer_address: document.getElementById("modal-customer-address") ? document.getElementById("modal-customer-address").value : '',
            taken_by: document.getElementById("modal-taken-by") ? document.getElementById("modal-taken-by").value : '',
            offline_id: Date.now()
        };
        
        offlineSales.push(saleData);
        localStorage.setItem("offline_sales", JSON.stringify(offlineSales));
        
        alert("⚡ Sale saved OFFLINE! It will sync automatically when internet connection is restored.");
        closeModal();
        clearCart();
    } else {
        window.originalConfirmCheckout();
    }
};
</script>

<?php if(isset($_GET['cleared']) && $_GET['cleared'] == 1): ?>
<script>alert("Previous month's history has been cleared successfully.");</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

