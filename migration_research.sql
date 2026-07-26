-- =====================================================================
-- Migration: adds research projects, organized by medical specialty
-- (matches the department list already used on the homepage's Services
-- section — see $specialties in translations.php).
-- Run this once against the existing healthcare_system database.
-- =====================================================================
USE healthcare_system;

CREATE TABLE IF NOT EXISTS research_projects (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    specialty      VARCHAR(100) NOT NULL,           -- e.g. "Cardiology", "General Surgery" — matches $specialties en_title
    title          VARCHAR(200) NOT NULL,
    author_name    VARCHAR(120) DEFAULT NULL,        -- student/resident/doctor name, free text
    status         ENUM('ongoing','completed') NOT NULL DEFAULT 'ongoing',
    description    TEXT NOT NULL,
    conclusion     TEXT DEFAULT NULL,                -- filled in once completed
    started_date   DATE NOT NULL,
    completed_date DATE DEFAULT NULL,
    created_by     INT UNSIGNED NOT NULL,             -- FK -> users.id (doctor who logged it)
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_research_staff
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
