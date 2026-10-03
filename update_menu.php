<?php
\ = 'C:/xampp/htdocs/point of sale/includes/header.php';
\ = file_get_contents(\);

// 1. Replace the JS cloning logic with hardcoded dropdown links
\ = '
                <div id="kebabDropdown" style="display: none; position: absolute; top: calc(100% + 12px); left: 0; background: #ffffff; min-width: 240px; box-shadow: 0 12px 30px rgba(0,0,0,0.15); border-radius: 12px; z-index: 9999; padding: 0.5rem 0; max-height: 75vh; overflow-y: auto; overflow-x: hidden;">
                    <div style="padding: 0.5rem 1rem 0.3rem; font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Admin Menu</div>
                    <?php if(\): ?>
                    <a href="staff.php" class="<?php echo \ == \'staff.php\' ? \'active\' : \'\'; ?>">dY Staff</a>
                    <a href="hr.php" class="<?php echo \ == \'hr.php\' ? \'active\' : \'\'; ?>">dY\' HR & Payroll</a>
                    <a href="settings.php" class="<?php echo \ == \'settings.php\' ? \'active\' : \'\'; ?>">?? Settings</a>
                    <a href="audit_logs.php" class="<?php echo \ == \'audit_logs.php\' ? \'active\' : \'\'; ?>">?? Audit Logs</a>
                    <?php endif; ?>
                </div>
';

\ = preg_replace('/<div id="kebabDropdown"([^>]*)>(.*?)<\/div>/s', \, \);

// 2. Remove the JS cloning block completely
\ = preg_replace('/<script>\s*document\.addEventListener\("DOMContentLoaded".*?<\/script>/s', '
        <script>
        document.addEventListener("click", function(e) {
            const dropdown = document.getElementById("kebabDropdown");
            const container = document.getElementById("kebabMenuContainer");
            if(dropdown && container && !container.contains(e.target)) {
                dropdown.classList.remove("show-dropdown");
            }
        });
        </script>
', \);

// 3. Remove these 4 links from the main nav
\ = preg_replace('/<li><a href="staff\.php".*?<\/li>/', '', \);
\ = preg_replace('/<li><a href="hr\.php".*?<\/li>/', '', \);
\ = preg_replace('/<li><a href="settings\.php".*?<\/li>/', '', \);
\ = preg_replace('/<li><a href="audit_logs\.php".*?<\/li>/', '', \);

file_put_contents(\, \);
echo "Updated header.php\n";
?>
