<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$current_branch_id = $_SESSION['branch_id'] ?? 1;
include 'includes/db.php';
include 'includes/header.php';

if (!$isAdmin) {
    die("<div class='container'><h2>Access Denied</h2><p>Only administrators can manage staff.</p></div>");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, branch_id, tenant_id) VALUES (?, ?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt->bind_param("sssi", $_POST['username'], $hash, $_POST['role'], $_POST['branch_id']);
        $stmt->execute();
    } elseif ($_POST['action'] == 'edit') {
        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=?, branch_id=? WHERE id=? AND tenant_id={$_SESSION['tenant_id']}");
            $stmt->bind_param("sssii", $_POST['username'], $hash, $_POST['role'], $_POST['branch_id'], $_POST['id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, role=?, branch_id=? WHERE id=? AND tenant_id={$_SESSION['tenant_id']}");
            $stmt->bind_param("ssii", $_POST['username'], $_POST['role'], $_POST['branch_id'], $_POST['id']);
        }
        $stmt->execute();
    } elseif ($_POST['action'] == 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND tenant_id={$_SESSION['tenant_id']}");
        $stmt->bind_param("i", $_POST['id']);
        $stmt->execute();
    }
    echo "<script>window.location.href='staff.php';</script>";
    exit;
}

$users = $conn->query("
    SELECT u.id, u.username, u.role, b.name as branch_name, b.id as branch_id
    FROM users u LEFT JOIN branches b ON u.branch_id = b.id WHERE u.tenant_id = {$_SESSION['tenant_id']} AND u.role != 'super_admin' 
    ORDER BY u.id ASC
");
$branchesList = $conn->query("SELECT id, name FROM branches WHERE tenant_id = {$_SESSION['tenant_id']}");
$branches = [];
while($b = $branchesList->fetch_assoc()) {
    $branches[] = $b;
}
?>

<div class="container" style="padding-top: 1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 0.2rem;">Staff Management</h1>
            <div style="font-size: 0.95rem; color: #64748b;">Manage user accounts, roles, and branch assignments.</div>
        </div>
        <button class="btn btn-primary" style="width: auto; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 4px rgba(37,99,235,0.2);" onclick="openModal('add')">+ Add User</button>
    </div>

    <div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; background: #ffffff;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <tr>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">ID</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Username</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Role</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Assigned Branch</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                
                <?php while($u = $users->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">#<?php echo $u['id']; ?></td>
                    <td style="padding: 1rem; font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($u['username']); ?></td>
                    <td style="padding: 1rem;">
                        <span style="background: <?php echo $u['role'] === 'admin' ? '#fee2e2' : ($u['role'] === 'manager' ? '#fef3c7' : '#e0e7ff'); ?>; color: <?php echo $u['role'] === 'admin' ? '#991b1b' : ($u['role'] === 'manager' ? '#92400e' : '#3730a3'); ?>; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                            <?php echo htmlspecialchars($u['role']); ?>
                        </span>
                    </td>
                    <td style="padding: 1rem; color: #475569; font-size: 0.9rem;">🏢 <?php echo htmlspecialchars($u['branch_name'] ?? 'None'); ?></td>
                    <td style="padding: 1rem;">
                        <button class="btn action-btn btn-edit" onclick='openModal("edit", <?php echo json_encode($u); ?>)'>✏️ Edit</button>
                        <?php if($u['role'] !== 'admin' || $u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                            <button type="submit" class="btn action-btn btn-delete">🗑️</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="staffModal" class="modal">
    <div class="modal-content" style="max-width: 500px; border-radius: 12px;">
        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.5rem;">
            <h2 id="modalTitle" style="margin: 0; font-size: 1.25rem; color: #1e293b;">Add Staff</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Username</label>
                <input type="text" name="username" id="modalUsername" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Password <small id="pwdHint" style="color: #94a3b8; font-weight: normal;"></small></label>
                <input type="password" name="password" id="modalPassword" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Role</label>
                <select name="role" id="modalRole" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <option value="cashier">Cashier</option>
                    <option value="manager">Branch Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Assign Branch</label>
                <select name="branch_id" id="modalBranch" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <?php foreach($branches as $b): ?>
                        <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn" style="background: #f1f5f9; color: #475569;" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Save Staff</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(action, data = null) {
    document.getElementById('staffModal').style.display = 'flex';
    document.getElementById('modalAction').value = action;
    
    if (action === 'edit' && data) {
        document.getElementById('modalTitle').innerText = 'Edit Staff';
        document.getElementById('modalId').value = data.id;
        document.getElementById('modalUsername').value = data.username;
        document.getElementById('modalRole').value = data.role;
        document.getElementById('modalBranch').value = data.branch_id;
        document.getElementById('modalPassword').required = false;
        document.getElementById('pwdHint').innerText = '(Leave blank to keep unchanged)';
        document.getElementById('modalSubmitBtn').innerText = 'Update Staff';
    } else {
        document.getElementById('modalTitle').innerText = 'Add Staff';
        document.getElementById('modalId').value = '';
        document.getElementById('modalUsername').value = '';
        document.getElementById('modalRole').value = 'cashier';
        document.getElementById('modalPassword').required = true;
        document.getElementById('pwdHint').innerText = '';
        document.getElementById('modalSubmitBtn').innerText = 'Save Staff';
    }
}
function closeModal() {
    document.getElementById('staffModal').style.display = 'none';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('staffModal')) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>



