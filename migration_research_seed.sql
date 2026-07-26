-- =====================================================================
-- Seeds one ongoing + one completed sample research project for each
-- specialty department (matches $specialties in translations.php).
-- These are PLACEHOLDER entries so the research pages aren't empty —
-- edit or delete them freely once you have real projects logged by your
-- doctors (via their dashboard in portal.php).
--
-- created_by picks any existing doctor account automatically, so this
-- runs safely regardless of which accounts you've already created.
-- Run this once, AFTER migration_research_doctor_link.sql.
-- =====================================================================
USE healthcare_system;

INSERT INTO research_projects (specialty, title, author_name, doctor_id, status, description, conclusion, started_date, completed_date, created_by)
SELECT * FROM (SELECT
    'Cardiology' AS specialty,
    'Early Detection Markers for Coronary Artery Disease in Urban Patients' AS title,
    NULL AS author_name, NULL AS doctor_id, 'ongoing' AS status,
    'A study tracking lipid profile and ECG patterns in patients aged 40-60 to identify early warning indicators ahead of standard diagnostic thresholds.' AS description,
    NULL AS conclusion, '2026-02-01' AS started_date, NULL AS completed_date,
    (SELECT id FROM users WHERE role='employee' LIMIT 1) AS created_by
) t
UNION ALL SELECT * FROM (SELECT
    'Cardiology', 'Post-Angioplasty Recovery Outcomes: A One-Year Follow-Up', NULL, NULL, 'completed',
    'Followed 60 post-angioplasty patients over 12 months to assess recovery quality, medication adherence, and recurrence rates.',
    'Patients with structured follow-up calls showed 30% better medication adherence and fewer readmissions.',
    '2025-01-15', '2025-12-20', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Ophthalmology', 'Digital Eye Strain Patterns Among Young Adults', NULL, NULL, 'ongoing',
    'Surveying screen-time habits and correlating them with early symptoms of digital eye strain in patients aged 18-30.',
    NULL, '2026-03-10', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Ophthalmology', 'Outcomes of Same-Day Cataract Surgery Discharge', NULL, NULL, 'completed',
    'Reviewed recovery outcomes for patients discharged the same day as cataract surgery versus overnight observation.',
    'Same-day discharge showed comparable recovery outcomes with no increase in complication rates.',
    '2025-04-01', '2025-10-15', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Pediatrics', 'Vaccination Adherence Patterns in Semi-Urban Households', NULL, NULL, 'ongoing',
    'Tracking on-time vaccination rates and identifying common reasons for delayed doses among registered pediatric patients.',
    NULL, '2026-01-20', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Pediatrics', 'Growth Chart Deviations in Children Under 5', NULL, NULL, 'completed',
    'Analyzed growth chart data from routine checkups to flag early nutritional deficiencies in children under 5.',
    'Identified a recurring pattern linking early deviation to iron-deficiency anemia, prompting earlier screening recommendations.',
    '2025-02-01', '2025-11-30', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Gynecology & Obstetrics', 'Maternal Nutrition and Third-Trimester Outcomes', NULL, NULL, 'ongoing',
    'Studying the relationship between third-trimester nutritional supplementation and birth weight outcomes.',
    NULL, '2026-02-15', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Gynecology & Obstetrics', 'Postpartum Follow-Up Compliance Study', NULL, NULL, 'completed',
    'Reviewed postpartum follow-up visit attendance and its correlation with early detection of complications.',
    'Reminder calls at the 2-week mark improved follow-up attendance significantly.',
    '2025-03-01', '2025-09-30', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Orthopedics', 'Recovery Timelines for Knee Replacement in Older Adults', NULL, NULL, 'ongoing',
    'Tracking mobility recovery milestones for patients over 60 following total knee replacement surgery.',
    NULL, '2026-01-05', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Orthopedics', 'Physiotherapy Timing After Fracture Surgery', NULL, NULL, 'completed',
    'Compared outcomes between early versus delayed physiotherapy start after fracture fixation surgery.',
    'Early physiotherapy (within 72 hours) was associated with faster return to mobility with no added risk.',
    '2025-01-10', '2025-08-25', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Neurology', 'Migraine Trigger Patterns in Working Professionals', NULL, NULL, 'ongoing',
    'Logging lifestyle and workplace factors correlated with migraine frequency among working-age patients.',
    NULL, '2026-02-20', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Neurology', 'Early Stroke Symptom Recognition Study', NULL, NULL, 'completed',
    'Assessed patient and family awareness of early stroke symptoms at the time of hospital admission.',
    'Awareness campaigns correlated with faster hospital arrival times among surveyed families.',
    '2025-02-10', '2025-10-05', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'General Surgery', 'Laparoscopic vs Open Appendectomy Recovery Comparison', NULL, NULL, 'ongoing',
    'Comparing hospital stay duration and complication rates between laparoscopic and open appendectomy procedures.',
    NULL, '2026-03-01', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'General Surgery', 'Surgical Site Infection Prevention Protocol Review', NULL, NULL, 'completed',
    'Reviewed infection rates before and after introducing a revised pre-operative antiseptic protocol.',
    'The revised protocol reduced surgical site infection rates in the reviewed sample.',
    '2025-01-01', '2025-07-15', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'ENT (Ear, Nose & Throat)', 'Hearing Loss Patterns in Industrial Workers', NULL, NULL, 'ongoing',
    'Screening long-term factory workers for noise-induced hearing loss and correlating it with years of exposure.',
    NULL, '2026-02-05', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'ENT (Ear, Nose & Throat)', 'Chronic Sinusitis Treatment Outcomes Review', NULL, NULL, 'completed',
    'Compared symptom relief outcomes between medical management and surgical intervention for chronic sinusitis.',
    'Surgical intervention showed faster symptom relief in patients unresponsive to 3+ months of medical management.',
    '2025-02-01', '2025-09-10', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Dermatology', 'Seasonal Skin Allergy Patterns in Pune Patients', NULL, NULL, 'ongoing',
    'Tracking seasonal variation in allergic skin reactions reported at the outpatient clinic over one year.',
    NULL, '2026-01-25', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Dermatology', 'Topical vs Oral Treatment for Moderate Acne', NULL, NULL, 'completed',
    'Compared patient-reported outcomes between topical-only and combined oral-topical acne treatment regimens.',
    'Combined regimens showed faster visible improvement within the first 6 weeks.',
    '2025-03-01', '2025-09-20', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Psychiatry', 'Workplace Stress and Sleep Quality Correlation Study', NULL, NULL, 'ongoing',
    'Surveying patients on workplace stress levels and correlating findings with self-reported sleep quality.',
    NULL, '2026-02-10', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Psychiatry', 'Group Therapy Attendance and Outcome Study', NULL, NULL, 'completed',
    'Reviewed attendance patterns in group therapy sessions and their correlation with self-reported improvement.',
    'Consistent attendance (80%+ sessions) correlated with markedly better self-reported outcomes.',
    '2025-01-15', '2025-08-30', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Urology', 'Kidney Stone Recurrence Risk Factors Study', NULL, NULL, 'ongoing',
    'Tracking dietary and hydration habits among patients with a history of kidney stones to identify recurrence risk factors.',
    NULL, '2026-02-01', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Urology', 'Minimally Invasive vs Traditional Stone Removal Outcomes', NULL, NULL, 'completed',
    'Compared recovery time and complication rates between minimally invasive and traditional stone removal procedures.',
    'Minimally invasive procedures showed shorter hospital stays with comparable complication rates.',
    '2025-01-05', '2025-07-30', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Nephrology', 'Early CKD Screening in Diabetic Patients', NULL, NULL, 'ongoing',
    'Screening diabetic patients for early chronic kidney disease markers as part of routine diabetes follow-up.',
    NULL, '2026-01-15', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Nephrology', 'Dialysis Patient Quality-of-Life Review', NULL, NULL, 'completed',
    'Surveyed dialysis patients on quality-of-life factors to identify areas for care improvement.',
    'Scheduling flexibility was the single largest factor affecting reported quality of life.',
    '2025-02-01', '2025-10-01', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Gastroenterology', 'Dietary Triggers in IBS Patients: A Tracking Study', NULL, NULL, 'ongoing',
    'Patients log daily meals and symptoms to identify individual dietary triggers for irritable bowel syndrome.',
    NULL, '2026-02-20', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Gastroenterology', 'Endoscopy Wait-Time Impact on Diagnosis Outcomes', NULL, NULL, 'completed',
    'Reviewed whether wait time for endoscopy affected diagnostic outcomes for suspected upper GI conditions.',
    'No significant outcome difference was found for wait times under 3 weeks.',
    '2025-01-20', '2025-08-10', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Pulmonology', 'Air Quality Correlation with Asthma Flare-Ups', NULL, NULL, 'ongoing',
    'Correlating local air quality index data with asthma patient flare-up frequency over the monsoon and winter seasons.',
    NULL, '2026-01-10', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Pulmonology', 'Post-TB Lung Function Recovery Study', NULL, NULL, 'completed',
    'Tracked lung function recovery in patients over 12 months following completion of TB treatment.',
    'Most patients regained 80%+ lung function within 9 months of treatment completion.',
    '2025-01-01', '2025-11-01', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Oncology', 'Early Screening Uptake in High-Risk Patients', NULL, NULL, 'ongoing',
    'Studying screening uptake rates among patients flagged as high-risk based on family history.',
    NULL, '2026-02-01', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Oncology', 'Patient Support Group Impact on Treatment Adherence', NULL, NULL, 'completed',
    'Reviewed treatment adherence rates for patients who joined a support group versus those who did not.',
    'Support group participants showed notably higher treatment completion rates.',
    '2025-01-15', '2025-09-30', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Dentistry', 'Oral Hygiene Habits in School-Age Children', NULL, NULL, 'ongoing',
    'Surveying oral hygiene habits and cavity rates among school-age children visiting the dental outpatient department.',
    NULL, '2026-02-15', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Dentistry', 'Root Canal vs Extraction: Patient Outcome Preferences', NULL, NULL, 'completed',
    'Reviewed long-term patient satisfaction between root canal treatment and extraction for damaged molars.',
    'Root canal patients reported higher satisfaction where the tooth remained functional after 1 year.',
    '2025-02-01', '2025-08-15', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Emergency Medicine', 'Golden Hour Response Time Analysis', NULL, NULL, 'ongoing',
    'Analyzing time-to-treatment data for trauma cases arriving within the critical "golden hour" window.',
    NULL, '2026-01-05', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Emergency Medicine', 'Triage Accuracy Review in High-Volume Periods', NULL, NULL, 'completed',
    'Reviewed triage categorization accuracy during peak patient-volume hours versus off-peak hours.',
    'Triage accuracy remained consistent, though average time-to-triage increased during peak hours.',
    '2025-01-10', '2025-07-01', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t

UNION ALL SELECT * FROM (SELECT
    'Physiotherapy', 'Home Exercise Adherence After Discharge', NULL, NULL, 'ongoing',
    'Tracking how consistently patients follow prescribed home exercise plans after physiotherapy discharge.',
    NULL, '2026-02-01', NULL, (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t
UNION ALL SELECT * FROM (SELECT
    'Physiotherapy', 'Recovery Outcomes: Supervised vs Home-Based Rehab', NULL, NULL, 'completed',
    'Compared mobility recovery outcomes between clinic-supervised rehab sessions and home-based programs.',
    'Supervised sessions showed modestly faster recovery, though home-based adherence was the bigger factor overall.',
    '2025-01-20', '2025-08-05', (SELECT id FROM users WHERE role='employee' LIMIT 1)
) t;
