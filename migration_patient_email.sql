-- =====================================================================
-- Migration: adds a dedicated email address field to patients
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

ALTER TABLE patients
    ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER contact_info;
