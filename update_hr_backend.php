<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/hr.php");

// 1. Fix global users fetch tenant_id
$f = str_replace(
    '$users = $conn->query("SELECT * FROM users ORDER BY username ASC");',
    '$users = $conn->query("SELECT * FROM users WHERE tenant_id = {$_SESSION[\'tenant_id\']} AND role != \'super_admin\' ORDER BY username ASC");',
    $f
);

// 2. Fix generate_payroll users fetch and logic
$bad_gen = <<<PHP
    elseif (\$_POST['action'] == 'generate_payroll') {
        \$month = \$conn->real_escape_string(\$_POST['payroll_month']); // YYYY-MM
        
        \$users = \$conn->query("SELECT id, base_salary, username FROM users WHERE role != 'admin' OR id = {\$_SESSION['user_id']}");
        while(\$u = \$users->fetch_assoc()) {
            \$uid = \$u['id'];
            \$base = \$u['base_salary'];
            
            // Calculate deductions based on absents
            \$absQ = \$conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_id = \$uid AND attendance_date LIKE '\$month-%' AND status = 'absent'");
            \$absents = \$absQ ? \$absQ->fetch_assoc()['c'] : 0;
            
            \$halfQ = \$conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_id = \$uid AND attendance_date LIKE '\$month-%' AND status = 'half-day'");
            \$halfDays = \$halfQ ? \$halfQ->fetch_assoc()['c'] : 0;
            
            \$perDay = \$base / 30; // approx
            \$deductions = (\$absents * \$perDay) + (\$halfDays * (\$perDay / 2));
            
            \$net = \$base - \$deductions;
            if(\$net < 0) \$net = 0;
            
            \$conn->query("INSERT INTO payroll (user_id, month_year, base_salary, deductions, net_salary, tenant_id) VALUES (\$uid, '\$month', \$base, \$deductions, \$net, {$_SESSION['tenant_id']}) ON DUPLICATE KEY UPDATE base_salary=\$base, deductions=\$deductions, net_salary=\$net");
        }
        \$message = "Payroll generated for \$month!";
        if(function_exists('log_audit')) log_audit(\$conn, \$_SESSION['user_id'], \$current_branch_id ?? 1, 'GENERATE_PAYROLL', 'payroll', 0, null, \$month, "Generated payroll");
        
        \$_SESSION['flash_success'] = \$message;
        echo "<script>window.location.href='hr.php?date=" . urlencode(\$date) . "';</script>";
        exit();
    }
PHP;

$good_gen = <<<PHP
    elseif (\$_POST['action'] == 'generate_payroll') {
        \$month = \$conn->real_escape_string(\$_POST['payroll_month']); // YYYY-MM
        
        \$users = \$conn->query("SELECT id, base_salary, username FROM users WHERE tenant_id = {\$_SESSION['tenant_id']} AND role != 'super_admin'");
        while(\$u = \$users->fetch_assoc()) {
            \$uid = \$u['id'];
            \$base = \$u['base_salary'];
            
            // Fetch any existing manual bonuses/deductions so we don't wipe them out
            \$bonus = 0;
            \$man_ded = 0;
            \$existingQ = \$conn->query("SELECT bonuses, manual_deductions FROM payroll WHERE user_id=\$uid AND month_year='\$month'");
            if (\$existingQ && \$existingQ->num_rows > 0) {
                \$row = \$existingQ->fetch_assoc();
                \$bonus = \$row['bonuses'];
                \$man_ded = \$row['manual_deductions'];
            }
            
            // Calculate auto-deductions based on absents
            \$absQ = \$conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_id = \$uid AND attendance_date LIKE '\$month-%' AND status = 'absent'");
            \$absents = \$absQ ? \$absQ->fetch_assoc()['c'] : 0;
            
            \$halfQ = \$conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_id = \$uid AND attendance_date LIKE '\$month-%' AND status = 'half-day'");
            \$halfDays = \$halfQ ? \$halfQ->fetch_assoc()['c'] : 0;
            
            \$perDay = \$base / 30; // approx
            \$deductions = (\$absents * \$perDay) + (\$halfDays * (\$perDay / 2));
            
            \$net = \$base - \$deductions - \$man_ded + \$bonus;
            if(\$net < 0) \$net = 0;
            
            \$conn->query("INSERT INTO payroll (user_id, month_year, base_salary, deductions, manual_deductions, bonuses, net_salary, tenant_id) VALUES (\$uid, '\$month', \$base, \$deductions, \$man_ded, \$bonus, \$net, {\$_SESSION['tenant_id']}) ON DUPLICATE KEY UPDATE base_salary=\$base, deductions=\$deductions, net_salary=\$net");
        }
        \$message = "Payroll generated for \$month!";
        if(function_exists('log_audit')) log_audit(\$conn, \$_SESSION['user_id'], \$current_branch_id ?? 1, 'GENERATE_PAYROLL', 'payroll', 0, null, \$month, "Generated payroll");
        
        \$_SESSION['flash_success'] = \$message;
        echo "<script>window.location.href='hr.php?date=" . urlencode(\$date) . "';</script>";
        exit();
    }
    elseif (\$_POST['action'] == 'update_payroll_manual') {
        \$pid = intval(\$_POST['payroll_id']);
        \$bonus = floatval(\$_POST['bonuses']);
        \$bonus_reason = \$conn->real_escape_string(\$_POST['bonus_reason']);
        \$man_ded = floatval(\$_POST['manual_deductions']);
        \$ded_reason = \$conn->real_escape_string(\$_POST['deduction_reason']);
        
        \$pq = \$conn->query("SELECT base_salary, deductions FROM payroll WHERE id = \$pid AND tenant_id = {\$_SESSION['tenant_id']}");
        if (\$pq && \$pq->num_rows > 0) {
            \$row = \$pq->fetch_assoc();
            \$base = \$row['base_salary'];
            \$auto_ded = \$row['deductions'];
            \$net = \$base - \$auto_ded - \$man_ded + \$bonus;
            if (\$net < 0) \$net = 0;
            
            \$conn->query("UPDATE payroll SET bonuses = \$bonus, bonus_reason = '\$bonus_reason', manual_deductions = \$man_ded, deduction_reason = '\$ded_reason', net_salary = \$net WHERE id = \$pid");
            \$_SESSION['flash_success'] = "Payroll manually updated!";
        }
        echo "<script>window.location.href='hr.php?date=" . urlencode(\$date) . "';</script>";
        exit();
    }
PHP;

$f = str_replace($bad_gen, $good_gen, $f);

// Fix tenant_id filtering for payroll display
$f = str_replace(
    '$pQ = $conn->query("SELECT p.*, u.username FROM payroll p JOIN users u ON p.user_id = u.id WHERE p.month_year = \'$cm\' ORDER BY p.id DESC");',
    '$pQ = $conn->query("SELECT p.*, u.username FROM payroll p JOIN users u ON p.user_id = u.id WHERE p.month_year = \'$cm\' AND p.tenant_id = {$_SESSION[\'tenant_id\']} ORDER BY p.id DESC");',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/hr.php", $f);
echo "Backend logic updated!";
?>
