<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

// 1. Update the POST logic
$f = preg_replace(
    '/\$admin_user = strtolower.*?;\s*\$admin_pass = password_hash.*?;/',
    '$admin_user = $conn->real_escape_string($_POST[\'admin_user\']);
    $raw_pass = $_POST[\'admin_pass\'];
    $admin_pass = password_hash($raw_pass, PASSWORD_DEFAULT);',
    $f
);

// 2. Update the success message
$f = str_replace(
    '$msg = "Tenant Created Successfully! Default Admin: $admin_user / password123 (Expires in 1 Month)";',
    '$msg = "Tenant Created Successfully! Admin Username: $admin_user / Password: $raw_pass (Expires in 1 Month)";',
    $f
);

// 3. Update the HTML form
$form_old = <<<FORM
                <div style="margin-bottom: 1rem;"><label>Plan</label><br><select name="plan" style="width:100%; padding:0.5rem;"><option value="basic">Basic ($29/mo)</option><option value="pro">Pro ($49/mo)</option><option value="enterprise">Enterprise ($99/mo)</option></select></div>
                <button type="submit" name="create_tenant" class="btn" style="background:#0ea5e9; color:white; width:100%; padding:0.75rem;">Create Account (1 Mo Free)</button>
FORM;

$form_new = <<<FORM
                <div style="margin-bottom: 1rem;"><label>Plan</label><br><select name="plan" style="width:100%; padding:0.5rem;"><option value="basic">Basic ($29/mo)</option><option value="pro">Pro ($49/mo)</option><option value="enterprise">Enterprise ($99/mo)</option></select></div>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 1rem 0;">
                <h4 style="margin-bottom: 0.5rem; color: #334155;">Admin Login Details</h4>
                <div style="margin-bottom: 1rem;"><label>Admin Username</label><br><input type="text" name="admin_user" required style="width:100%; padding:0.5rem;" placeholder="e.g. zain_admin"></div>
                <div style="margin-bottom: 1rem;"><label>Admin Password</label><br><input type="text" name="admin_pass" required style="width:100%; padding:0.5rem;" placeholder="e.g. secret123"></div>
                <button type="submit" name="create_tenant" class="btn" style="background:#0ea5e9; color:white; width:100%; padding:0.75rem;">Create Account (1 Mo Free)</button>
FORM;

$f = str_replace($form_old, $form_new, $f);

file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Successfully updated form and logic!";
?>
