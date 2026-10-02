<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/hr.php");

// 1. Add base_salary to modal UI
$bad_modal = <<<HTML
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Bonus Amount ($)</label>
                        <input type="number" step="0.01" name="bonuses" id="modal_bonuses" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
HTML;

$good_modal = <<<HTML
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Base Salary ($)</label>
                        <input type="number" step="0.01" name="base_salary" id="modal_base_salary" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Bonus Amount ($)</label>
                        <input type="number" step="0.01" name="bonuses" id="modal_bonuses" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
HTML;
$f = str_replace($bad_modal, $good_modal, $f);

// 2. Add base_salary to modal JS
$f = str_replace(
    "document.getElementById('modal_payroll_id').value = pr.id;",
    "document.getElementById('modal_payroll_id').value = pr.id;\n            document.getElementById('modal_base_salary').value = pr.base_salary;",
    $f
);

// 3. Update update_payroll_manual backend logic
$bad_backend = <<<PHP
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

$good_backend = <<<PHP
    elseif (\$_POST['action'] == 'update_payroll_manual') {
        \$pid = intval(\$_POST['payroll_id']);
        \$new_base = floatval(\$_POST['base_salary']);
        \$bonus = floatval(\$_POST['bonuses']);
        \$bonus_reason = \$conn->real_escape_string(\$_POST['bonus_reason']);
        \$man_ded = floatval(\$_POST['manual_deductions']);
        \$ded_reason = \$conn->real_escape_string(\$_POST['deduction_reason']);
        
        \$pq = \$conn->query("SELECT user_id, deductions FROM payroll WHERE id = \$pid AND tenant_id = {\$_SESSION['tenant_id']}");
        if (\$pq && \$pq->num_rows > 0) {
            \$row = \$pq->fetch_assoc();
            \$uid = \$row['user_id'];
            \$auto_ded = \$row['deductions'];
            \$net = \$new_base - \$auto_ded - \$man_ded + \$bonus;
            if (\$net < 0) \$net = 0;
            
            // Update payroll
            \$conn->query("UPDATE payroll SET base_salary = \$new_base, bonuses = \$bonus, bonus_reason = '\$bonus_reason', manual_deductions = \$man_ded, deduction_reason = '\$ded_reason', net_salary = \$net WHERE id = \$pid");
            // Also update the user's permanent base salary so next month is correct
            \$conn->query("UPDATE users SET base_salary = \$new_base WHERE id = \$uid");
            
            \$_SESSION['flash_success'] = "Payroll manually updated!";
        }
        echo "<script>window.location.href='hr.php?date=" . urlencode(\$date) . "';</script>";
        exit();
    }
PHP;
$f = str_replace($bad_backend, $good_backend, $f);

file_put_contents("C:/xampp/htdocs/point of sale/hr.php", $f);
echo "hr.php fixed!";
?>
