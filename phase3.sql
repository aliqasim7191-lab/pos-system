CREATE TABLE IF NOT EXISTS tax_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some default tax classes
INSERT INTO tax_classes (name, rate) VALUES ('Standard Tax', 10.00), ('Zero Tax', 0.00), ('Luxury Tax', 20.00)
ON DUPLICATE KEY UPDATE name=name;

-- Add tax_class_id to products
ALTER TABLE products ADD COLUMN IF NOT EXISTS tax_class_id INT NULL AFTER category;
ALTER TABLE products ADD CONSTRAINT fk_prod_tax FOREIGN KEY IF NOT EXISTS (tax_class_id) REFERENCES tax_classes(id) ON DELETE SET NULL;

-- Insert currency settings if not exist
INSERT INTO settings (setting_key, setting_value) VALUES ('currency_symbol', '$') ON DUPLICATE KEY UPDATE setting_key=setting_key;
INSERT INTO settings (setting_key, setting_value) VALUES ('currency_code', 'USD') ON DUPLICATE KEY UPDATE setting_key=setting_key;
INSERT INTO settings (setting_key, setting_value) VALUES ('secondary_currency_symbol', 'PKR') ON DUPLICATE KEY UPDATE setting_key=setting_key;
INSERT INTO settings (setting_key, setting_value) VALUES ('exchange_rate', '278.50') ON DUPLICATE KEY UPDATE setting_key=setting_key;

