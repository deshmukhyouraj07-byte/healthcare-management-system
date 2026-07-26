-- =====================================================================
-- Migration: adds full basic-info fields for GUEST appointment bookings
-- (date of birth, gender, address) so a new/walk-in patient can fill in
-- proper intake details without needing an account.
-- Run this once, after migration_appointments_guest.sql.
-- =====================================================================
USE healthcare_system;

ALTER TABLE appointments
    ADD COLUMN guest_dob     DATE DEFAULT NULL AFTER guest_email,
    ADD COLUMN guest_gender  ENUM('Male','Female','Other','Prefer not to say') DEFAULT NULL AFTER guest_dob,
    ADD COLUMN guest_address VARCHAR(255) DEFAULT NULL AFTER guest_gender;
