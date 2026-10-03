<?php
$f = file_get_contents("C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/task.md");
$f = str_replace("- [/] Modify `hr.php` backend logic", "- [x] Modify `hr.php` backend logic", $f);
$f = str_replace("- [ ] Apply `tenant_id` isolation to user fetch queries", "- [x] Apply `tenant_id` isolation to user fetch queries", $f);
$f = str_replace("- [ ] Handle `update_payroll_manual` POST action", "- [x] Handle `update_payroll_manual` POST action", $f);
$f = str_replace("- [ ] Factor in manual deductions/bonuses during `generate_payroll`", "- [x] Factor in manual deductions/bonuses during `generate_payroll`", $f);
$f = str_replace("- [ ] Modify `hr.php` UI", "- [/] Modify `hr.php` UI", $f);
file_put_contents("C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/task.md", $f);
echo "Tasks updated";
?>
