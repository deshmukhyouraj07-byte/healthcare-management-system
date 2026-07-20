<?php
/**
 * index.php — Hospital Landing Page
 * Change the hospital name in ONE place below.
 */
session_start();
require_once __DIR__ . '/translations.php';

const HOSPITAL_NAME = "Sassoon General Hospital, Pune"; // English fallback for pages without translations.php

// First-time visitors (no language chosen yet) go to the language picker first.
if (!isset($_SESSION['lang'])) {
    header('Location: language_select.php');
    exit;
}

$HOSPITAL_NAME = t('hospital_name'); // shows in English or Marathi depending on the visitor's choice
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($HOSPITAL_NAME) ?> | Healthcare Management System</title>

<!-- Bootstrap 5 CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<!-- Google Font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">

<style>
  :root{
    /* ---- Customize your palette here ---- */
    --brand-primary:#0d6ea8;
    --brand-secondary:#12b8a6;
    --brand-dark:#0b2e4a;
    --brand-light:#f4f9fc;
    --brand-font: 'Poppins', 'Noto Sans Devanagari', sans-serif;
  }
  body{ font-family:var(--brand-font); color:#28323c; background:#fff; }
  .navbar{ background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .navbar-brand{ font-weight:700; color:var(--brand-dark)!important; letter-spacing:.3px; }
  .navbar-brand i{ color:var(--brand-primary); }
  .nav-link{ font-weight:500; color:#3a4a58!important; }
  .nav-link:hover{ color:var(--brand-primary)!important; }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
  .lang-toggle{ border:1px solid #dfe7ee; background:#fff; color:var(--brand-dark); font-weight:600; }
  .lang-toggle:hover{ background:var(--brand-light); }

  .hero{
    background:linear-gradient(120deg, var(--brand-dark) 0%, var(--brand-primary) 100%);
    color:#fff; padding:110px 0 90px; position:relative; overflow:hidden;
  }
  .hero::after{
    content:""; position:absolute; right:-120px; top:-120px; width:420px; height:420px;
    background:rgba(255,255,255,.08); border-radius:50%;
  }
  .hero h1{ font-weight:700; font-size:2.9rem; }
  .hero p.lead{ color:#dfeefa; max-width:560px; }
  .hero-badge{ background:rgba(255,255,255,.15); padding:6px 16px; border-radius:30px; font-size:.85rem; display:inline-block; margin-bottom:18px; }
  .hero-art{ background:rgba(255,255,255,.08); border-radius:24px; padding:40px; backdrop-filter:blur(4px); }

  section{ padding:80px 0; }
  .section-title{ font-weight:700; color:var(--brand-dark); }
  .section-eyebrow{ color:var(--brand-secondary); font-weight:600; letter-spacing:1px; text-transform:uppercase; font-size:.8rem; }

  .service-card{ border:none; border-radius:16px; padding:34px 26px; height:100%; transition:.25s; box-shadow:0 4px 18px rgba(13,110,168,.08); }
  .service-card:hover{ transform:translateY(-6px); box-shadow:0 12px 28px rgba(13,110,168,.16); }
  .service-icon{ width:64px; height:64px; border-radius:16px; display:flex; align-items:center; justify-content:center; background:var(--brand-light); color:var(--brand-primary); font-size:1.8rem; margin-bottom:18px; }

  .about-img-placeholder{ background:var(--brand-light); border-radius:20px; min-height:340px; display:flex; align-items:center; justify-content:center; color:var(--brand-primary); font-size:3rem; }

  footer{ background:var(--brand-dark); color:#cfe3f0; padding:40px 0; }
  footer a{ color:#cfe3f0; }

  /* Login modal */
  .login-option-card{
    border:2px solid #e7eef3; border-radius:14px; padding:26px 18px; text-align:center;
    cursor:pointer; transition:.2s; text-decoration:none; color:#28323c; display:block;
  }
  .login-option-card:hover{ border-color:var(--brand-primary); background:var(--brand-light); color:var(--brand-dark); }
  .login-option-card i{ font-size:2rem; color:var(--brand-primary); margin-bottom:10px; display:block; }
</style>
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand" href="#home">
      <img src="bjmc_logo.jpg" alt="main logo"width="25px"height="30px"><?= htmlspecialchars($HOSPITAL_NAME) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
        <li class="nav-item"><a class="nav-link" href="#about"><?= htmlspecialchars(t('nav_about')) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#services"><?= htmlspecialchars(t('nav_services')) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="research.php"><?= htmlspecialchars(t('nav_research')) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="pharmacy.php"><?= htmlspecialchars(t('nav_pharmacy')) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#contact"><?= htmlspecialchars(t('nav_contact')) ?></a></li>
        <li class="nav-item mt-2 mt-lg-0">
          <a href="switch_language.php?lang=<?= currentLang() === 'en' ? 'mr' : 'en' ?>" class="btn lang-toggle rounded-pill px-3">
            <i class="bi bi-translate me-1"></i> <?= htmlspecialchars(t('language_switch')) ?>
          </a>
        </li>
        <li class="nav-item mt-2 mt-lg-0">
          <button type="button" class="btn btn-brand px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-box-arrow-in-right me-1"></i> <?= htmlspecialchars(t('nav_login')) ?>
          </button>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ===================== HERO ===================== -->
<header class="hero" id="home">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="hero-badge"><i class="bi bi-shield-check"></i> <?= htmlspecialchars(t('hero_badge')) ?></span>
        <h1><?= htmlspecialchars($HOSPITAL_NAME) ?></h1>
        <p class="lead"><?= htmlspecialchars(t('hero_lead')) ?></p>
        <div class="d-flex gap-3 mt-4">
          <a href="#contact" class="btn btn-light btn-lg rounded-pill px-4 fw-semibold"><?= htmlspecialchars(t('hero_book')) ?></a>
          <a href="#services" class="btn btn-outline-light btn-lg rounded-pill px-4"><?= htmlspecialchars(t('hero_services')) ?></a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-art text-center">
         <img src="sassooon.jpg" alt="building"width="550px"height="300px">
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ===================== ABOUT US ===================== -->
<section id="about">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="about-img-placeholder">
          <img src="sassoonhospital-pune.png" alt="building"width="550px"height="300px">
        </div>
      </div>
      <div class="col-lg-6">
        <div class="section-eyebrow"><?= htmlspecialchars(t('about_eyebrow')) ?></div>
        <h2 class="section-title mb-3"><?= htmlspecialchars(t('about_title')) ?></h2>
        <p class="text-muted"><?= htmlspecialchars(t('about_body')) ?></p>
        <ul class="list-unstyled text-muted">
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><?= htmlspecialchars(t('about_point1')) ?></li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><?= htmlspecialchars(t('about_point2')) ?></li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><?= htmlspecialchars(t('about_point3')) ?></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ===================== SERVICES ===================== -->
<section id="services" class="bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-eyebrow"><?= htmlspecialchars(t('services_eyebrow')) ?></div>
      <h2 class="section-title"><?= htmlspecialchars(t('services_title')) ?></h2>
      <p class="text-muted"><?= htmlspecialchars(t('services_intro')) ?></p>
    </div>
    <div class="row g-4">
      <?php
        $lang = currentLang();
        foreach ($specialties as $sp):
          $title = $lang === 'mr' ? $sp['mr_title'] : $sp['en_title'];
          $desc  = $lang === 'mr' ? $sp['mr_desc']  : $sp['en_desc'];
      ?>
        <div class="col-md-6 col-lg-4">
          <div class="service-card bg-white">
            <div class="service-icon"><i class="bi <?= htmlspecialchars($sp['icon']) ?>"></i></div>
            <h5 class="fw-bold"><?= htmlspecialchars($title) ?></h5>
            <p class="text-muted mb-0"><?= htmlspecialchars($desc) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== CONTACT US ===================== -->
<section id="contact">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-5">
        <div class="section-eyebrow"><?= htmlspecialchars(t('contact_eyebrow')) ?></div>
        <h2 class="section-title mb-3"><?= htmlspecialchars(t('contact_title')) ?></h2>
        <p class="text-muted mb-4"><?= htmlspecialchars(t('contact_intro')) ?></p>
        <ul class="list-unstyled text-muted">
          <li class="mb-3"><i class="bi bi-geo-alt-fill text-primary me-2"></i> Address : Jai Prakash Narayan Road, Railway Station Road Pune, Maharashtra 411001.</li>
          <li class="mb-3"><i class="bi bi-telephone-fill text-primary me-2"></i> +91 1800 4856 93</li>
          <li class="mb-3"><i class="bi bi-envelope-fill text-primary me-2"></i> deanbjmcpune@gmail.com</li>
        </ul>
      </div>
      <div class="col-lg-7">
        <form class="row g-3">
          <div class="col-md-6">
            <label class="form-label"><?= htmlspecialchars(t('contact_name')) ?></label>
            <input type="text" class="form-control" placeholder="Jane Doe">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= htmlspecialchars(t('contact_email')) ?></label>
            <input type="email" class="form-control" placeholder="jane@example.com">
          </div>
          <div class="col-12">
            <label class="form-label"><?= htmlspecialchars(t('contact_message')) ?></label>
            <textarea class="form-control" rows="4" placeholder="<?= htmlspecialchars(t('contact_message_ph')) ?>"></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-brand rounded-pill px-4"><?= htmlspecialchars(t('contact_send')) ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ===================== FOOTER ===================== -->
<footer>
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <div>&copy; <?= date('Y') ?> <?= htmlspecialchars($HOSPITAL_NAME) ?>. <?= htmlspecialchars(t('footer_rights')) ?></div>
    <div>
      <a href="#" class="me-3"><i class="bi bi-facebook"></i></a>
      <a href="#" class="me-3"><i class="bi bi-twitter-x"></i></a>
      <a href="#"><i class="bi bi-instagram"></i></a>
    </div>
  </div>
</footer>

<!-- ===================== LOGIN MODAL ===================== -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:18px; border:none;">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><?= htmlspecialchars(t('login_modal_title')) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2 pb-4">
        <p class="text-muted mb-4"><?= htmlspecialchars(t('login_modal_intro')) ?></p>
        <div class="row g-3">
          <div class="col-md-4">
            <a href="portal.php?role=employee" class="login-option-card">
              <i class="bi bi-person-badge"></i>
              <div class="fw-semibold"><?= htmlspecialchars(t('login_doctor')) ?></div>
              <div class="small text-muted"><?= htmlspecialchars(t('login_doctor_desc')) ?></div>
            </a>
          </div>
          <div class="col-md-4">
            <a href="portal.php?role=staff" class="login-option-card">
              <i class="bi bi-heart-pulse"></i>
              <div class="fw-semibold"><?= htmlspecialchars(t('login_staff')) ?></div>
              <div class="small text-muted"><?= htmlspecialchars(t('login_staff_desc')) ?></div>
            </a>
          </div>
          <div class="col-md-4">
            <a href="portal.php?role=patient" class="login-option-card">
              <i class="bi bi-person-heart"></i>
              <div class="fw-semibold"><?= htmlspecialchars(t('login_patient')) ?></div>
              <div class="small text-muted"><?= htmlspecialchars(t('login_patient_desc')) ?></div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS Bundle CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
