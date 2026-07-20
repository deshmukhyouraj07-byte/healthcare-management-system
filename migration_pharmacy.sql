-- =====================================================================
-- Migration: adds Pharmacy / Medicine Ordering feature
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

CREATE TABLE IF NOT EXISTS medicines (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    description   VARCHAR(255) DEFAULT NULL,
    category      VARCHAR(100) DEFAULT NULL,        -- e.g. "Pain Relief", "Antibiotics"
    price         DECIMAL(10,2) NOT NULL,
    stock_qty     INT UNSIGNED NOT NULL DEFAULT 0,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS medicine_orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id       VARCHAR(20)  NOT NULL,          -- FK -> patients.patient_id
    delivery_type    ENUM('home_delivery','pickup') NOT NULL DEFAULT 'pickup',
    delivery_address VARCHAR(255) DEFAULT NULL,
    total_amount     DECIMAL(10,2) NOT NULL,
    payment_status   ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_patient
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS medicine_order_items (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id       INT UNSIGNED NOT NULL,
    medicine_id    INT UNSIGNED NOT NULL,
    medicine_name  VARCHAR(150) NOT NULL,             -- snapshot at time of order
    quantity       INT UNSIGNED NOT NULL,
    unit_price     DECIMAL(10,2) NOT NULL,
    subtotal       DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_orderitems_order
        FOREIGN KEY (order_id) REFERENCES medicine_orders(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_orderitems_medicine
        FOREIGN KEY (medicine_id) REFERENCES medicines(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Demo medicines — replace with your real inventory, or delete this block
-- ---------------------------------------------------------------------
INSERT INTO medicines (name, description, category, price, stock_qty) VALUES
    ('Paracetamol 500mg', 'Pain relief and fever reducer, strip of 10 tablets', 'Pain Relief', 25.00, 200),
    ('Amoxicillin 250mg', 'Antibiotic capsules, strip of 10', 'Antibiotics', 60.00, 120),
    ('Cetirizine 10mg', 'Antihistamine for allergy relief, strip of 10', 'Allergy', 30.00, 150),
    ('Vitamin D3 Softgel', 'Bone health supplement, bottle of 30', 'Supplements', 180.00, 80),
    ('ORS Sachet', 'Oral rehydration salts, box of 10 sachets', 'General', 45.00, 300);
