<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset($isAdmin) || !$isAdmin) {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Only administrators can manage employees.</p></div>";
    include 'includes/footer.php';
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        // Assume default password '123456' for new users if none is provided, or require it
        $password = password_hash($_POST['password'] ?: '123456', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $_POST['username'], $password, $_POST['role']);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] == 'edit') {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $_POST['username'], $password, $_POST['role'], $_POST['id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
            $stmt->bind_param("ssi", $_POST['username'], $_POST['role'], $_POST['id']);
        }
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] == 'delete') {
        // Prevent deleting oneself
        if ($_POST['id'] != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo "<script>window.location.href='employees.php';</script>";
    exit;
}

$users = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC");
?>

<div class="container" style="padding-top: 1rem; max-width: 1000px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 0.2rem;">Employees & Users</h1>
            <div style="font-size: 0.95rem; color: #64748b;">Manage system access, roles, and employee accounts.</div>
        </div>
        <button class="btn btn-primary" style="width: auto; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 4px rgba(37,99,235,0.2);" onclick="openModal('add')">+ Add Employee</button>
    </div>

    <div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; background: #ffffff;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <tr>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">ID</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Username</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Role</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Date Created</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($u = $users->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                    <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">#<?php echo $u['id']; ?></td>
                    <td style="padding: 1rem;">
                        <div style="font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 32px; height: 32px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem;">👤</div>
                            <?php echo htmlspecialchars($u['username']); ?>
                        </div>
                    </td>
                    <td style="padding: 1rem;">
                        <?php if($u['role'] === 'admin'): ?>
                            <span style="background: #dbeafe; color: #1e40af; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Admin</span>
                        <?php else: ?>
                            <span style="background: #f1f5f9; color: #475569; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Cashier</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">
                        <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display:flex; gap:0.5rem;">
                            <button onclick='openEdit(<?php echo htmlspecialchars(json_encode($u)); ?>)' class="btn action-btn btn-edit">Edit</button>
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Are you sure you want to remove this employee account?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn action-btn btn-delete">Delete</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($users->num_rows == 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: #64748b;">No employees found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Employee Modal -->
<div id="employeeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 id="modalTitle" style="margin-bottom: 1.5rem; font-size: 1.4rem; color: #0f172a;">Add Employee</h3>
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="employeeId" value="">
            
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Username *</label>
                <input type="text" name="username" id="empUsername" required style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Password <span id="pwdHint" style="font-size: 0.8rem; font-weight: normal; color: #94a3b8;"></span></label>
                <input type="password" name="password" id="empPassword" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Role *</label>
                <select name="role" id="empRole" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
                    <option value="cashier">Cashier (POS Only)</option>
                    <option value="admin">Admin (Full Access)</option>
                </select>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap: 1rem;">
                <button type="button" class="btn" style="width:auto; background:#f1f5f9; color:#475569; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 500;" onclick="document.getElementById('employeeModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="width:auto; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600;">Save Employee</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode) {
    document.getElementById('employeeModal').style.display = 'flex';
    if(mode === 'add') {
        document.getElementById('modalTitle').innerText = 'Add Employee';
        document.getElementById('formAction').value = 'add';
        document.getElementById('employeeId').value = '';
        document.getElementById('empUsername').value = '';
        document.getElementById('empPassword').value = '';
        document.getElementById('empPassword').required = true;
        document.getElementById('pwdHint').innerText = '(Required)';
        document.getElementById('empRole').value = 'cashier';
    }
}

function openEdit(emp) {
    document.getElementById('employeeModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Edit Employee';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('employeeId').value = emp.id;
    document.getElementById('empUsername').value = emp.username;
    document.getElementById('empPassword').value = '';
    document.getElementById('empPassword').required = false;
    document.getElementById('pwdHint').innerText = '(Leave empty to keep current)';
    document.getElementById('empRole').value = emp.role;
}
</script>

<?php include 'includes/footer.php'; ?>
