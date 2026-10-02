<?php
$idx = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$idx = preg_replace('/>.*Reprint<\/button>/', '><i class="fa fa-print"></i> Reprint</button>', $idx);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $idx);
?>
