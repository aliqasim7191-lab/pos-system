USE pos_system;

-- Add unit column to products and change stock to DECIMAL
ALTER TABLE products ADD COLUMN unit VARCHAR(20) NOT NULL DEFAULT 'pcs';
ALTER TABLE products MODIFY COLUMN stock DECIMAL(10, 2) NOT NULL DEFAULT 0.00;

-- Change sale_items quantity to DECIMAL
ALTER TABLE sale_items MODIFY COLUMN quantity DECIMAL(10, 2) NOT NULL;

-- Add discount column to sales
ALTER TABLE sales ADD COLUMN discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00;
