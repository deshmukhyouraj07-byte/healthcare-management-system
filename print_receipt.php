<?php
/**
 * print_receipt.php — One-time printable acknowledgment slip shown right after
 * a staff/doctor registers a new patient. Contains the Patient ID, the
 * PLAIN-TEXT password (only available at this moment — never stored anywhere),
 * prescribed medicines, and next appointment date.
 *
 * Security: only accessible to a logged-in staff/doctor, and the data is
 * cleared from the session immediately after being read so it cannot be
 * reprinted later or by reloading the page after leaving.
 */
session_start();
require_once __DIR__ . '/translations.php';

if (!isset($_SESSION['lang'])) {
    header('Location: language_select.php');
    exit;
}

if (!isset($_SESSION['staff_id'])) {
    header('Location: portal.php?role=employee');
    exit;
}

$receipt = $_SESSION['last_registered_receipt'] ?? null;
unset($_SESSION['last_registered_receipt']); // one-time use only

$staffName = $_SESSION['staff_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(t('receipt_title')) ?> | <?= htmlspecialchars(t('hospital_name')) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; }
  body{ font-family:'Poppins','Noto Sans Devanagari', sans-serif; background:var(--brand-light); color:#28323c; }
  .receipt-box{ background:#fff; border-radius:14px; box-shadow:0 10px 30px rgba(11,46,74,.08); padding:50px; max-width:750px; margin:40px auto; }
  .receipt-header{ border-bottom:3px solid var(--brand-primary); padding-bottom:20px; margin-bottom:30px; }
  .hospital-name{ font-weight:700; color:var(--brand-dark); font-size:1.4rem; }
  .credential-box{ background:var(--brand-light); border:2px dashed var(--brand-primary); border-radius:10px; padding:20px; margin:20px 0; }
  .credential-row{ display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #e0e8ef; }
  .credential-row:last-child{ border-bottom:none; }
  .label{ color:#6b7a89; font-size:.9rem; }
  .value{ font-weight:700; color:var(--brand-dark); font-family:monospace; font-size:1.05rem; }
  .info-row{ display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f0f4f7; }
  .no-print{ }
  @media print {
    body{ background:#fff; }
    .no-print{ display:none !important; }
    .receipt-box{ box-shadow:none; margin:0; max-width:100%; }
  }
</style>
</head>
<body>

<div class="container no-print py-3 d-flex justify-content-between">
  <a href="portal.php?role=employee" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('back_dashboard')) ?>
  </a>
  <?php if ($receipt): ?>
  <button onclick="window.print()" class="btn btn-primary btn-sm">
    <i class="bi bi-printer-fill me-1"></i> <?= htmlspecialchars(t('receipt_print_btn')) ?>
  </button>
  <?php endif; ?>
</div>

<?php if (!$receipt): ?>
  <div class="container">
    <div class="alert alert-warning"><?= htmlspecialchars(t('receipt_expired')) ?></div>
  </div>
<?php else: ?>

<div class="receipt-box">
  <div class="receipt-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <div class="hospital-name"><i class="bi bi-heart-pulse-fill text-primary me-1"></i> <?= htmlspecialchars(t('hospital_name')) ?></div>
      <div class="text-muted small"><?= htmlspecialchars(t('receipt_title')) ?></div>
    </div>
    <div class="text-md-end">
      <div class="text-muted small"><?= htmlspecialchars(t('receipt_registered_on')) ?>: <?= date('F j, Y') ?></div>
    </div>
  </div>

  <div class="info-row">
    <span class="label"><?= htmlspecialchars(t('receipt_name')) ?></span>
    <span class="fw-semibold"><?= htmlspecialchars($receipt['full_name']) ?></span>
  </div>

  <div class="credential-box">
    <div class="credential-row">
      <span class="label"><?= htmlspecialchars(t('receipt_patient_id')) ?></span>
      <span class="value"><?= htmlspecialchars($receipt['patient_id']) ?></span>
    </div>
    <div class="credential-row">
      <span class="label"><?= htmlspecialchars(t('receipt_password')) ?></span>
      <span class="value"><?= htmlspecialchars($receipt['password']) ?></span>
    </div>
  </div>
  <p class="text-muted small"><i class="bi bi-info-circle me-1"></i> <?= htmlspecialchars(t('receipt_keep_safe')) ?></p>

  <div class="info-row">
    <span class="label"><?= htmlspecialchars(t('receipt_medicines')) ?></span>
    <span class="fw-semibold text-end"><?= $receipt['medicines'] !== '' ? nl2br(htmlspecialchars($receipt['medicines'])) : htmlspecialchars(t('receipt_none_noted')) ?></span>
  </div>

  <div class="info-row">
    <span class="label"><?= htmlspecialchars(t('receipt_next_appt')) ?></span>
    <span class="fw-semibold"><?= $receipt['next_appt'] !== '' ? htmlspecialchars(date('F j, Y', strtotime($receipt['next_appt']))) : htmlspecialchars(t('receipt_not_scheduled')) ?></span>
  </div>

  <p class="text-muted small mt-4 mb-0"><?= htmlspecialchars($staffName) ?></p>
</div>

<?php endif; ?>

</body>
</html>
