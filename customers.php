<?php
include 'includes/db.php';
include 'includes/header.php';

// Branch Filters
$bF_WHERE = $isAdmin ? " WHERE tenant_id = {$_SESSION['tenant_id']}" : " WHERE tenant_id = {$_SESSION['tenant_id']} AND branch_id = $current_branch_id";
$bF_AND = $isAdmin ? " AND tenant_id = {$_SESSION['tenant_id']}" : " AND tenant_id = {$_SESSION['tenant_id']} AND branch_id = $current_branch_id";


if (!isset($isAdmin) || !$isAdmin) {
    die("<div class='container'><h2>Access Denied</h2><p>Only administrators can manage customers.</p></div>");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $stmt = $conn->prepare("INSERT INTO customers (name, phone, email, address, customer_type, credit_limit, outstanding_balance, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt->bind_param("sssssdd", $_POST['name'], $_POST['phone'], $_POST['email'], $_POST['address'], $_POST['customer_type'], $_POST['credit_limit'], $_POST['outstanding_balance']);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] == 'edit') {
        $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, email=?, address=?, customer_type=?, credit_limit=?, outstanding_balance=? WHERE id=? AND tenant_id=?");
        $stmt->bind_param("sssssddii", $_POST['name'], $_POST['phone'], $_POST['email'], $_POST['address'], $_POST['customer_type'], $_POST['credit_limit'], $_POST['outstanding_balance'], $_POST['id'], $_SESSION['tenant_id']);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] == 'delete') {
        $c_id = (int)$_POST['id'];
        
        try {
            $conn->query("DELETE FROM customer_ledger WHERE customer_id = $c_id AND tenant_id = {$_SESSION['tenant_id']}");
            $conn->query("UPDATE sales SET customer_id = NULL WHERE customer_id = $c_id AND tenant_id = {$_SESSION['tenant_id']}");
            
            $stmt = $conn->prepare("DELETE FROM customers WHERE id=? AND tenant_id=?");
            $stmt->bind_param("ii", $c_id, $_SESSION['tenant_id']);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            die("Delete failed: " . $e->getMessage());
        }
    }
    echo "<script>window.location.href='customers.php';</script>";
    exit;
}

