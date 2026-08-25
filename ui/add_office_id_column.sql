-- Add office_id column to tbl_product table
-- Run this SQL in your database to enable office-based inventory tracking

ALTER TABLE tbl_product ADD COLUMN office_id INT DEFAULT NULL;

-- Add foreign key constraint (optional, for referential integrity)
ALTER TABLE tbl_product ADD FOREIGN KEY (office_id) REFERENCES tbl_office(id) ON DELETE SET NULL;

-- Create index for faster queries
CREATE INDEX idx_product_office ON tbl_product(office_id);
