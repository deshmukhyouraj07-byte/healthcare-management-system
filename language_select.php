<?php
/**
 * language_select.php
 * Shown once when a visitor first arrives with no language set in session.
 */
session_start();
const HOSPITAL_NAME = "Sassoon General Hospital, Pune";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = $_POST['lang'] ?? 'en';
    $_SESSION['lang'] = in_array($lang, ['en', 'mr'], true) ? $lang : 'en';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-dark:#0b2e4a; }
  body{
    font-family:'Poppins', sans-serif;
    background:linear-gradient(120deg, var(--brand-dark) 0%, var(--brand-primary) 100%);
    min-height:100vh; display:flex; align-items:center; justify-content:center; color:#fff;
  }
  .lang-card{ background:#fff; color:#28323c; border-radius:20px; padding:50px 40px; max-width:460px; width:90%; text-align:center; box-shadow:0 20px 50px rgba(0,0,0,.25); }
  .lang-btn{ display:block; width:100%; border:2px solid #e7eef3; border-radius:14px; padding:20px; margin-bottom:16px; background:#fff; cursor:pointer; font-size:1.15rem; font-weight:600; transition:.2s; }
  .lang-btn:hover{ border-color:var(--brand-primary); background:#f4f9fc; }
  .lang-sub{ font-size:.85rem; color:#7a8a99; font-weight:400; }
</style>
</head>
<body>

<div class="lang-card">
  <i class="bi bi-heart-pulse-fill" style="font-size:2.5rem; color:var(--brand-primary);"></i>
  <h4 class="fw-bold mt-2 mb-1"><?= htmlspecialchars(HOSPITAL_NAME) ?></h4>
  <p class="text-muted mb-4">Please select your language / कृपया तुमची भाषा निवडा</p>

  <form method="POST">
    <button type="submit" name="lang" value="en" class="lang-btn">
      English <span class="lang-sub d-block">Continue in English</span>
    </button>
    <button type="submit" name="lang" value="mr" class="lang-btn">
      मराठी <span class="lang-sub d-block">मराठीमध्ये सुरू ठेवा</span>
    </button>
  </form>
</div>

</body>
</html>
