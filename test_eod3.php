<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/end_of_day.php");
$s = strpos($f, "<?php endif; ?>\n        </div>");
echo substr($f, $s, 1000);
?>
