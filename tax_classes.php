<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'includes/db.php';

// Only admins can manage tax classes
$adminQ = $conn->query("SELECT role FROM users WHERE id = " . intval($_SESSION['user_id']));
$isAdmin = ($adminQ && $adminQ->fetch_assoc()['role'] === 'admin');
if (!$isAdmin) {
    die("Access Denied");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $conn->real_escape_string($_POST['name']);
        $rate = floatval($_POST['rate']);
        $conn->query("INSERT INTO tax_classes (name, rate, tenant_id) VALUES ('$name', $rate, {$_SESSION['tenant_id']})");
        $message = "Tax class added!";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM tax_classes WHERE id = $id");
        $message = "Tax class deleted!";
    }
}

$taxes = $conn->query("SELECT * FROM tax_classes ORDER BY id DESC");
include 'includes/header.php';
?>

<div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2>Manage Tax Classes</h2>
        <p>Define custom tax rates for specific products (e.g. standard, zero-rated, luxury).</p>
    </div>
    <a href="settings.php" class="btn" style="background:#e2e8f0; color:#475569;">&larr; Back to Settings</a>
</div>

<?php if($message) echo "<div style='background:#10b981;color:white;padding:1rem;border-radius:6px;margin-bottom:1rem;'>$message</div>"; ?>

<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="flex:1; min-width:300px; background:white; padding:1.5rem; border-radius:12px; border:1px solid #e2e8f0;">
        <h3>Add Tax Class</h3>
        <form method="POST" style="margin-top:1rem;">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom:1rem;">
                <label>Tax Class Name (e.g. Standard VAT)</label>
                <input type="text" name="name" required style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px; margin-top:0.3rem;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Rate (%)</label>
                <input type="number" step="0.01" name="rate" required style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px; margin-top:0.3rem;">
            </div>
            <button class="btn btn-primary" style="width:100%; border-radius:6px;">Add Tax Class</button>
        </form>
    </div>
    
    <div style="flex:2; min-width:400px; background:white; padding:1.5rem; border-radius:12px; border:1px solid #e2e8f0;">
        <h3>Current Tax Classes</h3>
        <table class="data-table" style="width:100%; margin-top:1rem; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0; text-align:left;">
                    <th style="padding:0.75rem;">ID</th>
                    <th style="padding:0.75rem;">Name</th>
                    <th style="padding:0.75rem;">Rate (%)</th>
                    <th style="padding:0.75rem;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($taxes && $taxes->num_rows > 0): ?>
                    <?php while($t = $taxes->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:0.75rem; color:#64748b;">#<?php echo $t['id']; ?></td>
                        <td style="padding:0.75rem; font-weight:600;"><?php echo htmlspecialchars($t['name']); ?></td>
                        <td style="padding:0.75rem; font-weight:bold; color:var(--primary-color);"><?php echo floatval($t['rate']); ?>%</td>
                        <td style="padding:0.75rem;">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this tax class? Products using it will fall back to default tax.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" style="background:#ef4444; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="padding:1rem; text-align:center; color:#64748b;">No custom tax classes defined.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
