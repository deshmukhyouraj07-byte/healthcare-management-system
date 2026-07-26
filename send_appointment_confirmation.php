<?php
/**
 * send_appointment_confirmation.php
 *
 * Sends a confirmation email once staff verify an appointment's payment
 * (see the "verify_appointment" action in portal.php). The email includes
 * hospital details, the attending doctor, the patient/booker's own details,
 * appointment date/time, and the paid amount (acting as a receipt).
 *
 * Deliberately plain HTML (no PDF) to avoid requiring the FPDF library —
 * only PHPMailer is needed, which send_prescription.php already uses.
 *
 * REQUIRES (same as send_prescription.php):
 *   /libs/PHPMailer/src/PHPMailer.php
 *   /libs/PHPMailer/src/SMTP.php
 *   /libs/PHPMailer/src/Exception.php
 *   smtp_config.php with real SMTP credentials filled in
 */

require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/smtp_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * @param array  $appt          The full appointments row (with doctor_name, doctor_specialty joined in).
 * @param string $recipientName Patient's or guest's full name.
 * @param string $recipientEmail Where to send the confirmation.
 * @return bool true on success; throws on failure (let the caller decide how to handle it).
 */
function sendAppointmentConfirmationEmail(array $appt, string $recipientName, string $recipientEmail): bool
{
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid recipient email address: "' . $recipientEmail . '"');
    }

    $apptDate = date('F j, Y', strtotime($appt['appointment_date']));
    $apptTime = date('g:i A', strtotime($appt['appointment_time']));
    $fee      = number_format((float) $appt['fee'], 2);
    $hospitalName = SMTP_FROM_NAME;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($recipientEmail, $recipientName);

    $mail->isHTML(true);
    $mail->Subject = 'Appointment Confirmed — ' . $hospitalName . ' (Ref #' . $appt['id'] . ')';
    $mail->Body    = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color:#28323c;">
            <div style="border-bottom: 3px solid #0d6ea8; padding-bottom: 16px; margin-bottom: 20px;">
                <h2 style="color:#0b2e4a; margin:0;">' . htmlspecialchars($hospitalName) . '</h2>
                <p style="color:#6b7a89; margin:4px 0 0;">Appointment Confirmation &amp; Payment Receipt</p>
            </div>

            <p>Dear ' . htmlspecialchars($recipientName) . ',</p>
            <p>Your appointment has been <strong>confirmed</strong>. Details below:</p>

            <table style="width:100%; border-collapse: collapse; margin: 16px 0;">
                <tr><td style="padding:8px 0; color:#6b7a89; width:40%;">Reference No.</td><td style="padding:8px 0; font-weight:bold;">#' . (int)$appt['id'] . '</td></tr>
                <tr style="border-top:1px solid #eef2f5;"><td style="padding:8px 0; color:#6b7a89;">Doctor</td><td style="padding:8px 0; font-weight:bold;">Dr. ' . htmlspecialchars($appt['doctor_name']) . ($appt['doctor_specialty'] ? ' (' . htmlspecialchars($appt['doctor_specialty']) . ')' : '') . '</td></tr>
                <tr style="border-top:1px solid #eef2f5;"><td style="padding:8px 0; color:#6b7a89;">Date</td><td style="padding:8px 0; font-weight:bold;">' . htmlspecialchars($apptDate) . '</td></tr>
                <tr style="border-top:1px solid #eef2f5;"><td style="padding:8px 0; color:#6b7a89;">Time</td><td style="padding:8px 0; font-weight:bold;">' . htmlspecialchars($apptTime) . '</td></tr>
                ' . ($appt['reason'] ? '<tr style="border-top:1px solid #eef2f5;"><td style="padding:8px 0; color:#6b7a89;">Reason</td><td style="padding:8px 0;">' . htmlspecialchars($appt['reason']) . '</td></tr>' : '') . '
                <tr style="border-top:2px solid #0d6ea8;"><td style="padding:10px 0; color:#6b7a89;">Consultation Fee Paid</td><td style="padding:10px 0; font-weight:bold; color:#0d6ea8;">₹' . $fee . '</td></tr>
            </table>

            <p style="background:#f4f9fc; border:1px solid #dfeefa; border-radius:8px; padding:12px 16px; font-size:14px;">
                Please arrive 10–15 minutes before your scheduled time. Bring this email or your reference number for verification at the reception desk.
            </p>

            <p style="margin-top:24px; color:#6b7a89; font-size:13px;">
                This is an automated confirmation from ' . htmlspecialchars($hospitalName) . '. If you did not make this booking, please contact the hospital directly.
            </p>
        </div>
    ';

    $mail->send(); // throws PHPMailer\PHPMailer\Exception on failure — let it propagate
    return true;
}
