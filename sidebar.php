<?php
function render_pos_sidebar($currentPage, $headerLogo, $headerStoreName, $branchName) {
    $tenantId = $_SESSION['tenant_id'] ?? 1;
?>
    <div class="pos-left-sidebar" style="width: 275px; flex: 0 0 275px; flex-shrink: 0; display: flex; flex-direction: column; gap: 0.5rem; background: #0f172a; border-radius: 0; padding: 1rem 0.75rem; box-shadow: 4px 0 25px rgba(0,0,0,0.15); border-right: 1px solid rgba(255,255,255,0.05); height: 100vh; position: sticky; top: 0; z-index: 1000;">
        
        <!-- Top Store Header Card with 3-Line Menu & Logo -->
        <div class="pos-sidebar-header-card">
            <!-- 3-Line Kebab Menu Button on Far Left -->
            <div style="position: relative; flex-shrink: 0;" id="kebabMenuContainer">
                <button onclick="document.getElementById('kebabDropdown').classList.toggle('show-dropdown'); event.stopPropagation();" style="background: linear-gradient(135deg, #2563eb 0%, #0284c7 100%); border: 1px solid rgba(255,255,255,0.25); color: white; cursor: pointer; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(37,99,235,0.35); transition: all 0.2s ease;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
                </button>
                <div id="kebabDropdown" style="display: none; position: absolute; top: calc(100% + 10px); left: 0; background: rgba(255, 255, 255, 0.98); min-width: 270px; box-shadow: 0 15px 40px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.05); border-radius: 14px; border-top: 4px solid #0ea5e9; z-index: 9999; padding: 0.5rem 0 1rem 0; backdrop-filter: blur(12px); max-height: 80vh; overflow-y: auto;">
                    <div style="padding: 0.5rem 1.2rem; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 0.5rem;">
                        <div id="google_translate_element"></div>
                        <a href="#" onclick="document.cookie='googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/'; document.cookie='googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.'+location.hostname; window.location.reload(); return false;" class="btn" style="background:#f1f5f9; color:#0f172a; margin-top:0.5rem; text-align:center; font-weight:600; font-size:0.85rem; display:block;">Reset to English</a>
                    </div>
                    <?php if(($_SESSION['role'] ?? '') === 'super_admin'): ?>
                        <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 0.5rem 0;"></div>
                        <a href="super_admin.php" style="color:#6366f1; font-weight:bold;">🌐 Super Admin Panel</a>
                    <?php endif; ?>
                    <a href="promotions.php" class="<?= $currentPage == 'promotions.php' ? 'active' : '' ?>"> 🎁 Promos</a>
                    <a href="ecommerce.php" class="<?= $currentPage == 'ecommerce.php' ? 'active' : '' ?>"> 🌐 E-Commerce</a>
                    
                    <a href="staff.php" class="<?= $currentPage == 'staff.php' ? 'active' : '' ?>"> 👥 Staff</a>
                    <a href="hr.php" class="<?= $currentPage == 'hr.php' ? 'active' : '' ?>"> 💼 HR & Payroll</a>
                    <a href="settings.php" class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>"> ⚙️ Settings</a>
                    <a href="settings_integrations.php" class="<?= $currentPage == 'settings_integrations.php' ? 'active' : '' ?>"> 🔌 Integrations</a>
                    <a href="store.php?t=<?= $tenantId ?>" target="_blank" style="background:#ecfdf5; color:#059669; border-left-color:#10b981; font-weight:bold;"> 🛒 View Online Store</a>
                    <a href="audit_logs.php" class="<?= $currentPage == 'audit_logs.php' ? 'active' : '' ?>"> 📜 Audit Logs</a>
                </div>
            </div>

            <!-- Store Logo & Name -->
            <div style="display: flex; align-items: center; gap: 0.55rem; min-width: 0; flex: 1; overflow: hidden;">
                <img src="<?= htmlspecialchars($headerLogo); ?>" alt="Logo" style="height: 42px; width: 42px; object-fit: cover; border-radius: 10px; background: white; flex-shrink: 0; border: none !important; box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
                <div style="display: flex; flex-direction: column; line-height: 1.2; min-width: 0; flex: 1; overflow: hidden;">
                    <span style="font-size: 0.9rem; font-weight: 800; color: #ffffff; line-height: 1.25; word-wrap: break-word; overflow-wrap: break-word; letter-spacing: 0.1px;"><?= htmlspecialchars($headerStoreName); ?></span>
                    <span style="font-size: 0.68rem; font-weight: 700; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">🏢 <?= htmlspecialchars($branchName); ?></span>
                </div>
            </div>
        </div>

        <!-- Vertical Menu List -->
        <div class="pos-vertical-menu-dark" style="display: flex; flex-direction: column; gap: 0.25rem; flex: 1; overflow-y: auto;">
            <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #64748b; padding: 0.5rem 0.75rem 0.15rem 0.75rem;">Navigation</div>
            
            <a href="index.php" class="v-nav-dark <?= $currentPage == 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-shop"></i> POS Terminal
            </a>
            <a href="dashboard.php" class="v-nav-dark <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i> Dashboard
            </a>
            <a href="sales.php" class="v-nav-dark <?= $currentPage == 'sales.php' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i> Sale Report
            </a>
            <a href="shift.php" class="v-nav-dark <?= $currentPage == 'shift.php' ? 'active' : '' ?>">
                <i class="bi bi-cash-coin"></i> Cash Register
            </a>
            <a href="analytics.php" class="v-nav-dark <?= $currentPage == 'analytics.php' ? 'active' : '' ?>">
                <i class="bi bi-graph-up"></i> Analytics
            </a>
            <a href="products.php" class="v-nav-dark <?= $currentPage == 'products.php' ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> Inventory
            </a>
            <a href="transfers.php" class="v-nav-dark <?= $currentPage == 'transfers.php' ? 'active' : '' ?>">
                <i class="bi bi-arrow-repeat"></i> Transfers
            </a>
            <a href="customers.php" class="v-nav-dark <?= $currentPage == 'customers.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Customers
            </a>
            <a href="suppliers.php" class="v-nav-dark <?= $currentPage == 'suppliers.php' ? 'active' : '' ?>">
                <i class="bi bi-truck"></i> Suppliers
            </a>
            <a href="purchase_orders.php" class="v-nav-dark <?= $currentPage == 'purchase_orders.php' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i> POs
            </a>
            <a href="purchases.php" class="v-nav-dark <?= $currentPage == 'purchases.php' ? 'active' : '' ?>">
                <i class="bi bi-cart-plus"></i> Purchases
            </a>
            <a href="expenses.php" class="v-nav-dark <?= $currentPage == 'expenses.php' ? 'active' : '' ?>">
                <i class="bi bi-wallet2"></i> Expenses
            </a>
            <a href="returns.php" class="v-nav-dark <?= $currentPage == 'returns.php' ? 'active' : '' ?>">
                <i class="bi bi-arrow-counterclockwise"></i> Returns
            </a>
            <a href="damaged_stock.php" class="v-nav-dark <?= $currentPage == 'damaged_stock.php' ? 'active' : '' ?>">
                <i class="bi bi-exclamation-triangle"></i> Damage Stock
            </a>
            
            <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #64748b; padding: 0.6rem 0.75rem 0.15rem 0.75rem; border-top: 1px solid rgba(255,255,255,0.08); margin-top: 0.3rem;">Reports & Admin</div>
            
            <a href="branches.php" class="v-nav-dark <?= $currentPage == 'branches.php' ? 'active' : '' ?>">
                <i class="bi bi-building"></i> Branches
            </a>
            <a href="reports.php" class="v-nav-dark <?= $currentPage == 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i> Sale History
            </a>
            <a href="historical_monthly.php" class="v-nav-dark <?= $currentPage == 'historical_monthly.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i> Monthly History
            </a>

        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var menu = document.querySelector('.pos-vertical-menu-dark');
        if (!menu) return;
        
        var activeNav = menu.querySelector('.active');
        if (activeNav) {
            // Instantly scroll sidebar to keep active menu option centered and visible
            activeNav.scrollIntoView({ block: 'center', behavior: 'instant' });
        }
        
        menu.addEventListener('scroll', function() {
            sessionStorage.setItem('sidebar_scroll_pos', menu.scrollTop);
        });
    });
    </script>
<?php
}
