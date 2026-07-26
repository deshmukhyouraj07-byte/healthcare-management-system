-- =====================================================================
-- CORRECTED password reset for all 5 nurse/staff accounts.
--
-- Why: the previous SQL I gave you used a "$2b$" bcrypt prefix (from the
-- tool I used to generate it). That is cryptographically the same
-- algorithm as PHP's own $2y$ format for these passwords, but PHP's
-- password_verify() specifically expects the "$2y$" prefix it generates
-- itself — a "$2b$" hash can fail to verify on some PHP builds, which is
-- almost certainly why you got "Invalid username or password."
--
-- This version uses "$2y$" instead. Re-run this — it will overwrite
-- whatever the previous script set.
--
--   Username   Password
--   --------   -----------
--   Vijay      Vijay@123
--   Jayesh     Jayesh@123
--   Sahil      Sahil@123
--   Tanuja     Tanuja@123
--   Sakshi     Sakshi@123
--
-- Run this once in phpMyAdmin (SQL tab) on the healthcare_system database.
-- =====================================================================
USE healthcare_system;

UPDATE users SET password_hash = '$2y$10$s5Jy9Vf4RenntHCdQUQABOy3ceOL3vA0BHRnr6xbUOMlJ5tkXyWJK' WHERE username = 'Vijay';
UPDATE users SET password_hash = '$2y$10$TMWJWsbnj14ByvsXLf8Pt.GWpl.ETM9sEdaEwyFiWKw3Ig4X5RKr.' WHERE username = 'Jayesh';
UPDATE users SET password_hash = '$2y$10$qdyCjW8YGKC1dnxrOhemMuiPtiPT7.l7CrGYF6XTXMgkXpbMr23ei' WHERE username = 'Sahil';
UPDATE users SET password_hash = '$2y$10$/a4yoiafCkSTJgMaWMHM9eRH1pyDCfrSzzU5PK0gs0zcghiFrhcs2' WHERE username = 'Tanuja';
UPDATE users SET password_hash = '$2y$10$GHt6il29lX9tqapaaz5hOuX61xjY2shVMl23zaA.Yz/CYbaFw2Ba2' WHERE username = 'Sakshi';
