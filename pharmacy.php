<?php
/**
 * pharmacy.php — Search medicines, add to cart. Cart lives in the session.
 * Checkout requires the person to be logged in as a patient (portal.php session).
 */
session_start();
require_once __DIR__ . '/db_config.php';
const HOSPITAL_NAME = "Sassoon General Hospital, Pune";

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // [medicine_id => quantity]
}

// ---- Add to cart ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $medId = (int) ($_POST['medicine_id'] ?? 0);
    $qty   = max(1, (int) ($_POST['quantity'] ?? 1));
    if ($medId > 0) {
        $_SESSION['cart'][$medId] = ($_SESSION['cart'][$medId] ?? 0) + $qty;
    }
    $redirectQs = [];
    if (isset($_GET['q'])) { $redirectQs['q'] = $_GET['q']; }
    if (isset($_GET['category'])) { $redirectQs['category'] = $_GET['category']; }
    header('Location: pharmacy.php' . ($redirectQs ? '?' . http_build_query($redirectQs) : ''));
    exit;
}

// ---- Remove from cart ----
if (isset($_GET['remove'])) {
    $medId = (int) $_GET['remove'];
    unset($_SESSION['cart'][$medId]);
    header('Location: pharmacy.php');
    exit;
}

// ---- Search ----
$query    = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$medicines = [];
$categories = [];
try {
    $pdo = getDbConnection();

    // Fetch distinct categories for the filter dropdown.
    $catStmt = $pdo->query('SELECT DISTINCT category FROM medicines WHERE is_active = 1 AND category IS NOT NULL ORDER BY category ASC');
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    $sql = 'SELECT * FROM medicines WHERE is_active = 1';
    $params = [];
    if ($query !== '') {
        $sql .= ' AND (name LIKE :q OR category LIKE :q)';
        $params[':q'] = '%' . $query . '%';
    }
    if ($category !== '') {
        $sql .= ' AND category = :cat';
        $params[':cat'] = $category;
    }
    $sql .= ' ORDER BY category ASC, name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll();
} catch (Throwable $e) {
    // silently ignore for demo purposes
}

