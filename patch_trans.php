<?php
// PHP Script to patch transfers.php
\ = 'C:/xampp/htdocs/point of sale/transfers.php';
\ = file_get_contents(\);

// Add delete logic at the top
\ = <<<EOD
if (\['REQUEST_METHOD'] == 'POST' && isset(\['action']) && \['action'] == 'delete') {
    \ = intval(\['transfer_id']);
    \->query("UPDATE stock_transfers SET is_deleted = 1 WHERE id = \");
    \ = "Transfer moved to history (soft deleted).";
}

EOD;
\ = str_replace('if (\[\'REQUEST_METHOD\'] == \'POST\' && isset(\[\'action\']) && \[\'action\'] == \'transfer\') {', \ . 'if (\[\'REQUEST_METHOD\'] == \'POST\' && isset(\[\'action\']) && \[\'action\'] == \'transfer\') {', \);

// Update query to check is_deleted and tabs
\ = isset(\['history']) ? 1 : 0;
\ = <<<EOD
                \ = isset(\['history']) ? 1 : 0;
                \ = \->query("
                    SELECT t.*, p.name as product_name, b1.name as from_name, b2.name as to_name 
                    FROM stock_transfers t
                    JOIN products p ON t.product_id = p.id
                    JOIN branches b1 ON t.from_branch = b1.id
                    JOIN branches b2 ON t.to_branch = b2.id
                    WHERE (t.from_branch = \ OR t.to_branch = \)
                    AND t.is_deleted = \
                    ORDER BY t.transfer_date DESC LIMIT 50
                ");
EOD;

\ = preg_replace('/\ = \->query\(".*?LIMIT 20\s*"\);/s', \, \);

// Update table header and rows for actions
\ = <<<EOD
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Transfer History</h3>
            <div>
                <a href="transfers.php" class="btn" style="background: <?php echo !isset(\['history']) ? 'var(--primary-color)' : '#e2e8f0'; ?>; color: <?php echo !isset(\['history']) ? 'white' : '#475569'; ?>; padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">Active</a>
                <a href="transfers.php?history=1" class="btn" style="background: <?php echo isset(\['history']) ? 'var(--primary-color)' : '#e2e8f0'; ?>; color: <?php echo isset(\['history']) ? 'white' : '#475569'; ?>; padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px; font-size: 0.9rem; margin-left: 0.5rem;">Deleted History</a>
            </div>
        </div>
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 0.75rem;">Date</th>
                    <th style="padding: 0.75rem;">Product</th>
                    <th style="padding: 0.75rem;">Direction</th>
                    <th style="padding: 0.75rem;">Qty</th>
                    <th style="padding: 0.75rem;">Status</th>
                    <th style="padding: 0.75rem; text-align: right;">Actions</th>
                </tr>
            </thead>
EOD;

\ = preg_replace('/<h3 style="margin-bottom: 1\.5rem;">Transfer History<\/h3>\s*<table class="data-table" style="width: 100%; border-collapse: collapse;">\s*<thead>\s*<tr style="border-bottom: 2px solid var\(--border-color\); text-align: left;">\s*<th style="padding: 0\.75rem;">Date<\/th>\s*<th style="padding: 0\.75rem;">Product<\/th>\s*<th style="padding: 0\.75rem;">Direction<\/th>\s*<th style="padding: 0\.75rem;">Qty<\/th>\s*<th style="padding: 0\.75rem;">Status<\/th>\s*<\/tr>\s*<\/thead>/s', \, \);

\ = <<<EOD
                        \ = "<a href='print_transfer.php?id=" . \['id'] . "' target='_blank' style='background:#3b82f6; color:white; padding: 4px 8px; border-radius: 4px; text-decoration:none; font-size: 0.8rem;'>Print/PDF</a>";
                        if (!\) {
                            \ .= "<form method='POST' style='display:inline; margin-left: 5px;' onsubmit=\"return confirm('Delete this transfer record?');\">
                                            <input type='hidden' name='action' value='delete'>
                                            <input type='hidden' name='transfer_id' value='".\['id']."'>
                                            <button type='submit' style='background:#ef4444; color:white; padding: 4px 8px; border-radius: 4px; border:none; cursor:pointer; font-size: 0.8rem;'>Delete</button>
                                         </form>";
                        }
                        
                        echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                        echo "<td style='padding: 0.75rem;'>" . date('d M Y h:i A', strtotime(\['transfer_date'])) . "</td>";
                        echo "<td style='padding: 0.75rem;'>" . htmlspecialchars(\['product_name']) . "</td>";
                        echo "<td style='padding: 0.75rem;'>" . \ . "</td>";
                        echo "<td style='padding: 0.75rem; font-weight:bold;'>" . \['quantity'] . "</td>";
                        echo "<td style='padding: 0.75rem;'><span style='background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:0.8rem;'>" . strtoupper(\['status']) . "</span></td>";
                        echo "<td style='padding: 0.75rem; text-align: right;'>" . \ . "</td>";
                        echo "</tr>";
EOD;

\ = preg_replace('/echo "<tr style=\'border-bottom: 1px solid var\(--border-color\);[^>]*>";.*?echo "<\/tr>";/s', \, \);

\ = str_replace("<td colspan='5'", "<td colspan='6'", \);

file_put_contents(\, \);
echo "Patched transfers.php";
?>
