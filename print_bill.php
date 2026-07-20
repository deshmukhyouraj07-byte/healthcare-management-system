<?php
/**
 * print_bill.php — Printable invoice for the currently logged-in patient.
 * Access is restricted to the patient's own session; no patient_id is
 * ever accepted from the URL, so one patient can never print another's bill.
 */
session_start();
require_once __DIR__ . '/db_config.php';

const HOSPITAL_NAME = "Sassoon General Hospital, Pune";

if (!isset($_SESSION['patient_pk'])) {
    header('Location: portal.php?role=patient');
    exit;
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['patient_pk']]);
$patient = $stmt->fetch();

if (!$patient) {
    header('Location: portal.php?role=patient');
    exit;
}

$stmt2 = $pdo->prepare('SELECT * FROM bills WHERE patient_id = :pid ORDER BY created_at ASC');
$stmt2->execute([':pid' => $patient['patient_id']]);
$bills = $stmt2->fetchAll();

$total = 0.0;
foreach ($bills as $b) {
    $total += (float) $b['amount'];
}

$invoiceNo = 'INV-' . $patient['patient_id'] . '-' . date('Ymd');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice <?= htmlspecialchars($invoiceNo) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --brand-primary:#0d6ea8; --brand-dark:#0b2e4a; --brand-light:#f4f9fc; }
  body{ font-family:'Poppins', sans-serif; background:var(--brand-light); color:#28323c; }
  .invoice-box{ background:#fff; border-radius:14px; box-shadow:0 10px 30px rgba(11,46,74,.08); padding:50px; max-width:800px; margin:40px auto; }
  .invoice-header{ border-bottom:3px solid var(--brand-primary); padding-bottom:20px; margin-bottom:30px; }
  .hospital-name{ font-weight:700; color:var(--brand-dark); font-size:1.5rem; }
  table thead{ background:var(--brand-light); }
  .no-print{ }
  @media print {
    body{ background:#fff; }
    .no-print{ display:none !important; }
    .invoice-box{ box-shadow:none; margin:0; max-width:100%; }
  }
</style>
</head>
<body>

<div class="container no-print py-3 d-flex justify-content-between">
  <a href="portal.php?role=patient" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Back to Dashboard
  </a>
  <button onclick="window.print()" class="btn btn-primary btn-sm">
    <i class="bi bi-printer-fill me-1"></i> Print / Save as PDF
  </button>
</div>

<div class="invoice-box">
  <div class="invoice-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <div class="hospital-name"><i class="bi bi-heart-pulse-fill text-primary me-1"></i> <?= htmlspecialchars(HOSPITAL_NAME) ?></div>
      <div class="text-muted small"> Jai Prakash Narayan Road, Railway Station Road Pune, Maharashtra 411001.</div>
      <div class="text-muted small">sassoonhospital@gmail.com</div>
    </div>
    <div class="text-md-end">
      <div class="fw-bold">INVOICE</div>
      <div class="text-muted small">No: <?= htmlspecialchars($invoiceNo) ?></div>
      <div class="text-muted small">Date: <?= date('F j, Y') ?></div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-6">
      <div class="small text-muted text-uppercase fw-semibold mb-1">Billed To</div>
      <div class="fw-semibold"><?= htmlspecialchars($patient['full_name']) ?></div>
      <div class="text-muted small">Patient ID: <?= htmlspecialchars($patient['patient_id']) ?></div>
      <div class="text-muted small"><?= htmlspecialchars($patient['contact_info']) ?></div>
    </div>
  </div>

  <table class="table">
    <thead>
      <tr><th>#</th><th>Description</th><th>Status</th><th class="text-end">Amount</th></tr>
    </thead>
    <tbody>
      <?php if (!$bills): ?>
        <tr><td colspan="4" class="text-muted text-center py-4">No billable items on record.</td></tr>
      <?php else: ?>
        <?php foreach ($bills as $i => $b): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($b['description']) ?></td>
            <td class="text-capitalize"><?= htmlspecialchars($b['status']) ?></td>
            <td class="text-end">₹<?= number_format((float)$b['amount'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr class="fw-bold">
        <td colspan="3" class="text-end">Total Due</td>
        <td class="text-end">₹<?= number_format($total, 2) ?></td>
      </tr>
    </tfoot>
  </table>

  <p class="text-muted small mt-4 mb-0">This invoice reflects charges assigned by clinical staff and is
    generated for the patient's own records. Please contact billing for any discrepancies.</p>
</div>

</body>
</html>
