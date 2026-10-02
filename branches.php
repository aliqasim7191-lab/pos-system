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
    die("<div class='container'><h2>Access Denied</h2><p>Only administrators can manage branches.</p></div>");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $stmt = $conn->prepare("INSERT INTO branches (name, address, phone, tenant_id) VALUES (?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt->bind_param("sss", $_POST['name'], $_POST['address'], $_POST['phone']);
        $stmt->execute();
    } elseif ($_POST['action'] == 'edit') {
        $stmt = $conn->prepare("UPDATE branches SET name=?, address=?, phone=? WHERE id=? AND tenant_id={$_SESSION['tenant_id']}");
        $stmt->bind_param("sssi", $_POST['name'], $_POST['address'], $_POST['phone'], $_POST['id']);
        $stmt->execute();
    }
    echo "<script>window.location.href='branches.php';</script>";
    exit;
}

$branches = $conn->query("SELECT * FROM branches WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY id ASC");
?>

<div class="container" style="padding-top: 1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 0.2rem;">Branches</h1>
            <div style="font-size: 0.95rem; color: #64748b;">Manage multiple stores and locations.</div>
        </div>
        <button class="btn btn-primary" style="width: auto; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 4px rgba(37,99,235,0.2);" onclick="openModal('add')">+ Add Branch</button>
    </div>

    <div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; background: #ffffff;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <tr>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">ID</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Branch Name</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Address</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Phone</th>
                    <th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                
                <?php while($b = $branches->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">#<?php echo $b['id']; ?></td>
                    <td style="padding: 1rem; font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($b['name']); ?></td>
                    <td style="padding: 1rem; color: #475569; font-size: 0.9rem;"><?php echo htmlspecialchars($b['address']); ?></td>
                    <td style="padding: 1rem; color: #475569; font-size: 0.9rem;"><?php echo htmlspecialchars($b['phone']); ?></td>
                    <td style="padding: 1rem;">
                        <button class="btn action-btn btn-edit" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($b)); ?>)">✏️ Edit</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="branchModal" class="modal">
    <div class="modal-content" style="max-width: 500px; border-radius: 12px;">
        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.5rem;">
            <h2 id="modalTitle" style="margin: 0; font-size: 1.25rem; color: #1e293b;">Add Branch</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Branch Name</label>
                <input type="text" name="name" id="modalName" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Address</label>
                <input type="text" name="address" id="modalAddress" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Phone</label>
                <input type="text" name="phone" id="modalPhone" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn" style="background: #f1f5f9; color: #475569;" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Save Branch</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(action, data = null) {
    document.getElementById('branchModal').style.display = 'flex';
    document.getElementById('modalAction').value = action;
    
    if (action === 'edit' && data) {
        document.getElementById('modalTitle').innerText = 'Edit Branch';
        document.getElementById('modalId').value = data.id;
        document.getElementById('modalName').value = data.name;
        document.getElementById('modalAddress').value = data.address;
        document.getElementById('modalPhone').value = data.phone;
        document.getElementById('modalSubmitBtn').innerText = 'Update Branch';
    } else {
        document.getElementById('modalTitle').innerText = 'Add Branch';
        document.getElementById('modalId').value = '';
        document.getElementById('modalName').value = '';
        document.getElementById('modalAddress').value = '';
        document.getElementById('modalPhone').value = '';
        document.getElementById('modalSubmitBtn').innerText = 'Save Branch';
    }
}
function closeModal() {
    document.getElementById('branchModal').style.display = 'none';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('branchModal')) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>



