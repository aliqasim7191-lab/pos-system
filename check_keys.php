<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
$res = $conn->query("SHOW INDEXES FROM settings");
while($row = $res->fetch_assoc()) {
    echo $row['Key_name'] . " | " . $row['Column_name'] . "\n";
}
?>
