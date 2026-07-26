<?php
/**
 * id_card.php — Printable Virtual ID Card for a logged-in doctor/staff member.
 * Linked from the "Virtual ID Card" panel on their portal.php dashboard.
 */
session_start();
require_once __DIR__ . '/session_helpers.php';
enforceStaffSessionTimeout(120);
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/translations.php';

if (!isset($_SESSION['lang'])) {
    header('Location: language_select.php');
    exit;
}

if (!isset($_SESSION['staff_id'])) {
    header('Location: portal.php?role=employee');
    exit;
}

$HOSPITAL_NAME = t('hospital_name');
$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT username, full_name, role, specialty, availability, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['staff_id']]);
$staff = $stmt->fetch();

if (!$staff) {
    header('Location: portal.php?role=employee');
    exit;
}

$isDoctor = $staff['role'] === 'employee';
$roleLabel = $isDoctor ? t('login_doctor') : t('login_staff');
$cardBackUrl = 'portal.php?role=' . ($isDoctor ? 'employee' : 'staff');
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(t('id_card_page_title')) ?> | <?= htmlspecialchars($HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-secondary:#12b8a6; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; --brand-font:'Poppins','Noto Sans Devanagari', sans-serif; }
  body{ font-family:var(--brand-font); background:var(--brand-light); color:#28323c; }
  .no-print{ }

  .id-wrap{ max-width:420px; margin:40px auto; }
  .id-card-full{
    background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 20px 50px rgba(11,46,74,.15);
    border:1px solid #e7eef3;
  }
  .id-card-header{
    background:linear-gradient(120deg, var(--brand-dark) 0%, var(--brand-primary) 100%);
    color:#fff; padding:22px 24px 40px; text-align:center; position:relative;
  }
  .id-card-header .hosp-name{ font-weight:700; font-size:1rem; letter-spacing:.4px; }
  .id-card-header .card-label{ font-size:.72rem; text-transform:uppercase; letter-spacing:1.5px; color:#cfe3f0; margin-top:2px; }
  .id-card-avatar-wrap{
    width:96px; height:96px; border-radius:50%; background:#fff; border:4px solid #fff;
    display:flex; align-items:center; justify-content:center; margin:0 auto; margin-top:-48px;
    box-shadow:0 6px 16px rgba(0,0,0,.15); position:relative; z-index:2;
  }
  .id-card-avatar-wrap i{ font-size:2.6rem; color:var(--brand-primary); }
  .id-card-body{ padding:56px 24px 24px; text-align:center; }
  .id-card-body h3{ font-weight:800; color:var(--brand-dark); margin-bottom:2px; }
  .id-card-role-badge{
    display:inline-block; background:var(--brand-light); color:var(--brand-primary);
    border-radius:20px; padding:4px 14px; font-size:.8rem; font-weight:600; margin-bottom:18px;
  }
  .id-card-row{ display:flex; justify-content:space-between; padding:9px 4px; border-bottom:1px dashed #e7eef3; font-size:.9rem; text-align:left; }
  .id-card-row:last-child{ border-bottom:none; }
  .id-card-row .label{ color:#8494a3; }
  .id-card-row .value{ font-weight:700; color:var(--brand-dark); font-family:monospace; }
  .id-card-footer{
    background:var(--brand-light); padding:14px; text-align:center; font-size:.72rem; color:#8494a3;
  }
  .id-barcode{
    height:36px; background:repeating-linear-gradient(90deg, #28323c 0px, #28323c 2px, transparent 2px, transparent 5px);
    margin:16px 4px 4px; opacity:.85;
  }

  @media print {
    body{ background:#fff; }
    .no-print{ display:none !important; }
    .id-wrap{ margin:0 auto; }
    .id-card-full{ box-shadow:none; border:1px solid #ccc; }
  }
</style>
</head>
<body>

<div class="container no-print py-3 d-flex justify-content-between">
  <a href="<?= htmlspecialchars($cardBackUrl) ?>" class="btn btn-outline-secondary btn-sm">
<img src="bjmc_logo.jpg" alt="A descriptive fallback text" width="70" height="80" loading="lazy"><b>Sassoon general Hospital
   & BJ Medical College, Pune
</b>
  </a>
  <button onclick="window.print()" class="btn btn-primary btn-sm">
    <i class="bi bi-printer-fill me-1" height="10"></i> <?= htmlspecialchars(t('id_card_print_btn')) ?>
  </button>
</div>

<div class="id-wrap">
  <div class="id-card-full">
    <div class="id-card-header">
      <div class="hosp-name"><?= htmlspecialchars($HOSPITAL_NAME) ?></div>
      <div class="card-label"><?= htmlspecialchars(t('id_card_page_title')) ?></div>
    </div>

    <div class="id-card-avatar-wrap">
<img src="bjmc_logo.jpg" alt="A descriptive fallback text" width="50" height="60" loading="lazy">
    </div>

    <div class="id-card-body">
      <h3><?= htmlspecialchars($staff['full_name']) ?></h3>
      <div class="id-card-role-badge">
        <?= htmlspecialchars($roleLabel) ?><?php if ($isDoctor && !empty($staff['specialty'])): ?> &middot; <?= htmlspecialchars($staff['specialty']) ?><?php endif; ?>
      </div>

      <div class="id-card-row">
        <span class="label"><?= htmlspecialchars(t('id_card_employee_id')) ?></span>
        <span class="value"><?= htmlspecialchars($staff['username']) ?></span>
      </div>
      <?php if ($isDoctor): ?>
      <div class="id-card-row">
        <span class="label"><?= htmlspecialchars(t('portal_availability')) ?></span>
        <span class="value"><?= $staff['availability'] === 'available' ? htmlspecialchars(t('portal_available')) : htmlspecialchars(t('portal_not_available')) ?></span>
      </div>
      <?php endif; ?>
      <div class="id-card-row">
        <span class="label"><?= htmlspecialchars(t('id_card_since')) ?></span>
        <span class="value"><?= htmlspecialchars(date('M Y', strtotime($staff['created_at']))) ?></span>
      </div>

      <div class="id-barcode"></div>
    </div>

    <div class="id-card-footer">
      <?= htmlspecialchars(t('id_card_footer_note')) ?>
    </div>
  </div>
</div>

</body>
</html>
