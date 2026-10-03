<?php
$f = file_get_contents("C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/task.md");
$f = str_replace("- [/] Modify `hr.php` UI", "- [x] Modify `hr.php` UI", $f);
$f = str_replace("- [ ] Add Edit Modal for Bonuses & Deductions", "- [x] Add Edit Modal for Bonuses & Deductions", $f);
$f = str_replace("- [ ] Add 'Print Slip' button to the table", "- [x] Add 'Print Slip' button to the table", $f);
$f = str_replace("- [ ] Create `print_salary_slip.php`", "- [/] Create `print_salary_slip.php`", $f);
file_put_contents("C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/task.md", $f);
echo "Tasks updated";
?>
