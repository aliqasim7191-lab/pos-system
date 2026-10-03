<?php
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') { die("Access Denied. Super Admin only."); }

if (isset($_GET['renew_id'])) {
    $r_id = (int)$_GET['renew_id'];
    $conn->query("UPDATE tenants SET subscription_ends_at = DATE_ADD(COALESCE(subscription_ends_at, CURRENT_DATE), INTERVAL 1 MONTH), subscription_status = 'active' WHERE id = $r_id");
    header("Location: super_admin.php?msg=renewed");
    exit();
}
if (isset($_GET['extend_id']) && isset($_GET['months'])) {
    $e_id = (int)$_GET['extend_id'];
    $m = (int)$_GET['months'];
    if ($m > 0) {
        // If expired, add from today. If active, add to current expiry date
        $conn->query("UPDATE tenants SET subscription_ends_at = DATE_ADD(IF(subscription_ends_at < CURRENT_DATE, CURRENT_DATE, COALESCE(subscription_ends_at, CURRENT_DATE)), INTERVAL $m MONTH), subscription_status = 'active' WHERE id = $e_id");
        header("Location: super_admin.php?msg=extended&months=$m");
        exit();
    }
}
if (isset($_GET['suspend_id'])) {
    $s_id = (int)$_GET['suspend_id'];
    $conn->query("UPDATE tenants SET subscription_status = 'suspended' WHERE id = $s_id");
    header("Location: super_admin.php?msg=suspended");
    exit();
}

if (isset($_GET['delete_id'])) {
    $d_id = (int)$_GET['delete_id'];
    // Prevent deleting Tenant 1 (Main/Default)
    if ($d_id !== 1) {
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $tables = ['alerts', 'attendance', 'audit_logs', 'branches', 'customer_ledger', 'customers', 'damaged_stock', 'expenses', 'held_carts', 'held_sale_items', 'held_sales', 'monthly_reports_history', 'payroll', 'product_batches', 'product_variations', 'products', 'promo_codes', 'purchase_items', 'purchase_order_items', 'purchase_orders', 'purchases', 'return_items', 'returns', 'sale_items', 'sales', 'settings', 'shifts', 'stock_transfers', 'suppliers', 'tax_classes', 'users', 'z_reports_history'];
        foreach ($tables as $tbl) {
            // Check if tenant_id exists in table
            $check = $conn->query("SHOW COLUMNS FROM $tbl LIKE 'tenant_id'");
            if ($check && $check->num_rows > 0) {
                $conn->query("DELETE FROM $tbl WHERE tenant_id = $d_id");
            }
        }
        $conn->query("DELETE FROM tenants WHERE id = $d_id");
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        header("Location: super_admin.php?msg=deleted");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tenant'])) {
    $company_name = $conn->real_escape_string($_POST['company_name']);
    $domain = $conn->real_escape_string($_POST['domain']);
    $plan = $conn->real_escape_string($_POST['plan']);
    $conn->query("INSERT INTO tenants (company_name, domain, subscription_plan, subscription_ends_at) VALUES ('$company_name', '$domain', '$plan', DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH))");
        $new_tenant_id = $conn->insert_id;
    $admin_user = $conn->real_escape_string($_POST['admin_user']);
    $raw_pass = $_POST['admin_pass'];
    $admin_pass = password_hash($raw_pass, PASSWORD_DEFAULT);
    
    // 1. Create Branch
    $conn->query("INSERT INTO branches (name, tenant_id) VALUES ('$company_name Main Branch', $new_tenant_id)");
    $branch_id = $conn->insert_id;
    
    // 2. Create User
    $conn->query("INSERT INTO users (username, password, role, branch_id, tenant_id) VALUES ('$admin_user', '$admin_pass', 'admin', $branch_id, $new_tenant_id)");
    
    // 3. Seed Categories (only if categories table exists)
    $cat_check = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($cat_check && $cat_check->num_rows > 0) {
        $conn->query("INSERT INTO categories (name, tenant_id) VALUES ('General', $new_tenant_id), ('Grocery', $new_tenant_id), ('Electronics', $new_tenant_id)");
    }
    
    // 4. Seed Tax Classes
    $conn->query("INSERT INTO tax_classes (name, rate, tenant_id) VALUES ('Standard Tax', 0.00, $new_tenant_id)");
    
    // 5. Seed Settings (Copy from Tenant 1 to ensure UI looks perfect)
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE tenant_id = 1");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $k = $conn->real_escape_string($row['setting_key']);
            $v = $conn->real_escape_string($row['setting_value']);
            // Overwrite specific settings
            if ($k === 'store_name') $v = $company_name;
            $conn->query("INSERT INTO settings (setting_key, setting_value, tenant_id) VALUES ('$k', '$v', $new_tenant_id)");
        }
    }
    
    // (Removed invalid integration_settings block)
    
    $msg = "Tenant Created Successfully! Admin Username: $admin_user / Password: $raw_pass (Expires in 1 Month)";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_super_admin'])) {
    $n_user = $conn->real_escape_string($_POST['new_super_user']);
    $n_pass = password_hash($_POST['new_super_pass'], PASSWORD_DEFAULT);
    $sid = (int)$_SESSION['user_id'];
    
    $conn->query("UPDATE users SET username = '$n_user', password = '$n_pass' WHERE id = $sid AND role = 'super_admin'");
    $msg = "Super Admin Credentials Updated Successfully! Next time login with Username: $n_user";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_license'])) {
    $key = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 16));
    $formatted_key = substr($key, 0, 4) . '-' . substr($key, 4, 4) . '-' . substr($key, 8, 4) . '-' . substr($key, 12, 4);
    $conn->query("INSERT INTO license_keys (license_key) VALUES ('$formatted_key')");
    $msg = "License Key Generated: $formatted_key";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_license'])) {
    $lid = intval($_POST['license_id']);
    $conn->query("UPDATE license_keys SET status = 'revoked' WHERE id = $lid");
    $msg = "License Key Revoked.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_license'])) {
    $lid = intval($_POST['license_id']);
    $conn->query("DELETE FROM license_keys WHERE id = $lid");
    $msg = "License Key Deleted.";
}

