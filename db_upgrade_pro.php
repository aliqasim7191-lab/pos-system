<?php
include 'includes/db.php';

// 1. Shifts Table
$conn->query("CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    opening_cash DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    closing_cash DECIMAL(10,2) NULL,
    expected_cash DECIMAL(10,2) NULL,
    status ENUM('open', 'closed') DEFAULT 'open'
)");

// 2. Suppliers Table
$conn->query("CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL,
    outstanding_payable DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 3. Purchases Tables
$conn->query("CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'received') DEFAULT 'received',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE
)");

// Held Sales Tables
$conn->query("CREATE TABLE IF NOT EXISTS held_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_name VARCHAR(100),
    reference_note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS held_sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    held_sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (held_sale_id) REFERENCES held_sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)");

// Alerts and Audit Logs
$conn->query("CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    action_link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)");

// Settings Table
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(100) NOT NULL DEFAULT 'SuperStore POS',
    store_phone VARCHAR(50) NULL,
    store_address TEXT NULL,
    store_email VARCHAR(100) NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    currency_symbol VARCHAR(10) DEFAULT '$',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default settings if empty
$checkSettings = $conn->query("SELECT id FROM settings");
if ($checkSettings && $checkSettings->num_rows == 0) {
    $conn->query("INSERT INTO settings (store_name, store_phone, store_address, store_email, tax_rate, currency_symbol, tenant_id) VALUES ('ALI OFFICIAL STORE', '+92 300 1234567', '123 Main Commercial Area\nCity, Country, 54000', 'info@aliofficial.com', 0.00, '$', {$_SESSION['tenant_id']})");
}

// 4. Expenses Table
$conn->query("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 5. Z-Report History Table
$conn->query("CREATE TABLE IF NOT EXISTS z_reports_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    total_orders INT DEFAULT 0,
    total_sales DECIMAL(10,2) DEFAULT 0,
    total_expenses DECIMAL(10,2) DEFAULT 0,
    total_supplier_payments DECIMAL(10,2) DEFAULT 0,
    net_income DECIMAL(10,2) DEFAULT 0,
    snapshot_data LONGTEXT,
    opening_cash DECIMAL(10,2) DEFAULT 0,
    expected_cash DECIMAL(10,2) DEFAULT 0,
    closing_cash DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

try {
    $conn->query("ALTER TABLE z_reports_history ADD COLUMN opening_cash DECIMAL(10,2) DEFAULT 0 AFTER snapshot_data");
    $conn->query("ALTER TABLE z_reports_history ADD COLUMN expected_cash DECIMAL(10,2) DEFAULT 0 AFTER opening_cash");
    $conn->query("ALTER TABLE z_reports_history ADD COLUMN closing_cash DECIMAL(10,2) DEFAULT 0 AFTER expected_cash");
    echo "Added cash tracking columns to z_reports_history.<br>";
} catch (Exception $e) {}

// 5. Alter Products Table
try {
    $conn->query("ALTER TABLE products ADD COLUMN wholesale_price DECIMAL(10,2) NULL AFTER price");
} catch (Exception $e) {
    // Ignore duplicate column error
}

// 6. Quotations Tables (Similar to Sales)
$conn->query("CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    customer_name VARCHAR(100) NULL,
    customer_address TEXT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft', 'converted') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS quotation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
)");

// 6. Session Clearing Columns (Historical Z-Reports)
try {
    $conn->query("ALTER TABLE sales ADD COLUMN is_cleared TINYINT(1) DEFAULT 0 AFTER discount");
    echo "Added is_cleared to sales.<br>";
} catch (Exception $e) {}

try {
    $conn->query("ALTER TABLE expenses ADD COLUMN is_cleared TINYINT(1) DEFAULT 0 AFTER amount");
    echo "Added is_cleared to expenses.<br>";
} catch (Exception $e) {}

try {
    $conn->query("ALTER TABLE purchases ADD COLUMN is_cleared TINYINT(1) DEFAULT 0 AFTER amount_paid");
    echo "Added is_cleared to purchases.<br>";
} catch (Exception $e) {}

// 7. Monthly Close System
$conn->query("CREATE TABLE IF NOT EXISTS monthly_reports_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_month VARCHAR(10) NOT NULL,
    total_sales DECIMAL(10,2) DEFAULT 0,
    total_expenses DECIMAL(10,2) DEFAULT 0,
    total_supplier_payments DECIMAL(10,2) DEFAULT 0,
    net_income DECIMAL(10,2) DEFAULT 0,
    snapshot_data LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

try {
    $conn->query("ALTER TABLE sales ADD COLUMN is_monthly_cleared TINYINT(1) DEFAULT 0 AFTER is_cleared");
} catch (Exception $e) {}

try {
    $conn->query("ALTER TABLE expenses ADD COLUMN is_monthly_cleared TINYINT(1) DEFAULT 0 AFTER is_cleared");
} catch (Exception $e) {}

try {
    $conn->query("ALTER TABLE purchases ADD COLUMN is_monthly_cleared TINYINT(1) DEFAULT 0 AFTER is_cleared");
} catch (Exception $e) {}

echo "Pro-Level Database Upgrade Completed Successfully.";
?>
