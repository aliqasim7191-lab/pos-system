USE pos_system;
ALTER TABLE sales 
ADD COLUMN customer_name VARCHAR(100) DEFAULT NULL,
ADD COLUMN customer_address TEXT DEFAULT NULL;
