-- =====================================================================
-- Migration: adds appointment booking (with payment proof) + admission
-- status tracking for patients (admitted today / currently admitted).
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

-- ---------------------------------------------------------------------
-- Admission tracking on patients — powers the staff/doctor-only
-- "Admitted Today" / "Currently Admitted" dashboard views.
-- Deliberately NOT exposed on the public site (patient privacy).
-- ---------------------------------------------------------------------
ALTER TABLE patients
    ADD COLUMN admission_status ENUM('outpatient','admitted','discharged') NOT NULL DEFAULT 'outpatient' AFTER is_active,
    ADD COLUMN admitted_at   DATETIME DEFAULT NULL AFTER admission_status,
    ADD COLUMN discharged_at DATETIME DEFAULT NULL AFTER admitted_at;

-- ---------------------------------------------------------------------
-- Appointments — booked by a logged-in patient, tied to a specific
-- doctor, gated by an appointment fee that must be paid (QR + uploaded
-- screenshot) and verified by staff before it counts as confirmed.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id          VARCHAR(20)  NOT NULL,        -- FK -> patients.patient_id
    doctor_id           INT UNSIGNED NOT NULL,        -- FK -> users.id (role = employee)
    appointment_date    DATE NOT NULL,
    appointment_time    TIME NOT NULL,
    reason              VARCHAR(255) DEFAULT NULL,
    fee                 DECIMAL(10,2) NOT NULL DEFAULT 200.00,
    status              ENUM('pending_payment','pending_verification','confirmed','rejected') NOT NULL DEFAULT 'pending_payment',
    payment_screenshot  VARCHAR(255) DEFAULT NULL,     -- relative path under /uploads/appointment_payments/
    verified_by         INT UNSIGNED DEFAULT NULL,     -- FK -> users.id (staff who verified payment)
    verified_at         DATETIME DEFAULT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appt_patient
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_appt_doctor
        FOREIGN KEY (doctor_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_appt_verifier
        FOREIGN KEY (verified_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;
