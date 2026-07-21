-- =====================================================================
-- Migration: makes email unique so patient login-by-email works reliably
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

ALTER TABLE patients
    ADD UNIQUE KEY uq_patients_email (email);
