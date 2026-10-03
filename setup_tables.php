<?php
$conn = new mysqli('localhost', 'root', '', 'pos_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Held Sales Table
$conn->query("CREATE TABLE IF NOT EXISTS held_sales (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    reference_name VARCHAR(255), 
    cart_data TEXT, 
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Audit Logs Table
$conn->query("CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    module VARCHAR(100),
    record_id INT,
    previous_value TEXT,
    new_value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Settings Table
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
)");

echo "Tables created successfully.";
$conn->close();
?>
