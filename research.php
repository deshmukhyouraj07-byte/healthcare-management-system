<?php
/**
 * research.php — Research projects grouped by medical specialty.
 * Uses the same $specialties list (from translations.php) that powers
 * the homepage's "Our Services" section, so departments stay consistent
 * across the site. Doctors log new research entries from their dashboard
 * in portal.php.
 */
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/translations.php';

if (!isset($_SESSION['lang'])) {
    header('Location: language_select.php');
    exit;
}

$HOSPITAL_NAME = t('hospital_name');
$lang = currentLang();
$pdo = getDbConnection();

$selectedSpecialty = trim($_GET['specialty'] ?? '');

// Count projects per specialty, for the overview grid.
$counts = [];
try {
    $stmt = $pdo->query('SELECT specialty, COUNT(*) AS cnt FROM research_projects GROUP BY specialty');
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row['specialty']] = (int) $row['cnt'];
    }
} catch (Throwable $e) {
    // silently ignore for demo purposes
}

$ongoing = [];
$completed = [];
$specialtyDoctors = [];
if ($selectedSpecialty !== '') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM research_projects WHERE specialty = :sp AND status = 'ongoing' ORDER BY started_date DESC");
        $stmt->execute([':sp' => $selectedSpecialty]);
        $ongoing = $stmt->fetchAll();

        $stmt2 = $pdo->prepare("SELECT * FROM research_projects WHERE specialty = :sp AND status = 'completed' ORDER BY completed_date DESC");
        $stmt2->execute([':sp' => $selectedSpecialty]);
        $completed = $stmt2->fetchAll();

        $stmt3 = $pdo->prepare("SELECT username, full_name, availability FROM users
                                 WHERE role = 'employee' AND is_active = 1 AND specialty = :sp
                                 ORDER BY full_name ASC");
        $stmt3->execute([':sp' => $selectedSpecialty]);
        $specialtyDoctors = $stmt3->fetchAll();
    } catch (Throwable $e) {
        // silently ignore for demo purposes
    }
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(t('research_title')) ?> | <?= htmlspecialchars($HOSPITAL_NAME) ?></title>
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
  .spec-card{ border:none; border-radius:16px; padding:26px; height:100%; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; transition:.2s; text-decoration:none; color:#28323c; display:block; }
  .spec-card:hover{ transform:translateY(-4px); box-shadow:0 12px 28px rgba(13,110,168,.14); color:var(--brand-dark); }
  .spec-icon{ width:56px; height:56px; border-radius:14px; background:var(--brand-light); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:14px; }
  .research-card{ border:none; border-radius:14px; padding:22px; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; margin-bottom:18px; }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="bjmc_logo.jpg" alt="A descriptive description of the image"height="45"width="40" class="d-inline-block align-text-top me-2">
 <?= htmlspecialchars($HOSPITAL_NAME) ?></a>
    <a href="<?= $selectedSpecialty !== '' ? 'research.php' : 'index.php' ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
      <i class="bi bi-arrow-left"></i> <?= $selectedSpecialty !== '' ? htmlspecialchars(t('back_research')) : htmlspecialchars(t('back_home')) ?>
    </a>
  </div>
</nav>

<header class="page-header">
  <div class="container">
    <h1 class="fw-bold mb-2">      <img src="sankelogo.jpg" alt="A descriptive description of the image"height="60"width="60" class="d-inline-block align-text-top me-2">
<?= htmlspecialchars(t('research_title')) ?></h1>
    <p class="mb-0" style="color:#dfeefa;"><?= htmlspecialchars(t('research_subtitle')) ?></p>
  </div>
</header>

<div class="container py-5">

  <?php if ($selectedSpecialty === ''): ?>
    <!-- ---------- SPECIALTY OVERVIEW GRID ---------- -->
    <div class="row g-4">
      <?php foreach ($specialties as $sp):
        $title = $lang === 'mr' ? $sp['mr_title'] : $sp['en_title'];
        $count = $counts[$sp['en_title']] ?? 0;
      ?>
        <div class="col-md-6 col-lg-4">
          <a href="research.php?specialty=<?= urlencode($sp['en_title']) ?>" class="spec-card">
            <div class="spec-icon"><i class="bi <?= htmlspecialchars($sp['icon']) ?>"></i></div>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($title) ?></h5>
            <p class="text-muted small mb-0"><?= $count ?> <?= htmlspecialchars(t('research_project_count')) ?></p>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <!-- ---------- PROJECTS FOR ONE SPECIALTY ---------- -->
    <?php
      $specTitle = $selectedSpecialty;
      foreach ($specialties as $sp) {
          if ($sp['en_title'] === $selectedSpecialty) {
              $specTitle = $lang === 'mr' ? $sp['mr_title'] : $sp['en_title'];
              break;
          }
      }
    ?>
    <h3 class="fw-bold mb-4"><?= htmlspecialchars($specTitle) ?></h3>

    <h5 class="section-title mb-3"><i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars(t('research_doctors_in_dept')) ?></h5>
    <?php if (!$specialtyDoctors): ?>
      <p class="text-muted small mb-4"><?= htmlspecialchars(t('research_no_doctors_in_dept')) ?></p>
    <?php else: ?>
      <div class="d-flex flex-wrap gap-2 mb-4">
        <?php foreach ($specialtyDoctors as $doc): ?>
          <a href="doctor_detail.php?username=<?= urlencode($doc['username']) ?>" class="badge rounded-pill px-3 py-2 text-decoration-none <?= $doc['availability'] === 'available' ? 'bg-success' : 'bg-secondary' ?>">
            <i class="bi bi-person-fill me-1"></i>Dr. <?= htmlspecialchars($doc['full_name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h5 class="section-title mb-3"><i class="bi bi-hourglass-split me-1"></i> <?= htmlspecialchars(t('research_ongoing_title')) ?></h5>
    <?php if (!$ongoing): ?>
      <p class="text-muted small mb-4"><?= htmlspecialchars(t('research_no_ongoing')) ?></p>
    <?php else: ?>
      <?php foreach ($ongoing as $proj): ?>
        <div class="research-card">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h6 class="fw-bold mb-1"><?= htmlspecialchars($proj['title']) ?></h6>
            <span class="badge bg-warning text-dark"><?= htmlspecialchars(t('research_ongoing')) ?></span>
          </div>
          <?php if ($proj['author_name']): ?><p class="text-muted small mb-2"><?= htmlspecialchars($proj['author_name']) ?></p><?php endif; ?>
          <p class="mb-2"><?= nl2br(htmlspecialchars($proj['description'])) ?></p>
          <p class="text-muted small mb-0"><?= htmlspecialchars(t('research_started')) ?>: <?= htmlspecialchars(date('F Y', strtotime($proj['started_date']))) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <h5 class="section-title mb-3 mt-4"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars(t('research_completed_title')) ?></h5>
    <?php if (!$completed): ?>
      <p class="text-muted small"><?= htmlspecialchars(t('research_no_completed')) ?></p>
    <?php else: ?>
      <?php foreach ($completed as $proj): ?>
        <div class="research-card">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h6 class="fw-bold mb-1"><?= htmlspecialchars($proj['title']) ?></h6>
            <span class="badge bg-success"><?= htmlspecialchars(t('research_completed')) ?></span>
          </div>
          <?php if ($proj['author_name']): ?><p class="text-muted small mb-2"><?= htmlspecialchars($proj['author_name']) ?></p><?php endif; ?>
          <p class="mb-2"><?= nl2br(htmlspecialchars($proj['description'])) ?></p>
          <?php if ($proj['conclusion']): ?>
            <p class="mb-2"><strong><?= htmlspecialchars(t('research_conclusion')) ?>:</strong> <?= nl2br(htmlspecialchars($proj['conclusion'])) ?></p>
          <?php endif; ?>
          <p class="text-muted small mb-0"><?= htmlspecialchars(t('research_started')) ?>: <?= htmlspecialchars(date('F Y', strtotime($proj['started_date']))) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>

</div>

</body>
</html>
