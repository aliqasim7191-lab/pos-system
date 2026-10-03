<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/footer.php");
$f = str_replace("app2.js", "app3.js", $f);
file_put_contents("C:/xampp/htdocs/point of sale/includes/footer.php", $f);
echo "Renamed app2.js to app3.js";
?>
