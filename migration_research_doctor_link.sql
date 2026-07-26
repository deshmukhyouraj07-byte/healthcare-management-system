-- =====================================================================
-- Migration: links research projects to a specific doctor (not just a
-- free-text author name), and adds optional profile fields (qualifications,
-- bio) so each doctor's detail page has real content to show.
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

ALTER TABLE research_projects
    ADD COLUMN doctor_id INT UNSIGNED DEFAULT NULL AFTER author_name,
    ADD CONSTRAINT fk_research_doctor
        FOREIGN KEY (doctor_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE users
    ADD COLUMN qualifications VARCHAR(255) DEFAULT NULL AFTER specialty,
    ADD COLUMN bio TEXT DEFAULT NULL AFTER qualifications;
