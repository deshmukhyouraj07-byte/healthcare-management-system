<?php
/**
 * payment.php — Shows the generated bill plus a QR code to pay.
 * Replace /assets/payment_qr.png with your own UPI/payment QR image.
 */
session_start();
require_once __DIR__ . '/db_config.php';
const HOSPITAL_NAME = "Sassoon General Hospital, Pune";

if (!isset($_SESSION['patient_pk'])) {
    header('Location: portal.php?role=patient');
    exit;
}

$orderId = (int) ($_GET['order_id'] ?? 0);
$pdo = getDbConnection();

// Mark as paid (self-reported confirmation after scanning the QR — see note in README).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    $stmt = $pdo->prepare('UPDATE medicine_orders SET payment_status = "paid" WHERE id = :id AND patient_id = (SELECT patient_id FROM patients WHERE id = :pk)');
    $stmt->execute([':id' => $orderId, ':pk' => $_SESSION['patient_pk']]);
    header('Location: payment.php?order_id=' . $orderId . '&confirmed=1');
    exit;
}

$stmt = $pdo->prepare('SELECT mo.* FROM medicine_orders mo
                        JOIN patients p ON p.patient_id = mo.patient_id
                        WHERE mo.id = :id AND p.id = :pk LIMIT 1');
$stmt->execute([':id' => $orderId, ':pk' => $_SESSION['patient_pk']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: pharmacy.php');
    exit;
}

$itemsStmt = $pdo->prepare('SELECT * FROM medicine_order_items WHERE order_id = :id');
$itemsStmt->execute([':id' => $orderId]);
$items = $itemsStmt->fetchAll();

$qrPath = __DIR__ . '/assets/payment_qr.png';
$qrExists = file_exists($qrPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment | <?= htmlspecialchars(HOSPITAL_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-secondary:#12b8a6; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; }
  body{ font-family:'Poppins', sans-serif; background:var(--brand-light); color:#28323c; }
  .navbar{ background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .card-box{ border:none; border-radius:16px; padding:30px; box-shadow:0 4px 18px rgba(13,110,168,.08); background:#fff; }
  .qr-box{ border:2px dashed #c9dbe8; border-radius:14px; padding:24px; text-align:center; }
  .btn-brand{ background:var(--brand-primary); border:none; color:#fff; font-weight:600; }
  .btn-brand:hover{ background:var(--brand-dark); color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php" style="color:var(--brand-dark);"><i class="bi bi-heart-pulse-fill text-primary me-1"></i> <?= htmlspecialchars(HOSPITAL_NAME) ?></a>
    <a href="portal.php?role=patient" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
  </div>
</nav>

<div class="container py-5">
  <?php if (isset($_GET['confirmed'])): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-1"></i> Thank you — your payment has been marked as received. Our pharmacy team will process your order.</div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card-box">
        <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-1"></i> Order #<?= (int)$order['id'] ?> — Bill</h5>
        <table class="table table-sm">
          <thead><tr><th>Item</th><th>Qty</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['medicine_name']) ?></td>
                <td><?= (int)$it['quantity'] ?></td>
                <td class="text-end">₹<?= number_format((float)$it['subtotal'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="fw-bold"><td colspan="2" class="text-end">Total</td><td class="text-end">₹<?= number_format((float)$order['total_amount'], 2) ?></td></tr>
          </tfoot>
        </table>
        <div class="small text-muted">
          Delivery: <?= $order['delivery_type'] === 'home_delivery' ? 'Home Delivery' : 'Pickup from Hospital Pharmacy' ?>
          <?php if ($order['delivery_address']): ?><br>Address: <?= htmlspecialchars($order['delivery_address']) ?><?php endif; ?>
        </div>
        <div class="mt-3">
          <span class="badge <?= $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?> text-capitalize">
            <?= htmlspecialchars($order['payment_status']) ?>
          </span>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card-box">
        <h5 class="fw-bold mb-3"><i class="bi bi-qr-code me-1"></i> Pay via QR Code</h5>
        <div class="qr-box mb-3">
          <?php if ($qrExists): ?>
            <img src="assets/payment_qr.png" alt="Payment QR Code" style="max-width:220px; width:100%;">
          <?php else: ?>
            <i class="bi bi-qr-code" style="font-size:4rem; color:#c9dbe8;"></i>
            <p class="text-muted small mt-2 mb-0">No QR code uploaded yet.<br>Place your image at <code>assets/payment_qr.png</code>.</p>
          <?php endif; ?>
        </div>
        <p class="text-muted small">Scan this code with any UPI / payment app and pay
          <strong>₹<?= number_format((float)$order['total_amount'], 2) ?></strong>. Once done, click the button below to confirm.</p>

        <?php if ($order['payment_status'] !== 'paid'): ?>
          <form method="POST">
            <input type="hidden" name="action" value="confirm_payment">
            <button type="submit" class="btn btn-brand w-100 py-2 rounded-pill">
              <i class="bi bi-check2-circle me-1"></i> I've Completed Payment
            </button>
          </form>
        <?php else: ?>
          <div class="alert alert-success mb-0 text-center">Payment confirmed. Thank you!</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>
