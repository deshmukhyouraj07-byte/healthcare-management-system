-- =====================================================================
-- Migration: adds doctor specialty + availability status
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

ALTER TABLE users
    ADD COLUMN specialty VARCHAR(100) DEFAULT NULL AFTER role,
    ADD COLUMN availability ENUM('available','not_available') NOT NULL DEFAULT 'available' AFTER specialty;
