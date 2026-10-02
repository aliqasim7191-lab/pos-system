<?php
include 'includes/db.php';

echo "<h2>Upgrading Database to Pro Level v2...</h2>";

// 1. Add points column to customers if not exists
$checkCol = $conn->query("SHOW COLUMNS FROM customers LIKE 'points'");
if ($checkCol && $checkCol->num_rows == 0) {
    if ($conn->query("ALTER TABLE customers ADD COLUMN points INT DEFAULT 0")) {
        echo "<p>✅ Added 'points' column to customers table.</p>";
    } else {
        echo "<p>❌ Error adding 'points' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ 'points' column already exists in customers.</p>";
}

// 2. Create Returns Table
$returnsQuery = "CREATE TABLE IF NOT EXISTS returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    user_id INT NOT NULL,
    total_refund DECIMAL(10,2) DEFAULT 0.00,
    return_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if ($conn->query($returnsQuery)) {
    echo "<p>✅ 'returns' table created/verified.</p>";
} else {
    echo "<p>❌ Error creating 'returns' table: " . $conn->error . "</p>";
}

// 3. Create Return Items Table
$returnItemsQuery = "CREATE TABLE IF NOT EXISTS return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    refund_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
)";
if ($conn->query($returnItemsQuery)) {
    echo "<p>✅ 'return_items' table created/verified.</p>";
} else {
    echo "<p>❌ Error creating 'return_items' table: " . $conn->error . "</p>";
}

// 4. Create Damaged Stock Table
$damagedStockQuery = "CREATE TABLE IF NOT EXISTS damaged_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    loss_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reason TEXT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if ($conn->query($damagedStockQuery)) {
    echo "<p>✅ 'damaged_stock' table created/verified.</p>";
} else {
    echo "<p>❌ Error creating 'damaged_stock' table: " . $conn->error . "</p>";
}

echo "<h3>Database Upgrade Complete!</h3>";
echo "<a href='index.php'>Go back to Home</a>";
?>
