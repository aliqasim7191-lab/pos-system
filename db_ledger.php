<?php
include 'includes/db.php';
$conn->query("CREATE TABLE IF NOT EXISTS customer_ledger (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, type ENUM('sale', 'payment_received', 'advance_deposit', 'refund') NOT NULL, sale_id INT DEFAULT NULL, amount DECIMAL(10,2) NOT NULL, balance_after DECIMAL(10,2) NOT NULL, description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, is_cleared TINYINT(1) DEFAULT 0)");
echo "Done";
?>
