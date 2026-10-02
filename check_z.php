<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
$res = $conn->query("SHOW COLUMNS FROM z_reports_history LIKE 'tenant_id'");
if($res->num_rows > 0) echo "Yes"; else echo "No";
?>
