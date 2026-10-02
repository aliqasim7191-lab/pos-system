<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad_overlay = <<<PHP
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(2px);
PHP;

$good_overlay = <<<PHP
    background: rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(1px);
PHP;

$bad_modal = <<<PHP
.shift-modal {
    background: white;
    padding: 3rem 4rem;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    text-align: center;
    animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    border: 1px solid rgba(0,0,0,0.05);
}
PHP;

$good_modal = <<<PHP
.shift-modal {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    padding: 3.5rem 4.5rem;
    border-radius: 28px;
    box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.4), inset 0 2px 4px rgba(255,255,255,1);
    text-align: center;
    animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    border: 1px solid rgba(226, 232, 240, 0.8);
    position: relative;
    overflow: hidden;
}
.shift-modal::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 6px;
    background: linear-gradient(90deg, #3b82f6, #0ea5e9, #2dd4bf);
}
PHP;

$f = str_replace($bad_overlay, $good_overlay, $f);
$f = str_replace($bad_modal, $good_modal, $f);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Modal attractive styling added!\n";
?>
