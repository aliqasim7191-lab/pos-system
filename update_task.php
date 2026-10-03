<?php
$f = file_get_contents("C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/task.md");
$f = str_replace("- [/] Execute Database Schema Updates", "- [x] Execute Database Schema Updates", $f);
$f = str_replace("- [ ] Add `deduction_reason` to `payroll`", "- [x] Add `deduction_reason` to `payroll`", $f);
$f = str_replace("- [ ] Add `bonus_reason` to `payroll`", "- [x] Add `bonus_reason` to `payroll`", $f);
$f = str_replace("- [ ] Add `manual_deductions` to `payroll`", "- [x] Add `manual_deductions` to `payroll`", $f);
$f = str_replace("- [ ] Modify `hr.php` backend logic", "- [/] Modify `hr.php` backend logic", $f);
file_put_contents("C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/task.md", $f);
echo "Tasks updated";
?>
