<?php
/**
 * seed_research.php — One-time helper that:
 *   1) sets users.image for each doctor below (path to their cropped photo,
 *      which you should upload to /assets/doctors/ on the server first), and
 *   2) adds a research project per doctor, written from their real listed
 *      disciplines rather than generic filler text.
 *
 * It only touches doctors who already exist in `users` (role='employee')
 * with a matching full_name — it never invents new staff rows. Specialty
 * matching is fuzzy ("Department of Dermatology" vs "Dermatology") so it
 * works regardless of exactly how your $specialties list is worded.
 *
 * After the named roster below, it falls back to the original generic
 * seeding for any OTHER specialty that still has zero research projects,
 * using whichever doctor already exists on staff there.
 *
 * Run this ONCE, then delete it or move it out of the public web root.
 */
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/translations.php';

$pdo = getDbConnection();

// ---- Real doctor roster (name, department as given, disciplines, photo) ----
// Photo paths assume you upload the files from the doctor-photos/ folder to
// /assets/doctors/ on your server. Rename the path below if you put them
// somewhere else.
$namedDoctors = [
    [
        'full_name'   => 'Sunil N Tolat',
        'department'  => 'Dermatology',
        'image'       => 'assets/doctors/sunil-n-tolat.png',
        'title'       => 'Improving Diagnostic Accuracy in Common and Rare Dermatoses',
        'description' => 'A clinical study reviewing diagnostic approaches used in outpatient dermatology, comparing clinical examination with dermoscopy-assisted diagnosis to reduce misdiagnosis of overlapping skin conditions and shorten time to correct treatment.',
    ],
    [
        'full_name'   => 'Malangori Abdulgani Parande',
        'department'  => 'Preventive and Social Medicine',
        'image'       => 'assets/doctors/malangori-abdulgani-parande.png',
        'title'       => 'Influenza Surveillance and Immunization Coverage in the Community',
        'description' => 'A community health study tracking seasonal influenza case trends alongside local immunization coverage, using surveillance data to identify gaps in vaccine uptake and inform targeted outreach.',
    ],
    [
        'full_name'   => 'Kartik Singhai',
        'department'  => 'Psychiatry',
        'image'       => 'assets/doctors/kartik-singhai.png',
        'title'       => 'Patterns of Psychopathology in Patients Presenting with Common Mental Illness',
        'description' => 'A clinical psychiatry study examining how common mental illnesses present in outpatient settings, mapping symptom patterns and psychopathology to support earlier recognition and more targeted treatment planning.',
    ],
    [
        'full_name'   => 'Rajesh Karyakarte',
        'department'  => 'Microbiology',
        'image'       => 'assets/doctors/rajesh-karyakarte.png',
        'title'       => 'Antimicrobial Resistance Patterns in Clinical Isolates',
        'description' => 'A microbiology study analyzing antimicrobial resistance trends among clinical isolates from hospital patients, aiming to guide empirical antibiotic choices and support antimicrobial stewardship.',
    ],
    [
        'full_name'   => 'Nikhil Panse',
        'department'  => 'Plastic Surgery',
        'image'       => 'assets/doctors/nikhil-panse.png',
        'title'       => 'Wound Care and Cleft Reconstruction under National Health Programs',
        'description' => 'A study on the management of chronic and complex wounds alongside cleft lip and palate reconstruction, evaluating outcomes for patients reached through national health program outreach camps.',
    ],
    [
        'full_name'   => 'Minakshi Nalbale Bhosale',
        'department'  => 'Pediatric Surgery',
        'image'       => 'assets/doctors/minakshi-nalbale-bhosale.png',
        'title'       => 'Outcomes of Neonatal and Pediatric Urological Surgical Interventions',
        'description' => 'A review of surgical outcomes in neonatal and pediatric urology cases, evaluating operative techniques, complication rates, and post-operative recovery to refine surgical protocols for young patients.',
    ],
    [
        'full_name'   => 'Renu Bharadwaj',
        'department'  => 'Microbiology',
        'image'       => 'assets/doctors/renu-bharadwaj.png',
        'title'       => 'Immunological Correlates of Tuberculosis and Infectious Disease Progression',
        'description' => 'A study investigating immune response patterns in tuberculosis and related infectious diseases, aiming to identify biomarkers associated with disease progression and response to treatment.',
    ],
    [
        'full_name'   => 'Aarti Kinikar',
        'department'  => 'Pediatrics',
        'image'       => 'assets/doctors/aarti-kinikar.png',
        'title'       => 'Genetic and Infectious Contributors to Pediatric Illness',
        'description' => 'A pediatric study examining how medical genetics and infectious disease intersect in childhood illness, with a focus on earlier diagnosis and more effective management of pediatric infectious conditions.',
    ],
    [
        'full_name'   => 'Parag Sahasrabudhe',
        'department'  => 'Plastic Surgery',
        'image'       => 'assets/doctors/parag-sahasrabudhe.png',
        'title'       => 'Microvascular and Arterial Techniques in Hand and Reconstructive Surgery',
        'description' => 'A study of microvascular and arterial techniques used in hand surgery and reconstructive plastic surgery, evaluating functional recovery and long-term outcomes for patients undergoing these procedures.',
    ],
    [
        'full_name'   => 'Vasudha Belgaumkar',
        'department'  => 'Dermatology',
        'image'       => 'assets/doctors/vasudha-belgaumkar.png',
        'title'       => 'Histopathological Correlation in the Treatment of Dermatological Conditions',
        'description' => 'A dermatology study correlating histopathology findings with clinical treatment response across a range of skin conditions, aiming to better align treatment choice with underlying tissue-level findings.',
    ],
];

