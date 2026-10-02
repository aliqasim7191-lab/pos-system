<?php
include 'includes/db.php';
$conn->query("CREATE TABLE IF NOT EXISTS held_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_note VARCHAR(255) NOT NULL,
    customer_id INT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS held_sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    held_sale_id INT,
    product_id INT,
    quantity DECIMAL(10,2),
    price DECIMAL(10,2),
    FOREIGN KEY (held_sale_id) REFERENCES held_sales(id) ON DELETE CASCADE
)");
echo "Held sales tables created.";
?>
