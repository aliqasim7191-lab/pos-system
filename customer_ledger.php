<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset($_GET['id'])) {
    die("<div class='container'><h2>Error</h2><p>No customer selected.</p></div>");
}

$id = intval($_GET['id']);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_payment') {
    $amount = floatval($_POST['amount']);
    $desc = trim($_POST['description']) ?: 'Payment Received';
    
    if ($amount > 0) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE customers SET outstanding_balance = outstanding_balance - ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $id);
            $stmt->execute();
            $stmt->close();
            
            $newBalQ = $conn->query("SELECT outstanding_balance FROM customers WHERE id = $id AND tenant_id = {$_SESSION['tenant_id']} LIMIT 1");
            $newBal = $newBalQ->fetch_assoc()['outstanding_balance'];
            
            $type = 'payment_received';
            if ($newBal < 0 && ($newBal + $amount) <= 0) {
                $type = 'advance_deposit';
            }
            $stmtLedger = $conn->prepare("INSERT INTO customer_ledger (customer_id, type, amount, balance_after, description, tenant_id) VALUES (?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
            $stmtLedger->bind_param("isdds", $id, $type, $amount, $newBal, $desc);
            $stmtLedger->execute();
            $stmtLedger->close();
            
            $conn->commit();
            $message = "Payment added successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_payment') {
    $ledgerId = intval($_POST['ledger_id']);
    
    // Fetch ledger entry
    $lQ = $conn->query("SELECT * FROM customer_ledger WHERE id = $ledgerId AND tenant_id = {$_SESSION['tenant_id']} AND type IN ('payment_received', 'advance_deposit') LIMIT 1");
    if ($lQ && $lQ->num_rows > 0) {
        $lRow = $lQ->fetch_assoc();
        $amt = floatval($lRow['amount']);
        
        $conn->begin_transaction();
        try {
            // Reverse the payment from customer balance
            $stmt = $conn->prepare("UPDATE customers SET outstanding_balance = outstanding_balance + ? WHERE id = ?");
            $stmt->bind_param("di", $amt, $id);
            $stmt->execute();
            $stmt->close();
            
            // Delete the ledger entry
            $conn->query("DELETE FROM customer_ledger WHERE id = $ledgerId");
            
            $conn->commit();
            $message = "Payment record deleted and balance reversed successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error deleting payment: " . $e->getMessage();
        }
    }
}

$custRes = $conn->query("SELECT * FROM customers WHERE id = $id AND tenant_id = {$_SESSION['tenant_id']} LIMIT 1");
$customer = $custRes->fetch_assoc();

if (!$customer) {
    die("<div class='container'><h2>Error</h2><p>Customer not found.</p></div>");
}

$showHistory = isset($_GET['history']) && $_GET['history'] == 1;
$historyFilter = $showHistory ? "" : "AND cl.is_cleared = 0";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive_all') {
    $conn->query("UPDATE customer_ledger SET is_cleared = 1 WHERE customer_id = $id AND is_cleared = 0");
    echo "<script>window.location.href='customer_ledger.php?id=$id';</script>";
    exit;
}

$ledger = [];
$res = $conn->query("SELECT cl.*, s.taken_by, s.total_amount as sale_total, (SELECT GROUP_CONCAT(CONCAT(si.quantity, 'x ', p.name) SEPARATOR ', ') FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = cl.sale_id) as items_summary FROM customer_ledger cl LEFT JOIN sales s ON cl.sale_id = s.id WHERE cl.customer_id = $id AND cl.tenant_id = {$_SESSION['tenant_id']} $historyFilter ORDER BY cl.created_at DESC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $ledger[] = $row;
    }
}
?>