// ---- Build cart details for the sidebar ----
$cartItems = [];
$cartTotal = 0.0;
if (!empty($_SESSION['cart'])) {
    try {
        $pdo = getDbConnection();
        $ids = array_keys($_SESSION['cart']);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id IN ($in)");
        $stmt->execute($ids);
        $found = $stmt->fetchAll();
        foreach ($found as $m) {
            $qty = $_SESSION['cart'][$m['id']];
            $subtotal = $qty * (float) $m['price'];
            $cartTotal += $subtotal;
            $cartItems[] = ['medicine' => $m, 'qty' => $qty, 'subtotal' => $subtotal];
        }
    } catch (Throwable $e) {
        // silently ignore
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pharmacy | <?= htmlspecialchars(HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-secondary:#12b8a6; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; }
  body{ font-family:'Poppins', sans-serif; color:#28323c; background:var(--brand-light); }
  .navbar{ background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .navbar-brand{ font-weight:700; color:var(--brand-dark)!important; }
  .navbar-brand i{ color:var(--brand-primary); }
  .page-header{ background:linear-gradient(120deg, var(--brand-dark) 0%, var(--brand-primary) 100%); color:#fff; padding:55px 0 40px; }
  .med-card{ border:none; border-radius:16px; padding:22px; height:100%; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; }
  .med-icon{ width:48px; height:48px; border-radius:12px; background:var(--brand-light); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
  .cart-box{ background:#fff; border-radius:16px; padding:24px; box-shadow:0 4px 18px rgba(13,110,168,.08); position:sticky; top:90px; }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="bi bi-heart-pulse-fill me-1"></i> <?= htmlspecialchars(HOSPITAL_NAME) ?></a>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="bi bi-arrow-left"></i> Back to Home</a>
  </div>
</nav>

<header class="page-header">
  <div class="container">
    <h1 class="fw-bold mb-2"><i class="bi bi-capsule me-2"></i>Pharmacy</h1>
    <p class="mb-4" style="color:#dfeefa;">Search our available medicines and order with home delivery or pickup.</p>
    <form method="GET" class="d-flex gap-2 flex-wrap" style="max-width:600px;">
      <input type="text" name="q" class="form-control" placeholder="Search medicine name..." value="<?= htmlspecialchars($query) ?>" style="flex:1 1 220px;">
      <select name="category" class="form-select" style="flex:0 1 200px;" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-light fw-semibold px-4" type="submit"><i class="bi bi-search"></i></button>
    </form>
  </div>
</header>

<div class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php if (!$medicines): ?>
        <p class="text-muted">No medicines found<?= $query ? ' for "' . htmlspecialchars($query) . '"' : '' ?><?= $category ? ' in "' . htmlspecialchars($category) . '"' : '' ?>.</p>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($medicines as $m): ?>
            <div class="col-md-6">
              <div class="med-card">
                <div class="d-flex align-items-start gap-3 mb-2">
                  <div class="med-icon"><i class="bi bi-capsule"></i></div>
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars($m['name']) ?></div>
                    <?php if ($m['category']): ?><div class="text-muted small"><?= htmlspecialchars($m['category']) ?></div><?php endif; ?>
                  </div>
                </div>
                <?php if ($m['description']): ?><p class="text-muted small mb-2"><?= htmlspecialchars($m['description']) ?></p><?php endif; ?>
                <?php if (!empty($m['dosage_instructions'])): ?>
                  <p class="small mb-2" style="color:var(--brand-primary);"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($m['dosage_instructions']) ?></p>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold">₹<?= number_format((float)$m['price'], 2) ?></span>
                  <span class="small <?= $m['stock_qty'] > 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $m['stock_qty'] > 0 ? $m['stock_qty'] . ' in stock' : 'Out of stock' ?>
                  </span>
                </div>
                <?php if ($m['stock_qty'] > 0): ?>
                <form method="POST" action="pharmacy.php?q=<?= urlencode($query) ?>&category=<?= urlencode($category) ?>" class="d-flex gap-2">
                  <input type="hidden" name="action" value="add_to_cart">
                  <input type="hidden" name="medicine_id" value="<?= (int)$m['id'] ?>">
                  <input type="number" name="quantity" value="1" min="1" max="<?= (int)$m['stock_qty'] ?>" class="form-control form-control-sm" style="width:70px;">
                  <button type="submit" class="btn btn-brand btn-sm rounded-pill flex-grow-1"><i class="bi bi-cart-plus me-1"></i> Add to Cart</button>
                </form>
                <?php else: ?>
                  <button class="btn btn-secondary btn-sm rounded-pill w-100" disabled>Out of Stock</button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="cart-box">
        <h5 class="fw-bold mb-3"><i class="bi bi-cart3 me-1"></i> Your Cart</h5>
        <?php if (!$cartItems): ?>
          <p class="text-muted small">Your cart is empty.</p>
        <?php else: ?>
          <?php foreach ($cartItems as $item): ?>
            <div class="d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom">
              <div>
                <div class="small fw-semibold"><?= htmlspecialchars($item['medicine']['name']) ?></div>
                <div class="text-muted small">Qty: <?= (int)$item['qty'] ?> × ₹<?= number_format((float)$item['medicine']['price'],2) ?></div>
              </div>
              <div class="text-end">
                <div class="small fw-semibold">₹<?= number_format($item['subtotal'],2) ?></div>
                <a href="pharmacy.php?remove=<?= (int)$item['medicine']['id'] ?>" class="text-danger small">Remove</a>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="d-flex justify-content-between fw-bold mt-3 mb-3">
            <span>Total</span><span>₹<?= number_format($cartTotal, 2) ?></span>
          </div>
          <a href="checkout.php" class="btn btn-brand w-100 rounded-pill py-2">
            <i class="bi bi-bag-check-fill me-1"></i> Proceed to Checkout
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>
