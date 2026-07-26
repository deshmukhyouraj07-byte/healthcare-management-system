<?php
/**
 * view_payment_proof.php — Serves an appointment's payment screenshot,
 * but ONLY to a logged-in staff/doctor session. This exists so screenshots
 * aren't sitting at a guessable public URL under /uploads/ — nothing else
 * on the site should link directly into that folder.
 */
session_start();
require_once __DIR__ . '/session_helpers.php';
enforceStaffSessionTimeout(120);
require_once __DIR__ . '/db_config.php';

if (!isset($_SESSION['staff_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$appointmentId = (int) ($_GET['id'] ?? 0);

$pdo  = getDbConnection();
$stmt = $pdo->prepare('SELECT payment_screenshot FROM appointments WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $appointmentId]);
$row = $stmt->fetch();

if (!$row || !$row['payment_screenshot']) {
    http_response_code(404);
    exit('Not found');
}

// Keep the resolved path strictly inside /uploads/appointment_payments/.
$base = realpath(__DIR__ . '/uploads/appointment_payments');
$path = realpath(__DIR__ . '/' . $row['payment_screenshot']);

if (!$base || !$path || strpos($path, $base) !== 0 || !file_exists($path)) {
    http_response_code(404);
    exit('Not found');
}

$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Cache-Control: private, no-store');
readfile($path);
