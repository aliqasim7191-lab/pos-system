<?php
require_once 'includes/db.php';
$t_id = isset($_GET['t']) ? (int)$_GET['t'] : 0;
if ($t_id === 0) { die("Store not found (Missing Tenant ID)"); }

// Fetch Tenant Settings
$tSettings = [];
$setQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE tenant_id = $t_id");
if ($setQ) { while($r = $setQ->fetch_assoc()) $tSettings[$r['setting_key']] = $r['setting_value']; }
$storeName = $tSettings['store_name'] ?? 'Online Store';
$storeLogo = !empty($tSettings['store_logo']) ? $tSettings['store_logo'] : 'assets/images/logo.jpg';
$currency = $tSettings['currency_symbol'] ?? '$';

// Fetch Products
$products = [];
$pq = $conn->query("SELECT p.*, p.category as cat_name FROM products p WHERE p.tenant_id = $t_id AND p.stock > 0 ORDER BY p.id DESC");
if ($pq) { while($p = $pq->fetch_assoc()) $products[] = $p; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($storeName); ?> - Online Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; background: #f8fafc; color: #1e293b; }
        .header { background: #ffffff; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top:0; z-index:100; }
        .logo-area { display: flex; align-items: center; gap: 10px; }
        .logo-area img { height: 40px; border-radius: 6px; }
        .logo-area h1 { margin: 0; font-size: 1.2rem; color: #0f172a; }
        
        .cart-btn { background: #3b82f6; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-weight: bold; position: relative; }
        .cart-count { position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; }
        
        .banner { background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 3rem 5%; text-align: center; }
        .banner h2 { margin: 0 0 10px 0; font-size: 2rem; }
        
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem; padding: 3rem 5%; }
        .product-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-5px); }
        .product-image { width: 100%; height: 200px; object-fit: cover; background: #f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:3rem; }
        .product-info { padding: 1rem; }
        .product-category { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .product-name { font-weight: 600; font-size: 1.1rem; margin: 0.5rem 0; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-price { color: #10b981; font-weight: bold; font-size: 1.2rem; margin-bottom: 1rem; }
        .add-btn { width: 100%; padding: 0.6rem; background: #f1f5f9; color: #3b82f6; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .add-btn:hover { background: #3b82f6; color: white; border-color: #3b82f6; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal h2 { margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding: 10px 0; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.9rem; margin-bottom: 5px; color: #475569; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; }
        .btn-checkout { background: #10b981; color: white; border: none; width: 100%; padding: 1rem; border-radius: 8px; font-weight: bold; font-size: 1.1rem; cursor: pointer; margin-top:1rem;}
        .close-modal { float:right; cursor:pointer; font-size:1.5rem; color:#94a3b8; }
    </style>
</head>
<body>

<div class="header">
    <div class="logo-area">
        <img src="<?php echo htmlspecialchars($storeLogo); ?>" alt="Logo">
        <h1><?php echo htmlspecialchars($storeName); ?></h1>
    </div>
    <button class="cart-btn" onclick="openCart()">
        <i class="fa fa-shopping-cart"></i> Cart <span class="cart-count" id="cartCount">0</span>
    </button>
</div>

<div class="banner">
    <h2>Welcome to <?php echo htmlspecialchars($storeName); ?></h2>
    <p>Order online and get your items delivered fast.</p>
</div>

<div class="products-grid">
    <?php foreach($products as $p): ?>
        <div class="product-card">
            <?php if(!empty($p['image'])): ?>
                <img src="<?php echo htmlspecialchars($p['image']); ?>" class="product-image">
            <?php else: ?>
                <div class="product-image"><i class="fa fa-box"></i></div>
            <?php endif; ?>
            <div class="product-info">
                <div class="product-category"><?php echo htmlspecialchars($p['cat_name'] ?? 'Uncategorized'); ?></div>
                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                <div class="product-price"><?php echo htmlspecialchars($currency); ?><?php echo number_format($p['price'], 2); ?></div>
                <button class="add-btn" onclick="addToCart(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>', <?php echo $p['price']; ?>)">
                    <i class="fa fa-plus"></i> Add to Cart
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Cart Modal -->
<div class="modal" id="cartModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeCart()">&times;</span>
        <h2>Your Shopping Cart</h2>
        <div id="cartItems" style="margin-bottom: 20px; min-height: 50px;"></div>
        <div style="text-align: right; font-weight: bold; font-size: 1.2rem; margin-bottom: 20px;">
            Total: <?php echo htmlspecialchars($currency); ?><span id="cartTotal">0.00</span>
        </div>
        
        <h3>Delivery Details</h3>
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" id="c_name" placeholder="Enter your name">
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" id="c_phone" placeholder="Enter your phone">
        </div>
        <div class="form-group">
            <label>Delivery Address</label>
            <textarea id="c_address" rows="3" placeholder="Full address"></textarea>
        </div>
        
        <button class="btn-checkout" onclick="placeOrder()">Place Order (Cash on Delivery)</button>
    </div>
</div>

<script>
let cart = [];

function addToCart(id, name, price) {
    let existing = cart.find(x => x.id === id);
    if(existing) {
        existing.qty += 1;
    } else {
        cart.push({id: id, name: name, price: price, qty: 1});
    }
    updateCartUI();
}

function updateCartUI() {
    document.getElementById('cartCount').innerText = cart.reduce((sum, item) => sum + item.qty, 0);
    
    let html = '';
    let total = 0;
    cart.forEach((item, index) => {
        let sub = item.price * item.qty;
        total += sub;
        html += `<div class="cart-item">
            <div><b>${item.name}</b><br><small><?php echo htmlspecialchars($currency); ?>${item.price.toFixed(2)} x ${item.qty}</small></div>
            <div>
                <?php echo htmlspecialchars($currency); ?>${sub.toFixed(2)}
                <i class="fa fa-trash" style="color:red; cursor:pointer; margin-left:10px;" onclick="removeFromCart(${index})"></i>
            </div>
        </div>`;
    });
    
    if(cart.length === 0) html = '<p style="color:#64748b; text-align:center;">Cart is empty</p>';
    
    document.getElementById('cartItems').innerHTML = html;
    document.getElementById('cartTotal').innerText = total.toFixed(2);
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function openCart() {
    document.getElementById('cartModal').style.display = 'flex';
}
function closeCart() {
    document.getElementById('cartModal').style.display = 'none';
}

function placeOrder() {
    if(cart.length === 0) { alert('Cart is empty!'); return; }
    let name = document.getElementById('c_name').value;
    let phone = document.getElementById('c_phone').value;
    let address = document.getElementById('c_address').value;
    
    if(!name || !phone || !address) { alert('Please fill all delivery details!'); return; }
    
    let btn = document.querySelector('.btn-checkout');
    btn.innerText = 'Processing...';
    btn.disabled = true;
    
    fetch('api_place_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            tenant_id: <?php echo $t_id; ?>,
            customer_name: name,
            customer_phone: phone,
            customer_address: address,
            cart: cart
        })
    }).then(res => res.json()).then(data => {
        if(data.success) {
            alert('Order placed successfully! We will contact you soon.');
            cart = [];
            updateCartUI();
            closeCart();
            document.getElementById('c_name').value = '';
            document.getElementById('c_phone').value = '';
            document.getElementById('c_address').value = '';
        } else {
            alert('Error placing order: ' + data.error);
        }
        btn.innerText = 'Place Order (Cash on Delivery)';
        btn.disabled = false;
    }).catch(err => {
        alert('Connection error');
        btn.innerText = 'Place Order (Cash on Delivery)';
        btn.disabled = false;
    });
}
</script>
</body>
</html>
