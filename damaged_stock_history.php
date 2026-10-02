<?php
include 'includes/db.php';
include 'includes/header.php';

$historyQ = $conn->query("SELECT d.*, p.name as product_name, u.username, s.name as supplier_name FROM damaged_stock d JOIN products p ON d.product_id = p.id JOIN users u ON d.user_id = u.id LEFT JOIN suppliers s ON d.supplier_id = s.id WHERE d.is_cleared = 1 ORDER BY d.id DESC");
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2>📚 Damaged Stock History</h2>
            <p style="color: var(--text-muted);">View all settled/cleared records of damaged stock and supplier returns.</p>
        </div>
        <div>
            <a href="damaged_stock.php" class="btn" style="background: #e2e8f0; color: #475569; padding: 0.6rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: bold;">&larr; Back to Active</a>
        </div>
    </div>
    
    <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Historical Records</h3>
            <div class="no-print" style="display: flex; gap: 0.5rem;">
                <button type="button" onclick="window.print()" class="btn" style="background: var(--primary-color); color: white; padding: 0.4rem 1rem; border: none; border-radius: 4px; cursor: pointer;">🖨️ Print</button>
                <button type="button" onclick="downloadPDF()" class="btn" style="background: #059669; color: white; padding: 0.4rem 1rem; border: none; border-radius: 4px; cursor: pointer;">📄 PDF</button>
            </div>
        </div>
        
        <div class="print-area">
            <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Date</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Product</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Supplier</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Qty</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Loss ($)</th>
                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Reason</th>
                    <th class="no-print" style="padding: 0.75rem; text-align: right; border-bottom: 2px solid var(--border-color);">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($historyQ && $historyQ->num_rows > 0): ?>
                    <?php while($row = $historyQ->fetch_assoc()): ?>
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); font-size: 0.85rem;"><?php echo date('M d, Y H:i', strtotime($row['logged_at'])); ?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); font-weight: 500;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); color: #0284c7;">
                            <?php echo htmlspecialchars($row['supplier_name'] ?: '—'); ?>
                        </td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); color: var(--danger); font-weight: bold;">-<?php echo floatval($row['quantity']); ?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">$<?php echo number_format($row['loss_amount'], 2); ?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.9rem;"><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td class="no-print" style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: right; white-space: nowrap;">
                            <a href="return_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" style="color: #059669; text-decoration: none; font-weight: bold;">📄 Receipt</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="padding: 1rem; text-align: center;">No history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .main-nav, .main-header { display: none !important; }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.querySelector('.print-area');
        const opt = {
            margin:       0.5,
            filename:     'Damaged_Stock_History.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 4, useCORS: true },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        
        const btn = document.querySelector('button[onclick="downloadPDF()"]');
        if(btn) btn.innerText = "⏳...";
        
        window.scrollTo(0,0);
        
        html2pdf().set(opt).from(element).save().then(() => {
            if(btn) btn.innerText = "📄 PDF";
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