<div class="container" style="padding-top: 1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2rem; color: var(--text-main);"><?php echo htmlspecialchars($customer['name']); ?> - Ledger</h1>
            <div style="font-size: 0.9rem; color: var(--text-muted);">Phone: <?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?> | Type: <?php echo strtoupper($customer['customer_type']); ?></div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.9rem; color: var(--text-muted);">Current Balance</div>
            <div style="font-size: 2rem; font-weight: bold; color: <?php echo $customer['outstanding_balance'] > 0 ? 'var(--danger)' : ($customer['outstanding_balance'] < 0 ? 'var(--success)' : 'var(--text-main)'); ?>;">
                <?php 
                    if ($customer['outstanding_balance'] < 0) {
                        echo "$" . number_format(abs($customer['outstanding_balance']), 2) . " <span style='font-size:1rem; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:8px; vertical-align:middle;'>💰 Advance Deposit</span>";
                    } elseif ($customer['outstanding_balance'] > 0) {
                        echo "$" . number_format($customer['outstanding_balance'], 2) . " <span style='font-size:1rem; background:#fee2e2; color:#b91c1c; padding:2px 8px; border-radius:8px; vertical-align:middle;'>⚠️ Payment Due</span>";
                    } else {
                        echo "$0.00 <span style='font-size:1rem; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:8px; vertical-align:middle;'>✅ Settled / Clear</span>";
                    }
                ?>
            </div>
        </div>
    </div>
    
    <?php if($message): ?>
        <div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap;">
        
        <div style="flex: 2; min-width: 500px; background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">Transaction History</h3>
                <div style="display:flex; gap:0.5rem; align-items: center; flex-wrap: wrap;">
                    <a href="ledger_receipt.php?id=<?php echo $id; ?><?php echo $showHistory ? '&history=1' : ''; ?>" target="_blank" class="btn" style="width: auto; background:linear-gradient(135deg, #0f172a 0%, #334155 100%); color:#fff; text-decoration:none; padding: 0.5rem 1.2rem; font-size: 0.9rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(15,23,42,0.2); white-space: nowrap; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 8px -1px rgba(15,23,42,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(15,23,42,0.2)';">🖨️ Print / PDF</a>
                    
                    <?php if($showHistory): ?>
                        <a href="customer_ledger.php?id=<?php echo $id; ?>" class="btn" style="width: auto; background:#f8fafc; color:#334155; text-decoration:none; padding: 0.5rem 1.2rem; font-size: 0.9rem; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); white-space: nowrap; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='#f8fafc';">Show Active Only</a>
                    <?php else: ?>
                        <a href="customer_ledger.php?id=<?php echo $id; ?>&history=1" class="btn" style="width: auto; background:#f8fafc; color:#475569; text-decoration:none; padding: 0.5rem 1.2rem; font-size: 0.9rem; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); white-space: nowrap; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='#f8fafc';">View Full History</a>
                        
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to clear/archive the current history? It will only be visible in Full History mode.');">
                            <input type="hidden" name="action" value="archive_all">
                            <button type="submit" class="btn" style="width: auto; background:#f59e0b; color:white; padding: 0.5rem 1.2rem; font-size: 0.9rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(245,158,11,0.2); white-space: nowrap; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 8px -1px rgba(245,158,11,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(245,158,11,0.2)';">Archive List</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 1rem;">Date</th>
                        <th style="padding: 1rem;">Type & Details</th>
                        <th style="padding: 1rem; text-align: right;">Debit (Sale)</th>
                        <th style="padding: 1rem; text-align: right;">Credit (Payment)</th>
                        <th style="padding: 1rem; text-align: right;">Balance</th>
                        <th style="padding: 1rem; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ledger as $l): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem;"><?php echo date('d M Y h:i A', strtotime($l['created_at'])); ?></td>
                        <td style="padding: 1rem;">
                            <strong><?php echo htmlspecialchars($l['description']); ?></strong>
                            <?php if($l['sale_id']): ?>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    <?php echo htmlspecialchars($l['items_summary']); ?>
                                    <?php if(!empty($l['taken_by'])): ?>
                                        <br><span style="color:var(--primary-color);"><strong>Taken By:</strong> <?php echo htmlspecialchars($l['taken_by']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="receipt.php?id=<?php echo $l['sale_id']; ?>" target="_blank" style="font-size: 0.8rem; color: var(--primary-color); text-decoration:none;">View Receipt</a>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right; color: var(--danger); font-weight: bold;">
                            <?php 
                                if($l['type'] === 'sale') echo '$' . number_format($l['amount'], 2);
                                elseif($l['type'] === 'cash_sale') echo '<span style="color:#94a3b8; font-weight:normal;">$' . number_format($l['amount'], 2) . '</span>';
                                else echo '-'; 
                            ?>
                        </td>
                        <td style="padding: 1rem; text-align: right; color: var(--success); font-weight: bold;">
                            <?php 
                                if(in_array($l['type'], ['payment_received', 'advance_deposit'])) echo '$' . number_format($l['amount'], 2);
                                elseif($l['type'] === 'cash_sale') echo '<span style="color:#94a3b8; font-weight:normal;">$' . number_format($l['amount'], 2) . '</span>';
                                else echo '-'; 
                            ?>
                        </td>
                        <td style="padding: 1rem; text-align: right; font-weight: bold; <?php echo $l['balance_after'] > 0 ? 'color: var(--danger)' : 'color: var(--success)'; ?>">
                            <?php 
                                if($l['balance_after'] < 0) {
                                    echo "$" . number_format(abs($l['balance_after']), 2) . " (Adv)";
                                } else {
                                    echo "$" . number_format($l['balance_after'], 2); 
                                }
                            ?>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php if(in_array($l['type'], ['payment_received', 'advance_deposit'])): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this payment? This will reverse the balance.');">
                                    <input type="hidden" name="action" value="delete_payment">
                                    <input type="hidden" name="ledger_id" value="<?php echo $l['id']; ?>">
                                    <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer;" title="Delete Payment">🗑️</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($ledger)): ?>
                        <tr><td colspan="5" style="padding: 2rem; text-align:center;">No transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="flex: 1; min-width: 300px; background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Add Payment / Advance</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_payment">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Amount Received ($)</label>
                <input type="number" step="0.01" name="amount" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 1rem; font-size: 1.2rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Description</label>
                <input type="text" name="description" placeholder="e.g. Cash Payment, Bank Transfer" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">Receive Payment</button>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
