<?php include 'includes/db.php'; $res = $conn->query("DESCRIBE expenses"); while($row = $res->fetch_assoc()) echo $row['Field'] . ' '; ?>
