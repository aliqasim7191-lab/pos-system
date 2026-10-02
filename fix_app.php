<?php
$app = file_get_contents("C:/xampp/htdocs/point of sale/assets/js/app2.js");
$app = preg_replace('/<p.*?>.*?<br>No items/', '<p style="text-align:center; padding: 2rem; color: var(--text-muted); font-size: 1.2rem;"><i class="fa fa-shopping-basket fa-3x" style="color:#cbd5e1; margin-bottom:1rem;"></i><br>No items', $app);
file_put_contents("C:/xampp/htdocs/point of sale/assets/js/app2.js", $app);
?>
