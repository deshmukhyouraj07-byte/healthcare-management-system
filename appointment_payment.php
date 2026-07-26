<?php
/**
 * appointment_payment.php — Shows the appointment fee + QR code, and lets
 * the person upload a screenshot as proof of payment. The appointment
 * moves to "pending_verification" once a screenshot is uploaded; staff
 * then verify it (see portal.php) before it becomes "confirmed".
 *
 * Access control: either (a) you're logged in as the patient this
 * appointment belongs to, or (b) you have the private access_token link
 * that was shown right after booking (this is how guest/walk-in bookings,
 * which have no account, get back to their own payment page).
 *
 * Replace /assets/payment_qr.png with your own UPI/payment QR image.
 */
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/translations.php';

if (!isset($_SESSION['lang'])) {
    header('Location: language_select.php');
    exit;
}

$HOSPITAL_NAME = t('hospital_name');
$pdo = getDbConnection();

$appointmentId = (int) ($_GET['id'] ?? 0);
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

$stmt = $pdo->prepare('SELECT a.*, u.full_name AS doctor_name, u.specialty AS doctor_specialty
                        FROM appointments a
                        JOIN users u ON u.id = a.doctor_id
                        WHERE a.id = :id LIMIT 1');
$stmt->execute([':id' => $appointmentId]);
$appt = $stmt->fetch();

if (!$appt) {
    header('Location: index.php');
    exit;
}

// Access check: logged-in matching patient, OR correct access token.
$loggedInMatch = isset($_SESSION['patient_id']) && $appt['patient_id'] === $_SESSION['patient_id'];
$tokenMatch    = $token !== '' && hash_equals((string) $appt['access_token'], (string) $token);

if (!$loggedInMatch && !$tokenMatch) {
    header('Location: index.php');
    exit;
}

$displayName = $appt['patient_id'] ? null : $appt['guest_name']; // filled in below once we know

$errors = [];
$uploadOk = false;

// ---- Upload payment screenshot as proof ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_payment_proof'
    && $appt['status'] === 'pending_payment') {

    if (empty($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = t('appt_err_screenshot_required');
    } else {
        $file = $_FILES['screenshot'];
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);

        if (!isset($allowedTypes[$mime])) {
            $errors[] = t('appt_err_screenshot_type');
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB cap
            $errors[] = t('appt_err_screenshot_size');
        } else {
            $uploadDir = __DIR__ . '/uploads/appointment_payments';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'appt_' . $appt['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$mime];
            $destPath = $uploadDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $relativePath = 'uploads/appointment_payments/' . $filename;
                $upd = $pdo->prepare('UPDATE appointments SET payment_screenshot = :path, status = "pending_verification" WHERE id = :id');
                $upd->execute([':path' => $relativePath, ':id' => $appt['id']]);
                $uploadOk = true;

                // Refresh local copy so the page below reflects the new state.
                $appt['payment_screenshot'] = $relativePath;
                $appt['status'] = 'pending_verification';
            } else {
                $errors[] = t('appt_err_screenshot_failed');
            }
        }
    }
}

$qrPath = __DIR__ . '/assets/payment_qr.png';
$qrExists = file_exists($qrPath);

$statusLabels = [
    'pending_payment'      => t('appt_status_pending_payment'),
    'pending_verification' => t('appt_status_pending_verification'),
    'confirmed'             => t('appt_status_confirmed'),
    'rejected'              => t('appt_status_rejected'),
];
$statusColors = [
    'pending_payment'      => 'bg-warning text-dark',
    'pending_verification' => 'bg-info text-dark',
    'confirmed'             => 'bg-success',
    'rejected'              => 'bg-danger',
];

