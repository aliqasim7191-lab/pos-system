<?php
require 'includes/db.php';
$res = $conn->query("DESCRIBE purchases");
while($row = $res->fetch_assoc()){
    print_r($row);
}
?>
