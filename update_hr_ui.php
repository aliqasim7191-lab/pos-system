<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/hr.php");

$bad_table = <<<HTML
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 0.75rem;">Staff</th>
                    <th style="padding: 0.75rem;">Base Salary</th>
                    <th style="padding: 0.75rem;">Net Pay</th>
                    <th style="padding: 0.75rem;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                \$cm = date('Y-m');
                if(isset(\$_POST['payroll_month'])) \$cm = \$_POST['payroll_month'];
                
                \$pQ = \$conn->query("SELECT p.*, u.username FROM payroll p JOIN users u ON p.user_id = u.id WHERE p.month_year = '\$cm' AND p.tenant_id = {\$_SESSION['tenant_id']} ORDER BY p.id DESC");
                if(\$pQ && \$pQ->num_rows > 0) {
                    while(\$pr = \$pQ->fetch_assoc()) {
                        echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                        echo "<td style='padding: 0.75rem; font-weight:500;'>" . htmlspecialchars(\$pr['username']) . "</td>";
                        
                        // Form to update salary directly from here if needed
                        echo "<td style='padding: 0.75rem;'>
                                <form method='POST' style='display:flex; gap:0.3rem;'>
                                    <input type='hidden' name='action' value='update_salary'>
                                    <input type='hidden' name='user_id' value='{\$pr['user_id']}'>
                                    <input type='number' name='base_salary' value='{\$pr['base_salary']}' style='width:70px; padding:0.2rem; border:1px solid #cbd5e1;'>
                                    <button class='btn' style='padding:2px 5px; font-size:0.7rem;'>Update</button>
                                </form>
                              </td>";
                              
                        echo "<td style='padding: 0.75rem; font-weight:bold; color:#166534;'>$" . number_format(\$pr['net_salary'],2) . "</td>";
                        
                        if(\$pr['status'] == 'unpaid') {
                            echo "<td style='padding: 0.75rem;'>
                                    <form method='POST'>
                                        <input type='hidden' name='action' value='pay_salary'>
                                        <input type='hidden' name='payroll_id' value='{\$pr['id']}'>
                                        <button class='btn' style='background:#f59e0b; color:white; padding:0.3rem 0.6rem; font-size:0.8rem;'>Mark Paid</button>
                                    </form>
                                  </td>";
                        } else {
                            echo "<td style='padding: 0.75rem;'><span style='color:white; background:#10b981; padding:2px 6px; border-radius:4px; font-size:0.8rem;'>Paid</span></td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='padding: 1rem; text-align:center;'>No payroll generated for \$cm. Update base salaries in Staff page or Generate Payroll.</td></tr>";
                }
                ?>
            </tbody>
        </table>
HTML;

$good_table = <<<HTML
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-size: 0.85rem; color: #64748b; text-transform: uppercase;">
                    <th style="padding: 0.75rem;">Staff</th>
                    <th style="padding: 0.75rem;">Base Salary</th>
                    <th style="padding: 0.75rem;">Adjustments</th>
                    <th style="padding: 0.75rem;">Net Pay</th>
                    <th style="padding: 0.75rem;">Status</th>
                    <th style="padding: 0.75rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                \$cm = date('Y-m');
                if(isset(\$_POST['payroll_month'])) \$cm = \$_POST['payroll_month'];
                if(isset(\$_GET['month'])) \$cm = \$_GET['month']; // support get param
                
                \$pQ = \$conn->query("SELECT p.*, u.username FROM payroll p JOIN users u ON p.user_id = u.id WHERE p.month_year = '\$cm' AND p.tenant_id = {\$_SESSION['tenant_id']} ORDER BY p.id DESC");
                if(\$pQ && \$pQ->num_rows > 0) {
                    while(\$pr = \$pQ->fetch_assoc()) {
                        echo "<tr style='border-bottom: 1px solid var(--border-color);'>";
                        echo "<td style='padding: 0.75rem; font-weight:600; color:#0f172a;'>" . htmlspecialchars(\$pr['username']) . "</td>";
                        
                        echo "<td style='padding: 0.75rem; color:#475569;'>$" . number_format(\$pr['base_salary'],2) . "</td>";
                        
                        \$adj = "";
                        \$total_ded = \$pr['deductions'] + \$pr['manual_deductions'];
                        if(\$pr['bonuses'] > 0) \$adj .= "<span style='color:#10b981; font-weight:600; font-size:0.85rem;'>+\$" . number_format(\$pr['bonuses'],2) . "</span> ";
                        if(\$total_ded > 0) \$adj .= "<span style='color:#ef4444; font-weight:600; font-size:0.85rem;'>-\$" . number_format(\$total_ded,2) . "</span>";
                        if(\$adj === "") \$adj = "<span style='color:#cbd5e1;'>None</span>";
                        
                        echo "<td style='padding: 0.75rem;'>\$adj</td>";
                        
                        echo "<td style='padding: 0.75rem; font-weight:bold; color:#166534; font-size:1.1rem;'>$" . number_format(\$pr['net_salary'],2) . "</td>";
                        
                        echo "<td style='padding: 0.75rem;'>";
                        if(\$pr['status'] == 'unpaid') {
                            echo "<form method='POST' style='display:inline;'>
                                    <input type='hidden' name='action' value='pay_salary'>
                                    <input type='hidden' name='payroll_id' value='{\$pr['id']}'>
                                    <button class='btn' style='background:#f59e0b; color:white; padding:0.3rem 0.6rem; font-size:0.8rem; border-radius:4px;'>Mark Paid</button>
                                  </form>";
                        } else {
                            echo "<span style='color:white; background:#10b981; padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; font-weight:600;'>Paid</span>";
                        }
                        echo "</td>";
                        
                        echo "<td style='padding: 0.75rem;'>
                                <div style='display:flex; gap:0.5rem;'>
                                    <button class='btn' style='background:#f1f5f9; color:#475569; padding:0.3rem 0.6rem; font-size:0.8rem; border-radius:4px;' onclick='openEditModal(" . json_encode(\$pr) . ")'>Edit</button>
                                    <a href='print_salary_slip.php?id={\$pr['id']}' target='_blank' class='btn' style='background:#e0f2fe; color:#0284c7; padding:0.3rem 0.6rem; font-size:0.8rem; border-radius:4px; text-decoration:none;'>Print Slip</a>
                                </div>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='padding: 2rem; text-align:center; color:#94a3b8;'>No payroll generated for \$cm.<br>Update attendance and click Generate to calculate salaries.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Edit Payroll Modal -->
        <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
            <div style="background:white; padding:2rem; border-radius:12px; width:400px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1.5rem; color:#0f172a;">Edit Salary Adjustments</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_payroll_manual">
                    <input type="hidden" name="payroll_id" id="modal_payroll_id">
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Bonus Amount ($)</label>
                        <input type="number" step="0.01" name="bonuses" id="modal_bonuses" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Bonus Reason</label>
                        <input type="text" name="bonus_reason" id="modal_bonus_reason" placeholder="e.g. Excellent performance" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Manual Deduction ($)</label>
                        <input type="number" step="0.01" name="manual_deductions" id="modal_manual_deductions" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:500; color:#475569;">Deduction Reason</label>
                        <input type="text" name="deduction_reason" id="modal_deduction_reason" placeholder="e.g. Broken equipment" style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    
                    <div style="display:flex; justify-content:flex-end; gap:1rem;">
                        <button type="button" class="btn" style="background:#f1f5f9; color:#475569;" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn" style="background:#10b981; color:white;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function openEditModal(pr) {
            document.getElementById('modal_payroll_id').value = pr.id;
            document.getElementById('modal_bonuses').value = pr.bonuses;
            document.getElementById('modal_bonus_reason').value = pr.bonus_reason || '';
            document.getElementById('modal_manual_deductions').value = pr.manual_deductions;
            document.getElementById('modal_deduction_reason').value = pr.deduction_reason || '';
            document.getElementById('editModal').style.display = 'flex';
        }
        </script>
HTML;

$f = str_replace($bad_table, $good_table, $f);

file_put_contents("C:/xampp/htdocs/point of sale/hr.php", $f);
echo "UI updated!";
?>
