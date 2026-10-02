<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
$res = $conn->query("SHOW TABLES LIKE '%categor%'");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
