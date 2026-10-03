<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_role = $_SESSION['role'] ?? 'super_admin';
$isAdmin = true;
$current_branch_id = $_SESSION['branch_id'] ?? 1;
$current_tenant_id = intval($_SESSION['tenant_id'] ?? 1);
$_SESSION['tenant_id'] = $current_tenant_id;

// Fetch Branch Name
$branchNameQ = $conn->query("SELECT name FROM branches WHERE id = $current_branch_id");
$branchName = ($branchNameQ && $branchNameQ->num_rows > 0) ? $branchNameQ->fetch_assoc()['name'] : 'Main Branch';

$currentPage = basename($_SERVER['PHP_SELF']);
$isManagerOrAdmin = true;
  
  // Fetch Tenant Settings (Logo & Name)
  $tSettings = [];
  $tSetQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE tenant_id = " . ($_SESSION['tenant_id'] ?? 1));
  if($tSetQ) { while($row = $tSetQ->fetch_assoc()) { $tSettings[$row['setting_key']] = $row['setting_value']; } }
  $headerStoreName = $tSettings['store_name'] ?? 'SuperStore';
  $headerLogo = !empty($tSettings['store_logo']) ? $tSettings['store_logo'] : 'assets/images/logo.jpg';


if (!$isAdmin && in_array($currentPage, ['reports.php', 'branches.php', 'settings.php', 'settings_integrations.php', 'staff.php', 'tax_classes.php'])) {
    die("<div style='padding:2rem; text-align:center;'><h2>Access Denied</h2><p>Only Super Administrators can access this page.</p><a href='index.php'>Return to Dashboard</a></div>");
}
if (!$isManagerOrAdmin && in_array($currentPage, ['products.php', 'sales.php', 'purchase_orders.php', 'purchases.php', 'expenses.php'])) {
    die("<div style='padding:2rem; text-align:center;'><h2>Access Denied</h2><p>Only Managers and Admins can access this page.</p><a href='index.php'>Return to Dashboard</a></div>");
}
?>
<!DOCTYPE html>
<html>
<head>
    <script>if(!sessionStorage.getItem('strict_session')) { window.location.href='logout.php'; }</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="manifest" href="manifest.json?v=2">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/icon-512.png">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icon-192.png">
    <link rel="shortcut icon" href="assets/icon-512.png">
    <link rel="apple-touch-icon" href="assets/icon-512.png">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js?v=2').catch(function(err) {
                    console.log('SW registration failed: ', err);
                });
            });
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <style>
    .show-dropdown { 
        display: flex !important; 
        flex-direction: column; 
        animation: dropFade 0.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    #kebabDropdown {
        border: 1px solid rgba(0,0,0,0.05);
        backdrop-filter: blur(10px);
    }
    #kebabDropdown::-webkit-scrollbar {
        width: 6px;
    }
    #kebabDropdown::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 10px;
    }
    #kebabDropdown a {
        color: #334155 !important;
        background: transparent !important;
        padding: 0.85rem 1.2rem;
        margin: 0.25rem 0.6rem;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 600;
        border-radius: 12px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    #kebabDropdown a:hover {
        background: linear-gradient(90deg, #eff6ff, #e0f2fe) !important;
        color: #0284c7 !important;
        border-color: #bae6fd;
        box-shadow: inset 0 0 0 1px rgba(2, 132, 199, 0.1);
    }
    #kebabDropdown a.active {
        background: linear-gradient(90deg, #dbeafe, #bfdbfe) !important;
        color: #1d4ed8 !important;
    }
    .menu-header {
        padding: 0.8rem 1.2rem 0.4rem;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        background: -webkit-linear-gradient(45deg, #2563eb, #06b6d4);
</head>
<body>
    <style>
    @keyframes dropFade {
        from { opacity: 0; transform: translateY(-10px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .show-dropdown { 
        display: flex !important; 
        flex-direction: column; 
        animation: none !important;
    }
    #kebabDropdown {
        border: 1px solid rgba(0,0,0,0.05);
        backdrop-filter: blur(10px);
    }
    #kebabDropdown::-webkit-scrollbar {
        width: 6px;
    }
    #kebabDropdown::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 10px;
    }
    #kebabDropdown a {
        color: #334155 !important;
        background: transparent !important;
        padding: 0.85rem 1.2rem;
        margin: 0.25rem 0.6rem;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 600;
        border-radius: 12px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    #kebabDropdown a:hover {
        background: linear-gradient(90deg, #eff6ff, #e0f2fe) !important;
        color: #0284c7 !important;
        border-color: #bae6fd;
        box-shadow: inset 0 0 0 1px rgba(2, 132, 199, 0.1);
    }
    #kebabDropdown a.active {
        background: linear-gradient(90deg, #dbeafe, #bfdbfe) !important;
        color: #1d4ed8 !important;
    }
    .menu-header {
        padding: 0.8rem 1.2rem 0.4rem;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        background: -webkit-linear-gradient(45deg, #2563eb, #06b6d4);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    </style>
    
    <div class="pos-app-wrapper" style="display: flex; min-height: 100vh; width: 100%; background: #f0f4f8;">
        
        <!-- Left Sidebar (Full height) -->
        <?php
        require_once __DIR__ . '/sidebar.php';
        render_pos_sidebar($currentPage, $headerLogo, $headerStoreName, $branchName);
        ?>

        <!-- Right Main Workspace -->
        <div class="pos-main-workspace" style="flex: 1; min-width: 0; display: flex; flex-direction: column;">
            
            <header class="main-header" style="background: linear-gradient(90deg, #1e40af, #0284c7); padding: 0.55rem 1.2rem; min-height: 54px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <button type="button" id="sidebarToggleBtn" onclick="toggleSidebar()" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: #ffffff; cursor: pointer; width: 36px; height: 36px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.2s ease;" onmouseover="this.style.background='rgba(255,255,255,0.3)';" onmouseout="this.style.background='rgba(255,255,255,0.15)';" title="Toggle Sidebar (Expand / Fullscreen)">
                <i id="sidebarToggleIcon" class="bi bi-layout-sidebar-inset" style="font-size: 1.15rem; color: #ffffff;"></i>
            </button>
        </div>
        <nav class="main-nav" style="display: none !important;">
            <ul>
            <li><a href="index.php" class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?> btn" style="background:#0ea5e9; color:white; border-radius:6px;"><i class="bi bi-display"></i> POS Terminal</a></li>
            <li><a href="shift.php" class="<?php echo $currentPage == 'shift.php' ? 'active' : ''; ?> btn" style="background:#f97316; color:white; border-radius:6px;"><i class="bi bi-cash-coin"></i> Cash Register</a></li>
                
                <?php if($isManagerOrAdmin): ?>
                <li><a href="dashboard.php" class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="analytics.php" class="<?php echo $currentPage == 'analytics.php' ? 'active' : ''; ?>"><i class="bi bi-graph-up"></i> Analytics</a></li>
                <li><a href="products.php" class="<?php echo $currentPage == 'products.php' ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Inventory</a></li>
                <li><a href="transfers.php" class="<?php echo $currentPage == 'transfers.php' ? 'active' : ''; ?>"><i class="bi bi-arrow-left-right"></i> Transfers</a></li>
                <li><a href="customers.php" class="<?php echo $currentPage == 'customers.php' ? 'active' : ''; ?>"><i class="bi bi-people"></i> Customers</a></li>
                <li><a href="suppliers.php" class="<?php echo $currentPage == 'suppliers.php' ? 'active' : ''; ?>"><i class="bi bi-truck"></i> Suppliers</a></li>
                <li><a href="purchase_orders.php" class="<?php echo $currentPage == 'purchase_orders.php' ? 'active' : ''; ?>"><i class="bi bi-file-earmark-text"></i> POs</a></li>
                <li><a href="purchases.php" class="<?php echo $currentPage == 'purchases.php' ? 'active' : ''; ?>"><i class="bi bi-cart-plus"></i> Purchases</a></li>
                <li><a href="expenses.php" class="<?php echo $currentPage == 'expenses.php' ? 'active' : ''; ?>"><i class="bi bi-receipt"></i> Expenses</a></li>
                <li><a href="sales.php" class="<?php echo $currentPage == 'sales.php' ? 'active' : ''; ?>"><i class="bi bi-clock-history"></i> Sales History</a></li>
                <li><a href="returns.php" class="<?php echo $currentPage == 'returns.php' ? 'active' : ''; ?>"><i class="bi bi-arrow-return-left"></i> Returns</a></li>
                <li><a href="damaged_stock.php" class="<?php echo $currentPage == 'damaged_stock.php' ? 'active' : ''; ?>"><i class="bi bi-exclamation-triangle"></i> Damage Stock</a></li>
                <?php endif; ?>
                
                <?php if($isAdmin): ?>
                <li><a href="branches.php" class="<?php echo $currentPage == 'branches.php' ? 'active' : ''; ?>"><i class="bi bi-building"></i> Branches</a></li>
                <li><a href="reports.php" class="<?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>"><i class="bi bi-file-bar-graph"></i> Sale Report</a></li>
                <li><a href="historical_monthly.php" class="<?php echo $currentPage == 'historical_monthly.php' ? 'active' : ''; ?>"><i class="bi bi-calendar3"></i> Monthly History</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <?php
            // Fetch Unread Alerts Count
            $alertCountQ = $conn->query("SELECT COUNT(id) as c FROM alerts WHERE is_read = 0 AND tenant_id = $current_tenant_id");
            $unreadCount = $alertCountQ ? $alertCountQ->fetch_assoc()['c'] : 0;
            
            // Fetch Low Stock Count (<= 10)
            $stockQ = $conn->query("SELECT id FROM products WHERE stock <= 10 AND tenant_id = $current_tenant_id");
            $lowStockCount = 0;
            if ($stockQ) {
                $dismissed = isset($_SESSION['dismissed_alerts']) ? $_SESSION['dismissed_alerts'] : [];
                while($r = $stockQ->fetch_assoc()) {
                    if (!in_array('ls_' . $r['id'], $dismissed)) {
                        $lowStockCount++;
                    }
                }
            }
            
            $totalAlerts = $unreadCount + $lowStockCount;
            
            // Fetch Supplier Payables
            $suppQ = $conn->query("SELECT id FROM suppliers WHERE outstanding_payable > 0 AND tenant_id = $current_tenant_id");
            $payableCount = 0;
            if ($suppQ) {
                while($r = $suppQ->fetch_assoc()) {
                    if (!isset($_SESSION['dismissed_alerts']) || !in_array('supp_' . $r['id'], $_SESSION['dismissed_alerts'])) {
                        $payableCount++;
                    }
                }
            }
            
            // Fetch Customer Receivables
            $custQ = $conn->query("SELECT id FROM customers WHERE outstanding_balance > 0 AND tenant_id = $current_tenant_id");
            $receivableCount = 0;
            if ($custQ) {
                while($r = $custQ->fetch_assoc()) {
                    if (!isset($_SESSION['dismissed_alerts']) || !in_array('cust_' . $r['id'], $_SESSION['dismissed_alerts'])) {
                        $receivableCount++;
                    }
                }
            }
            
            // Fetch Today's Expenses
            $expQ = $conn->query("SELECT id FROM expenses WHERE DATE(created_at) = CURDATE() AND tenant_id = $current_tenant_id");
            $expensesCount = 0;
            if ($expQ) {
                while($r = $expQ->fetch_assoc()) {
                    if (!isset($_SESSION['dismissed_alerts']) || !in_array('exp_' . $r['id'], $_SESSION['dismissed_alerts'])) {
                        $expensesCount++;
                    }
                }
            }
            
            $totalAlerts += $payableCount + $receivableCount + $expensesCount;
        ?>

        <!-- Fixed Right Actions (Profile, Alerts, End of Day, Logout) -->
        <div style="display: flex; align-items: center; gap: 0.4rem; margin-left: auto; padding-left: 0.2rem; flex-shrink: 0;">
            
            <!-- Quick Stock Update Button -->
            <?php if($isManagerOrAdmin): ?>
            
            <?php endif; ?>

            <!-- Google Translate Scripts -->
            <script type="text/javascript">
            function googleTranslateElementInit() {
              new google.translate.TranslateElement({includedLanguages: 'en,ur,ar,hi', layout: google.translate.TranslateElement.InlineLayout.SIMPLE, autoDisplay: false}, 'google_translate_element');
            }
        
    <?php if(isset($_GET['purchase_done'])): ?>
    window.onload = function() {
        showToast('✅ Purchase Recorded!', '<?= addslashes(htmlspecialchars($_GET['pname'])) ?> updated. New Stock: <?= floatval($_GET['newstock']) ?>. Sell Price: Rs. <?= floatval($_GET['newprice'] ?? 0) ?>', 'success');
        
        // Clean URL
        const newUrl = window.location.href
            .replace(/([?&])purchase_done=1/g, '')
            .replace(/([?&])pname=[^&]*/g, '')
            .replace(/([?&])newstock=[^&]*/g, '')
            .replace(/([?&])newprice=[^&]*/g, '')
            .replace(/[?&]$/, '')
            .replace(/\?&/, '?');
            
        history.replaceState(null, null, newUrl);
    };
    <?php endif; ?>

    </script>
            <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
            <style>
                .goog-te-gadget-simple {
                    background-color: #f1f5f9;
                    border: 1px solid #cbd5e1;
                    border-radius: 4px;
                    padding: 0.2rem 0.5rem;
                    font-size: 0.85rem;
                }
                .goog-te-gadget-icon { display: none; }
                .goog-text-highlight { background-color: transparent !important; box-shadow: none !important; }
                body { top: 0 !important; }
                .skiptranslate iframe { display: none !important; }
            </style>

            <?php if($isAdmin): ?>
                <a href="end_of_day.php" style="display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.9rem; background: linear-gradient(135deg, #fbbf24, #d97706); border: none; border-radius: 8px; color: #fff; font-weight: 700; text-decoration: none; font-size: 0.85rem; box-shadow: 0 2px 6px rgba(217, 119, 6, 0.4); transition: all 0.2s ease; letter-spacing: 0.3px; text-shadow: 0 1px 1px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(217, 119, 6, 0.5)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 6px rgba(217, 119, 6, 0.4)';">
                    <span style="font-size: 1.1rem; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.2));">&#x1F319;</span> End of Day
                </a>
            <?php endif; ?>
            
            <div style="display: flex; align-items: center; gap: 0.4rem;">
                <button onclick="toggleFullScreen()" title="Full Screen (F11)" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)';" onmouseout="this.style.background='rgba(255,255,255,0.1)';">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
                </button>
                <a href="alerts.php" id="alertBellIcon" style="position: relative; color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.1); transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)';" onmouseout="this.style.background='rgba(255,255,255,0.1)';">
                    <svg width="18" height="18" fill="#fbbf24" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <?php if($totalAlerts > 0): ?>
                        <span style="position: absolute; top: -5px; right: -8px; background: #ef4444; color: white; font-size: 0.65rem; font-weight: bold; padding: 1px 4px; border-radius: 99px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <?php echo $totalAlerts; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>

            <div style="display: flex; align-items: center;">
                <a href="logout.php" style="color: white; background: #ef4444; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; text-decoration: none; font-weight: 600; margin-left: 0.2rem; transition: background 0.2s;" onmouseover="this.style.background='#dc2626';" onmouseout="this.style.background='#ef4444';">Logout</a>
            </div>


        </div>
        <script>
        document.addEventListener("click", function(e) {
            const dropdown = document.getElementById("kebabDropdown");
            const container = document.getElementById("kebabMenuContainer");
            if(dropdown && container && !container.contains(e.target)) {
                dropdown.classList.remove("show-dropdown");
            }
        });
    
    <?php if(isset($_GET['purchase_done'])): ?>
    window.onload = function() {
        showToast('✅ Purchase Recorded!', '<?= addslashes(htmlspecialchars($_GET['pname'])) ?> updated. New Stock: <?= floatval($_GET['newstock']) ?>. Sell Price: Rs. <?= floatval($_GET['newprice'] ?? 0) ?>', 'success');
        
        // Clean URL
        const newUrl = window.location.href
            .replace(/([?&])purchase_done=1/g, '')
            .replace(/([?&])pname=[^&]*/g, '')
            .replace(/([?&])newstock=[^&]*/g, '')
            .replace(/([?&])newprice=[^&]*/g, '')
            .replace(/[?&]$/, '')
            .replace(/\?&/, '?');
            
        history.replaceState(null, null, newUrl);
    };
    <?php endif; ?>

    </script>
    
<!-- Global Quick Purchase Modal -->
<div id="globalPurchaseModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center; text-align:left;">
    <div style="background:white; border-radius:16px; padding:2rem; width:100%; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,0.2); position:relative;">
        <button onclick="document.getElementById('globalPurchaseModal').style.display='none'" style="position:absolute; top:1rem; right:1rem; background:#f1f5f9; border:none; border-radius:50%; width:32px; height:32px; font-size:1.2rem; cursor:pointer; color:#64748b;">✕</button>
        <h3 style="color:#0f172a; margin:0 0 0.3rem 0;">📦 Quick Purchase / Stock Update</h3>
        <p style="color:#64748b; font-size:0.9rem; margin:0 0 1.5rem 0;">Buy stock instantly. <a href="purchases.php" style="color:#2563eb; font-weight:600;">View History &rarr;</a></p>

        <?php
            // Fetch Products and Suppliers for global modal
            $gp_products = $conn->query("SELECT id, name, purchase_price, price FROM products WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY name");
            $gp_suppliers = $conn->query("SELECT id, name FROM suppliers WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY name");
        ?>

        <form method="POST" action="alerts.php">
            <input type="hidden" name="quick_purchase" value="1">
            <input type="hidden" name="redirect_back" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">

            <!-- Product -->
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">🛍️ Product (Konsi item)</label>
                <select name="product_id" id="gpProductId" required style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem;" onchange="updateGPPrice()">
                    <option value="">-- Select Product --</option>
                    <?php if($gp_products) while($p = $gp_products->fetch_assoc()): ?>
                    <option value="<?php echo $p['id']; ?>" data-cost="<?php echo $p['purchase_price']; ?>" data-sell="<?php echo $p['price']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Supplier -->
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:0.3rem;">🏭 Supplier (Kis say le rahe hain)</label>
                <select name="supplier_id" required style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem;">
                    <option value="0">-- Walk-in / No Supplier --</option>
                    <?php if($gp_suppliers) while($s = $gp_suppliers->fetch_assoc()): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
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
    document.getElementById('globalPurchaseModal').style.display = 'flex';
}
function updateGPPrice() {
    var sel = document.getElementById('gpProductId');
    var opt = sel.options[sel.selectedIndex];
    if(opt.value) {
        document.getElementById('gpCostPrice').value = opt.getAttribute('data-cost') || '';
        document.getElementById('gpSellPrice').value = opt.getAttribute('data-sell') || '';
    } else {
        document.getElementById('gpCostPrice').value = '';
        document.getElementById('gpSellPrice').value = '';
    }
    calcGPTotal();
}
function calcGPTotal() {
    var qty = parseFloat(document.getElementById('gpQty').value) || 0;
    var cost = parseFloat(document.getElementById('gpCostPrice').value) || 0;
    var total = qty * cost;
    if (total > 0) {
        document.getElementById('gpTotalAmt').textContent = total.toLocaleString('en-PK', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('gpTotalDisplay').style.display = 'block';
        document.getElementById('gpAmountPaid').placeholder = 'Max: ' + total.toFixed(2);
    } else {
        document.getElementById('gpTotalDisplay').style.display = 'none';
    }
}
</script>
</header>

    <script>
        // Force remove dark theme if previously set by user
        localStorage.removeItem('theme');
        document.documentElement.removeAttribute('data-theme');
        
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    showToast("Error", "Error attempting to enable full-screen mode.", "error");
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

    <?php if(isset($_GET['purchase_done'])): ?>
    window.onload = function() {
        showToast('✅ Purchase Recorded!', '<?= addslashes(htmlspecialchars($_GET['pname'])) ?> updated. New Stock: <?= floatval($_GET['newstock']) ?>. Sell Price: Rs. <?= floatval($_GET['newprice'] ?? 0) ?>', 'success');
        
        // Clean URL
        const newUrl = window.location.href
            .replace(/([?&])purchase_done=1/g, '')
            .replace(/([?&])pname=[^&]*/g, '')
            .replace(/([?&])newstock=[^&]*/g, '')
            .replace(/([?&])newprice=[^&]*/g, '')
            .replace(/[?&]$/, '')
            .replace(/\?&/, '?');
            
        history.replaceState(null, null, newUrl);
    };
    <?php endif; ?>

    </script>

    <!-- Global Toast Notification Container -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;"></div>

    <script>
    function showToast(title, message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        let bgColor = '#10b981'; // success green
        let icon = '✅';
        if (type === 'error') { bgColor = '#ef4444'; icon = '❌'; }
        else if (type === 'warning') { bgColor = '#f59e0b'; icon = '⚠️'; }
        else if (type === 'info') { bgColor = '#3b82f6'; icon = 'ℹ️'; }

        toast.style.cssText = `
            background: ${bgColor};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            min-width: 300px;
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.95;
            cursor: pointer;
        `;

        toast.innerHTML = `
            <div style="font-size: 1.25rem;">${icon}</div>
            <div>
                <strong style="display: block; font-size: 1rem; margin-bottom: 0.2rem;">${title}</strong>
                <span style="font-size: 0.9rem; opacity: 0.9;">${message}</span>
            </div>
        `;

        // Click to dismiss
        toast.onclick = () => {
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.remove(), 300);
        };

        container.appendChild(toast);
        
        // Trigger animation
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });

        // Auto remove after 4 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.transform = 'translateX(120%)';
                setTimeout(() => {
                    if (toast.parentNode) toast.remove();
                }, 300);
            }
        }, 4000);
    }

    <?php if(isset($_GET['purchase_done'])): ?>
    window.onload = function() {
        showToast('✅ Purchase Recorded!', '<?= addslashes(htmlspecialchars($_GET['pname'])) ?> updated. New Stock: <?= floatval($_GET['newstock']) ?>. Sell Price: Rs. <?= floatval($_GET['newprice'] ?? 0) ?>', 'success');
        
        // Clean URL
        const newUrl = window.location.href
            .replace(/([?&])purchase_done=1/g, '')
            .replace(/([?&])pname=[^&]*/g, '')
            .replace(/([?&])newstock=[^&]*/g, '')
            .replace(/([?&])newprice=[^&]*/g, '')
            .replace(/[?&]$/, '')
            .replace(/\?&/, '?');
            
        history.replaceState(null, null, newUrl);
    };
    <?php endif; ?>

    function toggleSidebar() {
        const sidebar = document.querySelector('.pos-left-sidebar');
        const toggleIcon = document.getElementById('sidebarToggleIcon');
        if (!sidebar) return;
        
        if (sidebar.style.display === 'none') {
            sidebar.style.display = 'flex';
            localStorage.setItem('sidebarCollapsed', 'false');
            if (toggleIcon) toggleIcon.className = 'bi bi-layout-sidebar-inset';
        } else {
            sidebar.style.display = 'none';
            localStorage.setItem('sidebarCollapsed', 'true');
            if (toggleIcon) toggleIcon.className = 'bi bi-layout-sidebar';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            const sidebar = document.querySelector('.pos-left-sidebar');
            const toggleIcon = document.getElementById('sidebarToggleIcon');
            if (sidebar) sidebar.style.display = 'none';
            if (toggleIcon) toggleIcon.className = 'bi bi-layout-sidebar';
        }
    });
    </script>

    <main class="container" style="padding: 0.75rem 0.5rem 0.5rem 0.25rem; flex: 1; width: 100%; box-sizing: border-box;">
