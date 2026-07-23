-- =====================================================================
-- Migration: adds patient billing capability
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

CREATE TABLE IF NOT EXISTS bills (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id    VARCHAR(20)  NOT NULL,              -- FK -> patients.patient_id
    description   VARCHAR(255) NOT NULL,               -- e.g. "Consultation Fee", "Lab Test - CBC"
    amount        DECIMAL(10,2) NOT NULL,
    status        ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
    created_by    INT UNSIGNED NOT NULL,               -- FK -> users.id (nurse/staff who assigned it)
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bills_patient
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bills_staff
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