$results = [];

// Match a loosely-worded department label ("Department of X") to the
// specialty's en_title used across the rest of the site.
function matchSpecialty(array $specialties, string $department): ?string {
    $needle = strtolower(trim(preg_replace('/^department of\s+/i', '', $department)));
    foreach ($specialties as $sp) {
        $hay = strtolower(trim(preg_replace('/^department of\s+/i', '', $sp['en_title'])));
        if ($hay === $needle || str_contains($hay, $needle) || str_contains($needle, $hay)) {
            return $sp['en_title'];
        }
    }
    return null;
}

$coveredSpecialties = [];

foreach ($namedDoctors as $doc) {
    $specialty = matchSpecialty($specialties, $doc['department']);
    if (!$specialty) {
        $results[] = "{$doc['full_name']}: no matching specialty found for \"{$doc['department']}\" — skipped.";
        continue;
    }
    $coveredSpecialties[$specialty] = true;

    // Only touch a doctor who already exists on staff.
    $find = $pdo->prepare("SELECT id FROM users WHERE role = 'employee' AND full_name = :name LIMIT 1");
    $find->execute([':name' => $doc['full_name']]);
    $userId = $find->fetchColumn();

    if (!$userId) {
        $results[] = "{$doc['full_name']}: not found in users table — skipped (add them first, or fix the name to match exactly).";
        continue;
    }

    // Set their photo.
    $upd = $pdo->prepare("UPDATE users SET image = :img WHERE id = :id");
    $upd->execute([':img' => $doc['image'], ':id' => $userId]);

    // Skip adding a project if this doctor already has one.
    $existing = $pdo->prepare("SELECT COUNT(*) FROM research_projects WHERE author_name = :name");
    $existing->execute([':name' => $doc['full_name']]);
    if ((int) $existing->fetchColumn() > 0) {
        $results[] = "{$doc['full_name']}: photo updated; already has research — project not duplicated.";
        continue;
    }

    $title = $doc['title'];
    $description = $doc['description'];

    $insert = $pdo->prepare("INSERT INTO research_projects
        (specialty, title, description, author_name, status, started_date, completed_date, conclusion)
        VALUES (:specialty, :title, :description, :author_name, :status, :started_date, :completed_date, :conclusion)");

    $insert->execute([
        ':specialty'      => $specialty,
        ':title'          => $title,
        ':description'    => $description,
        ':author_name'    => $doc['full_name'],
        ':status'         => 'ongoing',
        ':started_date'   => date('Y-m-d', strtotime('-5 months')),
        ':completed_date' => null,
        ':conclusion'     => null,
    ]);

    $results[] = "{$doc['full_name']}: photo set, research project added under $specialty.";
}

// ---- Fallback: fill in any other specialty still at zero projects ----
foreach ($specialties as $sp) {
    $specialty = $sp['en_title'];
    if (isset($coveredSpecialties[$specialty])) {
        continue;
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM research_projects WHERE specialty = :sp');
    $countStmt->execute([':sp' => $specialty]);
    if ((int) $countStmt->fetchColumn() > 0) {
        continue; // already has research, leave it alone
    }

    $docStmt = $pdo->prepare("SELECT full_name FROM users
                               WHERE role = 'employee' AND is_active = 1 AND specialty = :sp
                               ORDER BY full_name ASC LIMIT 1");
    $docStmt->execute([':sp' => $specialty]);
    $doctor = $docStmt->fetchColumn();

    if (!$doctor) {
        $results[] = "$specialty: no doctor on staff in this department — skipped.";
        continue;
    }

    $insert = $pdo->prepare("INSERT INTO research_projects
        (specialty, title, description, author_name, status, started_date, completed_date, conclusion)
        VALUES (:specialty, :title, :description, :author_name, :status, :started_date, :completed_date, :conclusion)");

    $insert->execute([
        ':specialty'      => $specialty,
        ':title'          => "Clinical Outcomes Study in $specialty",
        ':description'    => "An ongoing study evaluating treatment outcomes and patient recovery patterns within the $specialty department.",
        ':author_name'    => $doctor,
        ':status'         => 'ongoing',
        ':started_date'   => date('Y-m-d', strtotime('-4 months')),
        ':completed_date' => null,
        ':conclusion'     => null,
    ]);

    $results[] = "$specialty: no roster entry, so added a generic project for Dr. $doctor.";
}

header('Content-Type: text/plain; charset=UTF-8');
echo implode("\n", $results) . "\n";
