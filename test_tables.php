<?php
include "C:/xampp/htdocs/point of sale/includes/db.php";
$q = $conn->query("SHOW TABLES");
while($r = $q->fetch_array()){
    echo $r[0] . " | ";
}
echo "\n";
?>
