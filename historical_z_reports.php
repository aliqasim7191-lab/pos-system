<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset($isAdmin) || !$isAdmin) {
    die("<div class='container'><h2>Access Denied</h2><p>Only administrators can view historical reports.</p></div>");
}

$filterDate = isset($_GET['date']) ? trim($_GET['date']) : '';
$tenantId = intval($_SESSION['tenant_id'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_report') {
    $delId = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM z_reports_history WHERE id = ? AND tenant_id = ?");
    $stmt->bind_param("ii", $delId, $tenantId);
    $stmt->execute();
    $stmt->close();
    echo "<script>window.location.href='historical_z_reports.php" . ($filterDate ? "?date=" . urlencode($filterDate) : "") . "';</script>";
    exit;
}

if ($filterDate) {
    if (strlen($filterDate) === 7) {
        $stmt = $conn->prepare("SELECT * FROM z_reports_history WHERE (DATE_FORMAT(COALESCE(report_date, created_at), '%Y-%m') = ? OR DATE_FORMAT(created_at, '%Y-%m') = ?) AND tenant_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("ssi", $filterDate, $filterDate, $tenantId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM z_reports_history WHERE (DATE(COALESCE(report_date, created_at)) = ? OR DATE(created_at) = ? OR report_date = ?) AND tenant_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("sssi", $filterDate, $filterDate, $filterDate, $tenantId);
    }
    $stmt->execute();
    $history = $stmt->get_result();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT * FROM z_reports_history WHERE tenant_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $history = $stmt->get_result();
    $stmt->close();
}
?>

<div class="container dashboard-container" style="padding-top: 1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2rem; color: var(--text-main);">Historical Z-Reports</h1>
            <div style="font-size: 0.9rem; color: var(--text-muted);">View permanent snapshots of past End of Day and Shift settlements.</div>
        </div>
        
        <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
            <label style="font-weight: 500;">Filter by Date:</label>
            <input type="date" name="date" value="<?php echo htmlspecialchars($filterDate); ?>" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            <?php if($filterDate): ?>
                <a href="historical_z_reports.php" class="btn" style="background: #e2e8f0; color: #333; padding: 0.5rem 1rem; text-decoration: none;">Clear Filter</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Date Generated</th>
                    <th>Net Income</th>
                    <th>Opening Cash</th>
                    <th>Expected Cash</th>
                    <th>Closing Cash</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($history && $history->num_rows > 0): while($r = $history->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight: 600;">#<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-main);"><?php echo date('d M Y', strtotime($r['report_date'])); ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('h:i A', strtotime($r['created_at'])); ?></div>
                    </td>
                    <td style="font-weight: bold; color: <?php echo $r['net_income'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">$<?php echo number_format($r['net_income'], 2); ?></td>
                    <td style="font-weight: 600;">$<?php echo number_format($r['opening_cash'] ?? 0, 2); ?></td>
                    <td style="font-weight: 600; color: var(--primary-color);">$<?php echo number_format($r['expected_cash'] ?? 0, 2); ?></td>
                    <td style="font-weight: bold; color: <?php echo ($r['closing_cash'] >= $r['expected_cash']) ? 'var(--success)' : 'var(--danger)'; ?>;">
                        $<?php echo number_format($r['closing_cash'] ?? 0, 2); ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="view_z_report.php?id=<?php echo $r['id']; ?>" class="btn action-btn btn-edit" style="text-decoration:none;">👁️ View Details</a>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this Z-Report? This cannot be undone.');" style="margin:0;">
                                <input type="hidden" name="action" value="delete_report">
                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                <button type="submit" class="btn action-btn btn-delete" style="cursor:pointer; border:none;">🗑️ Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-muted);">No historical Z-Reports found. Run an End of Day settlement first.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