$customers = $conn->query("
    SELECT c.*, 
           COALESCE(SUM(s.total_amount), 0) as total_spent,
           COUNT(s.id) as total_orders,
           MAX(s.created_at) as last_purchase
    FROM customers c 
    LEFT JOIN sales s ON c.name = s.customer_name AND s.tenant_id = {$_SESSION['tenant_id']}
    WHERE c.tenant_id = {$_SESSION['tenant_id']}
    GROUP BY c.id 
    ORDER BY c.name ASC
");
?>

<div class="container" style="padding-top: 1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 0.2rem;">Customers</h1>
            <div style="font-size: 0.95rem; color: #64748b;">Manage your clients, credit limits, and balances.</div>
        </div>
        <button class="btn btn-primary" style="width: auto; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 4px rgba(37,99,235,0.2);" onclick="openModal('add')">+ Add Customer</button>
    </div>

    <div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow-x: auto; overflow-y: hidden; background: #ffffff;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <tr>
                    <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">ID</th>
                    <th style="padding: 0.65rem 0.5rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Name</th>
                    <th style="padding: 0.65rem 0.5rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Contact</th>
                    <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Type</th>
                    <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Tier</th>
                    <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Total Spent</th>
                    <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">AOV (Avg)</th>
                    <th style="padding: 0.65rem 0.5rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Last Purchase</th>
                    <th style="padding: 0.65rem 0.5rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Balance</th>
                    <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($c = $customers->fetch_assoc()): 
                    // Calculate CRM Tier
                    $tier = 'Bronze';
                    $tColor = '#d97706'; // bronze/orange
                    $tBg = '#fef3c7';
                    if ($c['total_spent'] >= 5000) { $tier = 'Platinum'; $tColor = '#1e3a8a'; $tBg = '#dbeafe'; }
                    elseif ($c['total_spent'] >= 2000) { $tier = 'Gold'; $tColor = '#ca8a04'; $tBg = '#fef08a'; }
                    elseif ($c['total_spent'] >= 500) { $tier = 'Silver'; $tColor = '#475569'; $tBg = '#f1f5f9'; }
                    
                    $aov = $c['total_orders'] > 0 ? $c['total_spent'] / $c['total_orders'] : 0;
                ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                    <td style="padding: 0.6rem 0.4rem; text-align: center; color: #64748b; font-size: 0.82rem; white-space: nowrap;">#<?php echo $c['id']; ?></td>
                    <td style="padding: 0.6rem 0.5rem; font-weight: 600; color: #0f172a; font-size: 0.85rem; white-space: nowrap;"><?php echo htmlspecialchars($c['name']); ?></td>
                    <td style="padding: 0.6rem 0.5rem; white-space: nowrap;">
                        <div style="color: #0f172a; font-size: 0.82rem; font-weight: 500;"><?php echo htmlspecialchars($c['phone']); ?></div>
                        <div style="color: #64748b; font-size: 0.75rem;"><?php echo htmlspecialchars($c['email']); ?></div>
                    </td>
                    <td style="padding: 0.6rem 0.4rem; text-align: center; white-space: nowrap;">
                        <span style="background: #f1f5f9; color: #475569; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">
                            <?php echo $c['customer_type']; ?>
                        </span>
                    </td>
                    <td style="padding: 0.6rem 0.4rem; text-align: center; white-space: nowrap;">
                        <span style="background: <?php echo $tBg; ?>; color: <?php echo $tColor; ?>; padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                            <?php echo $tier; ?>
                        </span>
                    </td>
                    <td style="padding: 0.6rem 0.5rem; text-align: right; font-weight: 600; color: #0f172a; font-size: 0.85rem; white-space: nowrap;">$<?php echo number_format($c['total_spent'], 2); ?></td>
                    <td style="padding: 0.6rem 0.5rem; text-align: right; color: #475569; font-size: 0.85rem; white-space: nowrap;">$<?php echo number_format($aov, 2); ?></td>
                    <td style="padding: 0.6rem 0.5rem; text-align: center; color: #64748b; font-size: 0.8rem; white-space: nowrap;">
                        <?php echo $c['last_purchase'] ? date('M d, Y', strtotime($c['last_purchase'])) : 'Never'; ?>
                    </td>
                    <td style="padding: 0.6rem 0.5rem; text-align: center; font-weight: bold; white-space: nowrap;">
                        <?php if($c['outstanding_balance'] > 0): ?>
                            <div style="color: #ef4444; font-size: 0.85rem;">$<?php echo number_format($c['outstanding_balance'], 2); ?></div>
                            <div style="font-size: 0.7rem; background: #fee2e2; color: #b91c1c; padding: 1px 5px; border-radius: 4px; display: inline-block; margin-top: 2px;">⚠️ Due</div>
                        <?php elseif($c['outstanding_balance'] < 0): ?>
                            <div style="color: #10b981; font-size: 0.85rem;">+$<?php echo number_format(abs($c['outstanding_balance']), 2); ?></div>
                            <div style="font-size: 0.7rem; background: #dcfce7; color: #15803d; padding: 1px 5px; border-radius: 4px; display: inline-block; margin-top: 2px;">💰 Deposit</div>
                        <?php else: ?>
                            <div style="color: #94a3b8; font-size: 0.85rem;">$0.00</div>
                            <div style="font-size: 0.7rem; background: #f8fafc; color: #64748b; padding: 1px 5px; border-radius: 4px; display: inline-block; margin-top: 2px;">✅ Settled</div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.6rem 0.4rem; text-align: right; white-space: nowrap;">
                        <div style="display:flex; gap:0.35rem; justify-content:flex-end; align-items: center;">
                            <a href="customer_ledger.php?id=<?php echo $c['id']; ?>" class="btn action-btn btn-clear" style="text-decoration:none; padding: 0.35rem 0.6rem !important; font-size: 0.78rem !important;">Ledger</a>
                            <button onclick='openEdit(<?php echo htmlspecialchars(json_encode($c)); ?>)' class="btn action-btn btn-edit" style="padding: 0.35rem 0.6rem !important; font-size: 0.78rem !important;">Edit</button>
                            <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Delete this customer?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" class="btn action-btn btn-delete" style="padding: 0.35rem 0.6rem !important; font-size: 0.78rem !important;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($customers->num_rows == 0): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 4rem; color: #64748b;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                        <h3 style="margin: 0; color: #0f172a; font-size: 1.2rem;">No Customers Yet</h3>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem;">You haven't added any customers to your database.</p>
                        <button class="btn btn-primary" style="margin-top: 1.5rem;" onclick="openModal('add')">+ Add Customer</button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="customerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding: 2rem; border-radius: 12px; width: 500px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 id="modalTitle" style="margin-bottom: 1.5rem;">Add Customer</h3>
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="customerId" value="">
            
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Full Name *</label>
                <input type="text" name="name" id="cName" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Phone</label>
                    <input type="text" name="phone" id="cPhone" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Email</label>
                    <input type="email" name="email" id="cEmail" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Address</label>
                <input type="text" name="address" id="cAddress" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Customer Type</label>
                    <select name="customer_type" id="cType" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px;">
                        <option value="walk_in">Walk In</option>
                        <option value="registered">Registered</option>
                        <option value="credit">Credit / B2B</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Credit Limit ($)</label>
                    <input type="number" step="0.01" name="credit_limit" id="cLimit" value="0.00" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Initial / Outstanding Balance ($)</label>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">(Positive = Owe You, Negative = Advance Deposit)</div>
                <input type="number" step="0.01" name="outstanding_balance" id="cBalance" value="0.00" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap: 1rem;">
                <button type="button" class="btn" style="width:auto; background:#e2e8f0; color:var(--text-main);" onclick="document.getElementById('customerModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="width:auto;">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode) {
    document.getElementById('customerModal').style.display = 'flex';
    if(mode === 'add') {
        document.getElementById('modalTitle').innerText = 'Add Customer';
        document.getElementById('formAction').value = 'add';
        document.getElementById('customerId').value = '';
        document.getElementById('cName').value = '';
        document.getElementById('cPhone').value = '';
        document.getElementById('cEmail').value = '';
        document.getElementById('cAddress').value = '';
        document.getElementById('cType').value = 'registered';
        document.getElementById('cLimit').value = '0.00';
        document.getElementById('cBalance').value = '0.00';
    }
}

function openEdit(customer) {
    document.getElementById('customerModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Edit Customer';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('customerId').value = customer.id;
    document.getElementById('cName').value = customer.name;
    document.getElementById('cPhone').value = customer.phone;
    document.getElementById('cEmail').value = customer.email;
    document.getElementById('cAddress').value = customer.address;
    document.getElementById('cType').value = customer.customer_type;
    document.getElementById('cLimit').value = customer.credit_limit;
    document.getElementById('cBalance').value = customer.outstanding_balance;
}
</script>

<?php include 'includes/footer.php'; ?>
