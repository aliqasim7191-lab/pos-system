<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad_style = <<<PHP
.shift-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(3px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
PHP;

$good_style = <<<PHP
.shift-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.15); /* Very light dark overlay */
    backdrop-filter: none; /* No blur, perfectly clear background */
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
PHP;

$bad_icon = <<<PHP
        <div class="lock-icon-container">
            <i class="fa fa-lock"></i>
        </div>
PHP;

$good_icon = <<<PHP
        <div class="lock-icon-container" style="font-size: 3rem; line-height: 1;">
            🔒
        </div>
PHP;

$f = str_replace($bad_style, $good_style, $f);
$f = str_replace($bad_icon, $good_icon, $f);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Icon and background fixed!\n";
?>
