-- =====================================================================
-- Healthcare Management System — Database Schema
-- Engine: MySQL 5.7+ / MariaDB 10.3+
-- =====================================================================

CREATE DATABASE IF NOT EXISTS healthcare_system
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE healthcare_system;

-- ---------------------------------------------------------------------
-- 1. USERS  (Employees / Admins / Nursing Staff)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,          -- password_hash() output (bcrypt)
    full_name       VARCHAR(120) NOT NULL,
    role            ENUM('employee', 'staff', 'admin') NOT NULL DEFAULT 'staff',
    email           VARCHAR(120) DEFAULT NULL,
    can_provision_credentials TINYINT(1) NOT NULL DEFAULT 1, -- right to create patient logins
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. PATIENTS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id        VARCHAR(20)  NOT NULL UNIQUE,   -- e.g. PT-2026-00042 (auto-generated)
    full_name         VARCHAR(120) NOT NULL,
    date_of_birth     DATE         NOT NULL,
    gender            ENUM('Male','Female','Other','Prefer not to say') NOT NULL,
    contact_info      VARCHAR(150) NOT NULL,          -- phone / email
    address           VARCHAR(255) DEFAULT NULL,
    medical_history   TEXT         DEFAULT NULL,       -- free-text current disease mgmt / history
    password_hash     VARCHAR(255) NOT NULL,           -- bcrypt hash for patient portal login
    registered_by     INT UNSIGNED NOT NULL,           -- FK -> users.id (staff who registered them)
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patients_registered_by
        FOREIGN KEY (registered_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. PATIENT_RECORDS (vitals / prescriptions / disease tracking logs)
--    Read-only for patients, writable by staff — powers the patient
--    dashboard's "vitals, prescriptions, disease tracking logs" view.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patient_records (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id    VARCHAR(20)  NOT NULL,              -- FK -> patients.patient_id
    record_type   ENUM('vital','prescription','disease_log') NOT NULL,
    title         VARCHAR(150) NOT NULL,
    details       TEXT         NOT NULL,
    recorded_by   INT UNSIGNED NOT NULL,               -- FK -> users.id
    recorded_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_records_patient
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_records_staff
        FOREIGN KEY (recorded_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Seed data (demo only — replace/remove in production)
-- Default password for the demo staff account below is: Admin@123
-- ---------------------------------------------------------------------
-- INSERT INTO users (username, password_hash, full_name, role)
-- VALUES ('admin', '$2y$10$replace_with_output_of_password_hash()', 'System Administrator', 'admin');
