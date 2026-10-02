<?php include 'includes/db.php'; $conn->query("ALTER TABLE purchases ADD COLUMN is_cleared TINYINT(1) DEFAULT 0"); echo 'Done'; ?>
