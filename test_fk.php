<?php
include "C:/xampp/htdocs/point of sale/includes/db.php";
$q = $conn->query("SHOW CREATE TABLE users");
$r = $q->fetch_assoc();
echo $r['Create Table'] . "\n";
?>