$patientDisplayName = $appt['patient_id'] ? null : $appt['guest_name'];
if ($appt['patient_id']) {
    $pstmt = $pdo->prepare('SELECT full_name FROM patients WHERE patient_id = :pid LIMIT 1');
    $pstmt->execute([':pid' => $appt['patient_id']]);
    $prow = $pstmt->fetch();
    $patientDisplayName = $prow ? $prow['full_name'] : $appt['patient_id'];
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(t('appt_payment_title')) ?> | <?= htmlspecialchars($HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; }
  body{ font-family:'Poppins', sans-serif; background:var(--brand-light); color:#28323c; }
  .navbar{ background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .card-box{ border:none; border-radius:16px; padding:30px; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; }
  .qr-box{ border:2px dashed #c9dbe8; border-radius:14px; padding:24px; text-align:center; }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php" style="color:var(--brand-dark);"><i class="bi bi-heart-pulse-fill text-primary me-1"></i> <?= htmlspecialchars($HOSPITAL_NAME) ?></a>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('back_home')) ?></a>
  </div>
</nav>

<div class="container py-5">

  <?php if (!$appt['patient_id']): ?>
    <div class="alert alert-warning small">
      <i class="bi bi-bookmark-star me-1"></i> <?= htmlspecialchars(t('appt_guest_bookmark_notice')) ?>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <?php if ($uploadOk): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars(t('appt_upload_success')) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card-box">
        <h5 class="fw-bold mb-3"><i class="bi bi-calendar-event me-1"></i> <?= htmlspecialchars(t('appt_summary_title')) ?></h5>
        <div class="mb-2"><span class="text-muted small"><?= htmlspecialchars(t('appt_your_name')) ?>:</span> <strong><?= htmlspecialchars($patientDisplayName) ?></strong></div>
        <div class="mb-2"><span class="text-muted small"><?= htmlspecialchars(t('appt_choose_doctor')) ?>:</span> <strong>Dr. <?= htmlspecialchars($appt['doctor_name']) ?></strong><?= $appt['doctor_specialty'] ? ' (' . htmlspecialchars($appt['doctor_specialty']) . ')' : '' ?></div>
        <div class="mb-2"><span class="text-muted small"><?= htmlspecialchars(t('appt_date')) ?>:</span> <strong><?= htmlspecialchars(date('F j, Y', strtotime($appt['appointment_date']))) ?></strong></div>
        <div class="mb-2"><span class="text-muted small"><?= htmlspecialchars(t('appt_time')) ?>:</span> <strong><?= htmlspecialchars(date('g:i A', strtotime($appt['appointment_time']))) ?></strong></div>
        <?php if ($appt['reason']): ?><div class="mb-2"><span class="text-muted small"><?= htmlspecialchars(t('appt_reason')) ?>:</span> <?= htmlspecialchars($appt['reason']) ?></div><?php endif; ?>
        <hr>
        <div class="d-flex justify-content-between fw-bold mb-2">
          <span><?= htmlspecialchars(t('appt_fee_label')) ?></span><span>₹<?= number_format((float)$appt['fee'], 2) ?></span>
        </div>
        <span class="badge <?= $statusColors[$appt['status']] ?>"><?= htmlspecialchars($statusLabels[$appt['status']]) ?></span>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card-box">
        <h5 class="fw-bold mb-3"><i class="bi bi-qr-code me-1"></i> <?= htmlspecialchars(t('appt_pay_qr_title')) ?></h5>
        <div class="qr-box mb-3">
          <?php if ($qrExists): ?>
            <img src="assets/payment_qr.png" alt="Payment QR Code" style="max-width:220px; width:100%;">
          <?php else: ?>
            <i class="bi bi-qr-code" style="font-size:4rem; color:#c9dbe8;"></i>
            <p class="text-muted small mt-2 mb-0"><?= htmlspecialchars(t('appt_no_qr')) ?> <code>assets/payment_qr.png</code>.</p>
          <?php endif; ?>
        </div>

        <?php if ($appt['status'] === 'pending_payment'): ?>
          <p class="text-muted small"><?= htmlspecialchars(t('appt_scan_pay_text')) ?> <strong>₹<?= number_format((float)$appt['fee'], 2) ?></strong>. <?= htmlspecialchars(t('appt_then_upload_text')) ?></p>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_payment_proof">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="mb-3">
              <label class="form-label"><?= htmlspecialchars(t('appt_screenshot_label')) ?> *</label>
              <input type="file" name="screenshot" class="form-control" accept="image/png,image/jpeg,image/webp" required>
              <div class="form-text"><?= htmlspecialchars(t('appt_screenshot_hint')) ?></div>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2 rounded-pill">
              <i class="bi bi-upload me-1"></i> <?= htmlspecialchars(t('appt_paid_upload_btn')) ?>
            </button>
          </form>
        <?php elseif ($appt['status'] === 'pending_verification'): ?>
          <div class="alert alert-info mb-0 text-center"><i class="bi bi-hourglass-split me-1"></i> <?= htmlspecialchars(t('appt_awaiting_verification')) ?></div>
        <?php elseif ($appt['status'] === 'confirmed'): ?>
          <div class="alert alert-success mb-0 text-center"><i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars(t('appt_confirmed_msg')) ?></div>
        <?php else: ?>
          <div class="alert alert-danger mb-0 text-center"><i class="bi bi-x-circle-fill me-1"></i> <?= htmlspecialchars(t('appt_rejected_msg')) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!$appt['patient_id']): ?>
    <p class="text-muted small mt-4">
      <?= htmlspecialchars(t('appt_bookmark_this_link')) ?><br>
      <code><?= htmlspecialchars('https://' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com') . '/appointment_payment.php?id=' . $appt['id'] . '&token=' . $token) ?></code>
    </p>
  <?php endif; ?>
</div>

</body>
</html>
