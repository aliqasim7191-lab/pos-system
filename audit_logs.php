<?php
include 'includes/db.php';
include 'includes/header.php';

if (!$isAdmin) {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Only Super Administrators can view Audit Logs.</p></div>";
    include 'includes/footer.php';
    exit();
}

$logs = $conn->query("
    SELECT a.*, u.username, b.name as branch_name 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN branches b ON a.branch_id = b.id 
    ORDER BY a.created_at DESC 
    LIMIT 100
");
?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h2>Security & Audit Logs</h2>
    <p>Track all sensitive actions across all branches.</p>
</div>

<div style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow-x: auto;">
    <table class="data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                <th style="padding: 1rem;">Date/Time</th>
                <th style="padding: 1rem;">User</th>
                <th style="padding: 1rem;">Branch</th>
                <th style="padding: 1rem;">Action</th>
                <th style="padding: 1rem;">Details</th>
                <th style="padding: 1rem;">Changes</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($logs && $logs->num_rows > 0) {
                while($log = $logs->fetch_assoc()) {
                    $action_color = 'var(--text-main)';
                    if (strpos(strtolower($log['action_type']), 'delete') !== false) $action_color = 'var(--danger)';
                    if (strpos(strtolower($log['action_type']), 'update') !== false) $action_color = '#f59e0b';
                    if (strpos(strtolower($log['action_type']), 'refund') !== false) $action_color = '#ef4444';
                    
                    echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                    echo "<td style='padding: 1rem; color: var(--text-muted); font-size: 0.9rem;'>" . date('d M Y h:i A', strtotime($log['created_at'])) . "</td>";
                    echo "<td style='padding: 1rem; font-weight: 500;'>" . htmlspecialchars($log['username'] ?? 'System') . "</td>";
                    echo "<td style='padding: 1rem;'>" . htmlspecialchars($log['branch_name'] ?? 'N/A') . "</td>";
                    echo "<td style='padding: 1rem; font-weight: bold; color: $action_color;'>" . htmlspecialchars($log['action_type']) . "</td>";
                    echo "<td style='padding: 1rem;'>" . htmlspecialchars($log['description']) . "</td>";
                    
                    $changes = '';
                    if ($log['old_value'] || $log['new_value']) {
                        $changes = "<div style='font-size:0.85rem; color:var(--text-muted);'><span style='text-decoration:line-through;'>{$log['old_value']}</span> ➔ <span style='font-weight:bold;color:var(--text-main);'>{$log['new_value']}</span></div>";
                    }
                    echo "<td style='padding: 1rem;'>" . $changes . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='padding: 2rem; text-align:center; color: var(--text-muted);'>No audit logs found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
