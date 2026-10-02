<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access Denied");
}

include 'includes/db.php';

$pid = intval($_GET['id'] ?? 0);
$tenant_id = $_SESSION['tenant_id'];

$q = $conn->query("SELECT p.*, u.username, u.role, t.company_name as tenant_name 
                   FROM payroll p 
                   JOIN users u ON p.user_id = u.id 
                   LEFT JOIN tenants t ON p.tenant_id = t.id 
                   WHERE p.id = $pid AND p.tenant_id = $tenant_id");

if (!$q || $q->num_rows == 0) {
    die("Salary slip not found or access denied.");
}

$slip = $q->fetch_assoc();
$month_display = date("F Y", strtotime($slip['month_year'] . "-01"));
$company_name = $slip['tenant_name'] ? $slip['tenant_name'] : "Company";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Slip - <?php echo htmlspecialchars($slip['username']); ?> - <?php echo $month_display; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 2rem;
            color: #334155;
        }
        .slip-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 3rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .header h1 {
            margin: 0;
            color: #0f172a;
            font-size: 2rem;
        }
        .header p {
            margin: 0.5rem 0 0 0;
            color: #64748b;
            font-size: 1.1rem;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .detail-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .detail-item strong {
            display: block;
            color: #475569;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }
        .detail-item span {
            font-size: 1.1rem;
            color: #0f172a;
            font-weight: 600;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .salary-table th, .salary-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .salary-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
        }
        .salary-table td.amount {
            text-align: right;
            font-family: monospace;
            font-size: 1.1rem;
        }
        .salary-table th.amount {
            text-align: right;
        }
        .net-pay {
            background: #10b981;
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .net-pay h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .net-pay .amount {
            font-size: 2rem;
            font-weight: bold;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .slip-container { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="slip-container">
        <div class="header">
            <h1><?php echo htmlspecialchars($company_name); ?></h1>
            <p>Salary Slip for <strong><?php echo $month_display; ?></strong></p>
        </div>
        
        <div class="details-grid">
            <div class="detail-item">
                <strong>Employee Name</strong>
                <span><?php echo htmlspecialchars($slip['username']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Designation</strong>
                <span style="text-transform: capitalize;"><?php echo htmlspecialchars($slip['role']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Salary Month</strong>
                <span><?php echo $month_display; ?></span>
            </div>
            <div class="detail-item">
                <strong>Status</strong>
                <span style="color: <?php echo $slip['status'] == 'paid' ? '#10b981' : '#f59e0b'; ?>; text-transform: uppercase;">
                    <?php echo htmlspecialchars($slip['status']); ?>
                    <?php if($slip['status'] == 'paid') echo " (" . $slip['paid_date'] . ")"; ?>
                </span>
            </div>
        </div>
        
        <table class="salary-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Base Salary</strong></td>
                    <td class="amount">$<?php echo number_format($slip['base_salary'], 2); ?></td>
                </tr>
                
                <?php if($slip['deductions'] > 0): ?>
                <tr>
                    <td>Leaves / Absences Deduction</td>
                    <td class="amount" style="color: #ef4444;">-$<?php echo number_format($slip['deductions'], 2); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if($slip['manual_deductions'] > 0): ?>
                <tr>
                    <td>Custom Deduction <br><small style="color:#64748b;"><?php echo htmlspecialchars($slip['deduction_reason']); ?></small></td>
                    <td class="amount" style="color: #ef4444;">-$<?php echo number_format($slip['manual_deductions'], 2); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if($slip['bonuses'] > 0): ?>
                <tr>
                    <td>Bonus / Allowance <br><small style="color:#64748b;"><?php echo htmlspecialchars($slip['bonus_reason']); ?></small></td>
                    <td class="amount" style="color: #10b981;">+$<?php echo number_format($slip['bonuses'], 2); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="net-pay">
            <h2>Net Salary Payable</h2>
            <div class="amount">$<?php echo number_format($slip['net_salary'], 2); ?></div>
        </div>
        
        <div style="margin-top: 4rem; display: flex; justify-content: space-between; color: #64748b;">
            <div style="text-align: center;">
                <div style="border-bottom: 1px solid #cbd5e1; width: 200px; margin-bottom: 0.5rem;"></div>
                Employer Signature
            </div>
            <div style="text-align: center;">
                <div style="border-bottom: 1px solid #cbd5e1; width: 200px; margin-bottom: 0.5rem;"></div>
                Employee Signature
            </div>
        </div>
        <p style="text-align: center; color: #94a3b8; font-size: 0.85rem; margin-top: 3rem;">This is a system generated document.</p>
    </div>
</body>
</html>
