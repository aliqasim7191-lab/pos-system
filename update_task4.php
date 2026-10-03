<?php
$f = file_get_contents("C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/task.md");
$f = str_replace("- [/] Create `print_salary_slip.php`", "- [x] Create `print_salary_slip.php`", $f);
$f = str_replace("- [ ] Query payroll and user data", "- [x] Query payroll and user data", $f);
$f = str_replace("- [ ] Design printable A4 CSS layout", "- [x] Design printable A4 CSS layout", $f);
$f = str_replace("- [ ] Auto-trigger `window.print()`", "- [x] Auto-trigger `window.print()`", $f);
file_put_contents("C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/task.md", $f);
echo "Tasks updated";
?>
