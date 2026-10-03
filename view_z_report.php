<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset($isAdmin) || !$isAdmin) {
    die("<div class='container'><h2>Access Denied</h2></div>");
}

if (!isset($_GET['id'])) {
    die("<div class='container'><h2>Report ID Missing</h2></div>");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM z_reports_history WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
    die("<div class='container'><h2>Report Not Found</h2></div>");
}

// Decode JSON snapshot
$snapshot = json_decode($report['snapshot_data'], true);
$detailedOrdersArr = $snapshot['orders'] ?? [];
$detailedExpensesArr = $snapshot['expenses'] ?? [];
$detailedPurchasesArr = $snapshot['purchases'] ?? [];
$detailedReturnsArr = $snapshot['returns'] ?? [];
$returnsSummary = $snapshot['returns_summary'] ?? [];
?>

<div class="animate-fade-in container" style="max-width: 800px; margin: 3rem auto; background: var(--surface-color); padding: 3rem; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;" class="no-print">
        <a href="historical_z_reports.php" class="btn" style="background: #e2e8f0; color: #333; text-decoration: none;">&larr; Back</a>
        <a href="print_historical_z_report.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-primary" style="padding: 0.8rem 2rem; text-decoration:none;">🖨️ Print / PDF</a>
    </div>

    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="color: var(--text-main); font-size: 2.5rem; margin-bottom: 0.5rem;">Historical Z-Report</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem;">Snapshot Date: <strong><?php echo date('l, F j, Y', strtotime($report['report_date'])); ?></strong></p>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Generated at: <?php echo date('d M Y - h:i A', strtotime($report['created_at'])); ?></p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Total Orders</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--text-main);"><?php echo $report['total_orders']; ?></div>
        </div>
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Total Sales</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--primary-color);">$<?php echo number_format($report['total_sales'], 2); ?></div>
        </div>
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Expenses & Purchases</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--danger);">-$<?php echo number_format($report['total_expenses'] + $report['total_supplier_payments'], 2); ?></div>
        </div>
        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 12px; border: 1px solid #bbf7d0;">
            <div style="color: #166534; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Net Daily Income</div>
            <div style="font-size: 2rem; font-weight: 800; color: #15803d;">$<?php echo number_format($report['net_income'], 2); ?></div>
        </div>
    </div>

    <!-- Cash Drawer Summary -->
    <?php if(isset($report['expected_cash'])): ?>
    <div style="background: #f1f5f9; border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-bottom: 3rem;">
        <h4 style="margin-bottom: 1rem; color: var(--text-main); font-size: 1.1rem;">Cash Drawer Summary</h4>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1.1rem; font-weight: bold; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">
            <span style="color: var(--text-main);">Opening Cash (Purana Cash):</span>
            <strong style="color: var(--text-main);">$<?php echo number_format($report['opening_cash'] ?? 0, 2); ?></strong>
        </div>

        <div style="margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; font-weight: 600;">Cash Flow Summary (Naya Cash)</div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1rem; padding-top: 0.5rem;">
            <?php 
                $opening = $report['opening_cash'] ?? 0;
                $expected = $report['expected_cash'] ?? 0;
                $netCashGenerated = $expected - $opening; 
            ?>
            <span style="color: var(--text-main); font-weight: 500;">Baqi Cash (Net Generated):</span>
            <strong style="color: <?php echo $netCashGenerated >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;"><?php echo $netCashGenerated >= 0 ? '+' : ''; ?>$<?php echo number_format($netCashGenerated, 2); ?></strong>
        </div>

        <div style="border-top: 2px solid #cbd5e1; margin: 1rem 0; padding-top: 1rem; display: flex; justify-content: space-between; font-size: 1.2rem;">
            <span style="color: var(--text-muted); font-weight: bold;">System Expected Total Cash:</span>
            <strong style="color: var(--primary-color);">$<?php echo number_format($expected, 2); ?></strong>
        </div>
        
        <div style="background: <?php echo (($report['closing_cash']??0) >= $expected) ? '#f0fdf4' : '#fef2f2'; ?>; padding: 1rem; border-radius: 8px; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: bold; color: var(--text-main); font-size: 1.2rem;">Actual Cash (From Shifts)</span>
            <div style="text-align: right;">
                <strong style="color: <?php echo (($report['closing_cash']??0) >= $expected) ? 'var(--success)' : 'var(--danger)'; ?>; font-size: 1.5rem;">
                    $<?php echo number_format($report['closing_cash'] ?? 0, 2); ?>
                </strong>
                <?php $variance = ($report['closing_cash']??0) - $expected; if($variance != 0): ?>
                    <div style="font-size: 0.85rem; color: <?php echo $variance < 0 ? 'var(--danger)' : 'var(--success)'; ?>; margin-top: 0.2rem;">
                        Variance: $<?php echo number_format($variance, 2); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detailed Breakdowns -->
    <div class="detailed-reports">
        
        <!-- Orders -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Orders Snapshot</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Order ID</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Customer</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Method</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Time</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedOrdersArr)): foreach($detailedOrdersArr as $o): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);">#<?php echo $o['id']; ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($o['customer_name'] ?: 'Walk-in'); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-transform: capitalize;"><?php echo $o['payment_method']; ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($o['created_at'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600;">$<?php echo number_format($o['total_amount'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No orders in this report.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Expenses -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Expenses Snapshot</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Expense Title</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Date</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedExpensesArr)): foreach($detailedExpensesArr as $e): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($e['category']); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($e['expense_date'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600; color: var(--danger);">-$<?php echo number_format($e['amount'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No expenses in this report.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Supplier Payments -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Supplier Payments Snapshot</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Supplier Name</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Time</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedPurchasesArr)): foreach($detailedPurchasesArr as $p): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($p['supplier_name'] ?: 'Unknown'); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($p['created_at'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600; color: #eab308;">-$<?php echo number_format($p['amount_paid'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No supplier payments in this report.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Product Returns Snapshot -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Product Returns Snapshot</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.95rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Return ID</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Receipt #</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color);">Time</th>
                    <th style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right;">Total Refund</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($detailedReturnsArr)): foreach($detailedReturnsArr as $r): ?>
                <tr>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);">#<?php echo $r['id']; ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);">Sale #<?php echo $r['sale_id']; ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color);"><?php echo date('h:i A', strtotime($r['created_at'])); ?></td>
                    <td style="padding: 0.75rem; border: 1px solid var(--border-color); text-align: right; font-weight: 600; color: #db2777;">-$<?php echo number_format($r['total_refund'], 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4" style="padding: 1rem; text-align: center; border: 1px solid var(--border-color);">No product returns in this report.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<style>
    @media print {
        .main-header, .no-print { display: none !important; }
        .container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        body { background: white !important; }
        .detailed-reports { display: block !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
    }
</style>

<?php include 'includes/footer.php'; ?>
