<?php
include 'includes/db.php';
include 'includes/header.php';

// Branch Filters
$bF_WHERE = $isAdmin ? " WHERE tenant_id = {$_SESSION['tenant_id']}" : " WHERE tenant_id = {$_SESSION['tenant_id']} AND branch_id = $current_branch_id";
$bF_AND = $isAdmin ? " AND tenant_id = {$_SESSION['tenant_id']}" : " AND tenant_id = {$_SESSION['tenant_id']} AND branch_id = $current_branch_id";


if (!$isAdmin) {
    echo "<div style='padding: 2rem;'><h2>Access Denied</h2><p>You do not have permission to view this page.</p></div>";
    include 'includes/footer.php';
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $category = trim($_POST['category']);
        $amount = floatval($_POST['amount']);
        $expense_date = trim($_POST['expense_date']);
        $notes = trim($_POST['notes']);
        $userId = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, expense_date, notes, tenant_id) VALUES (?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt->bind_param("isdss", $userId, $category, $amount, $expense_date, $notes);
        if ($stmt->execute()) {
            $message = "Expense logged successfully.";
        } else {
            $message = "Error logging expense.";
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM expenses WHERE id=? AND tenant_id=?");
        $stmt->bind_param("ii", $id, $_SESSION['tenant_id']);
        if ($stmt->execute()) {
            $message = "Expense deleted successfully.";
        }
        $stmt->close();
    }
}

// Fetch Expenses
$showHistory = isset($_GET['history']) && $_GET['history'] == '1';

if ($showHistory) {
    // Show all expenses
    $result = $conn->query("SELECT e.*, u.username FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.tenant_id = {$_SESSION['tenant_id']} ORDER BY expense_date DESC, created_at DESC");
} else {
    // Show only uncleared (active shift) expenses
    $result = $conn->query("SELECT e.*, u.username FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.is_cleared = 0 AND e.tenant_id = {$_SESSION['tenant_id']} ORDER BY expense_date DESC, created_at DESC");
}

$expenses = [];
$totalExpenses = 0;
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $expenses[] = $row;
        $totalExpenses += $row['amount'];
    }
}
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 0.2rem;"><?php echo $showHistory ? 'All Past Expenses (History)' : 'Active Shift Expenses'; ?></h2>
        <p style="color: #64748b; font-size: 0.95rem;">
            <?php echo $showHistory ? 'Showing all historical expenses.' : 'Log daily expenses to calculate accurate net profit. Cleared at EOD.'; ?>
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <?php if($showHistory): ?>
            <a href="expenses.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">View Active Expenses</a>
        <?php else: ?>
            <a href="expenses.php?history=1" class="btn" style="background: #ffffff; color: #475569; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; text-decoration: none; border: 1px solid #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">📚 View All History</a>
            <button class="btn btn-primary" style="padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 4px rgba(37,99,235,0.2);" onclick="openAddModal()">+ Log New Expense</button>
        <?php endif; ?>
    </div>
</div>

<?php if($message): ?>
    <div style="background: #dcfce7; color: #15803d; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #bbf7d0; font-weight: 500;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="background: #ffffff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem; border: 1px solid #e2e8f0; display: inline-block;">
    <h3 style="color: #ef4444; font-size: 1.2rem; margin: 0;">Total Logged: $<?php echo number_format($totalExpenses, 2); ?></h3>
</div>

<div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; background: #ffffff;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
            <tr>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Date</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Category</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Amount</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Notes</th>
                <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Logged By</th>
                <th style="padding: 1rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($expenses as $expense): ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                    <td style="padding: 1rem; color: #0f172a; font-weight: 500;"><?php echo date('d M Y', strtotime($expense['expense_date'])); ?></td>
                    <td style="padding: 1rem; font-weight: 600; color: #334155;">
                        <span style="background: #f1f5f9; color: #475569; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.85rem;"><?php echo htmlspecialchars($expense['category']); ?></span>
                    </td>
                    <td style="padding: 1rem; color: #ef4444; font-weight: 700;">$<?php echo number_format($expense['amount'], 2); ?></td>
                    <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;"><?php echo htmlspecialchars($expense['notes']); ?></td>
                    <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">👤 <?php echo htmlspecialchars($expense['username']); ?></td>
                    <td style="padding: 1rem; text-align: right;">
                        <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Delete this expense?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
                            <button type="submit" class="btn action-btn btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($expenses)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 4rem; color: #64748b;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💸</div>
                        <h3 style="margin: 0; color: #0f172a; font-size: 1.2rem;">No Expenses Found</h3>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem;">You haven't logged any expenses.</p>
                        <button class="btn btn-primary" style="margin-top: 1.5rem;" onclick="openAddModal()">+ Log Expense</button>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div></div>

<!-- Add Modal -->
<div id="expenseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Log Expense</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Expense Date *</label>
                <input type="date" name="expense_date" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Category *</label>
                <select name="category" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                    <option value="">- Select -</option>
                    <option value="Electricity / Utilities">Electricity / Utilities</option>
                    <option value="Rent">Rent</option>
                    <option value="Staff Salary">Staff Salary</option>
                    <option value="Store Supplies / Maintenance">Store Supplies / Maintenance</option>
                    <option value="Tea & Snacks (Petty Cash)">Tea & Snacks (Petty Cash)</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Amount ($) *</label>
                <input type="number" step="0.01" name="amount" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Notes (Optional)</label>
                <textarea name="notes" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;"></textarea>
            </div>

            <div style="display:flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save</button>
                <button type="button" class="btn" style="flex: 1; background:#e2e8f0; color:var(--text-main);" onclick="document.getElementById('expenseModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('expenseModal').style.display = 'flex';
}
</script>

<?php include 'includes/footer.php'; ?>
