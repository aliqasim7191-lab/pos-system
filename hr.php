<?php
include 'includes/db.php';
include 'includes/header.php';

if (!$isAdmin) {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Only Admins can manage HR & Payroll.</p></div>";
    include 'includes/footer.php';
    exit();
}

// Flash messages
$message = '';
if (isset($_SESSION['flash_success'])) {
    $message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$date = $_GET['date'] ?? date('Y-m-d');


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_attendance') {
        $att_date = $conn->real_escape_string($_POST['attendance_date']);
        foreach($_POST['attendance'] as $user_id => $status) {
            $uid = intval($user_id);
            $stat = $conn->real_escape_string($status);
            $conn->query("INSERT INTO attendance (user_id, attendance_date, status, tenant_id) VALUES ($uid, '$att_date', '$stat', {$_SESSION['tenant_id']}) ON DUPLICATE KEY UPDATE status='$stat'");
        }
        $message = "Attendance saved successfully for $att_date!";
        if(function_exists('log_audit')) log_audit($conn, $_SESSION['user_id'], $current_branch_id ?? 1, 'MARK_ATTENDANCE', 'attendance', 0, null, $att_date, "Marked attendance");
        
        $_SESSION['flash_success'] = $message;
        echo "<script>window.location.href='hr.php?date=" . urlencode($att_date) . "';</script>";
        exit();
    }
    elseif ($_POST['action'] == 'update_salary') {
        $uid = intval($_POST['user_id']);
        $salary = floatval($_POST['base_salary']);
        $conn->query("UPDATE users SET base_salary = $salary WHERE id = $uid");
        
        $_SESSION['flash_success'] = "Salary updated successfully.";
        echo "<script>window.location.href='hr.php?date=" . urlencode($date) . "';</script>";
        exit();
    }
    elseif ($_POST['action'] == 'generate_payroll') {
        $month = $conn->real_escape_string($_POST['payroll_month']); // YYYY-MM
        
        $users = $conn->query("SELECT id, base_salary, username FROM users WHERE tenant_id = {$_SESSION['tenant_id']} AND role != 'super_admin'");
        while($u = $users->fetch_assoc()) {
            $uid = $u['id'];
            $base = $u['base_salary'];
            
            // Fetch any existing manual bonuses/deductions so we don't wipe them out
            $bonus = 0;
            $man_ded = 0;
            $existingQ = $conn->query("SELECT bonuses, manual_deductions FROM payroll WHERE user_id=$uid AND month_year='$month'");
            if ($existingQ && $existingQ->num_rows > 0) {
                $row = $existingQ->fetch_assoc();
                $bonus = $row['bonuses'];
                $man_ded = $row['manual_deductions'];
            }
            
            // Calculate auto-deductions based on absents
            $absQ = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_id = $uid AND attendance_date LIKE '$month-%' AND status = 'absent'");
            $absents = $absQ ? $absQ->fetch_assoc()['c'] : 0;
            
            $halfQ = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_id = $uid AND attendance_date LIKE '$month-%' AND status = 'half-day'");
            $halfDays = $halfQ ? $halfQ->fetch_assoc()['c'] : 0;
            
            $perDay = $base / 30; // approx
            $deductions = ($absents * $perDay) + ($halfDays * ($perDay / 2));
            
            $net = $base - $deductions - $man_ded + $bonus;
            if($net < 0) $net = 0;
            
            $conn->query("INSERT INTO payroll (user_id, month_year, base_salary, deductions, manual_deductions, bonuses, net_salary, tenant_id) VALUES ($uid, '$month', $base, $deductions, $man_ded, $bonus, $net, {$_SESSION['tenant_id']}) ON DUPLICATE KEY UPDATE base_salary=$base, deductions=$deductions, net_salary=$net");
        }
        $message = "Payroll generated for $month!";
        if(function_exists('log_audit')) log_audit($conn, $_SESSION['user_id'], $current_branch_id ?? 1, 'GENERATE_PAYROLL', 'payroll', 0, null, $month, "Generated payroll");
        
        $_SESSION['flash_success'] = $message;
        echo "<script>window.location.href='hr.php?date=" . urlencode($date) . "';</script>";
        exit();
    }
    elseif ($_POST['action'] == 'update_payroll_manual') {
        $pid = intval($_POST['payroll_id']);
        $new_base = floatval($_POST['base_salary']);
        $bonus = floatval($_POST['bonuses']);
        $bonus_reason = $conn->real_escape_string($_POST['bonus_reason']);
        $man_ded = floatval($_POST['manual_deductions']);
        $ded_reason = $conn->real_escape_string($_POST['deduction_reason']);
        
        $pq = $conn->query("SELECT user_id, deductions FROM payroll WHERE id = $pid AND tenant_id = {$_SESSION['tenant_id']}");
        if ($pq && $pq->num_rows > 0) {
            $row = $pq->fetch_assoc();
            $uid = $row['user_id'];
            $auto_ded = $row['deductions'];
            $net = $new_base - $auto_ded - $man_ded + $bonus;
            if ($net < 0) $net = 0;
            
            // Update payroll
            $conn->query("UPDATE payroll SET base_salary = $new_base, bonuses = $bonus, bonus_reason = '$bonus_reason', manual_deductions = $man_ded, deduction_reason = '$ded_reason', net_salary = $net WHERE id = $pid");
            // Also update the user's permanent base salary so next month is correct
            $conn->query("UPDATE users SET base_salary = $new_base WHERE id = $uid");
            
            $_SESSION['flash_success'] = "Payroll manually updated!";
        }
        echo "<script>window.location.href='hr.php?date=" . urlencode($date) . "';</script>";
        exit();
    }
    elseif ($_POST['action'] == 'pay_salary') {
        $pid = intval($_POST['payroll_id']);
        $conn->query("UPDATE payroll SET status = 'paid', paid_date = CURDATE() WHERE id = $pid");
        $_SESSION['flash_success'] = "Salary marked as paid!";
        echo "<script>window.location.href='hr.php?date=" . urlencode($date) . "';</script>";
        exit();
    }
}

