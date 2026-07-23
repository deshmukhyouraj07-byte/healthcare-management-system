<?php
/**
 * checkout.php — Confirms delivery details and creates the order (bill).
 * Requires the person to be logged in as a patient (shares session with portal.php).
 */
session_start();
require_once __DIR__ . '/db_config.php';
const HOSPITAL_NAME = "Sunrise General Hospital";

// Must be logged in as a patient to order.
if (!isset($_SESSION['patient_pk'])) {
    header('Location: portal.php?role=patient');
    exit;
}

if (empty($_SESSION['cart'])) {
    header('Location: pharmacy.php');
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['patient_pk']]);
$patient = $stmt->fetch();

// Build cart details.
$cartItems = [];
$cartTotal = 0.0;
$ids = array_keys($_SESSION['cart']);
$in  = implode(',', array_fill(0, count($ids), '?'));
$stmt2 = $pdo->prepare("SELECT * FROM medicines WHERE id IN ($in)");
$stmt2->execute($ids);
foreach ($stmt2->fetchAll() as $m) {
    $qty = $_SESSION['cart'][$m['id']];
    $subtotal = $qty * (float) $m['price'];
    $cartTotal += $subtotal;
    $cartItems[] = ['medicine' => $m, 'qty' => $qty, 'subtotal' => $subtotal];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryType = $_POST['delivery_type'] ?? 'pickup';
    $address      = trim($_POST['delivery_address'] ?? '');

    if (!in_array($deliveryType, ['home_delivery', 'pickup'], true)) {
        $deliveryType = 'pickup';
    }
    if ($deliveryType === 'home_delivery' && $address === '') {
        $errors[] = 'Please enter a delivery address for home delivery.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $orderStmt = $pdo->prepare('INSERT INTO medicine_orders (patient_id, delivery_type, delivery_address, total_amount, payment_status)
                                         VALUES (:pid, :dtype, :addr, :total, "pending")');
            $orderStmt->execute([
                ':pid'   => $patient['patient_id'],
                ':dtype' => $deliveryType,
                ':addr'  => $deliveryType === 'home_delivery' ? $address : null,
                ':total' => $cartTotal,
            ]);
            $orderId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO medicine_order_items (order_id, medicine_id, medicine_name, quantity, unit_price, subtotal)
                                        VALUES (:oid, :mid, :mname, :qty, :price, :sub)');
            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    ':oid'   => $orderId,
                    ':mid'   => $item['medicine']['id'],
                    ':mname' => $item['medicine']['name'],
                    ':qty'   => $item['qty'],
                    ':price' => $item['medicine']['price'],
                    ':sub'   => $item['subtotal'],
                ]);
                // Reduce stock.
                $pdo->prepare('UPDATE medicines SET stock_qty = GREATEST(0, stock_qty - :q) WHERE id = :id')
                    ->execute([':q' => $item['qty'], ':id' => $item['medicine']['id']]);
            }

            $pdo->commit();
            unset($_SESSION['cart']); // clear cart

            header('Location: payment.php?order_id=' . $orderId);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Could not place your order. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | <?= htmlspecialchars(HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; }
  body{ font-family:'Poppins', sans-serif; background:var(--brand-light); color:#28323c; }
  .navbar{ background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .card-box{ border:none; border-radius:16px; padding:30px; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php" style="color:var(--brand-dark);"><i class="bi bi-heart-pulse-fill text-primary me-1"></i> <?= htmlspecialchars(HOSPITAL_NAME) ?></a>
    <a href="pharmacy.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="bi bi-arrow-left"></i> Back to Pharmacy</a>
  </div>
</nav>

<div class="container py-5">
  <h3 class="fw-bold mb-4">Checkout</h3>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card-box">
        <h5 class="fw-bold mb-3">Delivery Details</h5>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label d-block">Delivery Option</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="delivery_type" value="pickup" id="pickup" checked>
              <label class="form-check-label" for="pickup"><i class="bi bi-shop me-1"></i> Pickup from Hospital Pharmacy</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="delivery_type" value="home_delivery" id="home_delivery">
              <label class="form-check-label" for="home_delivery"><i class="bi bi-truck me-1"></i> Home Delivery</label>
            </div>
          </div>
          <div class="mb-3" id="addressField" style="display:none;">
            <label class="form-label">Delivery Address</label>
            <textarea name="delivery_address" class="form-control" rows="3" placeholder="Full address for delivery"></textarea>
          </div>
          <button type="submit" class="btn btn-brand w-100 py-2 rounded-pill">
            <i class="bi bi-receipt me-1"></i> Place Order &amp; Generate Bill
          </button>
        </form>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card-box">
        <h5 class="fw-bold mb-3">Order Summary</h5>
        <?php foreach ($cartItems as $item): ?>
          <div class="d-flex justify-content-between small mb-2">
            <span><?= htmlspecialchars($item['medicine']['name']) ?> × <?= (int)$item['qty'] ?></span>
            <span>₹<?= number_format($item['subtotal'], 2) ?></span>
          </div>
        <?php endforeach; ?>
        <hr>
        <div class="d-flex justify-content-between fw-bold">
          <span>Total</span><span>₹<?= number_format($cartTotal, 2) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const radios = document.querySelectorAll('input[name="delivery_type"]');
  const addressField = document.getElementById('addressField');
  radios.forEach(r => r.addEventListener('change', () => {
    addressField.style.display = document.getElementById('home_delivery').checked ? 'block' : 'none';
  }));
</script>
</body>
</html>
