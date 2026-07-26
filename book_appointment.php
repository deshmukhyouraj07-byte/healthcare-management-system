<?php
/**
 * book_appointment.php — Anyone can book an appointment here, no account
 * needed. If a patient happens to be logged in already, we link the
 * appointment to their record (so it shows on their dashboard too);
 * otherwise we store their basic details directly on the appointment
 * (a "guest" booking) and give them a private link (via a random token)
 * to track/pay for it.
 */
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/translations.php';

if (!isset($_SESSION['lang'])) {
    header('Location: language_select.php');
    exit;
}

// A flat consultation fee for now — change here if fees should vary by doctor/specialty.
const APPOINTMENT_FEE = 200.00;

$HOSPITAL_NAME = t('hospital_name');
$pdo = getDbConnection();

$isLoggedInPatient = isset($_SESSION['patient_pk']);
$patientProfile = null;
if ($isLoggedInPatient) {
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['patient_pk']]);
    $patientProfile = $stmt->fetch();
}

$doctors = [];
try {
    $stmt = $pdo->query("SELECT id, full_name, specialty FROM users
                          WHERE role = 'employee' AND is_active = 1
                          ORDER BY (specialty IS NULL), specialty ASC, full_name ASC");
    $doctors = $stmt->fetchAll();
} catch (Throwable $e) {
    // silently ignore for demo purposes
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $date     = trim($_POST['appointment_date'] ?? '');
    $time     = trim($_POST['appointment_time'] ?? '');
    $reason   = trim($_POST['reason'] ?? '');
    $guestName    = trim($_POST['guest_name'] ?? '');
    $guestContact = trim($_POST['guest_contact'] ?? '');
    $guestEmail   = trim($_POST['guest_email'] ?? '');
    $guestDob     = trim($_POST['guest_dob'] ?? '');
    $guestGender  = trim($_POST['guest_gender'] ?? '');
    $guestAddress = trim($_POST['guest_address'] ?? '');

    if ($doctorId <= 0) {
        $errors[] = t('appt_err_doctor');
    }
    if ($date === '' || strtotime($date) === false || $date < date('Y-m-d')) {
        $errors[] = t('appt_err_date');
    }
    if ($time === '') {
        $errors[] = t('appt_err_time');
    }
    if (!$isLoggedInPatient) {
        if ($guestName === '') {
            $errors[] = t('appt_err_name');
        }
        if ($guestContact === '') {
            $errors[] = t('appt_err_contact');
        }
        if ($guestEmail !== '' && !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = t('appt_err_email');
        }
        if ($guestDob === '' || strtotime($guestDob) === false || $guestDob > date('Y-m-d')) {
            $errors[] = t('appt_err_dob');
        }
        if (!in_array($guestGender, ['Male', 'Female', 'Other', 'Prefer not to say'], true)) {
            $errors[] = t('appt_err_gender');
        }
    }

    if (!$errors) {
        try {
            $accessToken = bin2hex(random_bytes(24));

            if ($isLoggedInPatient && $patientProfile) {
                $stmt = $pdo->prepare('INSERT INTO appointments
                    (patient_id, doctor_id, appointment_date, appointment_time, reason, fee, status, access_token)
                    VALUES (:pid, :did, :date, :time, :reason, :fee, "pending_payment", :token)');
                $stmt->execute([
                    ':pid'    => $patientProfile['patient_id'],
                    ':did'    => $doctorId,
                    ':date'   => $date,
                    ':time'   => $time,
                    ':reason' => $reason !== '' ? $reason : null,
                    ':fee'    => APPOINTMENT_FEE,
                    ':token'  => $accessToken,
                ]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO appointments
                    (patient_id, guest_name, guest_contact, guest_email, guest_dob, guest_gender, guest_address, doctor_id, appointment_date, appointment_time, reason, fee, status, access_token)
                    VALUES (NULL, :gname, :gcontact, :gemail, :gdob, :ggender, :gaddress, :did, :date, :time, :reason, :fee, "pending_payment", :token)');
                $stmt->execute([
                    ':gname'    => $guestName,
                    ':gcontact' => $guestContact,
                    ':gemail'   => $guestEmail !== '' ? $guestEmail : null,
                    ':gdob'     => $guestDob,
                    ':ggender'  => $guestGender,
                    ':gaddress' => $guestAddress !== '' ? $guestAddress : null,
                    ':did'      => $doctorId,
                    ':date'     => $date,
                    ':time'     => $time,
                    ':reason'   => $reason !== '' ? $reason : null,
                    ':fee'      => APPOINTMENT_FEE,
                    ':token'    => $accessToken,
                ]);
            }
            $appointmentId = $pdo->lastInsertId();
            header('Location: appointment_payment.php?id=' . $appointmentId . '&token=' . $accessToken);
            exit;
        } catch (Throwable $e) {
            $errors[] = t('appt_err_failed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(t('appt_book_title')) ?> | <?= htmlspecialchars($HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; --brand-font:'Poppins','Noto Sans Devanagari', sans-serif; }
  body{ font-family:var(--brand-font); background:var(--brand-light); color:#28323c; }
  .navbar{ background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .card-box{ border:none; border-radius:16px; padding:34px; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php" style="color:var(--brand-dark);">
            <img src="bjmc_logo.jpg" alt="A descriptive description of the image"height="45"width="40" class="d-inline-block align-text-top me-2">
 <?= htmlspecialchars($HOSPITAL_NAME) ?></a>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('back_home')) ?></a>
  </div>
</nav>

<div class="container py-5" style="max-width:640px;">
  <h3 class="fw-bold mb-2"><i class="bi bi-calendar-plus me-2"></i><?= htmlspecialchars(t('appt_book_title')) ?></h3>
  <p class="text-muted mb-4"><?= htmlspecialchars(t('appt_book_subtitle')) ?></p>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <?php if ($isLoggedInPatient && $patientProfile): ?>
    <div class="alert alert-info small">
      <i class="bi bi-person-check-fill me-1"></i> <?= htmlspecialchars(t('appt_booking_as')) ?> <strong><?= htmlspecialchars($patientProfile['full_name']) ?></strong> (<?= htmlspecialchars($patientProfile['patient_id']) ?>).
      <a href="portal.php?role=patient&logout=patient"><?= htmlspecialchars(t('appt_not_you')) ?></a>
    </div>
  <?php endif; ?>

  <div class="card-box">
    <form method="POST">
      <?php if (!$isLoggedInPatient): ?>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(t('appt_your_name')) ?> *</label>
            <input type="text" name="guest_name" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(t('appt_your_contact')) ?> *</label>
            <input type="text" name="guest_contact" class="form-control" placeholder="+91..." required>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(t('appt_your_dob')) ?> *</label>
            <input type="date" name="guest_dob" class="form-control" max="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(t('appt_your_gender')) ?> *</label>
            <select name="guest_gender" class="form-select" required>
              <option value="" disabled selected><?= htmlspecialchars(t('appt_choose_gender_ph')) ?></option>
              <option value="Male"><?= htmlspecialchars(t('portal_gender_male')) ?></option>
              <option value="Female"><?= htmlspecialchars(t('portal_gender_female')) ?></option>
              <option value="Other"><?= htmlspecialchars(t('portal_gender_other')) ?></option>
              <option value="Prefer not to say"><?= htmlspecialchars(t('portal_gender_prefer_not')) ?></option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(t('appt_your_email')) ?></label>
            <input type="email" name="guest_email" class="form-control" placeholder="you@example.com">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><?= htmlspecialchars(t('appt_your_address')) ?></label>
          <input type="text" name="guest_address" class="form-control" placeholder="<?= htmlspecialchars(t('appt_address_ph')) ?>">
          <div class="form-text"><?= htmlspecialchars(t('appt_email_hint')) ?></div>
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(t('appt_choose_doctor')) ?> *</label>
        <select name="doctor_id" class="form-select" required>
          <option value="" disabled selected><?= htmlspecialchars(t('appt_choose_doctor_ph')) ?></option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= (int)$doc['id'] ?>">
              Dr. <?= htmlspecialchars($doc['full_name']) ?><?= $doc['specialty'] ? ' — ' . htmlspecialchars($doc['specialty']) : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label"><?= htmlspecialchars(t('appt_date')) ?> *</label>
          <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label"><?= htmlspecialchars(t('appt_time')) ?> *</label>
          <input type="time" name="appointment_time" class="form-control" required>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label"><?= htmlspecialchars(t('appt_reason')) ?></label>
        <textarea name="reason" class="form-control" rows="3" placeholder="<?= htmlspecialchars(t('appt_reason_ph')) ?>"></textarea>
      </div>
      <div class="alert" style="background:rgba(13,110,168,.08); border:1px solid rgba(13,110,168,.2);">
        <?= htmlspecialchars(t('appt_fee_notice')) ?> ₹<?= number_format(APPOINTMENT_FEE, 2) ?>
      </div>
      <button type="submit" class="btn btn-brand w-100 py-2 rounded-pill">
        <i class="bi bi-calendar-check me-1"></i> <?= htmlspecialchars(t('appt_book_btn')) ?>
      </button>
    </form>
  </div>
</div>

</body>
</html>
