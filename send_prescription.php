<?php
/**
 * send_prescription.php
 *
 * Generates a formatted PDF prescription (hospital letterhead with logo,
 * patient details, medical history, prescribed medicines, next appointment,
 * and attending doctor/staff info) and emails it to the patient.
 *
 * REQUIRES two third-party libraries to be manually installed (see the
 * setup instructions provided alongside this file):
 *   /libs/fpdf/fpdf.php                       — PDF generation
 *   /libs/PHPMailer/src/PHPMailer.php          — Email sending
 *   /libs/PHPMailer/src/SMTP.php
 *   /libs/PHPMailer/src/Exception.php
 *
 * Also requires smtp_config.php (copy smtp_config.sample.php and fill in
 * your real email credentials — never commit smtp_config.php to git).
 */

require_once __DIR__ . '/libs/fpdf/fpdf.php';
require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/smtp_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Prescription PDF with a letterhead (logo + hospital name), generated
 * using FPDF. Kept as its own class so the layout is easy to adjust later.
 */
class PrescriptionPDF extends FPDF
{
    public string $hospitalName = 'Sassoon General Hospital, Pune';
    public string $logoPath = __DIR__ . '/assets/bjmc_logo.jpg';

    function Header(): void
    {
        if (file_exists($this->logoPath)) {
            $imgInfo = @getimagesize($this->logoPath);
            $type = 'JPEG';
            if ($imgInfo && isset($imgInfo['mime'])) {
                if ($imgInfo['mime'] === 'image/png') { $type = 'PNG'; }
                elseif ($imgInfo['mime'] === 'image/gif') { $type = 'GIF'; }
            }
            $this->Image($this->logoPath, 10, 8, 18, 0, $type);
            $this->SetXY(32, 10);
        } else {
            $this->SetXY(10, 10);
        }
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 8, $this->hospitalName, 0, 1, 'L');
        $this->SetX(32);
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 6, 'Patient Prescription & Registration Summary', 0, 1, 'L');
        $this->Ln(4);
        $this->SetDrawColor(13, 110, 168);
        $this->SetLineWidth(0.6);
        $this->Line(10, 26, 200, 26);
        $this->Ln(8);
    }

    function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, 'Generated on ' . date('F j, Y g:i A') . ' — Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function sectionTitle(string $title): void
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(11, 46, 74);
        $this->Cell(0, 8, $title, 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
    }

    function fieldRow(string $label, string $value): void
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(50, 7, $label, 0, 0, 'L');
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 7, $value !== '' ? $value : '-', 0, 'L');
    }
}

/**
 * Builds the PDF file on disk and returns its path.
 */
function buildPrescriptionPdf(array $patient, array $doctor): string
{
    $pdf = new PrescriptionPDF();
    $pdf->AddPage();

    $pdf->sectionTitle('Patient Information');
    $pdf->fieldRow('Patient ID:', $patient['patient_id']);
    $pdf->fieldRow('Full Name:', $patient['full_name']);
    $pdf->fieldRow('Date of Birth:', $patient['dob']);
    $pdf->fieldRow('Gender:', $patient['gender']);
    $pdf->fieldRow('Contact:', $patient['contact']);
    $pdf->fieldRow('Email:', $patient['email']);
    $pdf->Ln(4);

    $pdf->sectionTitle('Attending ' . ($doctor['role'] === 'employee' ? 'Doctor' : 'Staff'));
    $pdf->fieldRow('Name:', $doctor['name']);
    if (!empty($doctor['specialty'])) {
        $pdf->fieldRow('Specialty:', $doctor['specialty']);
    }
    $pdf->Ln(4);

    $pdf->sectionTitle('Medical History / Current Disease Management');
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 7, $patient['history'] !== '' ? $patient['history'] : 'None noted.');
    $pdf->Ln(4);

    $pdf->sectionTitle('Prescribed Medicines');
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 7, $patient['medicines'] !== '' ? $patient['medicines'] : 'None prescribed at this visit.');
    $pdf->Ln(4);

    $pdf->sectionTitle('Next Appointment');
    $pdf->SetFont('Arial', '', 10);
    $nextAppt = $patient['next_appt'] !== '' ? date('F j, Y', strtotime($patient['next_appt'])) : 'Not scheduled.';
    $pdf->Cell(0, 7, $nextAppt, 0, 1);

    $outputDir = __DIR__ . '/prescriptions';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    $filePath = $outputDir . '/prescription_' . preg_replace('/[^A-Za-z0-9_-]/', '', $patient['patient_id']) . '.pdf';
    $pdf->Output('F', $filePath);

    return $filePath;
}

/**
 * Generates the PDF and emails it to the patient. Returns true on success,
 * false on any failure (registration itself is NOT rolled back if this fails).
 */
function sendPrescriptionEmail(array $patient, array $doctor): bool
{
    if (empty($patient['email']) || !filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Patient email address is missing or invalid: "' . ($patient['email'] ?? '') . '"');
    }

    $pdfPath = buildPrescriptionPdf($patient, $doctor);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($patient['email'], $patient['full_name']);

    $mail->isHTML(true);
    $mail->Subject = 'Your Registration & Prescription — ' . SMTP_FROM_NAME;
    $mail->Body    = '
        <p>Dear ' . htmlspecialchars($patient['full_name']) . ',</p>
        <p>Thank you for registering at <strong>' . htmlspecialchars(SMTP_FROM_NAME) . '</strong>.
        Your Patient ID is <strong>' . htmlspecialchars($patient['patient_id']) . '</strong>.</p>
        <p>Please find attached your prescription and registration summary as a PDF,
        including your current medical history, prescribed medicines, and next
        appointment date.</p>
        <p>Regards,<br>' . htmlspecialchars(SMTP_FROM_NAME) . '</p>
    ';
    $mail->addAttachment($pdfPath, 'Prescription_' . $patient['patient_id'] . '.pdf');

    $mail->send(); // throws PHPMailer\PHPMailer\Exception on failure — let it propagate
    return true;
}
