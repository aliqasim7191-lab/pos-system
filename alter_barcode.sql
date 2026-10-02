USE pos_system;
ALTER TABLE products ADD COLUMN barcode VARCHAR(50) DEFAULT NULL;
ALTER TABLE products ADD UNIQUE (barcode);
