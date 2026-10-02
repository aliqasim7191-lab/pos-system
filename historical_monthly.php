<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset($isAdmin) || !$isAdmin) {
    die("<div class='container'><h2>Access Denied</h2><p>Only administrators can view historical reports.</p></div>");
}

$curYear  = (int)date('Y');
$curMonth = (int)date('m');
$curMonthName = date('F Y');

$tenantId = $_SESSION['tenant_id'] ?? 1;

// Check if current month is already closed
$curClosedCheck = $conn->query("SELECT id FROM monthly_reports_history WHERE MONTH(report_month) = $curMonth AND YEAR(report_month) = $curYear AND tenant_id = $tenantId LIMIT 1");
$isCurClosed = ($curClosedCheck && $curClosedCheck->num_rows > 0);

$liveData = null;
if (!$isCurClosed) {
    // 1. Live Sales (Total Revenue)
    $qS = $conn->query("SELECT SUM(total_amount) as total FROM sales WHERE MONTH(created_at) = $curMonth AND YEAR(created_at) = $curYear AND tenant_id = $tenantId");
    $liveSales = floatval($qS ? ($qS->fetch_assoc()['total'] ?? 0) : 0);

    // 2. Live Expenses
    $qE = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE (MONTH(expense_date) = $curMonth OR MONTH(created_at) = $curMonth) AND (YEAR(expense_date) = $curYear OR YEAR(created_at) = $curYear) AND tenant_id = $tenantId");
    $liveExpenses = floatval($qE ? ($qE->fetch_assoc()['total'] ?? 0) : 0);

    // 3. Live Supplier Payments
    $qP = $conn->query("SELECT SUM(amount_paid) as total FROM purchases WHERE MONTH(created_at) = $curMonth AND YEAR(created_at) = $curYear AND tenant_id = $tenantId");
    $liveSupplierPayments = floatval($qP ? ($qP->fetch_assoc()['total'] ?? 0) : 0);

    // 4. Live Net Profit = Sales - Expenses - Supplier Payments
    $liveNetProfit = $liveSales - $liveExpenses - $liveSupplierPayments;

    $liveData = [
        'month' => $curMonthName,
        'sales' => $liveSales,
        'expenses' => $liveExpenses,
        'supplier_payments' => $liveSupplierPayments,
        'net_profit' => $liveNetProfit
    ];
}

$history = $conn->query("SELECT * FROM monthly_reports_history WHERE tenant_id = $tenantId ORDER BY report_month DESC, created_at DESC");
?>

<div class="container dashboard-container" style="padding-top: 1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2rem; color: var(--text-main);">Historical Monthly Reports</h1>
            <div style="font-size: 0.9rem; color: var(--text-muted);">View live active month metrics & permanent snapshots of closed months.</div>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="reports.php" class="btn btn-primary" style="text-decoration: none; padding: 0.6rem 1.5rem; width: auto !important; border-radius: 6px !important; display: inline-flex; align-items: center; justify-content: center;">&larr; Back</a>
            <button type="button" onclick="downloadPDF()" class="btn" style="background: #10b981; color: white; padding: 0.6rem 1.5rem; border: none; cursor: pointer; width: auto !important; border-radius: 6px !important; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">📄 PDF</button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Report Month</th>
                    <th>Date Closed</th>
                    <th>Total Sales</th>
                    <th>Total Expenses</th>
                    <th>Supplier Payments</th>
                    <th>Net Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($liveData): ?>
                <tr style="background: #f0fdf4; border-left: 5px solid #10b981;">
                    <td style="font-weight: 700; color: #0f172a; font-size: 1.1rem;">
                        <?php echo htmlspecialchars($liveData['month']); ?>
                        <span style="background: #10b981; color: #ffffff; font-size: 0.72rem; font-weight: 700; padding: 0.18rem 0.6rem; border-radius: 999px; margin-left: 0.5rem; vertical-align: middle; text-transform: uppercase; letter-spacing: 0.03em;">
                            ⚡ Active Live
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #059669; font-size: 0.95rem;">Live / In Progress</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Real-time current month data</div>
                    </td>
                    <td style="font-weight: 700; color: #2563eb; font-size: 1rem;">$<?php echo number_format($liveData['sales'], 2); ?></td>
                    <td style="color: #ef4444; font-weight: 600;">-$<?php echo number_format($liveData['expenses'], 2); ?></td>
                    <td style="color: #ca8a04; font-weight: 600;">-$<?php echo number_format($liveData['supplier_payments'], 2); ?></td>
                    <td style="font-weight: 800; font-size: 1.05rem; color: <?php echo $liveData['net_profit'] >= 0 ? '#059669' : '#ef4444'; ?>;">
                        $<?php echo number_format($liveData['net_profit'], 2); ?>
                    </td>
                </tr>
                <?php endif; ?>

                <?php 
                $hasRecords = ($liveData !== null) || ($history && $history->num_rows > 0);
                if($history && $history->num_rows > 0): while($r = $history->fetch_assoc()): 
                ?>
                <tr>
                    <td style="font-weight: 700; color: var(--text-main); font-size: 1.1rem;">
                        <?php echo date('F Y', strtotime($r['report_month'] . '-01')); ?>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-main);"><?php echo date('d M Y', strtotime($r['created_at'])); ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('h:i A', strtotime($r['created_at'])); ?></div>
                    </td>
                    <td style="font-weight: 600; color: var(--primary-color);">$<?php echo number_format($r['total_sales'], 2); ?></td>
                    <td style="color: var(--danger);">-$<?php echo number_format($r['total_expenses'], 2); ?></td>
                    <td style="color: #eab308;">-$<?php echo number_format($r['total_supplier_payments'], 2); ?></td>
                    <td style="font-weight: bold; color: <?php echo $r['net_income'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                        $<?php echo number_format($r['net_income'], 2); ?>
                    </td>
                </tr>
                <?php endwhile; elseif(!$hasRecords): ?>
                <tr><td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">No monthly reports found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.querySelector('.table-wrapper') || document.body;
        const opt = {
            margin:       0.5,
            filename:     'historical_monthly_reports.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 4, useCORS: true },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
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
