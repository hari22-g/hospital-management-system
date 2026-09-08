<?php
session_start();
include("../../connection/config.php");
include("../../include/hospital_info.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

$userType = strtolower(trim((string) ($_SESSION['user_type'] ?? '')));
if ($userType !== 'patient') {
    header('Location: ../../include/dashboard_link.php');
    exit();
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$email = $_SESSION['email'] ?? '';
$localhost = "http://" . $_SERVER['SERVER_NAME'] . "/MedC/MedC/";
$hospital_info = getHospitalInfo();

function medc_table_exists(mysqli $conn, string $table): bool
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($safeTable === '') {
        return false;
    }

    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($safeTable) . "'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function medc_parse_forwarded_bill_label(string $label, int $patientId): array
{
    $parts = array_map('trim', explode('|', $label));
    $namePart = $parts[1] ?? '';
    $emailPart = '';

    if (preg_match('/\(([^)]+)\)/', $namePart, $matches)) {
        $emailPart = trim((string) ($matches[1] ?? ''));
        $namePart = trim((string) preg_replace('/\s*\([^)]+\)\s*/', '', $namePart));
    }

    return [
        'id' => $patientId > 0 ? 'PID-' . $patientId : ($parts[0] ?? 'N/A'),
        'name' => $namePart !== '' ? $namePart : ($parts[1] ?? 'N/A'),
        'email' => $emailPart !== '' ? $emailPart : 'N/A',
        'appointment' => $parts[2] ?? 'N/A',
        'status' => $parts[3] ?? 'N/A'
    ];
}

$patientData = null;
if (!empty($email)) {
    $stmtPatientByEmail = $conn->prepare("SELECT * FROM patient WHERE email = ? LIMIT 1");
    if ($stmtPatientByEmail) {
        $stmtPatientByEmail->bind_param("s", $email);
        $stmtPatientByEmail->execute();
        $patientResultByEmail = $stmtPatientByEmail->get_result();
        $patientData = $patientResultByEmail ? $patientResultByEmail->fetch_assoc() : null;
        $stmtPatientByEmail->close();
    }
}

if (!$patientData && $user_id > 0) {
    $stmtPatientById = $conn->prepare("SELECT * FROM patient WHERE pid = ? LIMIT 1");
    if ($stmtPatientById) {
        $stmtPatientById->bind_param("i", $user_id);
        $stmtPatientById->execute();
        $patientResultById = $stmtPatientById->get_result();
        $patientData = $patientResultById ? $patientResultById->fetch_assoc() : null;
        $stmtPatientById->close();
    }
}

if (!$patientData) {
    $patientData = [
        'pid' => $user_id,
        'firstname' => 'Patient',
        'lastname' => '',
        'gender' => 'N/A',
        'state' => 'N/A',
        'country' => 'N/A',
        'bloodgroup' => 'N/A',
        'height' => 'N/A',
        'weight' => 'N/A',
        'dob' => null
    ];
}

$heightCm = is_numeric($patientData['height']) ? (float) $patientData['height'] : null;
$weightKg = is_numeric($patientData['weight']) ? (float) $patientData['weight'] : null;
$bmi = null;
if ($heightCm && $heightCm > 0 && $weightKg && $weightKg > 0) {
    $heightM = $heightCm / 100;
    $bmi = round($weightKg / ($heightM * $heightM), 1);
}

$healthStatus = 'Stable';
if ($bmi !== null) {
    if ($bmi < 18.5) {
        $healthStatus = 'Needs Nutrition';
    } elseif ($bmi >= 25) {
        $healthStatus = 'Monitor Weight';
    }
}

$patientRecordId = (int) ($patientData['pid'] ?? $user_id);

$forwardedBillInvoiceId = (int) ($_GET['invoice_id'] ?? 0);
$forwardedBillPatientId = (int) ($_GET['patient_id'] ?? 0);
$forwardedBillLabel = trim((string) ($_GET['patient_label'] ?? ''));
$forwardedBillServices = trim((string) ($_GET['services'] ?? ''));
$forwardedBillAmount = (float) ($_GET['amount'] ?? 0);
$forwardedBillConsultationFee = (float) ($_GET['consultation_fee'] ?? 0);
$forwardedBillPaymentStatus = strtolower(trim((string) ($_GET['payment_status'] ?? 'not_paid')));
$forwardedBillGeneratedOn = trim((string) ($_GET['generated_on'] ?? ''));
$forwardedBillPrescriptionId = (int) ($_GET['prescription_id'] ?? 0);
$forwardedBillMatches = $forwardedBillPatientId <= 0 || $forwardedBillPatientId === $patientRecordId || $forwardedBillPatientId === $user_id || $forwardedBillInvoiceId > 0;
$forwardedBillEnabled = false;
$forwardedBillSummary = [];
$forwardedBillDoctor = '';
$forwardedBillItems = [];
$forwardedBillTotal = $forwardedBillAmount;

if ($forwardedBillMatches) {
    $invoiceRow = null;
    if (medc_table_exists($conn, 'invoices')) {
        if ($forwardedBillInvoiceId > 0) {
            $invoiceStmt = $conn->prepare(
                "SELECT invoice_id, appointment_id, patient_id, doctor_id, invoice_date, total_amount, paid_amount, status, notes
                 FROM invoices
                 WHERE invoice_id = ? AND patient_id = ?
                 LIMIT 1"
            );
            if ($invoiceStmt) {
                $invoiceStmt->bind_param('ii', $forwardedBillInvoiceId, $patientRecordId);
                $invoiceStmt->execute();
                $invoiceRow = $invoiceStmt->get_result()->fetch_assoc();
                $invoiceStmt->close();
            }
        }

        if (!$invoiceRow) {
            $invoiceStmt = $conn->prepare(
                "SELECT invoice_id, appointment_id, patient_id, doctor_id, invoice_date, total_amount, paid_amount, status, notes
                 FROM invoices
                 WHERE patient_id = ?
                 ORDER BY invoice_date DESC, invoice_id DESC
                 LIMIT 1"
            );
            if ($invoiceStmt) {
                $invoiceStmt->bind_param('i', $patientRecordId);
                $invoiceStmt->execute();
                $invoiceRow = $invoiceStmt->get_result()->fetch_assoc();
                $invoiceStmt->close();
            }
        }
    }

    if ($invoiceRow) {
        $forwardedBillEnabled = true;
        $forwardedBillInvoiceId = (int) ($invoiceRow['invoice_id'] ?? $forwardedBillInvoiceId);
        $forwardedBillPatientId = (int) ($invoiceRow['patient_id'] ?? $patientRecordId);
        $forwardedBillAmount = (float) ($invoiceRow['total_amount'] ?? $forwardedBillAmount);
        $forwardedBillPaymentStatus = strtolower(trim((string) ($invoiceRow['status'] ?? $forwardedBillPaymentStatus)));
        $forwardedBillGeneratedOn = (string) ($invoiceRow['invoice_date'] ?? $forwardedBillGeneratedOn);
        $forwardedBillServices = trim((string) ($invoiceRow['notes'] ?? $forwardedBillServices));

        $forwardedBillSummary = medc_parse_forwarded_bill_label($forwardedBillLabel, $forwardedBillPatientId);
        $forwardedBillSummary['name'] = trim((string) ($patientData['firstname'] ?? '') . ' ' . (string) ($patientData['lastname'] ?? '')) ?: ($forwardedBillSummary['name'] ?? 'N/A');
        $forwardedBillSummary['email'] = (string) ($patientData['email'] ?? 'N/A');
        $forwardedBillSummary['appointment'] = $forwardedBillSummary['appointment'] !== 'N/A'
            ? $forwardedBillSummary['appointment']
            : ('Invoice #' . $forwardedBillInvoiceId);
        $forwardedBillSummary['status'] = $forwardedBillPaymentStatus === 'paid' ? 'Paid' : 'Not Paid';

        if (medc_table_exists($conn, 'doctor')) {
            $doctorStmt = $conn->prepare(
                "SELECT d.f_name, d.l_name
                 FROM doctor d
                 INNER JOIN invoices i ON i.doctor_id = d.d_id
                 WHERE i.invoice_id = ?
                 LIMIT 1"
            );
            if ($doctorStmt) {
                $doctorStmt->bind_param('i', $forwardedBillInvoiceId);
                $doctorStmt->execute();
                $doctorRow = $doctorStmt->get_result()->fetch_assoc();
                $doctorStmt->close();
                if ($doctorRow) {
                    $forwardedBillDoctor = trim((string) ($doctorRow['f_name'] ?? '') . ' ' . (string) ($doctorRow['l_name'] ?? ''));
                }
            }
        }

        if (medc_table_exists($conn, 'invoice_items')) {
            $itemStmt = $conn->prepare(
                "SELECT description, quantity, rate, amount, item_type
                 FROM invoice_items
                 WHERE invoice_id = ?
                 ORDER BY item_id ASC"
            );
            if ($itemStmt) {
                $itemStmt->bind_param('i', $forwardedBillInvoiceId);
                $itemStmt->execute();
                $itemResult = $itemStmt->get_result();
                while ($row = $itemResult->fetch_assoc()) {
                    $description = trim((string) ($row['description'] ?? ''));
                    $amountValue = (float) ($row['amount'] ?? 0);
                    $forwardedBillItems[] = [
                        'medicine_name' => $description,
                        'quantity' => (float) ($row['quantity'] ?? 1),
                        'rate' => (float) ($row['rate'] ?? 0),
                        'amount' => $amountValue,
                        'dosage' => '',
                        'frequency' => '',
                        'food_timing' => '',
                        'duration_days' => '',
                        'instructions' => ''
                    ];
                }
                $itemStmt->close();
            }
        }

        if (empty($forwardedBillItems) && $forwardedBillPrescriptionId > 0 && medc_table_exists($conn, 'prescriptions') && medc_table_exists($conn, 'prescription_medicines')) {
            $billStmt = $conn->prepare(
                "SELECT pr.prescription_id, pr.patient_id, pr.doctor_id, p.firstname, p.lastname, d.f_name, d.l_name
                 FROM prescriptions pr
                 INNER JOIN patient p ON p.pid = pr.patient_id
                 LEFT JOIN doctor d ON d.d_id = pr.doctor_id
                 WHERE pr.prescription_id = ? AND pr.patient_id = ?
                 LIMIT 1"
            );
            if ($billStmt) {
                $billStmt->bind_param("ii", $forwardedBillPrescriptionId, $patientRecordId);
                $billStmt->execute();
                $billPrescription = $billStmt->get_result()->fetch_assoc();
                $billStmt->close();

                if ($billPrescription) {
                    $forwardedBillDoctor = trim((string) ($billPrescription['f_name'] ?? '') . ' ' . (string) ($billPrescription['l_name'] ?? ''));
                    $forwardedBillSummary['appointment'] = 'Prescription #' . $forwardedBillPrescriptionId;

                    $itemStmt = $conn->prepare(
                        "SELECT medicine_name, quantity, unit_price, dosage, frequency, food_timing, duration_days, instructions
                         FROM prescription_medicines
                         WHERE prescription_id = ?
                         ORDER BY medicine_id ASC"
                    );
                    if ($itemStmt) {
                        $itemStmt->bind_param("i", $forwardedBillPrescriptionId);
                        $itemStmt->execute();
                        $itemResult = $itemStmt->get_result();
                        while ($row = $itemResult->fetch_assoc()) {
                            $quantity = (float) ($row['quantity'] ?? 0);
                            if ($quantity <= 0) {
                                $quantity = 1;
                            }
                            $rate = (float) ($row['unit_price'] ?? 0);
                            $itemAmount = round($quantity * $rate, 2);
                            $forwardedBillItems[] = [
                                'medicine_name' => (string) ($row['medicine_name'] ?? ''),
                                'quantity' => $quantity,
                                'rate' => $rate,
                                'amount' => $itemAmount,
                                'dosage' => (string) ($row['dosage'] ?? ''),
                                'frequency' => (string) ($row['frequency'] ?? ''),
                                'food_timing' => (string) ($row['food_timing'] ?? ''),
                                'duration_days' => (string) ($row['duration_days'] ?? ''),
                                'instructions' => (string) ($row['instructions'] ?? '')
                            ];
                        }
                        $itemStmt->close();
                    }
                }
            }
        }

        if (!empty($forwardedBillItems)) {
            $forwardedBillTotal = array_reduce($forwardedBillItems, function ($carry, $item) {
                return $carry + (float) ($item['amount'] ?? 0);
            }, 0.0);
        }
    } elseif (
        $forwardedBillAmount > 0 ||
        $forwardedBillConsultationFee > 0 ||
        $forwardedBillPrescriptionId > 0 ||
        $forwardedBillServices !== '' ||
        $forwardedBillLabel !== ''
    ) {
        $forwardedBillEnabled = true;
        $forwardedBillSummary = medc_parse_forwarded_bill_label($forwardedBillLabel, $forwardedBillPatientId > 0 ? $forwardedBillPatientId : $patientRecordId);
        $forwardedBillTotal = $forwardedBillAmount > 0 ? $forwardedBillAmount : $forwardedBillConsultationFee;
    }
}

// Upcoming appointments
$appointments = [];
$appointmentStmt = $conn->prepare(
    "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.jitsi_meeting_link,
            d.f_name AS doctor_fname, d.l_name AS doctor_lname, d.specialization
     FROM appointment a
     LEFT JOIN doctor d ON a.did = d.d_id
     WHERE a.pid = ? OR a.user_id = ?
     ORDER BY a.appointment_date ASC, a.appointment_time ASC"
);
if ($appointmentStmt) {
    $appointmentStmt->bind_param("ii", $user_id, $user_id);
    $appointmentStmt->execute();
    $appointmentResult = $appointmentStmt->get_result();
    while ($row = $appointmentResult->fetch_assoc()) {
        $appointments[] = $row;
    }
    $appointmentStmt->close();
}

$upcomingAppointments = [];
$now = time();
foreach ($appointments as $apt) {
    $aptDate = (string) ($apt['appointment_date'] ?? '');
    $aptTime = (string) ($apt['appointment_time'] ?? '');
    $aptTimestamp = strtotime($aptDate . ' ' . $aptTime);
    if ($aptTimestamp === false) {
        continue;
    }
    $status = strtolower(trim((string) ($apt['status'] ?? 'pending')));
    if ($status === 'cancelled' || $status === 'completed') {
        continue;
    }
    if ($aptTimestamp >= $now) {
        $apt['appointment_timestamp'] = $aptTimestamp;
        $upcomingAppointments[] = $apt;
    }
}

$featuredAppointment = $upcomingAppointments[0] ?? null;
$secondaryAppointments = array_slice($upcomingAppointments, 1, 2);

// Prescribed medicines
$medications = [];
if (medc_table_exists($conn, 'prescriptions') && medc_table_exists($conn, 'prescription_medicines')) {
    $medStmt = $conn->prepare(
        "SELECT pm.medicine_name, pm.generic_name, pm.dosage, pm.frequency, pm.food_timing, pm.duration_days,
                pm.instructions, pr.created_at, d.f_name AS doctor_fname, d.l_name AS doctor_lname
         FROM prescriptions pr
         INNER JOIN prescription_medicines pm ON pm.prescription_id = pr.prescription_id
         INNER JOIN doctor d ON d.d_id = pr.doctor_id
         WHERE pr.patient_id = ?
         ORDER BY pr.created_at DESC, pm.medicine_id ASC
         LIMIT 20"
    );
    if ($medStmt) {
        $medStmt->bind_param("i", $patientRecordId);
        $medStmt->execute();
        $medResult = $medStmt->get_result();
        while ($row = $medResult->fetch_assoc()) {
            $medications[] = $row;
        }
        $medStmt->close();
    }
} elseif (medc_table_exists($conn, 'doctor_prescriptions')) {
    $medStmt = $conn->prepare(
        "SELECT dp.medicine_name, '' AS generic_name, dp.dosage, dp.duration AS duration_label,
                dp.instructions, dp.created_at, d.f_name AS doctor_fname, d.l_name AS doctor_lname
         FROM doctor_prescriptions dp
         INNER JOIN doctor d ON d.d_id = dp.did
         WHERE dp.pid = ?
         ORDER BY dp.created_at DESC
         LIMIT 20"
    );
    if ($medStmt) {
        $medStmt->bind_param("i", $patientRecordId);
        $medStmt->execute();
        $medResult = $medStmt->get_result();
        while ($row = $medResult->fetch_assoc()) {
            $row['frequency'] = '';
            $row['food_timing'] = '';
            $row['duration_days'] = $row['duration_label'] ?? '';
            $medications[] = $row;
        }
        $medStmt->close();
    }
}

// Reports
$reports = [];
$reportStmt = $conn->prepare("SELECT report_type, referred_by, report_date, file_path FROM reports WHERE user_id = ? ORDER BY report_date DESC LIMIT 3");
if ($reportStmt) {
    $reportStmt->bind_param("i", $user_id);
    $reportStmt->execute();
    $reportResult = $reportStmt->get_result();
    while ($row = $reportResult->fetch_assoc()) {
        $reports[] = $row;
    }
    $reportStmt->close();
}

function medc_report_link(string $localhost, string $filePath): string
{
    $trimmed = trim($filePath);
    if ($trimmed === '') {
        return '';
    }
    if (stripos($trimmed, 'http://') === 0 || stripos($trimmed, 'https://') === 0) {
        return $trimmed;
    }
    if (stripos($trimmed, 'uploads/Patient_Reports') !== false) {
        return $trimmed;
    }
    return $localhost . 'uploads/Patient_Reports/' . ltrim($trimmed, '/');
}

// Biomarkers
$biomarker = null;
$biomarkerStmt = $conn->prepare(
    "SELECT total_cholesterol, hdl_cholesterol, ldl_cholesterol, triglycerides,
            systolic_blood_pressure, diastolic_blood_pressure, non_fasting_glucose,
            glucose_post_fast, hba1c
     FROM biomarker WHERE user_id = ? ORDER BY data_id DESC LIMIT 1"
);
if ($biomarkerStmt) {
    $biomarkerStmt->bind_param("i", $user_id);
    $biomarkerStmt->execute();
    $biomarkerResult = $biomarkerStmt->get_result();
    $biomarker = $biomarkerResult ? $biomarkerResult->fetch_assoc() : null;
    $biomarkerStmt->close();
}

// Weight trend data
$weightTrend = [];
$weightStmt = $conn->prepare("SELECT month, weight FROM weight_tracker WHERE user_id = ? ORDER BY month DESC, created_at DESC LIMIT 7");
if ($weightStmt) {
    $weightStmt->bind_param("i", $user_id);
    $weightStmt->execute();
    $weightResult = $weightStmt->get_result();
    while ($row = $weightResult->fetch_assoc()) {
        $weightTrend[] = [
            'label' => date('M d', strtotime($row['month'] ?? 'now')),
            'weight' => (float) ($row['weight'] ?? 0)
        ];
    }
    $weightStmt->close();
}
$weightTrend = array_reverse($weightTrend);

// Notifications from live data
$notifications = [];
if ($featuredAppointment) {
    $aptDateLabel = date('M j, Y', strtotime($featuredAppointment['appointment_date'] ?? 'now'));
    $notifications[] = [
        'icon' => 'fa-calendar-check',
        'title' => 'Upcoming Appointment',
        'text' => 'Next visit on ' . $aptDateLabel . ' at ' . date('h:i A', strtotime($featuredAppointment['appointment_time'] ?? ''))
    ];
}
if (!empty($reports)) {
    $latestReport = $reports[0];
    $notifications[] = [
        'icon' => 'fa-file-medical',
        'title' => 'New Report Available',
        'text' => ($latestReport['report_type'] ?? 'Medical report') . ' uploaded.'
    ];
}
if (!empty($medications)) {
    $notifications[] = [
        'icon' => 'fa-pills',
        'title' => 'Medicine Plan Active',
        'text' => count($medications) . ' medications listed for you.'
    ];
}
if (empty($notifications)) {
    $notifications[] = [
        'icon' => 'fa-bell',
        'title' => 'All caught up',
        'text' => 'No new notifications right now.'
    ];
}

$notificationCount = count($notifications);

function medc_status_badge(string $status): string
{
    $normalized = strtolower(trim($status));
    if ($normalized === 'confirmed') {
        return 'Confirmed';
    }
    if ($normalized === 'pending') {
        return 'Pending';
    }
    if ($normalized === 'completed') {
        return 'Completed';
    }
    if ($normalized === 'cancelled') {
        return 'Cancelled';
    }
    return ucfirst($normalized);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MedC</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --brand-blue: #0b3ea8;
            --brand-blue-dark: #072e7c;
            --brand-accent: #19b3e6;
            --brand-bg: #f5f7fb;
            --brand-text: #1f2a44;
            --brand-muted: #6b7a99;
            --brand-card: #ffffff;
            --brand-shadow: 0 16px 30px rgba(10, 36, 91, 0.08);
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--brand-bg);
            color: var(--brand-text);
        }

        body.dark-mode {
            background: #0f172a;
            color: #e2e8f0;
        }

        body.dark-mode .section-card,
        body.dark-mode .center-modal {
            background: #111827;
            color: #e2e8f0;
            border-color: rgba(148, 163, 184, 0.2);
        }

        body.dark-mode .text-muted {
            color: #94a3b8 !important;
        }

        .navbar {
            background: var(--brand-blue);
        }

        .navbar .nav-link,
        .navbar .navbar-brand {
            color: #ffffff;
            font-weight: 600;
        }

        .navbar .nav-link.active {
            border-bottom: 3px solid #ffffff;
        }

        .navbar .nav-link:hover {
            color: #e3f0ff;
        }

        .top-search {
            min-width: 180px;
        }

        .badge-notify {
            position: absolute;
            top: 0;
            right: 2px;
            transform: translate(40%, -40%);
            background: #ff4d6d;
            color: #fff;
            border-radius: 999px;
            padding: 2px 6px;
            font-size: 0.7rem;
        }

        .dashboard-wrap {
            padding: 24px 0 48px;
        }

        .section-card {
            background: var(--brand-card);
            border-radius: 18px;
            padding: 22px;
            box-shadow: var(--brand-shadow);
            border: 1px solid #e4ecfb;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--brand-blue-dark);
            margin-bottom: 16px;
        }

        .welcome-card {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #ffffff 0%, #eef5ff 100%);
        }

        .welcome-card h1 {
            font-size: 1.6rem;
            margin-bottom: 6px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #eaf4ff;
            color: var(--brand-blue-dark);
        }

        .info-badge {
            padding: 6px 12px;
            border-radius: 999px;
            background: #e7fff4;
            color: #1c875a;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .profile-card {
            display: flex;
            gap: 18px;
            align-items: center;
            height: 100%;
        }

        .profile-details-card {
            height: 100%;
        }

        .profile-details-card .stat-grid {
            align-content: start;
        }

        .profile-card .profile-actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .avatar {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: #e1edff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand-blue);
            font-size: 2rem;
            flex: 0 0 84px;
        }

        .stat-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        }

        .stat-item {
            background: #f6f9ff;
            border-radius: 14px;
            padding: 12px 14px;
            border: 1px solid #e2ebfb;
        }

        .stat-item span {
            display: block;
            color: var(--brand-muted);
            font-size: 0.8rem;
        }

        .stat-item strong {
            font-size: 1rem;
            color: var(--brand-blue-dark);
        }

        .action-card {
            border: 1px solid #e6eefb;
            border-radius: 16px;
            padding: 16px;
            background: #ffffff;
            height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(10, 36, 91, 0.1);
        }

        .action-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #eaf4ff;
            color: var(--brand-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .appointment-card {
            border: 1px solid #e2ebfb;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            background: #ffffff;
        }

        .appointment-card.featured {
            border-color: #7ab6ff;
            box-shadow: 0 18px 30px rgba(40, 106, 212, 0.15);
            background: #f7fbff;
        }

        .tracker-card {
            border: 1px solid #e1eaf8;
            border-radius: 14px;
            padding: 14px;
            background: #ffffff;
        }

        .tracker-card h6 {
            margin-bottom: 6px;
        }

        .progress {
            height: 8px;
            border-radius: 999px;
        }

        .medicine-table th {
            background: #f1f6ff;
        }

        .report-card {
            border: 1px solid #e3ecfb;
            border-radius: 16px;
            padding: 16px;
            background: #ffffff;
        }

        .bill-card {
            border: 1px solid #dce7fb;
            background: linear-gradient(135deg, #ffffff 0%, #f6faff 100%);
        }

        .bill-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .bill-header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .bill-summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            margin-bottom: 16px;
        }

        .bill-summary-item {
            border: 1px solid #dfe8fb;
            border-radius: 14px;
            background: #ffffff;
            padding: 14px;
        }

        .bill-summary-item span {
            display: block;
            font-size: 0.82rem;
            color: var(--brand-muted);
            margin-bottom: 4px;
        }

        .bill-summary-item strong {
            display: block;
            font-size: 1.02rem;
            color: var(--brand-blue-dark);
            word-break: break-word;
        }

        .bill-total-box {
            border-radius: 16px;
            padding: 18px;
            background: linear-gradient(135deg, #edf5ff 0%, #ffffff 100%);
            border: 1px solid #d6e4fb;
            margin-bottom: 16px;
        }

        .bill-total-box .label {
            display: block;
            font-size: 0.9rem;
            color: var(--brand-muted);
            margin-bottom: 8px;
        }

        .bill-total-box .amount {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand-blue-dark);
            line-height: 1.1;
        }

        .bill-note {
            color: var(--brand-muted);
            font-size: 0.92rem;
            margin-top: 8px;
        }

        .bill-table thead th {
            background: #f1f6ff;
            color: var(--brand-blue-dark);
            border-bottom: 1px solid #dbe6f8 !important;
        }

        .recommend-card img {
            border-radius: 14px;
            height: 150px;
            object-fit: cover;
        }

        .notification-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #edf1f7;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .emergency-card {
            background: linear-gradient(135deg, #ff5f7a 0%, #ff8b6b 100%);
            color: #ffffff;
            border-radius: 18px;
            padding: 20px;
        }

        .emergency-card .btn {
            background: #ffffff;
            color: #d63c56;
            font-weight: 700;
        }

        .health-score {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }

        .score-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 8px solid #e6f0ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--brand-blue);
        }

        .settings-list li {
            padding: 10px 0;
            border-bottom: 1px dashed #e3ebf7;
        }

        .settings-list a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .settings-action {
            border: none;
            background: transparent;
            padding: 0;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
            color: inherit;
            font: inherit;
        }

        .modal-backdrop-blur {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(6px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 1040;
        }

        .modal-backdrop-blur.show {
            opacity: 1;
            pointer-events: auto;
        }

        .center-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.98);
            opacity: 0;
            pointer-events: none;
            width: min(92vw, 520px);
            max-height: 86vh;
            overflow-y: auto;
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e4ecfb;
            box-shadow: 0 16px 30px rgba(10, 36, 91, 0.18);
            padding: 22px;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 1050;
        }

        .center-modal.modal-lg {
            width: min(94vw, 760px);
        }

        .center-modal.show {
            opacity: 1;
            pointer-events: auto;
            transform: translate(-50%, -50%) scale(1);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .toggle-visibility {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--brand-muted);
        }

        .error-text {
            color: #dc2626;
            font-size: 0.85rem;
        }

        .settings-list li:last-child {
            border-bottom: none;
        }
    </style>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({ pageLanguage: 'en' }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>

<body>
    <div class="container dashboard-wrap">
        <div class="section-card welcome-card">
            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($patientData['firstname']); ?>.</h1>
                <p class="mb-2">Your care team is ready. Stay on top of your health with quick insights today.</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="pill"><i class="fas fa-calendar-alt"></i><?php echo date('l, F j, Y'); ?></span>
                    <span class="pill"><i class="fas fa-heartbeat"></i>Health status: <?php echo htmlspecialchars($healthStatus); ?></span>
                </div>
            </div>
            <div>
                <a class="btn btn-outline-primary me-2" href="<?php echo $localhost; ?>homepage.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
                <a class="btn btn-primary" href="<?php echo $localhost; ?>doctors_directory.php">
                    <i class="fas fa-calendar-check me-1"></i>Book Appointment
                </a>
            </div>
        </div>

        <?php if ($forwardedBillEnabled): ?>
            <div class="section-card bill-card" id="forwarded-bill">
                <div class="bill-header">
                    <div>
                        <div class="section-title mb-1">Forwarded Bill</div>
                        <div class="text-muted">The latest invoice sent from your doctor.</div>
                    </div>
                    <div class="bill-header-actions">
                        <span class="pill"><i class="fas fa-file-invoice me-1"></i><?php echo htmlspecialchars($forwardedBillSummary['appointment'] ?? 'Bill'); ?></span>
                        <span class="info-badge"><?php echo htmlspecialchars($forwardedBillPaymentStatus === 'paid' ? 'Paid' : 'Not Paid'); ?></span>
                        <button class="btn btn-outline-primary btn-sm" type="button" onclick="window.print()">Print Bill</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-close-bill>Close</button>
                        <?php if ($forwardedBillPrescriptionId > 0): ?>
                            <a class="btn btn-primary btn-sm" href="<?php echo $localhost; ?>patient_prescriptions.php?prescription_id=<?php echo (int) $forwardedBillPrescriptionId; ?>">View Prescription</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bill-summary-grid">
                    <div class="bill-summary-item">
                        <span>Patient Name</span>
                        <strong><?php echo htmlspecialchars($forwardedBillSummary['name'] ?? trim($patientData['firstname'] . ' ' . $patientData['lastname'])); ?></strong>
                    </div>
                    <div class="bill-summary-item">
                        <span>Patient ID</span>
                        <strong><?php echo htmlspecialchars($forwardedBillSummary['id'] ?? ('PID-' . $patientRecordId)); ?></strong>
                    </div>
                    <div class="bill-summary-item">
                        <span>Services</span>
                        <strong><?php echo htmlspecialchars($forwardedBillServices !== '' ? $forwardedBillServices : 'Prescription medicines'); ?></strong>
                    </div>
                    <div class="bill-summary-item">
                        <span>Doctor Consultation Fee</span>
                        <strong>INR <?php echo number_format($forwardedBillConsultationFee, 2); ?></strong>
                    </div>
                    <div class="bill-summary-item">
                        <span>Generated On</span>
                        <strong>
                            <?php
                            $forwardedGeneratedText = $forwardedBillGeneratedOn !== '' ? date('d M Y, h:i A', strtotime($forwardedBillGeneratedOn)) : date('d M Y, h:i A');
                            echo htmlspecialchars($forwardedGeneratedText);
                            ?>
                        </strong>
                    </div>
                    <div class="bill-summary-item">
                        <span>Doctor</span>
                        <strong><?php echo htmlspecialchars($forwardedBillDoctor !== '' ? $forwardedBillDoctor : 'Doctor'); ?></strong>
                    </div>
                </div>

                <?php if (!empty($forwardedBillItems)): ?>
                    <div class="table-responsive">
                        <table class="table align-middle bill-table mb-0">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Qty</th>
                                    <th>Rate</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forwardedBillItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($item['medicine_name']); ?></div>
                                            <div class="text-muted small">
                                                <?php
                                                $details = array_filter([
                                                    $item['dosage'] ?? '',
                                                    trim((string) ($item['frequency'] ?? '') . ' ' . (string) ($item['food_timing'] ?? '')),
                                                    $item['duration_days'] !== '' ? $item['duration_days'] . ' days' : '',
                                                ]);
                                                echo htmlspecialchars(implode(' | ', $details));
                                                ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars((string) $item['quantity']); ?></td>
                                        <td>Rs. <?php echo number_format((float) $item['rate'], 2); ?></td>
                                        <td>Rs. <?php echo number_format((float) $item['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bill-note">No line items were attached to this forwarded bill.</div>
                <?php endif; ?>

                <div class="bill-total-box mt-3">
                    <span class="label">Total Bill Amount</span>
                    <div class="amount">INR <?php echo number_format($forwardedBillTotal > 0 ? $forwardedBillTotal : $forwardedBillAmount, 2); ?></div>
                    <div class="bill-note">
                        <?php echo !empty($forwardedBillItems) ? 'Medicine charges and doctor fee are included in this total.' : 'Bill forwarded from the invoice page.'; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4 align-items-stretch" id="profile">
            <div class="col-lg-5">
                <div class="section-card profile-card h-100">
                <div class="avatar"><i class="fas fa-user"></i></div>
                <div>
                    <h5 class="mb-1"><?php echo htmlspecialchars(trim($patientData['firstname'] . ' ' . $patientData['lastname'])); ?></h5>
                    <div class="text-muted mb-2">Patient ID: <?php echo htmlspecialchars((string)($patientData['pid'] ?? $user_id)); ?></div>
                    <span class="info-badge"><?php echo htmlspecialchars($healthStatus); ?></span>
                    <?php if ($forwardedBillEnabled): ?>
                        <div class="profile-actions">
                            <a class="btn btn-primary btn-sm" href="#forwarded-bill">Show Bill</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
            <div class="col-lg-7">
                <div class="section-card profile-details-card h-100">
                    <div class="stat-grid">
                        <div class="stat-item"><span>Gender</span><strong><?php echo htmlspecialchars($patientData['gender']); ?></strong></div>
                        <div class="stat-item"><span>Location</span><strong><?php echo htmlspecialchars($patientData['state'] . ', ' . $patientData['country']); ?></strong></div>
                        <div class="stat-item"><span>Blood Group</span><strong><?php echo htmlspecialchars($patientData['bloodgroup']); ?></strong></div>
                        <div class="stat-item"><span>Height</span><strong><?php echo htmlspecialchars((string)$patientData['height']); ?><?php echo $patientData['height'] !== 'N/A' ? ' cm' : ''; ?></strong></div>
                        <div class="stat-item"><span>Weight</span><strong><?php echo htmlspecialchars((string)$patientData['weight']); ?><?php echo $patientData['weight'] !== 'N/A' ? ' kg' : ''; ?></strong></div>
                        <div class="stat-item"><span>BMI</span><strong><?php echo $bmi !== null ? htmlspecialchars((string)$bmi) : 'N/A'; ?></strong></div>
                        <div class="stat-item"><span>Status</span><strong><?php echo htmlspecialchars($healthStatus); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card mt-4" id="quick-actions">
            <div class="section-title">Quick Actions</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <a class="action-card" href="<?php echo $localhost; ?>doctors_directory.php">
                        <div class="action-icon"><i class="fas fa-calendar-plus"></i></div>
                        <h6>Book Appointment</h6>
                        <p class="text-muted mb-0">Schedule a visit instantly.</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="action-card" href="<?php echo $localhost; ?>patient_reports.php">
                        <div class="action-icon"><i class="fas fa-file-medical"></i></div>
                        <h6>View Reports</h6>
                        <p class="text-muted mb-0">Access lab results fast.</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="action-card" href="#medicines">
                        <div class="action-icon"><i class="fas fa-pills"></i></div>
                        <h6>Medicines</h6>
                        <p class="text-muted mb-0">View your prescribed medicines.</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="action-card" href="#health-tracker">
                        <div class="action-icon"><i class="fas fa-heartbeat"></i></div>
                        <h6>Health Tracker</h6>
                        <p class="text-muted mb-0">Monitor daily vitals.</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="action-card" href="<?php echo $localhost; ?>doctors_directory.php">
                        <div class="action-icon"><i class="fas fa-user-md"></i></div>
                        <h6>Find Doctor</h6>
                        <p class="text-muted mb-0">Locate specialists nearby.</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="action-card" href="tel:<?php echo htmlspecialchars($hospital_info['emergency_helpline'] ?? ''); ?>">
                        <div class="action-icon"><i class="fas fa-ambulance"></i></div>
                        <h6>Emergency Help</h6>
                        <p class="text-muted mb-0">Rapid response options.</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="action-card" href="<?php echo $localhost; ?>video_consultation.php">
                        <div class="action-icon"><i class="fas fa-comments"></i></div>
                        <h6>Chat with Doctor</h6>
                        <p class="text-muted mb-0">Message your care team.</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="action-card" href="<?php echo $localhost; ?>video_consultation.php">
                        <div class="action-icon"><i class="fas fa-stethoscope"></i></div>
                        <h6>Consult Now</h6>
                        <p class="text-muted mb-0">Start video consultation.</p>
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4" id="appointments">
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="section-title">Upcoming Appointments</div>
                    <?php if ($featuredAppointment): ?>
                        <?php
                        $featuredDoctor = trim((string) (($featuredAppointment['doctor_fname'] ?? '') . ' ' . ($featuredAppointment['doctor_lname'] ?? '')));
                        $featuredDoctor = $featuredDoctor === '' ? 'Doctor not assigned' : 'Dr. ' . $featuredDoctor;
                        $featuredSpec = $featuredAppointment['specialization'] ?? 'General';
                        $featuredType = !empty($featuredAppointment['jitsi_meeting_link']) ? 'Online' : 'In-person';
                        ?>
                        <div class="appointment-card featured">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($featuredDoctor); ?></h6>
                                    <div class="text-muted"><?php echo htmlspecialchars($featuredSpec); ?></div>
                                    <div class="mt-2">
                                        <?php echo date('M j, Y', strtotime($featuredAppointment['appointment_date'] ?? 'now')); ?> |
                                        <?php echo date('h:i A', strtotime($featuredAppointment['appointment_time'] ?? '')); ?> |
                                        <?php echo $featuredType; ?>
                                    </div>
                                </div>
                                <span class="info-badge"><?php echo htmlspecialchars(medc_status_badge($featuredAppointment['status'] ?? 'Pending')); ?></span>
                            </div>
                            <div class="mt-3 d-flex gap-2 flex-wrap">
                                <a class="btn btn-outline-primary btn-sm" href="<?php echo $localhost; ?>views/Patient/fetch_appointments.php">View</a>
                                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Reschedule</button>
                                <button class="btn btn-outline-danger btn-sm" type="button" disabled>Cancel</button>
                                <?php if (!empty($featuredAppointment['jitsi_meeting_link'])): ?>
                                    <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars($featuredAppointment['jitsi_meeting_link']); ?>" target="_blank">Join Call</a>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-sm" type="button" disabled>Join Call</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">No upcoming appointments.</div>
                    <?php endif; ?>

                    <?php foreach ($secondaryAppointments as $apt): ?>
                        <?php
                        $doctor = trim((string) (($apt['doctor_fname'] ?? '') . ' ' . ($apt['doctor_lname'] ?? '')));
                        $doctor = $doctor === '' ? 'Doctor not assigned' : 'Dr. ' . $doctor;
                        $spec = $apt['specialization'] ?? 'General';
                        $type = !empty($apt['jitsi_meeting_link']) ? 'Online' : 'In-person';
                        ?>
                        <div class="appointment-card">
                            <h6 class="mb-1"><?php echo htmlspecialchars($doctor); ?></h6>
                            <div class="text-muted"><?php echo htmlspecialchars($spec); ?></div>
                            <div class="mt-2">
                                <?php echo date('M j, Y', strtotime($apt['appointment_date'] ?? 'now')); ?> |
                                <?php echo date('h:i A', strtotime($apt['appointment_time'] ?? '')); ?> |
                                <?php echo $type; ?>
                            </div>
                            <div class="mt-2"><span class="pill"><?php echo htmlspecialchars(medc_status_badge($apt['status'] ?? 'Pending')); ?></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-6" id="health-tracker">
                <div class="section-card">
                    <div class="section-title">Health Tracker</div>
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-outline-primary btn-sm" type="button">Daily</button>
                        <button class="btn btn-outline-primary btn-sm" type="button">Weekly</button>
                        <button class="btn btn-outline-primary btn-sm" type="button">Monthly</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6"><div class="tracker-card"><h6>Blood Pressure</h6><div class="text-muted"><?php echo htmlspecialchars(($biomarker['systolic_blood_pressure'] ?? 'N/A')); ?>/<?php echo htmlspecialchars(($biomarker['diastolic_blood_pressure'] ?? 'N/A')); ?> mmHg</div><div class="progress mt-2"><div class="progress-bar" style="width: 72%;"></div></div></div></div>
                        <div class="col-sm-6"><div class="tracker-card"><h6>Blood Sugar</h6><div class="text-muted"><?php echo htmlspecialchars(($biomarker['glucose_post_fast'] ?? ($biomarker['non_fasting_glucose'] ?? 'N/A'))); ?> mg/dL</div><div class="progress mt-2"><div class="progress-bar bg-success" style="width: 64%;"></div></div></div></div>
                        <div class="col-sm-6"><div class="tracker-card"><h6>Heart Rate</h6><div class="text-muted">N/A</div><div class="progress mt-2"><div class="progress-bar bg-info" style="width: 40%;"></div></div></div></div>
                        <div class="col-sm-6"><div class="tracker-card"><h6>Oxygen Level</h6><div class="text-muted">N/A</div><div class="progress mt-2"><div class="progress-bar bg-primary" style="width: 40%;"></div></div></div></div>
                        <div class="col-sm-6"><div class="tracker-card"><h6>Weight</h6><div class="text-muted"><?php echo htmlspecialchars((string)($weightKg ?? $patientData['weight'])); ?> kg</div><div class="progress mt-2"><div class="progress-bar bg-warning" style="width: 52%;"></div></div></div></div>
                        <div class="col-sm-6"><div class="tracker-card"><h6>Temperature</h6><div class="text-muted">N/A</div><div class="progress mt-2"><div class="progress-bar bg-danger" style="width: 45%;"></div></div></div></div>
                    </div>
                    <?php if (!empty($weightTrend)): ?>
                        <div class="mt-3">
                            <canvas id="healthTrendChart" height="130"></canvas>
                            <div class="text-muted mt-2">Last updated: <?php echo date('M j, Y'); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="text-muted mt-2">Last updated: <?php echo date('M j, Y'); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="section-card" id="medicines">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0">Medicines & Prescriptions</div>
            </div>
            <div class="table-responsive">
                <table class="table medicine-table align-middle">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Dosage</th>
                            <th>Duration</th>
                            <th>Timing</th>
                            <th>Prescribed By</th>
                            <th>Instructions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($medications)): ?>
                            <?php foreach ($medications as $med): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($med['medicine_name'] ?? ''); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($med['generic_name'] ?? ''); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($med['dosage'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($med['duration_days'] ?? '-')) . ' days'; ?></td>
                                    <td><?php echo htmlspecialchars(trim((string) ($med['frequency'] ?? '') . ' ' . (string) ($med['food_timing'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars(trim((string) ($med['doctor_fname'] ?? '') . ' ' . (string) ($med['doctor_lname'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars($med['instructions'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No prescriptions on file.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="section-card emergency-card">
                    <h5 class="mb-3">Emergency Support</h5>
                    <p class="mb-2">Immediate assistance and rapid response contacts.</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="pill" style="background: rgba(255,255,255,0.2); color: #fff;">Ambulance: 108</span>
                        <span class="pill" style="background: rgba(255,255,255,0.2); color: #fff;">Emergency: <?php echo htmlspecialchars($hospital_info['emergency_helpline'] ?? ''); ?></span>
                    </div>
                    <a class="btn" href="tel:<?php echo htmlspecialchars($hospital_info['emergency_helpline'] ?? ''); ?>">SOS Call Now</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="section-title">Chat & Consultation</div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong>AI Health Assistant</strong>
                            <div class="text-muted">Ask questions anytime</div>
                        </div>
                        <a class="btn btn-outline-primary btn-sm" href="<?php echo $localhost; ?>video_consultation.php">Start Chat</a>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Recent Consultation</strong>
                            <div class="text-muted">Video visits and follow-ups</div>
                        </div>
                        <a class="btn btn-primary btn-sm" href="<?php echo $localhost; ?>video_consultation.php">Start Consultation</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4" id="reports">
            <div class="col-lg-12">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="section-title mb-0">Reports</div>
                        <a class="btn btn-outline-primary btn-sm" href="<?php echo $localhost; ?>patient_reports.php">Upload Report</a>
                    </div>
                    <div class="row g-3 mt-2">
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $report): ?>
                                <?php $link = medc_report_link($localhost, (string) ($report['file_path'] ?? '')); ?>
                                <div class="col-12">
                                    <div class="report-card d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($report['report_type'] ?? 'Report'); ?></h6>
                                            <div class="text-muted"><?php echo htmlspecialchars($report['report_date'] ?? ''); ?> | <?php echo htmlspecialchars($report['referred_by'] ?? ''); ?></div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <?php if ($link): ?>
                                                <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($link); ?>" target="_blank">View</a>
                                                <a class="btn btn-outline-secondary btn-sm" href="<?php echo htmlspecialchars($link); ?>" target="_blank">Download</a>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Unavailable</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-muted">No reports uploaded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4" id="settings">
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="section-title">Health Score & Analytics</div>
                    <div class="health-score">
                        <div class="score-circle">82</div>
                        <div>
                            <div class="text-muted">Improved by 4 points this month</div>
                            <div class="mt-2"><span class="pill">Risk Level: Low</span></div>
                            <ul class="mt-3">
                                <li>Continue hydration and balanced meals.</li>
                                <li>Track blood pressure twice weekly.</li>
                                <li>Maintain 7-8 hours sleep schedule.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="section-title">Settings & Preferences</div>
                    <ul class="settings-list list-unstyled mb-0">
                        <li><button class="settings-action" type="button" data-action="profile"><i class="fas fa-user-edit text-primary"></i><span data-i18n="editProfile">Edit Profile</span></button></li>
                        <li><button class="settings-action" type="button" data-action="password"><i class="fas fa-key text-primary"></i><span data-i18n="changePassword">Change Password</span></button></li>
                        <li><button class="settings-action" type="button" data-action="notifications"><i class="fas fa-bell text-primary"></i><span data-i18n="notifications">Notification Preferences</span></button></li>
                        <li><button class="settings-action" type="button" data-action="language"><i class="fas fa-language text-primary"></i><span data-i18n="language">Language Selection</span></button></li>
                        <li><button class="settings-action" type="button" data-action="privacy"><i class="fas fa-shield-alt text-primary"></i><span data-i18n="privacy">Privacy Settings</span></button></li>
                        <li><button class="settings-action" type="button" data-action="theme"><i class="fas fa-moon text-primary"></i><span data-i18n="theme">Light/Dark Mode</span></button></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-backdrop-blur" id="settingsModalBackdrop"></div>

    <div class="center-modal" id="profileModal" aria-hidden="true">
        <div class="modal-header">
            <div>
                <h5 class="mb-0" data-i18n="editProfile">Edit Profile</h5>
                <div class="text-muted" data-i18n="editProfileSub">Update your personal details.</div>
            </div>
            <button class="btn btn-light" type="button" data-close="settings-modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="profileForm">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars(trim($patientData['firstname'] . ' ' . $patientData['lastname'])); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($patientData['email'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($patientData['contact'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Profile Photo</label>
                <input type="file" class="form-control" name="photo" accept="image/*">
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save Changes</button>
                <button class="btn btn-outline-secondary" type="button" data-close="settings-modal">Cancel</button>
            </div>
        </form>
    </div>

    <div class="center-modal" id="passwordModal" aria-hidden="true">
        <div class="modal-header">
            <h5 class="mb-0" data-i18n="changePassword">Change Password</h5>
            <button class="btn btn-light" type="button" data-close="settings-modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="passwordForm">
            <div class="mb-3 position-relative">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-control" name="currentPassword" required>
                <button class="toggle-visibility" type="button" data-target="currentPassword"><i class="fas fa-eye"></i></button>
            </div>
            <div class="mb-3 position-relative">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="newPassword" required>
                <button class="toggle-visibility" type="button" data-target="newPassword"><i class="fas fa-eye"></i></button>
            </div>
            <div class="mb-3 position-relative">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirmPassword" required>
                <button class="toggle-visibility" type="button" data-target="confirmPassword"><i class="fas fa-eye"></i></button>
                <div class="error-text" id="passwordError"></div>
            </div>
            <button class="btn btn-primary w-100" type="submit">Update Password</button>
        </form>
    </div>

    <div class="center-modal" id="notificationsModal" aria-hidden="true">
        <div class="modal-header">
            <div>
                <h5 class="mb-0" data-i18n="notifications">Notification Preferences</h5>
                <div class="text-muted" data-i18n="notificationsSub">Choose how you receive updates.</div>
            </div>
            <button class="btn btn-light" type="button" data-close="settings-modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">App Notifications</div>
                <div class="text-muted">Receive alerts in the app.</div>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">Email Notifications</div>
                <div class="text-muted">Get updates by email.</div>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">SMS Alerts</div>
                <div class="text-muted">Text message reminders.</div>
            </div>
            <label class="switch">
                <input type="checkbox">
                <span class="slider"></span>
            </label>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">Assignment Alerts</div>
                <div class="text-muted">Assignments and tasks.</div>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">Attendance Alerts</div>
                <div class="text-muted">Appointment attendance reminders.</div>
            </div>
            <label class="switch">
                <input type="checkbox">
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <div class="center-modal" id="languageModal" aria-hidden="true">
        <div class="modal-header">
            <h5 class="mb-0" data-i18n="language">Language Selection</h5>
            <button class="btn btn-light" type="button" data-close="settings-modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-primary" type="button" data-lang="en">English</button>
            <button class="btn btn-outline-primary" type="button" data-lang="hi">Hindi</button>
            <button class="btn btn-outline-primary" type="button" data-lang="gu">Gujarati</button>
        </div>
    </div>

    <div class="center-modal modal-lg" id="privacyModal" aria-hidden="true">
        <div class="modal-header">
            <div>
                <h5 class="mb-0" data-i18n="privacy">Privacy Settings</h5>
                <div class="text-muted" data-i18n="privacySub">Control visibility and security options.</div>
            </div>
            <button class="btn btn-light" type="button" data-close="settings-modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card">
                    <h6 class="mb-3">Profile Visibility</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">Show profile to others</div>
                            <div class="text-muted">Toggle your public visibility.</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h6 class="mb-3">Data Sharing</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">Share anonymized data</div>
                            <div class="text-muted">Allow research insights.</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h6 class="mb-3">Security Options</h6>
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-outline-primary" type="button">Enable 2FA</button>
                        <button class="btn btn-outline-secondary" type="button">Manage Devices</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h6 class="mb-3">Logout Everywhere</h6>
                    <div class="text-muted mb-3">This will sign you out of all active sessions.</div>
                    <button class="btn btn-danger" type="button">Logout from all devices</button>
                </div>
            </div>
        </div>
    </div>

    <div class="center-modal" id="themeModal" aria-hidden="true">
        <div class="modal-header">
            <div>
                <h5 class="mb-0" data-i18n="theme">Light/Dark Mode</h5>
                <div class="text-muted" data-i18n="themeSub">Switch the app appearance instantly.</div>
            </div>
            <button class="btn btn-light" type="button" data-close="settings-modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <div class="fw-semibold">Enable Dark Mode</div>
                <div class="text-muted">Applies without reloading the page.</div>
            </div>
            <label class="switch">
                <input type="checkbox" id="themeToggle">
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const weightLabels = <?php echo json_encode(array_column($weightTrend, 'label')); ?>;
        const weightValues = <?php echo json_encode(array_column($weightTrend, 'weight')); ?>;
        const chartCtx = document.getElementById('healthTrendChart');
        if (chartCtx && weightLabels.length) {
            new Chart(chartCtx, {
                type: 'line',
                data: {
                    labels: weightLabels,
                    datasets: [{
                        label: 'Weight (kg)',
                        data: weightValues,
                        borderColor: '#0b3ea8',
                        backgroundColor: 'rgba(11, 62, 168, 0.12)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: false } }
                }
            });
        }

        const settingsActions = document.querySelectorAll('.settings-action');
        const settingsBackdrop = document.getElementById('settingsModalBackdrop');
        const settingsModals = document.querySelectorAll('.center-modal');
        const themeToggle = document.getElementById('themeToggle');

        const translations = {
            en: {
                editProfile: 'Edit Profile',
                editProfileSub: 'Update your personal details.',
                changePassword: 'Change Password',
                notifications: 'Notification Preferences',
                notificationsSub: 'Choose how you receive updates.',
                language: 'Language Selection',
                privacy: 'Privacy Settings',
                privacySub: 'Control visibility and security options.',
                theme: 'Light/Dark Mode',
                themeSub: 'Switch the app appearance instantly.'
            },
            hi: {
                editProfile: 'Edit Profile',
                editProfileSub: 'Apni details update karein.',
                changePassword: 'Change Password',
                notifications: 'Notification Preferences',
                notificationsSub: 'Updates kaise mile, chunen.',
                language: 'Language Selection',
                privacy: 'Privacy Settings',
                privacySub: 'Visibility aur security control karein.',
                theme: 'Light/Dark Mode',
                themeSub: 'Theme turant badlein.'
            },
            gu: {
                editProfile: 'Edit Profile',
                editProfileSub: 'Tamari details update karo.',
                changePassword: 'Change Password',
                notifications: 'Notification Preferences',
                notificationsSub: 'Updates kem male te pasand karo.',
                language: 'Language Selection',
                privacy: 'Privacy Settings',
                privacySub: 'Visibility ane security control karo.',
                theme: 'Light/Dark Mode',
                themeSub: 'Theme turant badlo.'
            }
        };

        function closeSettingsModals() {
            settingsModals.forEach(modal => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            });
            settingsBackdrop.classList.remove('show');
        }

        function openSettingsModal(id) {
            closeSettingsModals();
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            settingsBackdrop.classList.add('show');
        }

        function applyLanguage(lang) {
            const strings = translations[lang] || translations.en;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (strings[key]) {
                    el.textContent = strings[key];
                }
            });
            localStorage.setItem('medc-lang', lang);
        }

        settingsActions.forEach(button => {
            button.addEventListener('click', () => {
                const action = button.dataset.action;
                if (action === 'profile') {
                    openSettingsModal('profileModal');
                } else if (action === 'password') {
                    openSettingsModal('passwordModal');
                } else if (action === 'notifications') {
                    openSettingsModal('notificationsModal');
                } else if (action === 'language') {
                    openSettingsModal('languageModal');
                } else if (action === 'privacy') {
                    openSettingsModal('privacyModal');
                } else if (action === 'theme') {
                    openSettingsModal('themeModal');
                }
            });
        });

        document.querySelectorAll('[data-close="settings-modal"]').forEach(button => {
            button.addEventListener('click', closeSettingsModals);
        });

        settingsBackdrop.addEventListener('click', closeSettingsModals);

        window.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeSettingsModals();
            }
        });

        document.querySelectorAll('[data-lang]').forEach(button => {
            button.addEventListener('click', () => {
                applyLanguage(button.dataset.lang);
                closeSettingsModals();
            });
        });

        const forwardedBillCard = document.getElementById('forwarded-bill');
        const closeBillButton = document.querySelector('[data-close-bill]');
        if (forwardedBillCard && closeBillButton) {
            closeBillButton.addEventListener('click', () => {
                forwardedBillCard.style.display = 'none';
            });
        }

        document.querySelectorAll('.toggle-visibility').forEach(button => {
            button.addEventListener('click', () => {
                const target = button.dataset.target;
                const input = document.querySelector(`[name="${target}"]`);
                if (!input) return;
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                button.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        });

        document.getElementById('passwordForm').addEventListener('submit', event => {
            event.preventDefault();
            const form = event.currentTarget;
            const newPassword = form.newPassword.value.trim();
            const confirmPassword = form.confirmPassword.value.trim();
            const errorEl = document.getElementById('passwordError');
            errorEl.textContent = '';

            if (newPassword.length < 6) {
                errorEl.textContent = 'Password must be at least 6 characters.';
                return;
            }
            if (newPassword !== confirmPassword) {
                errorEl.textContent = 'Passwords do not match.';
                return;
            }

            closeSettingsModals();
            form.reset();
        });

        document.getElementById('profileForm').addEventListener('submit', event => {
            event.preventDefault();
            closeSettingsModals();
        });

        function applyTheme(enabled) {
            document.body.classList.toggle('dark-mode', enabled);
            localStorage.setItem('medc-theme', enabled ? 'dark' : 'light');
        }

        if (themeToggle) {
            themeToggle.addEventListener('change', event => {
                applyTheme(event.target.checked);
            });
        }

        const savedTheme = localStorage.getItem('medc-theme');
        if (savedTheme === 'dark' && themeToggle) {
            themeToggle.checked = true;
            applyTheme(true);
        }

        const savedLang = localStorage.getItem('medc-lang') || 'en';
        applyLanguage(savedLang);

    </script>
</body>

</html>
