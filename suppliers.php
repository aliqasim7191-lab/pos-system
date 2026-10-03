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
        $name = trim($_POST['name']);
        $contact = trim($_POST['contact_person']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $payable = floatval($_POST['outstanding_payable']);
        
        $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, outstanding_payable, branch_id, tenant_id) VALUES (?, ?, ?, ?, ?, ?, $current_branch_id, {$_SESSION['tenant_id']})");
        $stmt->bind_param("sssssd", $name, $contact, $phone, $email, $address, $payable);
        if ($stmt->execute()) {
            $message = "Supplier added successfully.";
        } else {
            $message = "Error adding supplier.";
        }
        $stmt->close();
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $contact = trim($_POST['contact_person']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $payable = floatval($_POST['outstanding_payable']);
        
        $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, outstanding_payable=? WHERE id=?");
        $stmt->bind_param("sssssdi", $name, $contact, $phone, $email, $address, $payable, $id);
        if ($stmt->execute()) {
            $message = "Supplier updated successfully.";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        try {
            // Unlink purchases and purchase orders from this supplier to avoid foreign key constraints
            $conn->query("UPDATE purchases SET supplier_id = NULL WHERE supplier_id = $id AND tenant_id = {$_SESSION['tenant_id']}");
            $conn->query("UPDATE purchase_orders SET supplier_id = NULL WHERE supplier_id = $id AND tenant_id = {$_SESSION['tenant_id']}");
            
            $stmt = $conn->prepare("DELETE FROM suppliers WHERE id=? AND tenant_id=?");
            $stmt->bind_param("ii", $id, $_SESSION['tenant_id']);
            if ($stmt->execute()) {
                $message = "Supplier deleted successfully.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Cannot delete supplier: " . $e->getMessage();
        }
    } elseif ($action === 'pay_balance') {
        $id = intval($_POST['id']);
        $amount = floatval($_POST['payment_amount']);
        
        $conn->begin_transaction();
        try {
            // Deduct from supplier
            $stmt = $conn->prepare("UPDATE suppliers SET outstanding_payable = outstanding_payable - ? WHERE id=?");
            $stmt->bind_param("di", $amount, $id);
            $stmt->execute();
            $stmt->close();
            
            // Always insert a new record for the payment so it correctly hits TODAY's cash drawer (is_cleared = 0)
            $userId = $_SESSION['user_id'] ?? 1;
            $stmt = $conn->prepare("INSERT INTO purchases (supplier_id, user_id, total_amount, amount_paid, status, is_cleared, tenant_id) VALUES (?, ?, 0, ?, 'received', 0, {$_SESSION['tenant_id']})");
            $stmt->bind_param("iid", $id, $userId, $amount);
            $stmt->execute();
            $lastUpdatedPO = $stmt->insert_id;
            $stmt->close();
            
            // Allocate payment to unpaid purchase orders (FIFO) visually only (don't break reports)
            // Wait, if reports sum amount_paid across ALL purchases, updating old POs AND inserting a new payment record will double-count the payment in reports!
            // Therefore, we MUST NOT update old POs. The global outstanding_payable handles the balance.

            
            $conn->commit();
            $message = "Supplier payment recorded successfully. <script>window.open('purchase_receipt.php?id=" . $lastUpdatedPO . "&source=supplier', '_blank');</script>";
        } catch(Exception $e) {
            $conn->rollback();
            $message = "Error recording payment.";
        }
    } elseif ($action === 'clear_history') {
        $id = intval($_POST['id']);
        $conn->begin_transaction();
        try {
            // Delete purchase items for cleared purchases
            $conn->query("DELETE pi FROM purchase_items pi JOIN purchases p ON pi.purchase_id = p.id WHERE p.supplier_id = $id AND p.is_cleared = 1");
            // Delete cleared purchases
            $conn->query("DELETE FROM purchases WHERE supplier_id = $id AND is_cleared = 1");
            $conn->commit();
            $message = "Historical cleared data for this supplier has been purged.";
        } catch(Exception $e) {
            $conn->rollback();
            $message = "Error clearing history.";
        }
    }
}

$result = $conn->query("SELECT * FROM suppliers WHERE tenant_id = {$_SESSION['tenant_id']} ORDER BY name ASC");
$suppliers = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0 0 0.25rem 0;">Supplier Management</h2>
        <p style="color: #64748b; margin: 0; font-size: 0.95rem;">Manage distributors, wholesalers, and outstanding accounts payable.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: nowrap;">
        <a href="purchases.php?history=1" style="background: #ffffff; color: #334155; border: 1px solid #cbd5e1; padding: 0 1.25rem; height: 42px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s ease;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)';">
            📚 View Payment History
        </a>
        <button onclick="openAddModal()" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; border: none; padding: 0 1.35rem; height: 42px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(37,99,235,0.25); transition: all 0.2s ease;" onmouseover="this.style.background='linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(37,99,235,0.35)';" onmouseout="this.style.background='linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.25)';">
            + Add New Supplier
        </button>
    </div>
</div>

<?php if($message): ?>
    <div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if(isset($error) && $error): ?>
    <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
    <table class="data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                <th style="padding: 1rem;">ID</th>
                <th style="padding: 1rem;">Supplier Name</th>
                <th style="padding: 1rem;">Contact Person</th>
                <th style="padding: 1rem;">Phone</th>
                <th style="padding: 1rem;">Outstanding Payable</th>
                <th style="padding: 1rem; text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($suppliers as $supplier): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 1rem;"><?php echo $supplier['id']; ?></td>
                    <td style="padding: 1rem; font-weight: 500;"><?php echo htmlspecialchars($supplier['name']); ?></td>
                    <td style="padding: 1rem;"><?php echo htmlspecialchars($supplier['contact_person'] ?? '-'); ?></td>
                    <td style="padding: 1rem;"><?php echo htmlspecialchars($supplier['phone'] ?? '-'); ?></td>
                    <td style="padding: 1rem; font-weight: bold; color: <?php echo $supplier['outstanding_payable'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>;">
                        $<?php echo number_format($supplier['outstanding_payable'], 2); ?>
                    </td>
                    <td style="padding: 1rem; text-align:right; display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                        <?php if($supplier['outstanding_payable'] > 0): ?>
                            <button class="btn action-btn btn-pay" onclick="openPaymentModal(<?php echo $supplier['id']; ?>, '<?php echo addslashes($supplier['name']); ?>', <?php echo $supplier['outstanding_payable']; ?>)">💲 Pay</button>
                        <?php else: ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to clear ALL past cleared purchase history for this supplier? This will remove old data from reports.');" style="display:inline; margin:0;">
                                <input type="hidden" name="action" value="clear_history">
                                <input type="hidden" name="id" value="<?php echo $supplier['id']; ?>">
                                <button type="submit" class="btn action-btn btn-clear" title="Balance is 0. You can clear history.">🧹 Clear</button>
                            </form>
                        <?php endif; ?>
                        
                        <button class="btn action-btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($supplier)); ?>)">✎ Edit</button>
                        
                        <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Delete this supplier?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $supplier['id']; ?>">
                            <button type="submit" class="btn action-btn btn-delete">🗑 Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($suppliers)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 4rem; color: #64748b;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🏢</div>
                        <h3 style="margin: 0; color: #0f172a; font-size: 1.2rem;">No Suppliers Found</h3>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem;">You haven't added any suppliers to your database.</p>
                        <button class="btn btn-primary" style="margin-top: 1.5rem;" onclick="openAddModal()">+ Add Supplier</button>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div id="supplierModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 500px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 id="modalTitle" style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Add Supplier</h3>
        
        <form method="POST">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId" value="">
            
            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Supplier / Company Name *</label>
                <input type="text" name="name" id="modalName" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>
            
            <div style="margin-bottom: 1rem; display:flex; gap: 1rem;">
                <div style="flex:1;">
                    <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Contact Person</label>
                    <input type="text" name="contact_person" id="modalContact" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Phone Number</label>
                    <input type="text" name="phone" id="modalPhone" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Email Address</label>
                <input type="email" name="email" id="modalEmail" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Full Address</label>
                <input type="text" name="address" id="modalAddress" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Initial Outstanding Payable ($)</label>
                <input type="number" step="0.01" name="outstanding_payable" id="modalPayable" value="0" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>

            <div style="display:flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Supplier</button>
                <button type="button" class="btn" style="flex: 1; background:#e2e8f0; color:var(--text-main);" onclick="document.getElementById('supplierModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Settle Balance</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="pay_balance">
            <input type="hidden" name="id" id="paySupplierId" value="">
            
            <p style="margin-bottom: 1rem; color: var(--text-muted);">Supplier: <strong id="paySupplierName" style="color: var(--text-main);"></strong></p>
            <p style="margin-bottom: 1rem; color: var(--text-muted);">Current Balance: <strong id="payCurrentBalance" style="color: var(--danger);"></strong></p>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Amount to Pay ($)</label>
                <input type="number" step="0.01" name="payment_amount" id="payAmountInput" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                <small style="color: var(--text-muted);">This will be recorded in the EOD as cash out.</small>
            </div>

            <div style="display:flex; gap: 1rem;">
                <button type="submit" class="btn" style="flex: 1; background: var(--success); color: white;">Record Payment</button>
                <button type="button" class="btn" style="flex: 1; background:#e2e8f0; color:var(--text-main);" onclick="document.getElementById('paymentModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(id, name, balance) {
    document.getElementById('paySupplierId').value = id;
    document.getElementById('paySupplierName').innerText = name;
    document.getElementById('payCurrentBalance').innerText = '$' + parseFloat(balance).toFixed(2);
    document.getElementById('payAmountInput').value = parseFloat(balance).toFixed(2);
    document.getElementById('payAmountInput').max = parseFloat(balance).toFixed(2);
    document.getElementById('paymentModal').style.display = 'flex';
}

function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add New Supplier';
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalId').value = '';
    document.getElementById('modalName').value = '';
    document.getElementById('modalContact').value = '';
    document.getElementById('modalPhone').value = '';
    document.getElementById('modalEmail').value = '';
    document.getElementById('modalAddress').value = '';
    document.getElementById('modalPayable').value = '0';
    document.getElementById('supplierModal').style.display = 'flex';
}

function openEditModal(supplier) {
    document.getElementById('modalTitle').innerText = 'Edit Supplier';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = supplier.id;
    document.getElementById('modalName').value = supplier.name;
    document.getElementById('modalContact').value = supplier.contact_person || '';
    document.getElementById('modalPhone').value = supplier.phone || '';
    document.getElementById('modalEmail').value = supplier.email || '';
    document.getElementById('modalAddress').value = supplier.address || '';
    document.getElementById('modalPayable').value = supplier.outstanding_payable;
    document.getElementById('supplierModal').style.display = 'flex';
}
</script>

<?php include 'includes/footer.php'; ?>
