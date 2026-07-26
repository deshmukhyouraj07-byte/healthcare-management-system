<?php
/**
 * doctors.php — Public Doctor Directory
 * Lists all active doctors (users.role = 'employee') with their specialty
 * and availability, pulled live from the database. Includes a search bar
 * that filters by name or specialty (server-side, via GET so results are
 * shareable/bookmarkable).
 */
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/translations.php';

if (!isset($_SESSION['lang'])) {
    header('Location: language_select.php');
    exit;
}

$HOSPITAL_NAME = t('hospital_name');
$query = trim($_GET['q'] ?? '');

$doctors = [];
try {
    $pdo = getDbConnection();
    $sql = "SELECT username, full_name, specialty, availability FROM users
            WHERE role = 'employee' AND is_active = 1";
    $params = [];
    if ($query !== '') {
        $sql .= ' AND (full_name LIKE :q OR specialty LIKE :q)';
        $params[':q'] = '%' . $query . '%';
    }
    $sql .= ' ORDER BY (specialty IS NULL), specialty ASC, full_name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $doctors = $stmt->fetchAll();
} catch (Throwable $e) {
    // silently ignore for demo purposes
}

// Map a specialty name to one of the Bootstrap Icons already used in $specialties,
// so a doctor's card gets a relevant icon instead of a generic one.
$specialtyIcon = static function (?string $specialty) use ($specialties): string {
    if (!$specialty) {
        return 'bi-person-badge';
    }
    foreach ($specialties as $sp) {
        if (strcasecmp($sp['en_title'], $specialty) === 0) {
            return $sp['icon'];
        }
    }
    return 'bi-person-badge';
};
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(t('doctors_title')) ?> | <?= htmlspecialchars($HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-secondary:#12b8a6; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; --brand-font:'Poppins','Noto Sans Devanagari', sans-serif; }
  body{ font-family:var(--brand-font); color:#28323c; background:var(--brand-light); }
  .navbar{ background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .navbar-brand{ font-weight:700; color:var(--brand-dark)!important; }
  .navbar-brand i{ color:var(--brand-primary); }
  .page-header{ background:linear-gradient(120deg, var(--brand-dark) 0%, var(--brand-primary) 100%); color:#fff; padding:55px 0 40px; }
  .doc-card{ border:none; border-radius:16px; padding:26px; height:100%; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; transition:.2s; }
  .doc-card:hover{ transform:translateY(-4px); box-shadow:0 12px 28px rgba(13,110,168,.14); }
  .doc-icon{ width:56px; height:56px; border-radius:14px; background:var(--brand-light); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:14px; }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand" href="index.php">      <img src="bjmc_logo.jpg" alt="A descriptive description of the image"height="45"width="40" class="d-inline-block align-text-top me-2">
 <?= htmlspecialchars($HOSPITAL_NAME) ?></a>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('back_home')) ?></a>
  </div>
</nav>

<header class="page-header">
  <div class="container">
    <h1 class="fw-bold mb-2"><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars(t('doctors_title')) ?></h1>
    <p class="mb-4" style="color:#dfeefa;"><?= htmlspecialchars(t('doctors_subtitle')) ?></p>
    <form method="GET" class="d-flex gap-2" style="max-width:480px;">
      <input type="text" name="q" class="form-control" placeholder="<?= htmlspecialchars(t('doctors_search_ph')) ?>" value="<?= htmlspecialchars($query) ?>">
      <button class="btn btn-light fw-semibold px-4" type="submit"><i class="bi bi-search"></i></button>
    </form>
  </div>
</header>

<div class="container py-5">
  <?php if (!$doctors): ?>
    <p class="text-muted"><?= htmlspecialchars(t('doctors_none_found')) ?><?= $query ? ' "' . htmlspecialchars($query) . '"' : '' ?>.</p>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($doctors as $doc): ?>
        <div class="col-md-6 col-lg-4">
          <a href="doctor_detail.php?username=<?= urlencode($doc['username']) ?>" class="doc-card text-decoration-none text-reset d-block">
            <div class="d-flex justify-content-between align-items-start">
              <div class="doc-icon"><i class="bi <?= htmlspecialchars($specialtyIcon($doc['specialty'])) ?>"></i></div>
              <span class="badge rounded-pill px-3 py-2 <?= $doc['availability'] === 'available' ? 'bg-success' : 'bg-secondary' ?>">
                <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>
                <?= $doc['availability'] === 'available' ? htmlspecialchars(t('portal_available')) : htmlspecialchars(t('portal_not_available')) ?>
              </span>
            </div>
            <h5 class="fw-bold mb-1">Dr. <?= htmlspecialchars($doc['full_name']) ?></h5>
            <p class="text-muted mb-0"><?= htmlspecialchars($doc['specialty'] ?: t('doctors_specialty_unset')) ?></p>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
