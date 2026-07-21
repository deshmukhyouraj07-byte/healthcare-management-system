<?php
/**
 * portal.php — Unified Login & Role Portal
 * Handles three roles via ?role= : employee | staff | patient
 *
 * LAYOUT A (employee/staff): login -> dashboard with "Register New Patient" form
 * LAYOUT B (patient):        login -> read-only profile / vitals / prescriptions view
 */
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/translations.php';
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

const HOSPITAL_LOGO_BASE64 = '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';

function buildReceiptPdf(array $receipt): string {
    $medicinesHtml = !empty($receipt['medicines']) && $receipt['medicines'] !== ''
        ? nl2br(htmlspecialchars($receipt['medicines']))
        : 'None noted';
    $nextApptHtml = !empty($receipt['next_appt']) && $receipt['next_appt'] !== ''
        ? htmlspecialchars(date('F j, Y', strtotime($receipt['next_appt'])))
        : 'Not scheduled';
    $dobHtml     = !empty($receipt['dob']) ? htmlspecialchars(date('F j, Y', strtotime($receipt['dob']))) : 'Not provided';
    $genderHtml  = !empty($receipt['gender']) ? htmlspecialchars($receipt['gender']) : 'Not provided';
    $contactHtml = !empty($receipt['contact']) ? htmlspecialchars($receipt['contact']) : 'Not provided';
    $addressHtml = !empty($receipt['address']) ? nl2br(htmlspecialchars($receipt['address'])) : 'Not provided';
    $emailHtml   = !empty($receipt['email']) ? htmlspecialchars($receipt['email']) : 'Not provided';

    $html = '
    <html><head><style>
        body{ font-family: sans-serif; color:#28323c; font-size:12.5px; }
        .header-table{ width:100%; border-collapse:collapse; margin-bottom:10px; }
        .header-table td{ border:none; padding:0; vertical-align:middle; }
        .logo-cell{ width:70px; }
        .logo-cell img{ width:60px; height:auto; }
        .hospital-name{ font-size:20px; font-weight:bold; color:#0b2e4a; }
        .hospital-sub{ font-size:11px; color:#6b7a89; }
        h3{ color:#0b2e4a; font-size:14px; margin:18px 0 6px 0; border-bottom:2px solid #0d6ea8; padding-bottom:4px; }
        table.info{ width:100%; border-collapse:collapse; margin-top:6px; }
        table.info td{ padding:6px 0; border-bottom:1px solid #eee; }
        .label{ color:#6b7a89; width:38%; }
        .value{ font-weight:bold; }
        .credbox{ background:#f4f9fc; border:2px dashed #0d6ea8; padding:15px; margin:12px 0; border-radius:6px; }
        .instructions{ background:#fff8ec; border:1px solid #f0d9a8; padding:14px 16px; border-radius:6px; margin-top:10px; }
        .instructions ul{ margin:6px 0 0 18px; padding:0; }
        .instructions li{ margin-bottom:5px; }
        .top-rule{ border-top:3px solid #0d6ea8; margin-top:8px; padding-top:12px; }
        .footer{ margin-top:26px; color:#8a97a3; font-size:10.5px; border-top:1px solid #eee; padding-top:10px; }
    </style></head><body>

        <table class="header-table">
            <tr>
                <td class="logo-cell"><img src="data:image/png;base64,' . HOSPITAL_LOGO_BASE64 . '"></td>
                <td>
                    <div class="hospital-name">Sassoon General Hospital</div>
                    <div class="hospital-sub">Jai Prakash Narayan Road, Railway Station Road, Pune, Maharashtra 411001</div>
                    <div class="hospital-sub">Patient Registration &amp; Acknowledgement Receipt &nbsp;|&nbsp; Date: ' . date('F j, Y') . '</div>
                </td>
            </tr>
        </table>
        <div class="top-rule"></div>

        <h3>Patient Information</h3>
        <table class="info">
            <tr><td class="label">Full Name</td><td class="value">' . htmlspecialchars($receipt['full_name']) . '</td></tr>
            <tr><td class="label">Date of Birth</td><td class="value">' . $dobHtml . '</td></tr>
            <tr><td class="label">Gender</td><td class="value">' . $genderHtml . '</td></tr>
            <tr><td class="label">Contact Number</td><td class="value">' . $contactHtml . '</td></tr>
            <tr><td class="label">Email</td><td class="value">' . $emailHtml . '</td></tr>
            <tr><td class="label">Address</td><td class="value">' . $addressHtml . '</td></tr>
        </table>

        <h3>Login Credentials</h3>
        <div class="credbox">
            <table class="info">
                <tr><td class="label">Patient ID</td><td class="value">' . htmlspecialchars($receipt['patient_id']) . '</td></tr>
                <tr><td class="label">Password</td><td class="value">' . htmlspecialchars($receipt['password']) . '</td></tr>
            </table>
        </div>

        <h3>Clinical Notes</h3>
        <table class="info">
            <tr><td class="label">Prescribed Medicines</td><td class="value">' . $medicinesHtml . '</td></tr>
            <tr><td class="label">Next Appointment</td><td class="value">' . $nextApptHtml . '</td></tr>
        </table>

        <h3>General Care Instructions</h3>
        <div class="instructions">
            <p style="margin:0;">Please follow these general guidelines during your treatment and recovery:</p>
            <ul>
                <li>Take all prescribed medicines exactly as directed by your doctor \xe2\x80\x94 do not skip or double doses.</li>
                <li>Get adequate rest and avoid strenuous activity until your doctor advises otherwise.</li>
                <li>Stay well hydrated and eat light, nutritious meals as tolerated.</li>
                <li>Keep this receipt and your login details safe \xe2\x80\x94 you will need your Patient ID and password to access your records online.</li>
                <li>Attend your next appointment as scheduled, or contact the hospital to reschedule if needed.</li>
                <li>If you experience worsening symptoms, high fever, severe pain, or any emergency, contact the hospital immediately or visit the nearest emergency department.</li>
                <li>Carry a valid photo ID and this receipt for all future hospital visits.</li>
            </ul>
        </div>

        <div class="footer">
            This receipt is system-generated and reflects the information provided at the time of registration.
            For questions, contact the hospital front desk or call +91 1800 4856 93.
        </div>
    </body></html>';

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfPath = sys_get_temp_dir() . '/receipt_' . $receipt['patient_id'] . '_' . uniqid() . '.pdf';
    file_put_contents($pdfPath, $dompdf->output());
    return $pdfPath;
}

function sendReceiptEmail(array $receipt): bool {
    if (empty($receipt['email'])) return false;

    $pdfPath = null;
    $mail = new PHPMailer(true);
    try {
        $pdfPath = buildReceiptPdf($receipt);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'deshmukhyouraj07@gmail.com';
        $mail->Password   = 'bjqoxkxloerojsww';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('deshmukhyouraj07@gmail.com', 'Sassoon General Hospital');
        $mail->addAddress($receipt['email'], $receipt['full_name']);
        $mail->addAttachment($pdfPath, 'Registration_Receipt.pdf');

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Sassoon General Hospital \xe2\x80\x94 Registration Confirmed';
        $mail->Body    = "
            <div style=\"font-family:sans-serif; color:#28323c;\">
                <table style=\"margin-bottom:14px;\"><tr>
                    <td style=\"padding-right:12px;\"><img src=\"cid:hospitallogo\" width=\"48\"></td>
                    <td><span style=\"font-size:18px; font-weight:bold; color:#0b2e4a;\">Sassoon General Hospital</span></td>
                </tr></table>
                <p>Dear {$receipt['full_name']},</p>
                <p>We're glad to have you with us. Your registration is complete, and we wish you a smooth and speedy recovery.</p>
                <p><b>Patient ID:</b> {$receipt['patient_id']}<br>
                <b>Password:</b> {$receipt['password']}</p>
                <p>Please keep these login details safe \xe2\x80\x94 you'll use them to check your prescriptions, appointments, and bills online.</p>
                <p>Attached is your full registration receipt as a PDF, including your details and general care instructions to follow during your treatment.</p>
                <p>Wishing you good health,<br>Sassoon General Hospital</p>
            </div>
        ";
        $mail->addEmbeddedImage(base64_decode(HOSPITAL_LOGO_BASE64), 'hospitallogo', 'logo.png', 'base64', 'image/png');

        $sent = $mail->send();
        return $sent;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    } finally {
        if ($pdfPath && file_exists($pdfPath)) {
            unlink($pdfPath);
        }
    }
}

if (!isset($_SESSION['lang'])) {
    header('Location: language_select.php');
    exit;
}

$role = $_GET['role'] ?? '';
$allowedRoles = ['employee', 'staff', 'patient'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'employee'; // sensible default instead of a hard error
}

$errors  = [];
$success = '';

/* =========================================================================
   POST HANDLING
   ========================================================================= */

// ---- Staff / Employee login -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'staff_login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = t('err_login_required');
    } else {
        try {
            $pdo  = getDbConnection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u AND is_active = 1 LIMIT 1');
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['staff_id']       = $user['id'];
                $_SESSION['staff_username'] = $user['username'];
                $_SESSION['staff_name']     = $user['full_name'];
                $_SESSION['staff_role']     = $user['role'];
                $_SESSION['can_provision']  = (bool) $user['can_provision_credentials'];
            } else {
                $errors[] = t('err_login_invalid');
            }
        } catch (Throwable $e) {
            $errors[] = t('err_login_failed');
        }
    }
}

// ---- Staff logout ------------------------------------------------------------
if (isset($_GET['logout']) && $_GET['logout'] === 'staff') {
    unset($_SESSION['staff_id'], $_SESSION['staff_username'], $_SESSION['staff_name'],
          $_SESSION['staff_role'], $_SESSION['can_provision']);
    header('Location: portal.php?role=' . $role);
    exit;
}

// ---- Register New Patient (staff action) -------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_patient'
    && isset($_SESSION['staff_id'])) {

    $fullName   = trim($_POST['full_name'] ?? '');
    $dob        = $_POST['dob'] ?? '';
    $gender     = $_POST['gender'] ?? '';
    $contact    = trim($_POST['contact_info'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $history    = trim($_POST['medical_history'] ?? '');
    $medicines  = trim($_POST['prescribed_medicines'] ?? '');
    $nextAppt   = trim($_POST['next_appointment_date'] ?? '');
    $patientPwd = $_POST['patient_password'] ?? '';
    $manualId   = trim($_POST['manual_patient_id'] ?? '');

    if ($fullName === '' || $dob === '' || $gender === '' || $contact === '' || $patientPwd === '') {
        $errors[] = t('err_patient_fields');
    } else {
        try {
            $pdo = getDbConnection();

            // Auto-generate a unique Patient ID unless staff supplied one manually.
            $patientId = $manualId !== '' ? $manualId : ('PT-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)));

            $hash = password_hash($patientPwd, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('INSERT INTO patients
                (patient_id, full_name, date_of_birth, gender, contact_info, address, email, medical_history, prescribed_medicines, next_appointment_date, password_hash, registered_by)
                VALUES (:pid, :name, :dob, :gender, :contact, :address, :email, :history, :medicines, :nextappt, :hash, :staff)');

            $stmt->execute([
                ':pid'      => $patientId,
                ':name'     => $fullName,
                ':dob'      => $dob,
                ':gender'   => $gender,
                ':contact'  => $contact,
                ':address'  => $address,
                ':email'    => $email !== '' ? $email : null,
                ':history'  => $history,
                ':medicines'=> $medicines !== '' ? $medicines : null,
                ':nextappt' => $nextAppt !== '' ? $nextAppt : null,
                ':hash'     => $hash,
                ':staff'    => $_SESSION['staff_id'],
            ]);

            $success = t('success_patient_registered') . ' ' . $patientId;

            // Stash a one-time receipt (with the PLAIN-TEXT password) so staff can
            // immediately print it. This is never stored in the database.
            $_SESSION['last_registered_receipt'] = [
                'patient_id' => $patientId,
                'password'   => $patientPwd,
                'full_name'  => $fullName,
                'dob'        => $dob,
                'gender'     => $gender,
                'contact'    => $contact,
                'address'    => $address,
                'email'      => $email,
                'medicines'  => $medicines,
                'next_appt'  => $nextAppt,
                'staff_id'   => $_SESSION['staff_id'],
            ];

            // Send the welcome/acknowledgement email immediately, right when the
            // doctor/staff clicks Register Patient — not dependent on the receipt
            // page being viewed afterward.
            sendReceiptEmail($_SESSION['last_registered_receipt']);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'uq_patients_email') || str_contains($e->getMessage(), 'email')) {
                $errors[] = 'A patient with this email address is already registered. Please use a different email, or check if this patient already exists.';
            } elseif (str_contains($e->getMessage(), 'PRIMARY') || str_contains($e->getMessage(), 'patient_id')) {
                $errors[] = t('err_patient_exists');
            } else {
                $errors[] = 'Could not register patient. ' . $e->getMessage();
            }
        }
    }
}

// ---- Update Doctor Specialty & Availability (self-service) --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_availability'
    && isset($_SESSION['staff_id']) && $_SESSION['staff_role'] === 'employee') {

    $specialty    = trim($_POST['specialty'] ?? '');
    $availability = $_POST['availability'] ?? 'available';

    if (!in_array($availability, ['available', 'not_available'], true)) {
        $availability = 'available';
    }

    try {
        $pdo  = getDbConnection();
        $stmt = $pdo->prepare('UPDATE users SET specialty = :spec, availability = :avail WHERE id = :id');
        $stmt->execute([
            ':spec'  => $specialty !== '' ? $specialty : null,
            ':avail' => $availability,
            ':id'    => $_SESSION['staff_id'],
        ]);
        $success = t('success_availability_updated');
    } catch (Throwable $e) {
        $errors[] = t('err_availability_update');
    }
}

// ---- Assign Bill to Patient (staff action) ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_bill'
    && isset($_SESSION['staff_id'])) {

    $billPatientId = trim($_POST['bill_patient_id'] ?? '');
    $description   = trim($_POST['bill_description'] ?? '');
    $amount        = $_POST['bill_amount'] ?? '';

    if ($billPatientId === '' || $description === '' || $amount === '' || !is_numeric($amount) || $amount <= 0) {
        $errors[] = t('err_bill_invalid');
    } else {
        try {
            $pdo = getDbConnection();

            // Only allow billing patients this staff member actually registered.
            $check = $pdo->prepare('SELECT id FROM patients WHERE patient_id = :pid AND registered_by = :sid LIMIT 1');
            $check->execute([':pid' => $billPatientId, ':sid' => $_SESSION['staff_id']]);

            if (!$check->fetch()) {
                $errors[] = t('err_bill_not_registered');
            } else {
                $stmt = $pdo->prepare('INSERT INTO bills (patient_id, description, amount, created_by)
                                        VALUES (:pid, :desc, :amt, :staff)');
                $stmt->execute([
                    ':pid'   => $billPatientId,
                    ':desc'  => $description,
                    ':amt'   => $amount,
                    ':staff' => $_SESSION['staff_id'],
                ]);
                $success = t('success_bill_added') . number_format((float)$amount, 2) . ' ' . t('success_bill_added_2') . ' ' . $billPatientId;
            }
        } catch (Throwable $e) {
            $errors[] = t('err_bill_failed');
        }
    }
}

// ---- Delete a patient record (staff can only delete patients they registered) --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_patient'
    && isset($_SESSION['staff_id'])) {

    $deletePatientId = trim($_POST['delete_patient_id'] ?? '');

    if ($deletePatientId === '') {
        $errors[] = t('err_bill_invalid');
    } else {
        try {
            $pdo = getDbConnection();

            // Only allow deleting patients this staff member actually registered.
            $check = $pdo->prepare('SELECT id FROM patients WHERE patient_id = :pid AND registered_by = :sid LIMIT 1');
            $check->execute([':pid' => $deletePatientId, ':sid' => $_SESSION['staff_id']]);

            if (!$check->fetch()) {
                $errors[] = t('err_bill_not_registered');
            } else {
                $stmt = $pdo->prepare('DELETE FROM patients WHERE patient_id = :pid AND registered_by = :sid');
                $stmt->execute([':pid' => $deletePatientId, ':sid' => $_SESSION['staff_id']]);
                $success = 'Patient record ' . $deletePatientId . ' was deleted.';
            }
        } catch (Throwable $e) {
            $errors[] = 'Could not delete patient. They may have bills or orders on record.';
        }
    }
}

// ---- Patient login -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'patient_login') {
    $loginEmail = trim($_POST['email'] ?? '');
    $password   = $_POST['patient_password_login'] ?? '';

    if ($loginEmail === '' || $password === '') {
        $errors[] = t('err_patient_id_required');
    } else {
        try {
            $pdo  = getDbConnection();
            $stmt = $pdo->prepare('SELECT * FROM patients WHERE email = :email AND is_active = 1 LIMIT 1');
            $stmt->execute([':email' => $loginEmail]);
            $patient = $stmt->fetch();

            if ($patient && password_verify($password, $patient['password_hash'])) {
                $_SESSION['patient_pk']  = $patient['id'];
                $_SESSION['patient_id']  = $patient['patient_id'];
            } else {
                $errors[] = t('err_patient_invalid');
            }
        } catch (Throwable $e) {
            $errors[] = t('err_login_failed');
        }
    }
}

// ---- Patient logout --------------------------------------------------------------
if (isset($_GET['logout']) && $_GET['logout'] === 'patient') {
    unset($_SESSION['patient_pk'], $_SESSION['patient_id']);
    header('Location: portal.php?role=patient');
    exit;
}

/* =========================================================================
   DATA FETCH FOR AUTHENTICATED VIEWS
   ========================================================================= */

$staffPatients = [];
$currentDoctor = null;
if (isset($_SESSION['staff_id'])) {
    try {
        $pdo  = getDbConnection();
        $stmt = $pdo->prepare('SELECT patient_id, full_name, date_of_birth, gender, contact_info, created_at
                                FROM patients WHERE registered_by = :sid ORDER BY created_at DESC');
        $stmt->execute([':sid' => $_SESSION['staff_id']]);
        $staffPatients = $stmt->fetchAll();

        if ($_SESSION['staff_role'] === 'employee') {
            $stmt2 = $pdo->prepare('SELECT specialty, availability FROM users WHERE id = :id LIMIT 1');
            $stmt2->execute([':id' => $_SESSION['staff_id']]);
            $currentDoctor = $stmt2->fetch();
        }
    } catch (Throwable $e) {
        // silently ignore for demo purposes
    }
}

$patientProfile = null;
$patientRecords = [];
$patientBills   = [];
$patientBillsTotal = 0.0;
if (isset($_SESSION['patient_pk'])) {
    try {
        $pdo  = getDbConnection();
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $_SESSION['patient_pk']]);
        $patientProfile = $stmt->fetch();

        if ($patientProfile) {
            $stmt2 = $pdo->prepare('SELECT * FROM patient_records WHERE patient_id = :pid ORDER BY recorded_at DESC');
            $stmt2->execute([':pid' => $patientProfile['patient_id']]);
            $patientRecords = $stmt2->fetchAll();

            $stmt3 = $pdo->prepare('SELECT * FROM bills WHERE patient_id = :pid ORDER BY created_at DESC');
            $stmt3->execute([':pid' => $patientProfile['patient_id']]);
            $patientBills = $stmt3->fetchAll();
            foreach ($patientBills as $b) {
                $patientBillsTotal += (float) $b['amount'];
            }
        }
    } catch (Throwable $e) {
        // silently ignore for demo purposes
    }
}

$roleLabels = [
    'employee' => t('login_doctor'),
    'staff'    => t('login_staff'),
    'patient'  => t('login_patient'),
];
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($roleLabels[$role]) ?> <?= htmlspecialchars(t('portal_title_suffix')) ?> | <?= htmlspecialchars(t('hospital_name')) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --brand-primary:#0d6ea8;
    --brand-secondary:#12b8a6;
    --brand-dark:#0b2e4a;
    --brand-light:#f4f9fc;
    --brand-font:'Poppins','Noto Sans Devanagari', sans-serif;
  }
  body{ font-family:var(--brand-font); background:var(--brand-light); color:#28323c; min-height:100vh; }
  .topbar{ background:var(--brand-dark); color:#fff; padding:14px 0; }
  .topbar a{ color:#fff; text-decoration:none; }
  .card-auth{ border:none; border-radius:18px; box-shadow:0 10px 30px rgba(11,46,74,.08); }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
  .role-pill{ background:rgba(13,110,168,.1); color:var(--brand-primary); font-weight:600; padding:4px 14px; border-radius:20px; font-size:.8rem; display:inline-block; }
  .section-title{ font-weight:700; color:var(--brand-dark); }
  .readonly-field{ background:#f0f4f7 !important; }
  .badge-rights{ background:var(--brand-secondary); }
  table thead{ background:var(--brand-light); }
  .lang-toggle{ border:1px solid rgba(255,255,255,.3); background:transparent; color:#fff; font-weight:600; }
  .lang-toggle:hover{ background:rgba(255,255,255,.1); color:#fff; }
</style>
</head>
<body>

<div class="topbar">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="index.php"><i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('portal_back_to')) ?> <?= htmlspecialchars(t('hospital_name')) ?></a>
    <div class="d-flex align-items-center gap-2">
      <a href="switch_language.php?lang=<?= currentLang() === 'en' ? 'mr' : 'en' ?>" class="btn lang-toggle rounded-pill btn-sm">
        <i class="bi bi-translate me-1"></i> <?= htmlspecialchars(t('language_switch')) ?>
      </a>
      <span class="role-pill" style="background:rgba(255,255,255,.15); color:#fff;">
        <?= htmlspecialchars($roleLabels[$role]) ?> <?= htmlspecialchars(t('portal_title_suffix')) ?>
      </span>
    </div>
  </div>
</div>

<div class="container py-5">

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?></span>
    <?php if (isset($_SESSION['last_registered_receipt'])): ?>
      <a href="print_receipt.php" target="_blank" class="btn btn-brand btn-sm rounded-pill">
        <i class="bi bi-printer-fill me-1"></i> <?= htmlspecialchars(t('portal_print_receipt')) ?>
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>


<?php /* ==========================================================================
         LAYOUT A — EMPLOYEE / STAFF
         ========================================================================== */ ?>
<?php if ($role === 'employee' || $role === 'staff'): ?>

  <?php if (!isset($_SESSION['staff_id'])): ?>
    <!-- ---------- STAFF LOGIN FORM ---------- -->
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card card-auth p-4 p-md-5">
          <div class="text-center mb-4">
            <i class="bi bi-person-badge" style="font-size:2.5rem; color:var(--brand-primary);"></i>
            <h3 class="section-title mt-2"><?= htmlspecialchars(t('portal_staff_login_title')) ?></h3>
            <p class="text-muted small mb-0"><?= htmlspecialchars(t('portal_staff_login_sub')) ?></p>
          </div>
          <form method="POST" action="portal.php?role=<?= htmlspecialchars($role) ?>">
            <input type="hidden" name="action" value="staff_login">
            <div class="mb-3">
              <label class="form-label"><?= htmlspecialchars(t('portal_username')) ?></label>
              <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-4">
              <label class="form-label"><?= htmlspecialchars(t('portal_password')) ?></label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2 rounded-pill">
              <i class="bi bi-box-arrow-in-right me-1"></i> <?= htmlspecialchars(t('portal_login_btn')) ?>
            </button>
          </form>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- ---------- STAFF DASHBOARD ---------- -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h3 class="section-title mb-0"><?= htmlspecialchars(t('portal_welcome')) ?>, <?= htmlspecialchars($_SESSION['staff_name']) ?></h3>
        <span class="text-muted small"><?= htmlspecialchars(t('portal_role_label')) ?>: <?= htmlspecialchars(ucfirst($_SESSION['staff_role'])) ?></span>
      </div>
      <a href="portal.php?role=<?= htmlspecialchars($role) ?>&logout=staff" class="btn btn-outline-danger rounded-pill">
        <i class="bi bi-box-arrow-right me-1"></i> <?= htmlspecialchars(t('portal_logout')) ?>
      </a>
    </div>

    <div class="alert d-flex align-items-center gap-2" style="background:rgba(18,184,166,.1); border:1px solid rgba(18,184,166,.3);">
      <span class="badge badge-rights rounded-pill px-3 py-2">
        <i class="bi bi-shield-lock-fill me-1"></i>
        <?= $_SESSION['can_provision'] ? htmlspecialchars(t('portal_rights_active')) : htmlspecialchars(t('portal_rights_none')) ?>
      </span>
      <span class="text-muted small mb-0"><?= htmlspecialchars(t('portal_rights_desc')) ?></span>
    </div>

    <?php if ($_SESSION['staff_role'] === 'employee' && $currentDoctor): ?>
    <div class="card card-auth p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h5 class="section-title mb-1"><i class="bi bi-clipboard2-pulse me-1"></i> <?= htmlspecialchars(t('portal_specialty_availability')) ?></h5>
          <p class="text-muted small mb-0"><?= htmlspecialchars(t('portal_specialty_desc')) ?></p>
        </div>
        <span class="badge rounded-pill px-3 py-2 <?= $currentDoctor['availability'] === 'available' ? 'bg-success' : 'bg-secondary' ?>">
          <i class="bi bi-circle-fill me-1" style="font-size:.55rem;"></i>
          <?= $currentDoctor['availability'] === 'available' ? htmlspecialchars(t('portal_available')) : htmlspecialchars(t('portal_not_available')) ?>
        </span>
      </div>
      <form method="POST" action="portal.php?role=<?= htmlspecialchars($role) ?>" class="row g-3 mt-1">
        <input type="hidden" name="action" value="update_availability">
        <div class="col-md-6">
          <label class="form-label"><?= htmlspecialchars(t('portal_specialty_label')) ?></label>
          <select name="specialty" class="form-select">
            <?php
              $specialtyOptions = ['Cardiology','Pediatrics','Neurology','General Medicine','Orthopedics','Dermatology','ENT','Gynecology'];
              $current = $currentDoctor['specialty'];
            ?>
            <option value="" <?= !$current ? 'selected' : '' ?> disabled><?= htmlspecialchars(t('portal_select_specialty')) ?></option>
            <?php foreach ($specialtyOptions as $opt): ?>
              <option value="<?= htmlspecialchars($opt) ?>" <?= $current === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label"><?= htmlspecialchars(t('portal_availability_label')) ?></label>
          <select name="availability" class="form-select">
            <option value="available" <?= $currentDoctor['availability'] === 'available' ? 'selected' : '' ?>><?= htmlspecialchars(t('portal_available')) ?></option>
            <option value="not_available" <?= $currentDoctor['availability'] === 'not_available' ? 'selected' : '' ?>><?= htmlspecialchars(t('portal_not_available')) ?></option>
          </select>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-brand rounded-pill px-4">
            <i class="bi bi-save2 me-1"></i> <?= htmlspecialchars(t('portal_update_status')) ?>
          </button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="row g-4 mt-1">
      <!-- Register New Patient -->
      <div class="col-lg-6">
        <div class="card card-auth p-4 h-100">
          <h5 class="section-title mb-3"><i class="bi bi-person-plus-fill me-1"></i> <?= htmlspecialchars(t('portal_register_patient_title')) ?></h5>
          <form method="POST" action="portal.php?role=<?= htmlspecialchars($role) ?>">
            <input type="hidden" name="action" value="register_patient">

            <div class="mb-3">
              <label class="form-label"><?= htmlspecialchars(t('portal_full_name')) ?> *</label>
              <input type="text" name="full_name" class="form-control" required>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label"><?= htmlspecialchars(t('portal_dob')) ?> *</label>
                <input type="date" name="dob" class="form-control" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><?= htmlspecialchars(t('portal_gender')) ?> *</label>
                <select name="gender" class="form-select" required>
                  <option value="" disabled selected><?= htmlspecialchars(t('portal_select_gender')) ?></option>
                  <option><?= htmlspecialchars(t('portal_gender_male')) ?></option>
                  <option><?= htmlspecialchars(t('portal_gender_female')) ?></option>
                  <option><?= htmlspecialchars(t('portal_gender_other')) ?></option>
                  <option><?= htmlspecialchars(t('portal_gender_prefer_not')) ?></option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label"><?= htmlspecialchars(t('portal_contact_info')) ?> *</label>
              <input type="text" name="contact_info" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" placeholder="patient@example.com">
            </div>

            <div class="mb-3">
              <label class="form-label"><?= htmlspecialchars(t('portal_address')) ?></label>
              <input type="text" name="address" class="form-control">
            </div>

            <div class="mb-3">
              <label class="form-label"><?= htmlspecialchars(t('portal_medical_history')) ?></label>
              <textarea name="medical_history" class="form-control" rows="3"
                placeholder="e.g. Type 2 Diabetes - on Metformin 500mg BID"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label"><?= htmlspecialchars(t('portal_prescribed_medicines')) ?></label>
              <textarea name="prescribed_medicines" class="form-control" rows="2"
                placeholder="<?= htmlspecialchars(t('portal_prescribed_medicines_ph')) ?>"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label"><?= htmlspecialchars(t('portal_next_appointment')) ?></label>
              <input type="date" name="next_appointment_date" class="form-control">
            </div>

            <hr>
            <p class="small text-muted mb-2"><i class="bi bi-key-fill me-1"></i> <?= htmlspecialchars(t('portal_credential_provisioning')) ?></p>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label"><?= htmlspecialchars(t('portal_patient_id')) ?> <span class="text-muted">(<?= htmlspecialchars(t('portal_patient_id_hint')) ?>)</span></label>
                <input type="text" name="manual_patient_id" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><?= htmlspecialchars(t('portal_assign_password')) ?> *</label>
                <input type="text" name="patient_password" class="form-control" required>
              </div>
            </div>

            <button type="submit" class="btn btn-brand w-100 py-2 rounded-pill mt-2">
              <i class="bi bi-save2 me-1"></i> <?= htmlspecialchars(t('portal_register_btn')) ?>
            </button>
          </form>
        </div>
      </div>

      <!-- Patients registered by this staff member -->
      <div class="col-lg-6">
        <div class="card card-auth p-4 h-100">
          <h5 class="section-title mb-3"><i class="bi bi-people-fill me-1"></i> <?= htmlspecialchars(t('portal_patients_registered')) ?></h5>
          <?php if (!$staffPatients): ?>
            <p class="text-muted small"><?= htmlspecialchars(t('portal_no_patients')) ?></p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr><th><?= htmlspecialchars(t('portal_patient_id')) ?></th><th><?= htmlspecialchars(t('portal_full_name')) ?></th><th><?= htmlspecialchars(t('portal_dob')) ?></th><th><?= htmlspecialchars(t('portal_gender')) ?></th><th><?= htmlspecialchars(t('portal_contact_info')) ?></th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($staffPatients as $p): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($p['patient_id']) ?></td>
                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                    <td><?= htmlspecialchars($p['date_of_birth']) ?></td>
                    <td><?= htmlspecialchars($p['gender']) ?></td>
                    <td><?= htmlspecialchars($p['contact_info']) ?></td>
                    <td>
                      <form method="POST" action="portal.php?role=<?= htmlspecialchars($role) ?>"
                            onsubmit="return confirm('Delete patient <?= htmlspecialchars(addslashes($p['patient_id'])) ?>? This cannot be undone.');">
                        <input type="hidden" name="action" value="delete_patient">
                        <input type="hidden" name="delete_patient_id" value="<?= htmlspecialchars($p['patient_id']) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete patient">
                          <i class="bi bi-trash3-fill"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>


      <!-- Assign Bill to Patient -->
      <div class="col-lg-6">
        <div class="card card-auth p-4">
          <h5 class="section-title mb-3"><i class="bi bi-receipt me-1"></i> <?= htmlspecialchars(t('portal_assign_bill_title')) ?></h5>
          <?php if (!$staffPatients): ?>
            <p class="text-muted small"><?= htmlspecialchars(t('portal_no_patients_bill')) ?></p>
          <?php else: ?>
            <form method="POST" action="portal.php?role=<?= htmlspecialchars($role) ?>">
              <input type="hidden" name="action" value="add_bill">
              <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars(t('login_patient')) ?> *</label>
                <select name="bill_patient_id" class="form-select" required>
                  <option value="" disabled selected><?= htmlspecialchars(t('portal_select_patient')) ?></option>
                  <?php foreach ($staffPatients as $p): ?>
                    <option value="<?= htmlspecialchars($p['patient_id']) ?>">
                      <?= htmlspecialchars($p['patient_id'] . ' — ' . $p['full_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars(t('portal_description')) ?> *</label>
                <input type="text" name="bill_description" class="form-control" required
                       placeholder="e.g. Consultation Fee, Lab Test - CBC">
              </div>
              <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars(t('portal_amount')) ?> (₹) *</label>
                <input type="number" name="bill_amount" class="form-control" step="0.01" min="0.01" required>
              </div>
              <button type="submit" class="btn btn-brand w-100 py-2 rounded-pill">
                <i class="bi bi-receipt-cutoff me-1"></i> <?= htmlspecialchars(t('portal_add_bill_btn')) ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>


<?php /* ==========================================================================
         LAYOUT B — PATIENT
         ========================================================================== */ ?>
<?php if ($role === 'patient'): ?>

  <?php if (!isset($_SESSION['patient_pk'])): ?>
    <!-- ---------- PATIENT LOGIN FORM ---------- -->
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card card-auth p-4 p-md-5">
          <div class="text-center mb-4">
            <i class="bi bi-person-heart" style="font-size:2.5rem; color:var(--brand-primary);"></i>
            <h3 class="section-title mt-2"><?= htmlspecialchars(t('portal_patient_login_title')) ?></h3>
            <p class="text-muted small mb-0"><?= htmlspecialchars(t('portal_patient_login_sub')) ?></p>
          </div>
          <form method="POST" action="portal.php?role=patient">
            <input type="hidden" name="action" value="patient_login">
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" placeholder="patient@example.com" required autofocus>
            </div>
            <div class="mb-4">
              <label class="form-label"><?= htmlspecialchars(t('portal_password')) ?></label>
              <input type="password" name="patient_password_login" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2 rounded-pill">
              <i class="bi bi-box-arrow-in-right me-1"></i> <?= htmlspecialchars(t('portal_login_btn')) ?>
            </button>
          </form>
        </div>
      </div>
    </div>

  <?php elseif ($patientProfile): ?>
    <!-- ---------- PATIENT DASHBOARD (READ-ONLY) ---------- -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h3 class="section-title mb-0"><?= htmlspecialchars(t('portal_welcome')) ?>, <?= htmlspecialchars($patientProfile['full_name']) ?></h3>
        <span class="text-muted small"><?= htmlspecialchars(t('portal_patient_id')) ?>: <?= htmlspecialchars($patientProfile['patient_id']) ?></span>
      </div>
      <a href="portal.php?role=patient&logout=patient" class="btn btn-outline-danger rounded-pill">
        <i class="bi bi-box-arrow-right me-1"></i> <?= htmlspecialchars(t('portal_logout')) ?>
      </a>
    </div>

    <div class="alert alert-info small">
      <i class="bi bi-lock-fill me-1"></i> <?= htmlspecialchars(t('portal_readonly_notice')) ?>
    </div>

    <div class="row g-4">
      <div class="col-lg-12">
        <div class="card card-auth p-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="section-title mb-0"><i class="bi bi-receipt me-1"></i> <?= htmlspecialchars(t('portal_my_bills')) ?></h5>
            <?php if ($patientBills): ?>
              <a href="print_bill.php" target="_blank" class="btn btn-brand rounded-pill btn-sm">
                <i class="bi bi-printer-fill me-1"></i> <?= htmlspecialchars(t('portal_print_bill')) ?>
              </a>
            <?php endif; ?>
          </div>
          <?php if (!$patientBills): ?>
            <p class="text-muted small"><?= htmlspecialchars(t('portal_no_bills')) ?></p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr><th><?= htmlspecialchars(t('portal_description')) ?></th><th><?= htmlspecialchars(t('portal_amount')) ?></th><th><?= htmlspecialchars(t('portal_status')) ?></th><th><?= htmlspecialchars(t('portal_date')) ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($patientBills as $b): ?>
                  <tr>
                    <td><?= htmlspecialchars($b['description']) ?></td>
                    <td>₹<?= number_format((float)$b['amount'], 2) ?></td>
                    <td>
                      <span class="badge <?= $b['status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?> text-capitalize">
                        <?= $b['status'] === 'paid' ? htmlspecialchars(t('portal_available')) : htmlspecialchars($b['status']) ?>
                      </span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($b['created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr class="fw-bold">
                    <td colspan="1" class="text-end"><?= htmlspecialchars(t('portal_total_due')) ?></td>
                    <td colspan="3">₹<?= number_format($patientBillsTotal, 2) ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card card-auth p-4">
          <h5 class="section-title mb-3"><i class="bi bi-person-vcard me-1"></i> <?= htmlspecialchars(t('portal_my_profile')) ?></h5>
          <div class="mb-3">
            <label class="form-label small text-muted"><?= htmlspecialchars(t('portal_full_name')) ?></label>
            <input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($patientProfile['full_name']) ?>" readonly disabled>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label small text-muted"><?= htmlspecialchars(t('portal_dob')) ?></label>
              <input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($patientProfile['date_of_birth']) ?>" readonly disabled>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label small text-muted"><?= htmlspecialchars(t('portal_gender')) ?></label>
              <input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($patientProfile['gender']) ?>" readonly disabled>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small text-muted"><?= htmlspecialchars(t('portal_contact_info')) ?></label>
            <input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($patientProfile['contact_info']) ?>" readonly disabled>
          </div>
          <div class="mb-1">
            <label class="form-label small text-muted"><?= htmlspecialchars(t('portal_medical_history')) ?></label>
            <textarea class="form-control readonly-field" rows="3" readonly disabled><?= htmlspecialchars($patientProfile['medical_history'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card card-auth p-4">
          <h5 class="section-title mb-3"><i class="bi bi-clipboard2-pulse me-1"></i> <?= htmlspecialchars(t('portal_vitals_title')) ?></h5>
          <?php if (!$patientRecords): ?>
            <p class="text-muted small"><?= htmlspecialchars(t('portal_no_records')) ?></p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr><th>Type</th><th>Title</th><th>Details</th><th><?= htmlspecialchars(t('portal_date')) ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($patientRecords as $r): ?>
                  <tr>
                    <td><span class="badge bg-secondary text-capitalize"><?= htmlspecialchars(str_replace('_',' ', $r['record_type'])) ?></span></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['title']) ?></td>
                    <td><?= htmlspecialchars($r['details']) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($r['recorded_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  <?php else: ?>
    <div class="alert alert-warning"><?= htmlspecialchars(t('err_session_expired')) ?></div>
  <?php endif; ?>

<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
