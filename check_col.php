<?php include 'includes/db.php'; $res = $conn->query("SELECT is_cleared FROM purchases LIMIT 1"); if($res){ echo 'Exists'; } else { echo 'Missing'; } ?>
