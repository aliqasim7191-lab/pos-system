code = """<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset(\['id'])) {
    die("<div class='container'><h2>Error</h2><p>No customer selected.</p></div>");
}

\ = intval(\['id']);
\ = '';

if (\['REQUEST_METHOD'] === 'POST' && isset(\['action']) && \['action'] === 'add_payment') {
    \ = floatval(\['amount']);
    \ = trim(\['description']) ?: 'Payment Received';
    
    if (\ > 0) {
        \->begin_transaction();
        try {
            // Subtract from outstanding balance
            \ = \->prepare("UPDATE customers SET outstanding_balance = outstanding_balance - ? WHERE id = ?");
            \->bind_param("di", \, \);
            \->execute();
            \->close();
            
            // Get new balance
            \ = \->query("SELECT outstanding_balance FROM customers WHERE id = \ LIMIT 1");
            \ = \->fetch_assoc()['outstanding_balance'];
            
            // Insert ledger
            \ = 'payment_received';
            if (\ < 0 && (\ + \) <= 0) {
                \ = 'advance_deposit';
            }
            \ = \->prepare("INSERT INTO customer_ledger (customer_id, type, amount, balance_after, description) VALUES (?, ?, ?, ?, ?)");
            \->bind_param("isdds", \, \, \, \, \);
            \->execute();
            \->close();
            
            \->commit();
            \ = "Payment added successfully!";
        } catch (Exception \) {
            \->rollback();
            \ = "Error: " . \->getMessage();
        }
    }
}

\ = \->query("SELECT * FROM customers WHERE id = \ LIMIT 1");
\ = \->fetch_assoc();

if (!\) {
    die("<div class='container'><h2>Error</h2><p>Customer not found.</p></div>");
}

// Fetch Ledger History
\ = [];
\ = \->query("SELECT cl.*, s.total_amount as sale_total, (SELECT GROUP_CONCAT(CONCAT(si.quantity, 'x ', p.name) SEPARATOR ', ') FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = cl.sale_id) as items_summary FROM customer_ledger cl LEFT JOIN sales s ON cl.sale_id = s.id WHERE cl.customer_id = \ ORDER BY cl.created_at DESC");
if (\) {
    while(\ = \->fetch_assoc()) {
        \[] = \;
    }
}
?>

<div class=\"container\" style=\"padding-top: 1rem;\">
    <div style=\"display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;\">
        <div>
            <h1 style=\"font-size: 2rem; color: var(--text-main);\"><?php echo htmlspecialchars(\['name']); ?> - Ledger</h1>
            <div style=\"font-size: 0.9rem; color: var(--text-muted);\">Phone: <?php echo htmlspecialchars(\['phone'] ?? 'N/A'); ?> | Type: <?php echo strtoupper(\['customer_type']); ?></div>
        </div>
        <div style=\"text-align: right;\">
            <div style=\"font-size: 0.9rem; color: var(--text-muted);\">Current Balance</div>
            <div style=\"font-size: 2rem; font-weight: bold; color: <?php echo \['outstanding_balance'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>;\">
                <?php 
                    if (\['outstanding_balance'] < 0) {
                        echo "$" . number_format(abs(\['outstanding_balance']), 2) . " (Advance)";
                    } else {
                        echo "$" . number_format(\['outstanding_balance'], 2) . " (Receivable)";
                    }
                ?>
            </div>
        </div>
    </div>
    
    <?php if(\): ?>
        <div style=\"background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;\">
            <?php echo \; ?>
        </div>
    <?php endif; ?>

    <div style=\"display:flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap;\">
        
        <div style=\"flex: 2; min-width: 500px; background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);\">
            <h3 style=\"margin-bottom: 1.5rem;\">Transaction History</h3>
            
            <table style=\"width: 100%; border-collapse: collapse; text-align: left;\">
                <thead>
                    <tr style=\"border-bottom: 2px solid var(--border-color);\">
                        <th style=\"padding: 1rem;\">Date</th>
                        <th style=\"padding: 1rem;\">Type & Details</th>
                        <th style=\"padding: 1rem; text-align: right;\">Debit (Sale)</th>
                        <th style=\"padding: 1rem; text-align: right;\">Credit (Payment)</th>
                        <th style=\"padding: 1rem; text-align: right;\">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach(\ as \): ?>
                    <tr style=\"border-bottom: 1px solid var(--border-color);\">
                        <td style=\"padding: 1rem;\"><?php echo date('d M Y h:i A', strtotime(\['created_at'])); ?></td>
                        <td style=\"padding: 1rem;\">
                            <strong><?php echo htmlspecialchars(\['description']); ?></strong>
                            <?php if(\['sale_id']): ?>
                                <div style=\"font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;\">
                                    <?php echo htmlspecialchars(\['items_summary']); ?>
                                </div>
                                <a href=\"receipt.php?id=<?php echo \['sale_id']; ?>\" target=\"_blank\" style=\"font-size: 0.8rem; color: var(--primary-color); text-decoration:none;\">View Receipt</a>
                            <?php endif; ?>
                        </td>
                        <td style=\"padding: 1rem; text-align: right; color: var(--danger); font-weight: bold;\">
                            <?php echo \['type'] === 'sale' ? '$' . number_format(\['amount'], 2) : '-'; ?>
                        </td>
                        <td style=\"padding: 1rem; text-align: right; color: var(--success); font-weight: bold;\">
                            <?php echo in_array(\['type'], ['payment_received', 'advance_deposit']) ? '$' . number_format(\['amount'], 2) : '-'; ?>
                        </td>
                        <td style=\"padding: 1rem; text-align: right; font-weight: bold; <?php echo \['balance_after'] > 0 ? 'color: var(--danger)' : 'color: var(--success)'; ?>\">
                            <?php 
                                if(\['balance_after'] < 0) {
                                    echo "$" . number_format(abs(\['balance_after']), 2) . " (Adv)";
                                } else {
                                    echo "$" . number_format(\['balance_after'], 2); 
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty(\)): ?>
                        <tr><td colspan=\"5\" style=\"padding: 2rem; text-align:center;\">No transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style=\"flex: 1; min-width: 300px; background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);\">
            <h3 style=\"margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;\">Add Payment / Advance</h3>
            <form method=\"POST\">
                <input type=\"hidden\" name=\"action\" value=\"add_payment\">
                
                <label style=\"display:block; margin-bottom: 0.5rem; font-weight: 500;\">Amount Received ($)</label>
                <input type=\"number\" step=\"0.01\" name=\"amount\" required style=\"width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 1rem; font-size: 1.2rem;\">
                
                <label style=\"display:block; margin-bottom: 0.5rem; font-weight: 500;\">Description</label>
                <input type=\"text\" name=\"description\" placeholder=\"e.g. Cash Payment, Bank Transfer\" style=\"width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 1.5rem;\">
                
                <button type=\"submit\" class=\"btn btn-primary\" style=\"width: 100%; padding: 1rem; font-size: 1.1rem;\">Receive Payment</button>
            </form>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
"""
with open('customer_ledger.php', 'w') as f:
    f.write(code)
