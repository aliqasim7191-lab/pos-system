CREATE TABLE IF NOT EXISTS product_variations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variation_name VARCHAR(100) NOT NULL,
    barcode VARCHAR(100) NULL,
    stock DECIMAL(10,2) DEFAULT 0.00,
    price DECIMAL(10,2) NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

ALTER TABLE sale_items ADD COLUMN IF NOT EXISTS variation_id INT NULL AFTER product_id;
ALTER TABLE sale_items ADD CONSTRAINT fk_sale_item_var FOREIGN KEY IF NOT EXISTS (variation_id) REFERENCES product_variations(id) ON DELETE SET NULL;

ALTER TABLE stock_transfers ADD COLUMN IF NOT EXISTS variation_id INT NULL AFTER product_id;
ALTER TABLE stock_transfers ADD CONSTRAINT fk_transfer_var FOREIGN KEY IF NOT EXISTS (variation_id) REFERENCES product_variations(id) ON DELETE SET NULL;

ALTER TABLE purchase_items ADD COLUMN IF NOT EXISTS variation_id INT NULL AFTER product_id;
ALTER TABLE purchase_items ADD CONSTRAINT fk_purchase_var FOREIGN KEY IF NOT EXISTS (variation_id) REFERENCES product_variations(id) ON DELETE SET NULL;
