-- =====================================================================
-- Migration: adds prescribed medicines + next appointment date to patients
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

ALTER TABLE patients
    ADD COLUMN prescribed_medicines TEXT DEFAULT NULL AFTER medical_history,
    ADD COLUMN next_appointment_date DATE DEFAULT NULL AFTER prescribed_medicines;
