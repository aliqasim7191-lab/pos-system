<?php
include 'includes/db.php';

echo "Upgrading Database for Phase 2...\n";

// 1. Create Customers Table
$sql = "CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    customer_type ENUM('walk_in', 'registered', 'credit') DEFAULT 'registered',
    credit_limit DECIMAL(10,2) DEFAULT 0.00,
    outstanding_balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Customers table created.\n";
} else {
    echo "Error creating customers table: " . $conn->error . "\n";
}

// 2. Alter Products Table
$cols = [
    "purchase_price DECIMAL(10,2) DEFAULT 0.00",
    "wholesale_price DECIMAL(10,2) DEFAULT 0.00",
    "min_stock DECIMAL(10,2) DEFAULT 10",
    "max_stock DECIMAL(10,2) DEFAULT 100",
    "expiry_date DATE NULL",
    "brand VARCHAR(100) NULL",
    "status ENUM('active', 'inactive') DEFAULT 'active'"
];
foreach ($cols as $col) {
    $colName = explode(" ", $col)[0];
    $check = $conn->query("SHOW COLUMNS FROM products LIKE '$colName'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE products ADD COLUMN $col");
        echo "Added column $colName to products.\n";
    }
}

// 3. Alter Sales Table
$salesCols = [
    "customer_id INT NULL",
    "payment_method ENUM('cash', 'card', 'bank', 'credit') DEFAULT 'cash'",
    "status ENUM('completed', 'held', 'refunded') DEFAULT 'completed'",
    "amount_received DECIMAL(10,2) DEFAULT 0.00",
    "change_returned DECIMAL(10,2) DEFAULT 0.00"
];
foreach ($salesCols as $col) {
    $colName = explode(" ", $col)[0];
    $check = $conn->query("SHOW COLUMNS FROM sales LIKE '$colName'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE sales ADD COLUMN $col");
        echo "Added column $colName to sales.\n";
    }
}

echo "Database Upgrade Phase 2 Complete.\n";
?>
