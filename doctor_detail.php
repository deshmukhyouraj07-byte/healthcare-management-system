<?php
/**
 * doctor_detail.php — Individual doctor profile page. Linked from
 * doctors.php and from the doctor badges on research.php's specialty
 * pages. Shows the doctor's photo (if one has been placed in
 * /assets/doctors/), qualifications/bio, and only THEIR OWN research
 * projects (linked via research_projects.doctor_id).
 *
 * PHOTO CONVENTION: place an image at
 *   /assets/doctors/<username>.jpg   (or .png / .webp)
 * e.g. for username "dr_yuvraj" -> /assets/doctors/dr_yuvraj.jpg
 * No code change needed — just drop the file in with the right name.
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

$username = trim($_GET['username'] ?? '');

$stmt = $pdo->prepare("SELECT id, username, full_name, specialty, availability, qualifications, bio, created_at
                        FROM users WHERE username = :u AND role = 'employee' AND is_active = 1 LIMIT 1");
$stmt->execute([':u' => $username]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('Location: doctors.php');
    exit;
}

// Photo lookup — try jpg/png/webp in that order, first match wins.
$photoUrl = null;
foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
    $candidate = __DIR__ . '/assets/doctors/' . $doctor['username'] . '.' . $ext;
    if (file_exists($candidate)) {
        $photoUrl = 'assets/doctors/' . $doctor['username'] . '.' . $ext;
        break;
    }
}

// This doctor's own research projects (only ones explicitly linked to them).
$ongoing = [];
$completed = [];
try {
    $stmt2 = $pdo->prepare("SELECT * FROM research_projects WHERE doctor_id = :did AND status = 'ongoing' ORDER BY started_date DESC");
    $stmt2->execute([':did' => $doctor['id']]);
    $ongoing = $stmt2->fetchAll();

    $stmt3 = $pdo->prepare("SELECT * FROM research_projects WHERE doctor_id = :did AND status = 'completed' ORDER BY completed_date DESC");
    $stmt3->execute([':did' => $doctor['id']]);
    $completed = $stmt3->fetchAll();
} catch (Throwable $e) {
    // silently ignore for demo purposes
}

$specialtyTitle = $doctor['specialty'];
foreach ($specialties as $sp) {
    if ($sp['en_title'] === $doctor['specialty']) {
        $specialtyTitle = $lang === 'mr' ? $sp['mr_title'] : $sp['en_title'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dr. <?= htmlspecialchars($doctor['full_name']) ?> | <?= htmlspecialchars($HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-secondary:#12b8a6; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; --brand-font:'Poppins','Noto Sans Devanagari', sans-serif; }
  body{ font-family:var(--brand-font); color:#28323c; background:var(--brand-light); }
  .navbar{ background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .navbar-brand{ font-weight:700; color:var(--brand-dark)!important; }
  .navbar-brand i{ color:var(--brand-primary); }
  .profile-header{ background:linear-gradient(120deg, var(--brand-dark) 0%, var(--brand-primary) 100%); color:#fff; padding:50px 0 90px; }
  .profile-card{ background:#fff; border-radius:20px; box-shadow:0 10px 30px rgba(11,46,74,.12); padding:30px; margin-top:-70px; }
  .doctor-photo{ width:140px; height:140px; border-radius:50%; object-fit:cover; border:5px solid #fff; box-shadow:0 6px 16px rgba(0,0,0,.15); background:var(--brand-light); }
  .doctor-photo-fallback{ width:140px; height:140px; border-radius:50%; border:5px solid #fff; box-shadow:0 6px 16px rgba(0,0,0,.15); background:var(--brand-light); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:3.2rem; }
  .research-card{ border:none; border-radius:14px; padding:22px; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; margin-bottom:18px; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="bi bi-heart-pulse-fill me-1"></i> <?= htmlspecialchars($HOSPITAL_NAME) ?></a>
    <a href="doctors.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('doctors_title')) ?></a>
  </div>
</nav>

<header class="profile-header">
  <div class="container">
    <span class="badge rounded-pill px-3 py-2 <?= $doctor['availability'] === 'available' ? 'bg-success' : 'bg-secondary' ?>">
      <?= $doctor['availability'] === 'available' ? htmlspecialchars(t('portal_available')) : htmlspecialchars(t('portal_not_available')) ?>
    </span>
  </div>
</header>

<div class="container pb-5">
  <div class="profile-card">
    <div class="d-flex align-items-end gap-4 flex-wrap mb-3">
      <?php if ($photoUrl): ?>
        <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Dr. <?= htmlspecialchars($doctor['full_name']) ?>" class="doctor-photo">
      <?php else: ?><img src="pediatric.jpg" alt="Girl in a jacket" style="width: 100px; height: 90px; border-radius: 50%; object-fit: cover;">
</div>
      <?php endif; ?>
      <div>
        <h2 class="fw-bold mb-1">Dr. <?= htmlspecialchars($doctor['full_name']) ?></h2>
        <div class="text-muted">
          <a href="research.php?specialty=<?= urlencode($doctor['specialty'] ?? '') ?>" class="text-decoration-none">
            <?= htmlspecialchars($specialtyTitle ?: t('doctors_specialty_unset')) ?>
          </a>
        </div>
        <?php if ($doctor['qualifications']): ?>
          <div class="text-muted small mt-1">"><img src="pediatric.jpg" alt="Girl in a jacket" width="500" height="600"></i><?= htmlspecialchars($doctor['qualifications']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($doctor['bio']): ?>
      <p class="mt-3"><?= nl2br(htmlspecialchars($doctor['bio'])) ?></p>
    <?php else: ?>
      <p class="text-muted small mt-3 fst-italic"><?= htmlspecialchars(t('doctor_no_bio_yet')) ?></p>
    <?php endif; ?>
  </div>

  <div class="mt-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-hourglass-split me-1"></i> <?= htmlspecialchars(t('research_ongoing_title')) ?></h5>
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

    <h5 class="fw-bold mb-3 mt-4"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars(t('research_completed_title')) ?></h5>
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
</div>

</body>
</html>
