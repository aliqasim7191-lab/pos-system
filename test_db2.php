<?php
include "C:/xampp/htdocs/point of sale/includes/db.php";
$q = $conn->query("DESCRIBE z_reports_history");
while($r = $q->fetch_assoc()){
    echo $r['Field'] . " | ";
}
echo "\n";
?>
