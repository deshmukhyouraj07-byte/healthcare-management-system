<?php
/**
 * researcher.php — Profile page for a single doctor: photo, department,
 * and the research they're credited with (matched via research_projects
 * .author_name = users.full_name, the same link research.php uses).
 * Linked from the department roster and research cards in research.php.
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

$doctorId   = $_GET['id'] ?? '';
$doctorName = trim($_GET['name'] ?? '');

$doctor = null;
$ongoing = [];
$completed = [];

try {
    if ($doctorId !== '') {
        $stmt = $pdo->prepare("SELECT id, full_name, specialty, availability, image FROM users
                                WHERE role = 'employee' AND is_active = 1 AND id = :id LIMIT 1");
        $stmt->execute([':id' => $doctorId]);
        $doctor = $stmt->fetch();
    } elseif ($doctorName !== '') {
        $stmt = $pdo->prepare("SELECT id, full_name, specialty, availability, image FROM users
                                WHERE role = 'employee' AND is_active = 1 AND full_name = :name LIMIT 1");
        $stmt->execute([':name' => $doctorName]);
        $doctor = $stmt->fetch();
    }

    if ($doctor) {
        $stmt = $pdo->prepare("SELECT * FROM research_projects WHERE author_name = :name AND status = 'ongoing' ORDER BY started_date DESC");
        $stmt->execute([':name' => $doctor['full_name']]);
        $ongoing = $stmt->fetchAll();

        $stmt2 = $pdo->prepare("SELECT * FROM research_projects WHERE author_name = :name AND status = 'completed' ORDER BY completed_date DESC");
        $stmt2->execute([':name' => $doctor['full_name']]);
        $completed = $stmt2->fetchAll();
    }
} catch (Throwable $e) {
    // silently ignore for demo purposes
}

// Resolve the specialty's display title from the same $specialties list
// research.php uses, so wording (and translation) stays consistent.
$specialtyTitle = $doctor['specialty'] ?? '';
if ($doctor) {
    foreach ($specialties as $sp) {
        if ($sp['en_title'] === $doctor['specialty']) {
            $specialtyTitle = $lang === 'mr' ? $sp['mr_title'] : $sp['en_title'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $doctor ? htmlspecialchars('Dr. ' . $doctor['full_name']) : htmlspecialchars(t('research_title')) ?> | <?= htmlspecialchars($HOSPITAL_NAME) ?></title>
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
  .profile-photo{ width:110px; height:110px; border-radius:50%; object-fit:cover; border:4px solid rgba(255,255,255,.35); background:#dfeefa; }
  .profile-photo-placeholder{ width:110px; height:110px; border-radius:50%; border:4px solid rgba(255,255,255,.35); background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:2.5rem; color:#fff; }
  .research-card{ border:none; border-radius:14px; padding:22px; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; margin-bottom:18px; }
  .section-title{ color:var(--brand-dark); }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand" href="index.php">    <img src="bjmc_logo.jpg" alt="A descriptive description of the image"height="45" width="38" class="me-1 rounded-circle">
 <?= htmlspecialchars($HOSPITAL_NAME) ?></a>
    <a href="<?= $doctor ? 'research.php?specialty=' . urlencode($doctor['specialty']) : 'research.php' ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
      <i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('back_research')) ?>
    </a>
  </div>
</nav>

<?php if (!$doctor): ?>

  <div class="container py-5 text-center">
    <h3 class="fw-bold mb-3">Researcher not found</h3>
    <p class="text-muted mb-4">We couldn't find a doctor matching this profile.</p>
    <a href="research.php" class="btn btn-brand rounded-pill px-4">Back to Research</a>
  </div>

<?php else: ?>

  <header class="page-header">
    <div class="container d-flex align-items-center gap-4 flex-wrap">
      <?php if (!empty($doctor['image'])): ?>
        <img src="<?= htmlspecialchars($doctor['image']) ?>" alt="Dr. <?= htmlspecialchars($doctor['full_name']) ?>" height="110" width="110" class="profile-photo">
      <?php else: ?>
        <span class="profile-photo-placeholder"><i class="bi bi-person-fill"></i></span>
      <?php endif; ?>
      <div>
        <h1 class="fw-bold mb-1">Dr. <?= htmlspecialchars($doctor['full_name']) ?></h1>
        <p class="mb-2" style="color:#dfeefa;"><?= htmlspecialchars($specialtyTitle) ?></p>
        <span class="badge rounded-pill <?= $doctor['availability'] === 'available' ? 'bg-success' : 'bg-secondary' ?>">
          <?= $doctor['availability'] === 'available' ? 'Available' : 'Busy' ?>
        </span>
      </div>
    </div>
  </header>

  <div class="container py-5">

    <h5 class="section-title fw-bold mb-3"><i class="bi bi-hourglass-split me-1"></i> <?= htmlspecialchars(t('research_ongoing_title')) ?></h5>
    <?php if (!$ongoing): ?>
      <p class="text-muted small mb-4"><?= htmlspecialchars(t('research_no_ongoing')) ?></p>
    <?php else: ?>
      <?php foreach ($ongoing as $proj): ?>
        <div class="research-card">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h6 class="fw-bold mb-1"><?= htmlspecialchars($proj['title']) ?></h6>
            <span class="badge bg-warning text-dark"><?= htmlspecialchars(t('research_ongoing')) ?></span>
          </div>
          <p class="mb-2"><?= nl2br(htmlspecialchars($proj['description'])) ?></p>
          <p class="text-muted small mb-0"><?= htmlspecialchars(t('research_started')) ?>: <?= htmlspecialchars(date('F Y', strtotime($proj['started_date']))) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <h5 class="section-title fw-bold mb-3 mt-4"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars(t('research_completed_title')) ?></h5>
    <?php if (!$completed): ?>
      <p class="text-muted small"><?= htmlspecialchars(t('research_no_completed')) ?></p>
    <?php else: ?>
      <?php foreach ($completed as $proj): ?>
        <div class="research-card">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h6 class="fw-bold mb-1"><?= htmlspecialchars($proj['title']) ?></h6>
            <span class="badge bg-success"><?= htmlspecialchars(t('research_completed')) ?></span>
          </div>
          <p class="mb-2"><?= nl2br(htmlspecialchars($proj['description'])) ?></p>
          <?php if ($proj['conclusion']): ?>
            <p class="mb-2"><strong><?= htmlspecialchars(t('research_conclusion')) ?>:</strong> <?= nl2br(htmlspecialchars($proj['conclusion'])) ?></p>
          <?php endif; ?>
          <p class="text-muted small mb-0"><?= htmlspecialchars(t('research_started')) ?>: <?= htmlspecialchars(date('F Y', strtotime($proj['started_date']))) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>

<?php endif; ?>

</body>
</html>