$tenants = $conn->query("SELECT * FROM tenants ORDER BY id DESC");
$total_tenants = $tenants->num_rows;
$total_sales_res = $conn->query("SELECT SUM(total_amount) as t FROM sales");
$total_sales = $total_sales_res->fetch_assoc()['t'] ?? 0;

// SaaS Analytics
$mrr_query = $conn->query("SELECT subscription_plan, COUNT(id) as count FROM tenants WHERE subscription_status = 'active' GROUP BY subscription_plan");
$mrr = 0;
while($row = $mrr_query->fetch_assoc()) {
    if($row['subscription_plan'] == 'basic') $mrr += ($row['count'] * 29);
    elseif($row['subscription_plan'] == 'pro') $mrr += ($row['count'] * 49);
    elseif($row['subscription_plan'] == 'enterprise') $mrr += ($row['count'] * 99);
}

$susp_query = $conn->query("SELECT COUNT(id) as count FROM tenants WHERE subscription_status = 'suspended'");
$suspended_count = $susp_query->fetch_assoc()['count'] ?? 0;

$exp_query = $conn->query("SELECT COUNT(id) as count FROM tenants WHERE subscription_status = 'active' AND subscription_ends_at BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)");
$expiring_soon = $exp_query->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if(!sessionStorage.getItem('strict_session')) { window.location.href='logout.php'; }</script>
    <script>
        // Strict Tab Closure Security Check
        if (!sessionStorage.getItem('strict_session')) {
            window.location.href = 'logout.php';
        }
    </script>
    <meta charset="UTF-8">
    <title>SaaS Super Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .saas-header { background: #0f172a; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 1.5rem; margin-bottom: 1.5rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .stat-box { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 1.5rem; border-radius: 12px; }
        .stat-box h3 { margin: 0; font-size: 1rem; opacity: 0.9; }
        .stat-box .num { font-size: 2rem; font-weight: bold; margin-top: 0.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        th { background: #f1f5f9; }
        .btn-sm { padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
<div class="saas-header"><h2><i class="fa fa-globe"></i> Global SaaS Admin</h2><a href="index.php" style="color:white; text-decoration:none;"><i class="fa fa-arrow-left"></i> Back to POS</a></div>
<div style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    <?php if(isset($msg)) echo "<div style='background:#10b981; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>$msg</div>"; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg']=='renewed') echo "<div style='background:#10b981; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Renewed for 1 Month!</div>"; ?>
<?php if(isset($_GET['msg']) && $_GET['msg']=='extended') echo "<div style='background:#10b981; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Extended for " . intval($_GET['months']) . " Months!</div>"; ?>
<?php if(isset($_GET['msg']) && $_GET['msg']=='updated') echo "<div style='background:#3b82f6; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Details Updated Successfully!</div>"; ?>
<?php if(isset($_GET['msg']) && $_GET['msg']=='deleted') echo "<div style='background:#ef4444; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Business Account Permanently Deleted!</div>"; ?>
    <div class="grid-3">
        <div class="stat-box"><h3>Active Businesses</h3><div class="num"><?php echo $total_tenants; ?></div></div>
        <div class="stat-box" style="background: linear-gradient(135deg, #8b5cf6, #6366f1);"><h3>Global SaaS Volume</h3><div class="num">$<?php echo number_format($total_sales, 2); ?></div></div>
        <div class="stat-box" style="background: linear-gradient(135deg, #10b981, #059669);"><h3>System Health</h3><div class="num">100% Online</div></div>
    </div>
    <div class="grid-3" style="margin-top: 2rem; grid-template-columns: 1fr 2fr;">
        <div class="card">
            <h3>Create New Tenant</h3>
            <form method="POST">
                <div style="margin-bottom: 1rem;"><label>Company Name</label><br><input type="text" name="company_name" required style="width:100%; padding:0.5rem;"></div>
                <div style="margin-bottom: 1rem;"><label>Domain (Optional)</label><br><input type="text" name="domain" style="width:100%; padding:0.5rem;"></div>
                <div style="margin-bottom: 1rem;"><label>Plan</label><br><select name="plan" style="width:100%; padding:0.5rem;"><option value="basic">Basic ($29/mo)</option><option value="pro">Pro ($49/mo)</option><option value="enterprise">Enterprise ($99/mo)</option></select></div>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 1rem 0;">
                <h4 style="margin-bottom: 0.5rem; color: #334155;">Admin Login Details</h4>
                <div style="margin-bottom: 1rem;"><label>Admin Username</label><br><input type="text" name="admin_user" required style="width:100%; padding:0.5rem;" placeholder="e.g. zain_admin"></div>
                <div style="margin-bottom: 1rem;"><label>Admin Password</label><br><input type="text" name="admin_pass" required style="width:100%; padding:0.5rem;" placeholder="e.g. secret123"></div>
                <button type="submit" name="create_tenant" class="btn" style="background:#0ea5e9; color:white; width:100%; padding:0.75rem;">Create Account (1 Mo Free)</button>
            </form>
        </div>
        
        <div> <!-- Start of Right Column Wrapper -->
        <div class="card">
            <h3>Update Super Admin Credentials</h3>
            <form method="POST">
                <div style="margin-bottom: 1rem;"><label>New Username</label><br><input type="text" name="new_super_user" required style="width:100%; padding:0.5rem;" placeholder="e.g. admin"></div>
                <div style="margin-bottom: 1rem;"><label>New Password</label><br><input type="text" name="new_super_pass" required style="width:100%; padding:0.5rem;" placeholder="e.g. newsecret"></div>
                <button type="submit" name="update_super_admin" class="btn" style="background:#8b5cf6; color:white; width:100%; padding:0.75rem;">Update Credentials</button>
            </form>
        </div>
        <div class="card" style="overflow-x: auto;">
            <h3>Registered Businesses</h3>
            <table>
                <tr><th>ID</th><th>Company</th><th>Plan</th><th>Status</th><th>Expiry Date</th><th>Action</th></tr>
                <?php while($t = $tenants->fetch_assoc()): 
                    $isExpired = strtotime($t['subscription_ends_at']) < time();
                    $statusColor = $t['subscription_status'] == 'suspended' ? '#ef4444' : ($isExpired ? '#f59e0b' : '#10b981');
                ?>
                <tr>
                    <td><?php echo $t['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($t['company_name']); ?></strong></td>
                    <td><span style="background:#e0e7ff; color:#4f46e5; padding:0.2rem 0.5rem; border-radius:4px;"><?php echo strtoupper($t['subscription_plan']); ?></span></td>
                    <td><span style="color:<?php echo $statusColor; ?>;"><i class="fa fa-circle"></i> <?php echo $isExpired ? 'Expired' : ucfirst($t['subscription_status']); ?></span></td>
                    <td><?php echo $t['subscription_ends_at'] ? date('M d, Y', strtotime($t['subscription_ends_at'])) : 'Lifetime'; ?></td>
                    <td style="white-space: nowrap;">
                        <a href="edit_tenant.php?id=<?php echo $t['id']; ?>" class="btn-sm" style="background:#3b82f6; color:white;"><i class="fa fa-pencil"></i> Edit</a>
                        <a href="javascript:void(0);" onclick="var m = prompt('Kitne maheenay (months) add karne hain?', '2'); if(m && !isNaN(m) && m > 0) window.location.href='?extend_id=<?php echo $t['id']; ?>&months='+m;" class="btn-sm" style="background:#10b981; color:white;"><i class="fa fa-calendar-plus-o"></i> Add Months</a>
                        <a href="?suspend_id=<?php echo $t['id']; ?>" class="btn-sm" style="background:#f59e0b; color:white;"><i class="fa fa-ban"></i> Suspend</a>
                        <?php if ($t['id'] != 1): ?>
                        <a href="javascript:void(0);" onclick="if(confirm('WARNING: Are you sure you want to PERMANENTLY delete this entire business and ALL its data? This cannot be undone.')) window.location.href='?delete_id=<?php echo $t['id']; ?>';" class="btn-sm" style="background:#ef4444; color:white;"><i class="fa fa-trash"></i> Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        </div> <!-- End of Right Column Wrapper -->
    </div>
</div>

    <!-- License Key Management -->
    <div class="card" style="margin-top: 2rem;">
        <h3>🔑 License Key Management</h3>
        <p style="color:#64748b; font-size:0.9rem;">Generate keys for new tenants. They must enter a valid key to access their dashboard.</p>
        
        <form method="POST" style="margin-bottom: 1.5rem;">
            <button type="submit" name="generate_license" style="background:#2563eb; color:white; border:none; padding:0.6rem 1.2rem; border-radius:6px; font-weight:600; cursor:pointer;">+ Generate New License Key</button>
        </form>

        <table style="width: 100%; text-align: left;">
            <thead>
                <tr>
                    <th>License Key</th>
                    <th>Status</th>
                    <th>Claimed By Tenant ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $keysQ = $conn->query("SELECT * FROM license_keys ORDER BY id DESC");
                while($k = $keysQ->fetch_assoc()):
                ?>
                <tr>
                    <td style="font-family: monospace; font-size:1.1rem; font-weight:bold; letter-spacing:1px; color:#0f172a;"><?php echo htmlspecialchars($k['license_key']); ?></td>
                    <td>
                        <?php if($k['status'] == 'unused'): ?>
                            <span style="background:#dbeafe; color:#1e40af; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.8rem;">Unused</span>
                        <?php elseif($k['status'] == 'active'): ?>
                            <span style="background:#dcfce7; color:#166534; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.8rem;">Active</span>
                        <?php else: ?>
                            <span style="background:#fee2e2; color:#991b1b; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.8rem;">Revoked</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $k['tenant_id'] ? 'Tenant #'.$k['tenant_id'] : '-'; ?></td>
                    <td style="display:flex; gap:0.5rem;">
                        <?php if($k['status'] == 'active'): ?>
                        <form method="POST" onsubmit="return confirm('Revoke this license? The tenant will be locked out immediately.');">
                            <input type="hidden" name="license_id" value="<?php echo $k['id']; ?>">
                            <button type="submit" name="revoke_license" style="background:#f59e0b; color:white; border:none; padding:0.3rem 0.6rem; border-radius:4px; cursor:pointer; font-size:0.8rem;">Revoke</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" onsubmit="return confirm('Delete this key permanently?');">
                            <input type="hidden" name="license_id" value="<?php echo $k['id']; ?>">
                            <button type="submit" name="delete_license" style="background:#ef4444; color:white; border:none; padding:0.3rem 0.6rem; border-radius:4px; cursor:pointer; font-size:0.8rem;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
