<?php
include 'includes/db.php';

$sql = "ALTER TABLE customers ADD COLUMN loyalty_points INT NOT NULL DEFAULT 0";
if ($conn->query($sql)) {
    echo "Successfully added loyalty_points to customers table.\n";
} else {
    echo "Error or already exists: " . $conn->error . "\n";
}
?>
