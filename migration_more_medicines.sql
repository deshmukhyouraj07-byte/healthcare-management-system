-- =====================================================================
-- Migration: adds dosage_instructions column + expands medicine catalog
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

ALTER TABLE medicines
    ADD COLUMN dosage_instructions VARCHAR(150) DEFAULT NULL AFTER description;

-- ---------------------------------------------------------------------
-- Fever
-- ---------------------------------------------------------------------
INSERT INTO medicines (name, description, dosage_instructions, category, price, stock_qty) VALUES
('Paracetamol 500mg', 'Fever and pain relief tablet, strip of 10', 'Take with water after food, every 6-8 hours', 'Fever', 25.00, 200),
('Paracetamol 650mg', 'Stronger fever/pain relief tablet, strip of 10', 'Take with water after food, every 6-8 hours', 'Fever', 32.00, 180),
('Ibuprofen 400mg', 'Fever, pain and inflammation relief, strip of 10', 'Take with water, always after food', 'Fever', 40.00, 140),
('Paracetamol Syrup (Children)', 'Fever relief syrup for children, 60ml bottle', 'Take with a measuring cup as directed, after food', 'Fever', 55.00, 90),

-- ---------------------------------------------------------------------
-- Anemia
-- ---------------------------------------------------------------------
('Ferrous Sulfate 325mg', 'Iron supplement for anemia, strip of 10', 'Take with water or juice, preferably on an empty stomach', 'Anemia', 45.00, 130),
('Folic Acid 5mg', 'Supports red blood cell production, strip of 10', 'Take with water, once daily', 'Anemia', 20.00, 160),
('Iron + Folic Acid Combo', 'Combined iron and folic acid tablets, strip of 15', 'Take with water after food to reduce stomach upset', 'Anemia', 60.00, 100),
('Vitamin B12 Tablets', 'Supports nerve and blood cell health, strip of 10', 'Take with water, once daily', 'Anemia', 50.00, 110),

-- ---------------------------------------------------------------------
-- Body Pain / General Pain Relief
-- ---------------------------------------------------------------------
('Diclofenac 50mg', 'Pain and inflammation relief for body aches, strip of 10', 'Take with water after food', 'Pain Relief', 35.00, 150),
('Aceclofenac + Paracetamol', 'Combination tablet for body pain and fever, strip of 10', 'Take with water after food, twice daily', 'Pain Relief', 48.00, 120),
('Muscle Relaxant Tablet', 'Relieves muscle spasm and body stiffness, strip of 10', 'Take with water after food', 'Pain Relief', 55.00, 95),
('Pain Relief Gel', 'Topical gel for localized body pain, 30g tube', 'Apply externally on affected area, 2-3 times daily', 'Pain Relief', 70.00, 85),

-- ---------------------------------------------------------------------
-- Typhoid
-- ---------------------------------------------------------------------
('Ciprofloxacin 500mg', 'Antibiotic used for typhoid and bacterial infections, strip of 10', 'Take with water, avoid dairy products near dosing time', 'Typhoid', 65.00, 100),
('Azithromycin 500mg', 'Antibiotic commonly used for typhoid fever, strip of 3', 'Take with water, once daily on empty stomach', 'Typhoid', 85.00, 90),
('Ofloxacin 200mg', 'Antibiotic for typhoid and related infections, strip of 10', 'Take with water after food, twice daily', 'Typhoid', 58.00, 80),

-- ---------------------------------------------------------------------
-- Pneumonia
-- ---------------------------------------------------------------------
('Amoxicillin 500mg', 'Antibiotic used for pneumonia and respiratory infections, strip of 10', 'Take with water after food, every 8 hours', 'Pneumonia', 70.00, 110),
('Azithromycin 250mg', 'Antibiotic for respiratory tract infections, strip of 6', 'Take with water, once daily on empty stomach', 'Pneumonia', 95.00, 75),
('Cough & Chest Congestion Syrup', 'Relieves cough and chest congestion, 100ml bottle', 'Take with a measuring cup as directed, after food', 'Pneumonia', 60.00, 100),

-- ---------------------------------------------------------------------
-- Thyroid
-- ---------------------------------------------------------------------
('Levothyroxine 50mcg', 'Thyroid hormone replacement for hypothyroidism, strip of 10', 'Take with water on an empty stomach, 30 mins before breakfast', 'Thyroid', 40.00, 130),
('Levothyroxine 100mcg', 'Higher-dose thyroid hormone replacement, strip of 10', 'Take with water on an empty stomach, 30 mins before breakfast', 'Thyroid', 48.00, 110),
('Methimazole 5mg', 'Used to manage hyperthyroidism, strip of 10', 'Take with water after food, as directed by physician', 'Thyroid', 55.00, 70),

-- ---------------------------------------------------------------------
-- General / Supplements (kept for a rounder catalog)
-- ---------------------------------------------------------------------
('ORS Sachet', 'Oral rehydration salts for dehydration, box of 10 sachets', 'Mix one sachet with 1 liter clean water, drink through the day', 'General', 45.00, 250),
('Vitamin D3 Softgel', 'Bone health supplement, bottle of 30', 'Take with water after food, once weekly or as directed', 'Supplements', 180.00, 80),
('Cetirizine 10mg', 'Antihistamine for allergy relief, strip of 10', 'Take with water, preferably at night', 'Allergy', 30.00, 150);
