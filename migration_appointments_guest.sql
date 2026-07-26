-- =====================================================================
-- Migration: allows appointment booking WITHOUT a patient login
-- (new/walk-in patients can book directly). Run this AFTER
-- migration_appointments.sql, once against the existing database.
-- =====================================================================
USE healthcare_system;

ALTER TABLE appointments
    MODIFY COLUMN patient_id VARCHAR(20) NULL,           -- NULL for guest bookings
    ADD COLUMN guest_name    VARCHAR(120) DEFAULT NULL AFTER patient_id,
    ADD COLUMN guest_contact VARCHAR(50)  DEFAULT NULL AFTER guest_name,
    ADD COLUMN guest_email   VARCHAR(150) DEFAULT NULL AFTER guest_contact,
    ADD COLUMN access_token  VARCHAR(64)  DEFAULT NULL AFTER guest_email,
    ADD UNIQUE KEY uq_appointments_access_token (access_token);
