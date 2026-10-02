<?php
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') { die("Access Denied. Super Admin only."); }

if (!isset($_GET['id'])) { die("Tenant ID missing."); }
$tenant_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tenant'])) {
    $company_name = $conn->real_escape_string($_POST['company_name']);
    $domain = $conn->real_escape_string($_POST['domain']);
    $plan = $conn->real_escape_string($_POST['plan']);
    
    // Update Tenant
    $conn->query("UPDATE tenants SET company_name = '$company_name', domain = '$domain', subscription_plan = '$plan' WHERE id = $tenant_id");
    
    // Update Settings Store Name
    $conn->query("UPDATE settings SET setting_value = '$company_name' WHERE setting_key = 'store_name' AND tenant_id = $tenant_id");
    
    // Update Admin User if provided
    $admin_user = $conn->real_escape_string($_POST['admin_user']);
    $raw_pass = $_POST['admin_pass'];
    
    if (!empty($admin_user)) {
        if (!empty($raw_pass)) {
            $admin_pass = password_hash($raw_pass, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET username = '$admin_user', password = '$admin_pass' WHERE role = 'admin' AND tenant_id = $tenant_id LIMIT 1");
        } else {
            $conn->query("UPDATE users SET username = '$admin_user' WHERE role = 'admin' AND tenant_id = $tenant_id LIMIT 1");
        }
    }
    
    header("Location: super_admin.php?msg=updated");
    exit();
}

$tQ = $conn->query("SELECT * FROM tenants WHERE id = $tenant_id LIMIT 1");
$tenant = $tQ->fetch_assoc();

$uQ = $conn->query("SELECT username FROM users WHERE role = 'admin' AND tenant_id = $tenant_id LIMIT 1");
$admin = $uQ->fetch_assoc();
$admin_user = $admin['username'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Tenant</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 2rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 2rem; max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: bold; margin-bottom: 0.5rem; color: #334155; }
        input, select { width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; }
        .btn-update { background: #0ea5e9; color: white; padding: 1rem 2rem; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; font-size: 1.1rem; }
        .btn-cancel { background: #94a3b8; color: white; padding: 1rem 2rem; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; font-size: 1.1rem; text-decoration: none; display: block; text-align: center; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="margin-top:0; color:#0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem;">Edit Business (Tenant #<?php echo $tenant_id; ?>)</h2>
        <form method="POST">
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" value="<?php echo htmlspecialchars($tenant['company_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Domain</label>
                <input type="text" name="domain" value="<?php echo htmlspecialchars($tenant['domain']); ?>">
            </div>
            <div class="form-group">
                <label>Subscription Plan</label>
                <select name="plan">
                    <option value="basic" <?php if($tenant['subscription_plan'] == 'basic') echo 'selected'; ?>>Basic</option>
                    <option value="pro" <?php if($tenant['subscription_plan'] == 'pro') echo 'selected'; ?>>Pro</option>
                    <option value="enterprise" <?php if($tenant['subscription_plan'] == 'enterprise') echo 'selected'; ?>>Enterprise</option>
                </select>
            </div>
            
            <h3 style="color:#0f172a; margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 1rem;">Update Admin Login</h3>
            <p style="color:#64748b; font-size: 0.9rem; margin-bottom: 1rem;">Leave password blank if you do not want to change it.</p>
            
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="admin_user" value="<?php echo htmlspecialchars($admin_user); ?>" required>
            </div>
            <div class="form-group">
                <label>New Password (Optional)</label>
                <input type="text" name="admin_pass" placeholder="Enter new password to overwrite">
            </div>
            
            <button type="submit" name="update_tenant" class="btn-update">Save Changes</button>
            <a href="super_admin.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>
