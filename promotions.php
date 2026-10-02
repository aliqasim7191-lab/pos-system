<?php
include 'includes/db.php';
include 'includes/header.php';

if (!$isManagerOrAdmin) {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Only Managers and Admins can manage promotions.</p></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $code = strtoupper(trim($conn->real_escape_string($_POST['code'])));
        $type = $conn->real_escape_string($_POST['discount_type']);
        $value = floatval($_POST['discount_value']);
        $min_order = floatval($_POST['min_order_value']);
        $expiry = !empty($_POST['expiry_date']) ? "'".$conn->real_escape_string($_POST['expiry_date'])."'" : 'NULL';
        
        $conn->query("INSERT INTO promo_codes (code, discount_type, discount_value, min_order_value, expiry_date, tenant_id) VALUES ('$code', '$type', $value, $min_order, $expiry, {$_SESSION['tenant_id']})");
        $message = "Promo code $code added successfully!";
        
        if(function_exists('log_audit')) log_audit($conn, $_SESSION['user_id'], $current_branch_id, 'ADD_PROMO', 'promo_code', $conn->insert_id, null, $code, "Created promo code $code");
    }
    elseif (isset($_POST['action']) && $_POST['action'] == 'toggle') {
        $id = intval($_POST['id']);
        $conn->query("UPDATE promo_codes SET is_active = NOT is_active WHERE id = $id");
        $message = "Promo code status updated!";
    }
    elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM promo_codes WHERE id = $id");
        $message = "Promo code deleted!";
    }
}
?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h2>Promo Codes & Vouchers</h2>
    <p>Create discount codes to boost sales.</p>
</div>

<?php if($message): ?>
    <div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <!-- Add Form -->
    <div style="flex: 1; min-width: 300px; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem;">Create Promo Code</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Promo Code</label>
                <input type="text" name="code" required style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; text-transform: uppercase;" placeholder="e.g. EID50">
            </div>
            
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Type</label>
                    <select name="discount_type" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="percentage">Percentage (%)</option>
                        <option value="flat">Flat Amount ($)</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Value</label>
                    <input type="number" step="0.01" name="discount_value" required style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="e.g. 50">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Min. Order Value (Optional)</label>
                <input type="number" step="0.01" name="min_order_value" value="0" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Expiry Date (Optional)</label>
                <input type="date" name="expiry_date" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; border-radius: 8px;">Save Promo Code</button>
        </form>
    </div>
    
    <!-- List -->
    <div style="flex: 2; min-width: 400px; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem;">Active Promotions</h3>
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 0.75rem;">Code</th>
                    <th style="padding: 0.75rem;">Discount</th>
                    <th style="padding: 0.75rem;">Min. Order</th>
                    <th style="padding: 0.75rem;">Expiry</th>
                    <th style="padding: 0.75rem;">Status</th>
                    <th style="padding: 0.75rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pq = $conn->query("SELECT * FROM promo_codes ORDER BY created_at DESC");
                if ($pq && $pq->num_rows > 0) {
                    while($pr = $pq->fetch_assoc()) {
                        $val = $pr['discount_type'] == 'percentage' ? $pr['discount_value'].'%' : '$'.$pr['discount_value'];
                        $status = $pr['is_active'] ? '<span style="color:#166534;background:#dcfce7;padding:2px 6px;border-radius:4px;">Active</span>' : '<span style="color:#991b1b;background:#fee2e2;padding:2px 6px;border-radius:4px;">Inactive</span>';
                        
                        echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                        echo "<td style='padding: 0.75rem; font-weight:bold; font-size:1.1rem; color:var(--primary-color);'>" . htmlspecialchars($pr['code']) . "</td>";
                        echo "<td style='padding: 0.75rem;'>" . $val . "</td>";
                        echo "<td style='padding: 0.75rem;'>$" . $pr['min_order_value'] . "</td>";
                        echo "<td style='padding: 0.75rem;'>" . ($pr['expiry_date'] ? date('d M Y', strtotime($pr['expiry_date'])) : 'Never') . "</td>";
                        echo "<td style='padding: 0.75rem;'>" . $status . "</td>";
                        echo "<td style='padding: 0.75rem; display:flex; gap:0.5rem;'>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='action' value='toggle'>
                                <input type='hidden' name='id' value='{$pr['id']}'>
                                <button type='submit' class='btn' style='padding:0.3rem 0.6rem; font-size:0.8rem;'>Toggle</button>
                            </form>
                            <form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this promo?\");'>
                                <input type='hidden' name='action' value='delete'>
                                <input type='hidden' name='id' value='{$pr['id']}'>
                                <button type='submit' class='btn btn-danger' style='padding:0.3rem 0.6rem; font-size:0.8rem;'>Delete</button>
                            </form>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='padding: 1rem; text-align:center; color:var(--text-muted);'>No promo codes found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