// Flash messages
if (isset($_SESSION['flash_success'])) {
    $message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Fetch users
$users = $conn->query("SELECT * FROM users WHERE tenant_id = {$_SESSION['tenant_id']} AND role != 'super_admin' ORDER BY username ASC");
$usersArr = [];
while($u = $users->fetch_assoc()) $usersArr[] = $u;

?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h2>HR & Payroll</h2>
    <p>Manage staff attendance and generate monthly salaries.</p>
</div>

<?php if($message): ?>
    <div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <!-- Attendance Section -->
    <div style="flex: 1; min-width: 400px; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            Daily Attendance
            <form method="GET" style="display:inline-flex; gap:0.5rem;">
                <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="this.form.submit()">
            </form>
        </h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_attendance">
            <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($date); ?>">
            
            <table class="data-table" style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                        <th style="padding: 0.75rem;">Staff Name</th>
                        <th style="padding: 0.75rem;">Role</th>
                        <th style="padding: 0.75rem;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach($usersArr as $u) {
                        $uid = $u['id'];
                        $stQ = $conn->query("SELECT status FROM attendance WHERE user_id = $uid AND attendance_date = '$date'");
                        $status = ($stQ && $stQ->num_rows > 0) ? $stQ->fetch_assoc()['status'] : 'present';
                        
                        echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                        echo "<td style='padding: 0.75rem; font-weight:500;'>" . htmlspecialchars($u['username']) . "</td>";
                        echo "<td style='padding: 0.75rem; text-transform:capitalize;'>" . htmlspecialchars($u['role']) . "</td>";
                        echo "<td style='padding: 0.75rem;'>
                                <select name='attendance[$uid]' style='padding:0.4rem; border-radius:4px; border:1px solid #cbd5e1;'>
                                    <option value='present' ".($status=='present'?'selected':'').">Present</option>
                                    <option value='absent' ".($status=='absent'?'selected':'').">Absent</option>
                                    <option value='half-day' ".($status=='half-day'?'selected':'').">Half Day</option>
                                    <option value='leave' ".($status=='leave'?'selected':'').">On Leave</option>
                                </select>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; border-radius: 8px;">Save Attendance</button>
        </form>
    </div>
    
    <!-- Payroll Section -->
    <div style="flex: 1; min-width: 400px; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            Payroll & Salaries
            
            <form method="POST" style="display:inline-flex; gap:0.5rem; align-items:center;">
                <input type="hidden" name="action" value="generate_payroll">
                <input type="month" name="payroll_month" value="<?php echo date('Y-m'); ?>" style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                <button type="submit" class="btn" style="background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 6px;">Generate</button>
            </form>
        </h3>
        
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-size: 0.85rem; color: #64748b; text-transform: uppercase;">
                    <th style="padding: 0.75rem;">Staff</th>
                    <th style="padding: 0.75rem;">Base Salary</th>
                    <th style="padding: 0.75rem;">Adjustments</th>
                    <th style="padding: 0.75rem;">Net Pay</th>
                    <th style="padding: 0.75rem;">Status</th>
                    <th style="padding: 0.75rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $cm = date('Y-m');
                if(isset($_POST['payroll_month'])) $cm = $_POST['payroll_month'];
                if(isset($_GET['month'])) $cm = $_GET['month']; // support get param
                
                $pQ = $conn->query("SELECT p.*, u.username FROM payroll p JOIN users u ON p.user_id = u.id WHERE p.month_year = '$cm' AND p.tenant_id = {$_SESSION['tenant_id']} ORDER BY p.id DESC");
                if($pQ && $pQ->num_rows > 0) {
                    while($pr = $pQ->fetch_assoc()) {
                        echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                        echo "<td style='padding: 0.75rem; font-weight:600; color:#0f172a;'>" . htmlspecialchars($pr['username']) . "</td>";
                        
                        echo "<td style='padding: 0.75rem; color:#475569;'>$" . number_format($pr['base_salary'],2) . "</td>";
                        
                        $adj = "";
                        $total_ded = $pr['deductions'] + $pr['manual_deductions'];
                        if($pr['bonuses'] > 0) $adj .= "<span style='color:#10b981; font-weight:600; font-size:0.85rem;'>+$" . number_format($pr['bonuses'],2) . "</span> ";
                        if($total_ded > 0) $adj .= "<span style='color:#ef4444; font-weight:600; font-size:0.85rem;'>-$" . number_format($total_ded,2) . "</span>";
                        if($adj === "") $adj = "<span style='color:#cbd5e1;'>None</span>";
                        
                        echo "<td style='padding: 0.75rem;'>$adj</td>";
                        
                        echo "<td style='padding: 0.75rem; font-weight:bold; color:#166534; font-size:1.1rem;'>$" . number_format($pr['net_salary'],2) . "</td>";
                        
                        echo "<td style='padding: 0.75rem;'>";
                        if($pr['status'] == 'unpaid') {
                            echo "<form method='POST' style='display:inline;'>
                                    <input type='hidden' name='action' value='pay_salary'>
                                    <input type='hidden' name='payroll_id' value='{$pr['id']}'>
                                    <button class='btn' style='background:#f59e0b; color:white; padding:0.3rem 0.6rem; font-size:0.8rem; border-radius:4px;'>Mark Paid</button>
                                  </form>";
                        } else {
                            echo "<span style='color:white; background:#10b981; padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; font-weight:600;'>Paid</span>";
                        }
                        echo "</td>";
                        
                        echo "<td style='padding: 0.75rem;'>
                                <div style='display:flex; gap:0.5rem;'>
                                    <button class='btn' style='background:#f1f5f9; color:#475569; padding:0.3rem 0.6rem; font-size:0.8rem; border-radius:4px;' onclick='openEditModal(" . json_encode($pr) . ")'>Edit</button>
                                    <a href='print_salary_slip.php?id={$pr['id']}' target='_blank' class='btn' style='background:#e0f2fe; color:#0284c7; padding:0.3rem 0.6rem; font-size:0.8rem; border-radius:4px; text-decoration:none;'>Print Slip</a>
                                </div>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='padding: 2rem; text-align:center; color:#94a3b8;'>No payroll generated for $cm.<br>Update attendance and click Generate to calculate salaries.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Edit Payroll Modal -->
        <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
            <div style="background:white; padding:2rem; border-radius:12px; width:400px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1.5rem; color:#0f172a;">Edit Salary Adjustments</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_payroll_manual">
                    <input type="hidden" name="payroll_id" id="modal_payroll_id">
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Base Salary ($)</label>
                        <input type="number" step="0.01" name="base_salary" id="modal_base_salary" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Bonus Amount ($)</label>
                        <input type="number" step="0.01" name="bonuses" id="modal_bonuses" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Bonus Reason</label>
                        <input type="text" name="bonus_reason" id="modal_bonus_reason" placeholder="e.g. Excellent performance" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Manual Deduction ($)</label>
                        <input type="number" step="0.01" name="manual_deductions" id="modal_manual_deductions" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Deduction Reason</label>
                        <input type="text" name="deduction_reason" id="modal_deduction_reason" placeholder="e.g. Broken equipment" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    
                    <div style="display:flex; justify-content:flex-end; gap:1rem;">
                        <button type="button" class="btn" style="background:#f1f5f9; color:#475569;" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn" style="background:#10b981; color:white;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function openEditModal(pr) {
            document.getElementById('modal_payroll_id').value = pr.id;
            document.getElementById('modal_base_salary').value = pr.base_salary;
            document.getElementById('modal_bonuses').value = pr.bonuses;
            document.getElementById('modal_bonus_reason').value = pr.bonus_reason || '';
            document.getElementById('modal_manual_deductions').value = pr.manual_deductions;
            document.getElementById('modal_deduction_reason').value = pr.deduction_reason || '';
            document.getElementById('editModal').style.display = 'flex';
        }
        </script>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
