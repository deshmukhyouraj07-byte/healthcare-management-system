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

    if ($fullName === '' || $dob === '' || $gender === '' || $contact === '' || $email === '' || $patientPwd === '') {
        $errors[] = t('err_patient_fields');
    } else {
        try {
            $pdo = getDbConnection();

            // Determine the Patient ID, guaranteeing it does not already exist.
            if ($manualId !== '') {
                // Staff supplied one manually — check it isn't already taken.
                $checkStmt = $pdo->prepare('SELECT id FROM patients WHERE patient_id = :pid LIMIT 1');
                $checkStmt->execute([':pid' => $manualId]);
                if ($checkStmt->fetch()) {
                    throw new RuntimeException('That Patient ID is already in use — please choose a different one or leave it blank to auto-generate.');
                }
                $patientId = $manualId;
            } else {
                // Auto-generate, actively checking the database until a truly
                // unique ID is found (bounded to avoid an infinite loop).
                $attempts = 0;
                do {
                    $patientId = 'PT-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
                    $checkStmt = $pdo->prepare('SELECT id FROM patients WHERE patient_id = :pid LIMIT 1');
                    $checkStmt->execute([':pid' => $patientId]);
                    $exists = (bool) $checkStmt->fetch();
                    $attempts++;
                } while ($exists && $attempts < 10);

                if ($exists) {
                    throw new RuntimeException('Could not generate a unique Patient ID after several attempts. Please try again.');
                }
            }

            $hash = password_hash($patientPwd, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('INSERT INTO patients
                (patient_id, full_name, date_of_birth, gender, contact_info, email, address, medical_history, prescribed_medicines, next_appointment_date, password_hash, registered_by)
                VALUES (:pid, :name, :dob, :gender, :contact, :email, :address, :history, :medicines, :nextappt, :hash, :staff)');

            $stmt->execute([
                ':pid'      => $patientId,
                ':name'     => $fullName,
                ':dob'      => $dob,
                ':gender'   => $gender,
                ':contact'  => $contact,
                ':email'    => $email,
                ':address'  => $address,
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
                'medicines'  => $medicines,
                'next_appt'  => $nextAppt,
                'staff_id'   => $_SESSION['staff_id'],
            ];
        } catch (Throwable $e) {
            $errors[] = t('err_patient_exists') . ' (' . $e->getMessage() . ')';
        }

        // Send the prescription PDF by email — in its OWN try/catch so that any
        // problem here (missing library, SMTP failure, etc.) is reported as an
        // email error and never mistaken for a registration failure above.
        if ($success !== '') {
            try {
                require_once __DIR__ . '/send_prescription.php';
                $doctorInfo = [
                    'name' => $_SESSION['staff_name'],
                    'role' => $_SESSION['staff_role'],
                ];
                if ($_SESSION['staff_role'] === 'employee') {
                    $docStmt = $pdo->prepare('SELECT specialty FROM users WHERE id = :id');
                    $docStmt->execute([':id' => $_SESSION['staff_id']]);
                    $docRow = $docStmt->fetch();
                    $doctorInfo['specialty'] = $docRow['specialty'] ?? '';
                }

                $emailSent = sendPrescriptionEmail([
                    'patient_id'  => $patientId,
                    'full_name'   => $fullName,
                    'dob'         => $dob,
                    'gender'      => $gender,
                    'contact'     => $contact,
                    'email'       => $email,
                    'history'     => $history,
                    'medicines'   => $medicines,
                    'next_appt'   => $nextAppt,
                ], $doctorInfo);

                if ($emailSent) {
                    $success .= ' ' . t('email_sent_success');
                } else {
                    $errors[] = t('email_sent_failed');
                }
            } catch (Throwable $e) {
                // Show the REAL underlying error so it can actually be debugged,
                // instead of a generic message. Safe to show here since only
                // staff/doctors see this page.
                $errors[] = t('email_sent_failed') . ' (' . $e->getMessage() . ')';
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

// ---- Patient login -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'patient_login') {
    $patientId = trim($_POST['patient_id'] ?? '');
    $password  = $_POST['patient_password_login'] ?? '';

    if ($patientId === '' || $password === '') {
        $errors[] = t('err_patient_id_required');
    } else {
        try {
            $pdo  = getDbConnection();
            $stmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = :pid AND is_active = 1 LIMIT 1');
            $stmt->execute([':pid' => $patientId]);
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
  <div class="alert alert-success"><i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['last_registered_receipt']) && $_SESSION['last_registered_receipt']['staff_id'] === ($_SESSION['staff_id'] ?? null)): ?>
  <div class="alert d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:rgba(13,110,168,.1); border:1px solid rgba(13,110,168,.3);">
    <span><i class="bi bi-file-earmark-medical-fill me-1"></i>
      <?= htmlspecialchars(t('receipt_name')) ?>: <strong><?= htmlspecialchars($_SESSION['last_registered_receipt']['full_name']) ?></strong>
      (<?= htmlspecialchars($_SESSION['last_registered_receipt']['patient_id']) ?>)
    </span>
    <a href="print_receipt.php" target="_blank" class="btn btn-brand btn-sm rounded-pill">
      <i class="bi bi-printer-fill me-1"></i> <?= htmlspecialchars(t('portal_print_receipt')) ?>
    </a>
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
              <label class="form-label"><?= htmlspecialchars(t('portal_patient_email')) ?> *</label>
              <input type="email" name="email" class="form-control" required placeholder="<?= htmlspecialchars(t('portal_patient_email_ph')) ?>">
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
                  <tr><th><?= htmlspecialchars(t('portal_patient_id')) ?></th><th><?= htmlspecialchars(t('portal_full_name')) ?></th><th><?= htmlspecialchars(t('portal_dob')) ?></th><th><?= htmlspecialchars(t('portal_gender')) ?></th><th><?= htmlspecialchars(t('portal_contact_info')) ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($staffPatients as $p): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($p['patient_id']) ?></td>
                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                    <td><?= htmlspecialchars($p['date_of_birth']) ?></td>
                    <td><?= htmlspecialchars($p['gender']) ?></td>
                    <td><?= htmlspecialchars($p['contact_info']) ?></td>
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
              <label class="form-label"><?= htmlspecialchars(t('portal_patient_id')) ?></label>
              <input type="text" name="patient_id" class="form-control" placeholder="PT-2026-XXXXX" required autofocus>
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
