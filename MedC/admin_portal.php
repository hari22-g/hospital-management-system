<?php
session_start();
require_once 'connection/config.php';
require_once 'include/hospital_info.php';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Debug mode: add ?debug=1 to see where the page hangs
if (isset($_GET['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    header('Content-Type: text/plain; charset=UTF-8');

    echo "admin_portal debug\n";
    echo "session user_type: " . ($_SESSION['user_type'] ?? 'none') . "\n";
    echo "db connected: " . ($conn ? 'yes' : 'no') . "\n\n";

    $today = date('Y-m-d');
    $queries = [
        'appointments_today' => "SELECT COUNT(*) as count FROM appointment WHERE appointment_date = '$today'",
        'patients_created_at_exists' => "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patient' AND COLUMN_NAME = 'created_at' LIMIT 1",
        'patients_total' => "SELECT COUNT(*) as count FROM patient",
        'doctors_count' => "SELECT COUNT(*) as count FROM doctor WHERE approval = 'approved'",
        'revenue_today' => "SELECT SUM(consultation_fees) as total FROM appointment a JOIN doctor d ON a.did = d.d_id WHERE a.appointment_date = '$today' AND a.status = 'Confirmed'",
        'pending_appointments' => "SELECT COUNT(*) as count FROM appointment WHERE status = 'Pending' AND appointment_date <= '$today'"
    ];

    $created_at_available = false;
    foreach ($queries as $label => $sql) {
        $start = microtime(true);
        $res = mysqli_query($conn, $sql);
        $elapsed = number_format(microtime(true) - $start, 4);
        echo $label . " (" . $elapsed . "s): ";
        if (!$res) {
            echo "ERROR " . mysqli_error($conn) . "\n";
            continue;
        }
        $row = mysqli_fetch_assoc($res);
        echo json_encode($row) . "\n";
        if ($label === 'patients_created_at_exists' && !empty($row)) {
            $created_at_available = true;
        }
    }

    if ($created_at_available) {
        $start = microtime(true);
        $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM patient WHERE DATE(created_at) = '$today'");
        $elapsed = number_format(microtime(true) - $start, 4);
        echo "patients_today (" . $elapsed . "s): ";
        if (!$res) {
            echo "ERROR " . mysqli_error($conn) . "\n";
        } else {
            $row = mysqli_fetch_assoc($res);
            echo json_encode($row) . "\n";
        }
    } else {
        echo "patients_today: skipped (created_at missing)\n";
    }

    exit();
}

// Get hospital info
$hospital_info = getHospitalInfo();

// Get today's statistics
$today = date('Y-m-d');
$appointments_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointment WHERE appointment_date = '$today'"))['count'];
$patients_today_query = "SELECT COUNT(*) as count FROM patient";
$created_at_exists = mysqli_query(
    $conn,
    "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patient' AND COLUMN_NAME = 'created_at' LIMIT 1"
);
if ($created_at_exists && mysqli_num_rows($created_at_exists) > 0) {
    $patients_today_query = "SELECT COUNT(*) as count FROM patient WHERE DATE(created_at) = '$today'";
}
$patients_today = mysqli_fetch_assoc(mysqli_query($conn, $patients_today_query))['count'];
$doctors_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM doctor WHERE approval = 'approved'"))['count'];
$revenue_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(consultation_fees) as total FROM appointment a JOIN doctor d ON a.did = d.d_id WHERE a.appointment_date = '$today' AND a.status = 'Confirmed'"))['total'] ?? 0;

// Get emergency alerts (pending appointments)
$emergency_alerts = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointment WHERE status = 'Pending' AND appointment_date <= '$today'") or die(mysqli_error($conn));
$pending_count = mysqli_fetch_assoc($emergency_alerts)['count'];

function medc_table_exists(mysqli $conn, string $table): bool
{
    $table = trim($table);
    if ($table === '') {
        return false;
    }

    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($safeTable === '') {
        return false;
    }

    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($safeTable) . "'");
    return $result && $result->num_rows > 0;
}

function medc_default_medicine_names(): array
{
    return [
        "Paracetamol 500mg",
        "Azithromycin 500mg",
        "Amoxicillin 500mg",
        "Cetrizine 10mg",
        "Pantoprazole 40mg",
        "Omeprazole 20mg",
        "Ibuprofen 400mg",
        "Dolo 650",
        "Crocin 500",
        "Metformin 500mg",
        "Amlodipine 5mg",
        "Losartan 50mg",
        "Vitamin D3",
        "Calcium Tablet",
        "ORS",
        "Cough Syrup",
        "Antacid Syrup",
        "Insulin",
        "Inhaler",
        "Other"
    ];
}

function medc_inventory_status($stockValue)
{
    $stock = (int) $stockValue;
    if ($stock > 100) {
        return ['label' => 'Good', 'className' => 'bg-success'];
    }
    if ($stock > 40) {
        return ['label' => 'Low', 'className' => 'bg-warning'];
    }

    return ['label' => 'Critical', 'className' => 'bg-danger'];
}

$medicineCatalog = [];
$medicineMap = [];

if (medc_table_exists($conn, 'medications')) {
    $medicineQuery = $conn->query("SELECT drug_id, medicine_name, stock_level, unit_price FROM medications ORDER BY medicine_name ASC");
    if ($medicineQuery) {
        while ($row = $medicineQuery->fetch_assoc()) {
            $name = trim((string) ($row['medicine_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $row['drug_id'] = (int) ($row['drug_id'] ?? 0);
            $row['stock_level'] = (int) ($row['stock_level'] ?? 0);
            $row['unit_price'] = (float) ($row['unit_price'] ?? 0);
            $medicineMap[$name] = $row;
        }
        $medicineQuery->free();
    }
}

foreach (medc_default_medicine_names() as $defaultName) {
    if (isset($medicineMap[$defaultName])) {
        $medicineCatalog[] = $medicineMap[$defaultName];
        unset($medicineMap[$defaultName]);
        continue;
    }

    $medicineCatalog[] = [
        'drug_id' => 0,
        'medicine_name' => $defaultName,
        'stock_level' => 0,
        'unit_price' => 0.00
    ];
}

foreach ($medicineMap as $extraMedicine) {
    $medicineCatalog[] = $extraMedicine;
}

function medc_price_from_medicine_catalog(array $medicineCatalog, string $medicineName): float
{
    foreach ($medicineCatalog as $medicine) {
        $name = trim((string) ($medicine['medicine_name'] ?? ''));
        if ($name !== '' && strcasecmp($name, $medicineName) === 0) {
            return (float) ($medicine['unit_price'] ?? 0);
        }
    }

    return 0.0;
}

function medc_build_billing_prescription_map(mysqli $conn, array $medicineCatalog): array
{
    $map = [];

    if (medc_table_exists($conn, 'prescriptions') && medc_table_exists($conn, 'prescription_medicines')) {
        $sql = "SELECT pr.prescription_id, pr.patient_id, pr.doctor_id, pr.created_at, pr.status,
                       p.firstname, p.lastname, p.email,
                       d.f_name, d.l_name,
                       pm.medicine_name, pm.quantity, pm.unit_price, pm.dosage, pm.frequency, pm.food_timing, pm.duration_days, pm.instructions
                FROM prescriptions pr
                INNER JOIN patient p ON p.pid = pr.patient_id
                LEFT JOIN doctor d ON d.d_id = pr.doctor_id
                LEFT JOIN prescription_medicines pm ON pm.prescription_id = pr.prescription_id
                ORDER BY pr.created_at DESC, pm.medicine_id ASC";
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pid = (int) ($row['patient_id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }

                if (!isset($map[$pid])) {
                    $doctorName = trim((string) ($row['f_name'] ?? '') . ' ' . (string) ($row['l_name'] ?? ''));
                    $map[$pid] = [
                        'prescription_id' => (int) ($row['prescription_id'] ?? 0),
                        'patient_id' => $pid,
                        'patient_name' => trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? '')),
                        'patient_email' => (string) ($row['email'] ?? ''),
                        'doctor_name' => $doctorName !== '' ? $doctorName : 'Doctor',
                        'status' => (string) ($row['status'] ?? ''),
                        'created_at' => (string) ($row['created_at'] ?? ''),
                        'items' => [],
                        'total' => 0.0
                    ];
                }

                $medicineName = trim((string) ($row['medicine_name'] ?? ''));
                if ($medicineName === '') {
                    continue;
                }

                $quantity = (float) ($row['quantity'] ?? 1);
                if ($quantity <= 0) {
                    $quantity = 1;
                }

                $unitPrice = (float) ($row['unit_price'] ?? 0);
                if ($unitPrice <= 0) {
                    $unitPrice = medc_price_from_medicine_catalog($medicineCatalog, $medicineName);
                }

                $amount = $quantity * $unitPrice;
                $map[$pid]['items'][] = [
                    'medicine_name' => $medicineName,
                    'dosage' => (string) ($row['dosage'] ?? ''),
                    'frequency' => (string) ($row['frequency'] ?? ''),
                    'food_timing' => (string) ($row['food_timing'] ?? ''),
                    'duration_days' => (string) ($row['duration_days'] ?? ''),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'instructions' => (string) ($row['instructions'] ?? '')
                ];
                $map[$pid]['total'] += $amount;
            }
            $result->free();
        }
    } elseif (medc_table_exists($conn, 'doctor_prescriptions')) {
        $sql = "SELECT dp.prescription_id, dp.pid, dp.did, dp.medicine_name, dp.dosage, dp.duration, dp.instructions, dp.created_at,
                       p.firstname, p.lastname, p.email,
                       d.f_name, d.l_name
                FROM doctor_prescriptions dp
                INNER JOIN patient p ON p.pid = dp.pid
                LEFT JOIN doctor d ON d.d_id = dp.did
                ORDER BY dp.created_at DESC, dp.prescription_id DESC";
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pid = (int) ($row['pid'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }

                if (!isset($map[$pid])) {
                    $doctorName = trim((string) ($row['f_name'] ?? '') . ' ' . (string) ($row['l_name'] ?? ''));
                    $map[$pid] = [
                        'prescription_id' => (int) ($row['prescription_id'] ?? 0),
                        'patient_id' => $pid,
                        'patient_name' => trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? '')),
                        'patient_email' => (string) ($row['email'] ?? ''),
                        'doctor_name' => $doctorName !== '' ? $doctorName : 'Doctor',
                        'status' => 'Draft',
                        'created_at' => (string) ($row['created_at'] ?? ''),
                        'items' => [],
                        'total' => 0.0
                    ];
                }

                $medicineName = trim((string) ($row['medicine_name'] ?? ''));
                if ($medicineName === '') {
                    continue;
                }

                $unitPrice = medc_price_from_medicine_catalog($medicineCatalog, $medicineName);
                $amount = $unitPrice;
                $map[$pid]['items'][] = [
                    'medicine_name' => $medicineName,
                    'dosage' => (string) ($row['dosage'] ?? ''),
                    'frequency' => '',
                    'food_timing' => '',
                    'duration_days' => (string) ($row['duration'] ?? ''),
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'instructions' => (string) ($row['instructions'] ?? '')
                ];
                $map[$pid]['total'] += $amount;
            }
            $result->free();
        }
    }

    foreach ($map as $pid => $bundle) {
        if (empty($bundle['items'])) {
            unset($map[$pid]);
            continue;
        }
        $map[$pid]['total'] = round((float) $bundle['total'], 2);
    }

    return $map;
}

$billingPrescriptionMap = medc_build_billing_prescription_map($conn, $medicineCatalog);

function infer_department_from_specialization($specialization)
{
    $value = strtolower(trim((string) $specialization));
    if ($value === '') {
        return '-';
    }

    $mapping = [
        'general physician' => 'General Medicine',
        'cardiologist' => 'Cardiology',
        'dermatologist' => 'Dermatology',
        'neurologist' => 'Neurology',
        'neurosurgeon' => 'Neurosurgery',
        'orthopedic surgeon' => 'Orthopedics',
        'pediatrician' => 'Pediatrics',
        'gynecologist' => 'Gynecology',
        'obstetrician' => 'Obstetrics',
        'ent specialist' => 'ENT',
        'ophthalmologist' => 'Ophthalmology',
        'dentist' => 'Dental',
        'psychiatrist' => 'Psychiatry',
        'psychologist' => 'Psychology',
        'urologist' => 'Urology',
        'nephrologist' => 'Nephrology',
        'gastroenterologist' => 'Gastroenterology',
        'endocrinologist' => 'Endocrinology',
        'pulmonologist' => 'Pulmonology',
        'oncologist' => 'Oncology',
        'radiologist' => 'Radiology',
        'pathologist' => 'Pathology',
        'general surgeon' => 'General Surgery',
        'laparoscopic surgeon' => 'Laparoscopic Surgery',
        'anesthesiologist' => 'Anesthesia',
        'rheumatologist' => 'Rheumatology',
        'immunologist' => 'Immunology',
        'physiotherapist' => 'Physiotherapy',
        'emergency medicine specialist' => 'Emergency',
        'icu specialist' => 'ICU',
        'family medicine' => 'Family Medicine',
        'internal medicine' => 'Internal Medicine',
        'vascular surgeon' => 'Vascular Surgery',
        'plastic surgeon' => 'Plastic Surgery',
        'cosmetologist' => 'Cosmetology',
        'sexologist' => 'General Medicine',
        'nutritionist' => 'Nutrition & Dietetics',
        'diabetologist' => 'Diabetology',
        'pain management specialist' => 'Pain Management'
    ];

    return $mapping[$value] ?? '-';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) ($hospital_info['hospital_name'] ?? 'MedC Hospital'), ENT_QUOTES, 'UTF-8'); ?> Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="include/admin_portal_base.css">
    <style>
        :root {
            --hospital-blue: #2f74de;
            --hospital-blue-dark: #214f98;
            --hospital-blue-soft: #eff6ff;
            --hospital-border: #d8e6fb;
            --hospital-teal: #1da8d6;
            --hospital-red: #e95a73;
            --hospital-ink: #214b90;
            --hospital-muted: #6d86b0;
            --hospital-surface: rgba(255, 255, 255, 0.96);
            --hospital-mint: #f1fffb;
            --hospital-lavender: #f7f4ff;
            --hospital-shadow: 0 18px 45px rgba(36, 93, 179, 0.14);
        }
        html {
            min-height: 100%;
            background: #ffffff;
        }
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            color: var(--hospital-ink);
            min-height: 100vh;
            padding: 12px;
            background: #ffffff;
            overflow-x: hidden;
        }
        .text-icon {
            display: inline-block;
            width: 1.2em;
            text-align: center;
            font-weight: 700;
            line-height: 1;
            color: var(--hospital-blue);
        }
        .medicine-preview-card {
            border: 1px solid rgba(47, 116, 222, 0.16);
            border-radius: 18px;
            padding: 16px 18px;
            background:
                linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(255, 255, 255, 0.98));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.95), 0 12px 24px rgba(36, 93, 179, 0.08);
        }
        .medicine-preview-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--hospital-ink);
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .medicine-preview-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .medicine-preview-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(47, 116, 222, 0.12);
            color: var(--hospital-ink);
            font-size: 0.92rem;
            font-weight: 600;
        }
        .medicine-preview-pill .label {
            color: var(--hospital-muted);
            font-weight: 600;
        }
        .medicine-preview-empty {
            color: var(--hospital-muted);
            font-size: 0.98rem;
        }
        .sidebar {
            background:
                radial-gradient(circle at left bottom, rgba(47, 116, 222, 0.12), transparent 45%),
                linear-gradient(180deg, #ffffff 0%, #f9fbff 70%, #eef5ff 100%);
            color: var(--hospital-ink);
            top: 12px;
            left: 12px;
            bottom: 12px;
            position: fixed;
            width: 352px;
            padding: 14px;
            box-shadow: var(--hospital-shadow);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 24px;
            border: 1px solid rgba(216, 230, 251, 0.95);
        }
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 6px 2px 8px 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(47, 116, 222, 0.35) rgba(216, 230, 251, 0.65);
        }
        .sidebar-nav::-webkit-scrollbar {
            width: 8px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(216, 230, 251, 0.7);
            border-radius: 999px;
            margin: 12px 0;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(47, 116, 222, 0.34);
            border-radius: 999px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(47, 116, 222, 0.52);
        }
        .main-content {
            margin-left: 364px;
            min-height: calc(100vh - 24px);
            padding: 20px 24px 36px;
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            border: 1px solid rgba(216, 230, 251, 0.92);
            background: #ffffff;
            box-shadow: var(--hospital-shadow);
        }
        .stat-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(251,253,255,0.94) 100%);
            border-radius: 22px;
            border: 1px solid var(--hospital-border);
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 14px 32px rgba(45, 101, 183, 0.10);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease, background 0.25s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px rgba(45, 101, 183, 0.14);
        }
        .doctor-form-wrapper {
            margin-bottom: 28px;
            margin-top: 18px;
            display: none;
        }
        .doctor-form-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #d9e5fb;
            overflow: hidden;
            box-shadow: 0 22px 50px rgba(18, 53, 112, 0.12);
        }
        .doctor-form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            padding: 22px 24px 18px;
            margin-bottom: 0;
            border-bottom: 1px solid #dbe7fb;
            background: linear-gradient(135deg, #f4f8ff 0%, #eef5ff 100%);
        }
        .doctor-form-header h4 {
            margin-bottom: 6px;
            color: var(--hospital-blue-dark);
            font-weight: 700;
        }
        .doctor-form-header h4 .text-icon {
            color: #2f74de;
        }
        .doctor-form-subtitle {
            margin: 6px 0 0;
            color: var(--hospital-muted);
            font-size: 0.95rem;
        }
        .doctor-id-pill {
            background: #eef6ff;
            border: 1px solid #cfe0fb;
            border-radius: 999px;
            padding: 10px 18px;
            min-width: 160px;
            text-align: center;
            box-shadow: 0 12px 24px rgba(45, 101, 183, 0.08);
        }
        .doctor-id-pill span {
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.7rem;
            color: var(--hospital-muted);
        }
        .doctor-id-pill strong {
            display: block;
            font-size: 1.1rem;
            color: var(--hospital-blue-dark);
        }
        .doctor-form-body {
            padding: 18px;
        }
        .doctor-section-grid {
            display: grid;
            gap: 12px;
        }
        .doctor-section-card {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border: 1px solid var(--hospital-border);
            border-radius: 20px;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(38, 89, 160, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .doctor-section-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(38, 89, 160, 0.12);
            border-color: #bcd2f6;
        }
        .doctor-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: var(--hospital-blue-dark);
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e8effa;
        }
        .doctor-section-title i {
            color: var(--hospital-blue);
        }
        .doctor-section-card.section-wide {
            grid-column: 1 / -1;
        }
        .required {
            color: var(--hospital-red);
            margin-left: 4px;
            font-weight: 700;
        }
        .field-error {
            color: #d14661;
            font-size: 0.85rem;
            margin-top: 6px;
            min-height: 1em;
        }
        .form-control.is-invalid,
        .form-select.is-invalid,
        .form-check-input.is-invalid {
            border-color: #d14661;
        }
        .doctor-form-card .form-control,
        .doctor-form-card .form-select,
        .doctor-form-card textarea.form-control {
            border-radius: 14px;
            border-color: #d9e4f5;
            padding: 11px 14px;
            background: #ffffff;
        }
        .doctor-form-card .mb-3 {
            margin-bottom: 0.75rem !important;
        }
        .doctor-form-card .row {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 0.35rem;
        }
        .doctor-form-card .form-control:focus,
        .doctor-form-card .form-select:focus,
        .doctor-form-card textarea.form-control:focus {
            border-color: #2f74de;
            box-shadow: 0 0 0 0.15rem rgba(47, 116, 222, 0.12);
        }
        .doctor-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .doctor-toggle input[type="checkbox"] {
            appearance: none;
            width: 46px;
            height: 26px;
            background: #dce8fb;
            border-radius: 999px;
            position: relative;
            outline: none;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .doctor-toggle input[type="checkbox"]::after {
            content: '';
            width: 20px;
            height: 20px;
            background: #ffffff;
            border-radius: 50%;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: transform 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }
        .doctor-toggle input[type="checkbox"]:checked {
            background: #2f74de;
        }
        .doctor-toggle input[type="checkbox"]:checked::after {
            transform: translateX(20px);
        }
        .doctor-toggle label {
            margin: 0;
            font-weight: 600;
            color: var(--hospital-ink);
        }
        .working-days {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .working-days label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f3f8ff;
            border: 1px solid #cfe0fb;
            border-radius: 999px;
            padding: 6px 12px;
            cursor: pointer;
            font-weight: 600;
            color: var(--hospital-ink);
            transition: transform 0.2s ease, background 0.2s ease;
        }
        .working-days label:hover {
            transform: translateY(-1px);
            background: #eaf2ff;
        }
        .working-days input {
            margin: 0;
        }
        .doctor-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e7effa;
        }
        .doctor-actions .btn {
            border-radius: 999px;
            padding: 10px 18px;
            min-width: 120px;
            font-weight: 700;
        }
        #doctorFormMessage {
            margin-bottom: 18px;
        }
        #photoPreview {
            padding-top: 10px;
        }
        #photoPreviewImg {
            border: 1px solid #d9e5fb;
            box-shadow: 0 10px 20px rgba(18, 53, 112, 0.08);
        }
        .doctor-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #1f9d6a;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 14px 30px rgba(31, 157, 106, 0.25);
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            z-index: 1200;
        }
        .doctor-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        @media (min-width: 992px) {
            .doctor-section-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 991.98px) {
            .doctor-form-header {
                flex-direction: column;
            }
        }
        .summary-card {
            cursor: pointer;
            min-height: 136px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            padding: 24px 18px;
            text-align: center;
        }
        .summary-card:hover {
            border-color: #9ec3fb;
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .stat-card h1,
        .stat-card h2,
        .stat-card h3,
        .stat-card h4 {
            color: var(--hospital-ink);
        }
        .stat-card p,
        .stat-card label,
        .stat-card small,
        .stat-card li,
        .stat-card span {
            color: var(--hospital-muted);
        }
        .metric-value {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1;
            color: var(--hospital-blue-dark);
        }
        .metric-label {
            margin: 0;
            font-size: 1rem;
            color: var(--hospital-ink) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
        }
        .metric-label i {
            color: #5c8fc4;
        }
        .metric-card--neutral {
            background: linear-gradient(180deg, #ffffff 0%, #f9fcff 100%);
        }
        .metric-card--mint {
            background: linear-gradient(135deg, #f8fffd 0%, var(--hospital-mint) 100%);
        }
        .metric-card--lavender {
            background: linear-gradient(135deg, #fbfbff 0%, var(--hospital-lavender) 100%);
        }
        .metric-card--revenue {
            background: linear-gradient(135deg, #20b1dd 0%, #1796cd 100%);
            border-color: rgba(23, 150, 205, 0.30);
            box-shadow: 0 18px 32px rgba(28, 145, 197, 0.22);
        }
        .metric-card--revenue .metric-value,
        .metric-card--revenue .metric-label,
        .metric-card--revenue .metric-label i {
            color: #fff !important;
        }
        .panel-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .panel-icon {
            color: var(--hospital-blue);
        }
        .dashboard-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 4px 18px;
            margin-bottom: 14px;
            border-bottom: 1px solid #dce8fb;
            font-size: 2.25rem;
            color: var(--hospital-blue-dark);
        }
        .dashboard-heading i {
            color: var(--hospital-blue-dark);
            font-size: 1.9rem;
        }
        .nav-item {
            border-bottom: 1px solid rgba(216, 230, 251, 0.82);
        }
        .nav-item:last-child {
            border-bottom: none;
        }
        .nav-link {
            color: var(--hospital-blue-dark);
            padding: 17px 20px;
            border-radius: 18px;
            margin: 4px 0;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 60px;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #4ca3ff 0%, #68bdfb 100%);
            color: white;
            transform: translateX(6px);
            box-shadow: 0 14px 28px rgba(58, 132, 235, 0.25);
        }
        .nav-link:hover .nav-symbol,
        .nav-link.active .nav-symbol {
            color: white;
        }
        .nav-symbol {
            width: 18px;
            font-size: 1.05rem;
            color: var(--hospital-blue-dark);
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            line-height: 1;
            flex: 0 0 18px;
        }
        .nav-link > span:not(.nav-symbol) {
            flex: 1;
        }
        .logo-section {
            text-align: center;
            padding: 10px 10px 8px;
            margin: 0 0 4px;
            border-radius: 22px;
            border: 1px solid #e4edf9;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 12px 28px rgba(45, 101, 183, 0.10);
        }
        .logo-section img {
            width: 100%;
            max-width: 300px;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            background: transparent;
            padding: 0;
            object-fit: contain;
            border-radius: 0;
            border: none;
            box-shadow: none;
        }
        .logo-section .portal-label {
            margin: 4px 0 0;
            color: #2d67b1;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .language-selector {
            padding: 6px 10px;
            margin: 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }
        .emergency-alert {
            background: linear-gradient(135deg, #ff8398, var(--hospital-red));
            color: white;
            padding: 18px 20px;
            border-radius: 18px;
            margin-bottom: 22px;
            box-shadow: 0 16px 30px rgba(233, 90, 115, 0.22);
        }
        .revenue-card {
            color: white;
        }
        .performance-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }
        .performance-chip {
            background: linear-gradient(180deg, #f4f8ff 0%, #edf5ff 100%);
            color: var(--hospital-blue-dark);
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 600;
            border: 1px solid #e1ebfb;
        }
        .calendar-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .calendar-grid th,
        .calendar-grid td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: center;
        }
        .calendar-grid th {
            background: #f5f9ff;
            font-weight: 700;
        }
        .calendar-day {
            height: 38px;
        }
        .calendar-today {
            background: var(--hospital-blue);
            color: #fff;
            font-weight: 700;
            border-radius: 6px;
        }
        .calendar-muted {
            color: #9aa3af;
        }
        .chart-controls {
            display: flex;
            gap: 10px;
            margin: 8px 0 14px;
            flex-wrap: wrap;
        }
        .chart-btn {
            border: 1px solid var(--hospital-blue);
            background: #fff;
            color: var(--hospital-blue);
            padding: 8px 16px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 0.98rem;
            font-weight: 600;
            box-shadow: 0 8px 18px rgba(47, 116, 222, 0.08);
        }
        .chart-btn.active {
            background: linear-gradient(135deg, var(--hospital-blue) 0%, #4e8ff3 100%);
            color: #fff;
        }
        .chart-tooltip {
            position: absolute;
            background: rgba(33, 75, 144, 0.96);
            color: #fff;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 0.78rem;
            pointer-events: none;
            transform: translate(-50%, -120%);
            white-space: nowrap;
            display: none;
            box-shadow: 0 12px 24px rgba(33, 75, 144, 0.18);
        }
        .chart-wrap {
            position: relative;
        }
        .main-content > div > h1:first-child {
            color: var(--hospital-ink);
            font-weight: 800;
        }
        .table thead th,
        .table-light th {
            background: #eff5ff;
            color: var(--hospital-ink);
        }
        .list-group-flush .list-group-item {
            background: transparent;
            color: var(--hospital-blue-dark);
            border-left: none;
            border-right: none;
            border-top: none;
            border-bottom: 1px solid #e3ecfb;
            padding: 15px 8px;
            font-size: 1rem;
        }
        .list-group-flush .list-group-item:first-child {
            border-top: 1px solid #e3ecfb;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 72px;
            padding: 0.42rem 0.8rem;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0.01em;
            text-align: center;
            text-shadow: none;
            box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.08);
        }
        .badge.bg-success {
            background: linear-gradient(180deg, #21a15f 0%, #17884f 100%);
            color: #ffffff;
        }
        .badge.bg-warning {
            background: linear-gradient(180deg, #ffd86f 0%, #ffc83d 100%);
            color: #523900;
        }
        .badge.bg-danger {
            background: linear-gradient(180deg, #ef6c84 0%, #df4665 100%);
            color: #ffffff;
        }
        .badge.bg-info {
            background: linear-gradient(180deg, #46b6f5 0%, #2196e3 100%);
            color: #ffffff;
        }
        .form-control,
        .form-select,
        textarea,
        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="file"] {
            border-radius: 14px;
            border-color: #d7e4fb;
            padding: 0.8rem 0.95rem;
            color: var(--hospital-blue-dark);
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: inset 0 1px 2px rgba(47, 116, 222, 0.03);
        }
        .form-control:focus,
        .form-select:focus,
        textarea:focus,
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="number"]:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #8eb8f8;
            box-shadow: 0 0 0 4px rgba(47, 116, 222, 0.12);
        }
        .btn {
            border-radius: 14px;
            box-shadow: 0 10px 22px rgba(47, 116, 222, 0.12);
        }
        .btn-primary,
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--hospital-blue) 0%, #4b8cf1 100%);
            border-color: var(--hospital-blue);
        }
        .btn-success,
        .btn-outline-success:hover {
            background: linear-gradient(135deg, var(--hospital-teal) 0%, #2eb9d7 100%);
            border-color: var(--hospital-teal);
        }
        .btn-outline-primary {
            color: var(--hospital-blue);
            border-color: var(--hospital-blue);
        }
        .btn-outline-success {
            color: var(--hospital-teal);
            border-color: var(--hospital-teal);
        }
        .btn-secondary {
            background: #edf4ff;
            color: var(--hospital-blue-dark);
            border-color: #d6e5fb;
        }
        .btn-light {
            background: rgba(255,255,255,0.96);
            color: var(--hospital-blue-dark);
            border-color: rgba(255,255,255,0.96);
        }
        .btn-outline-danger {
            background: transparent;
            color: #d9536b;
            border-color: #f0b6c1;
        }
        .btn-outline-danger:hover {
            background: #d9536b;
            color: #fff;
            border-color: #d9536b;
        }
        @media (max-width: 1200px) {
            .sidebar {
                width: 320px;
            }
            .main-content {
                margin-left: 332px;
            }
        }
        @media (max-width: 991px) {
            body {
                padding: 10px;
            }
            .sidebar {
                width: 100%;
                position: relative;
                top: auto;
                left: auto;
                bottom: auto;
                height: auto;
                overflow: visible;
                margin-bottom: 14px;
            }
            .sidebar-nav {
                overflow: visible;
            }
            .main-content {
                margin-left: 0;
                padding: 18px;
                min-height: auto;
            }
            .dashboard-heading {
                font-size: 1.9rem;
            }
        }
        .form-message {
            margin-bottom: 10px;
            font-weight: 600;
        }
        .form-message.success {
            color: #198754;
        }
        .form-message.error {
            color: #dc3545;
        }
        #healthDataDisplay {
            border-radius: 18px;
            border: 1px solid #dbe9ff;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 18px;
        }
        #healthDataDisplay .alert {
            border-radius: 14px;
            margin-bottom: 16px;
        }
        #healthDataDisplay h5,
        #healthDataDisplay h6 {
            color: #214b90;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 1.02rem;
        }
        #healthDataDisplay .table-responsive {
            border: 1px solid #e2ecfd;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        #healthDataDisplay .table {
            margin-bottom: 0;
            font-size: 0.95rem;
        }
        #healthDataDisplay .table thead th {
            background: #edf4ff;
            color: #214b90;
            font-weight: 700;
        }
        #healthDataDisplay .btn-sm {
            font-size: 0.85rem;
            padding: 0.35rem 0.72rem;
            border-radius: 10px;
        }
        #healthDataDisplay .form-control,
        #healthDataDisplay .form-select {
            min-height: 44px;
            font-size: 0.94rem;
        }
        #healthDataDisplay .input-group .btn {
            min-width: 112px;
        }
        #healthDataDisplay ul {
            margin-bottom: 0;
        }
        #healthDataDisplay .text-muted {
            color: #5d7da9 !important;
        }
        .health-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid #d4e4ff;
            background: linear-gradient(135deg, #f6faff 0%, #edf4ff 100%);
            margin-bottom: 14px;
        }
        .health-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .health-select-card {
            padding-bottom: 12px;
        }
        .health-select-card .row.g-2 {
            margin-bottom: 6px !important;
        }
        .health-select-card .table-responsive {
            margin-top: 8px !important;
        }
        .health-panel-grid .health-card {
            background: #ffffff;
            border: 1px solid #dce8fd;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 8px 18px rgba(45, 101, 183, 0.08);
            height: 100%;
        }
        .health-panel-grid .health-card h6 {
            margin-bottom: 12px;
            font-size: 0.98rem;
            letter-spacing: 0.01em;
        }
        .health-card .table thead th {
            font-size: 0.86rem;
        }
        .health-card .timeline-list {
            max-height: 230px;
            overflow: auto;
        }
        .health-card .timeline-list li {
            padding-bottom: 8px;
            border-bottom: 1px dashed #d8e6fb;
            margin-bottom: 8px;
        }
        .health-card .timeline-list li:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .table-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .form-actions {
            display: flex;
            gap: 8px;
        }
        .section-header-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            position: relative;
            z-index: 2;
        }
        .section-header-actions h1 {
            margin-bottom: 0;
        }
        #toggleDoctorFormBtn {
            position: relative;
            z-index: 3;
            pointer-events: auto;
        }
        .doctor-form-column {
            display: none;
        }

        /* Style Google Translate Dropdown */
        #google_translate_element {
            display: flex;
            align-items: center;
            height: 50px;
        }

        /* Style the Google Translate frame */
        .goog-te-gadget {
            color: white !important;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        /* Hide the "powered by Google" text */
        .goog-te-gadget span {
            display: none;
        }

        /* Style the dropdown button */
        .goog-te-combo {
            background-color: transparent;
            color: white !important;
            border: 2px solid white;
            border-radius: 5px;
            padding: 5px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Change dropdown color on hover */
        .goog-te-combo:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Style the translate toolbar */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        .goog-te-combo option {
            color: black !important;
            background-color: white !important;
        }

        .goog-te-gadget span,
        .goog-te-gadget img {
            display: none !important;
        }

        /* Ensure the select box remains visible */
        .goog-te-combo {
            color: white !important;
            font-size: 14px !important;
            border: 2px solid white;
            border-radius: 5px;
            padding: 5px;
        }

        /* Hide the footer */
        .goog-te-footer {
            display: none !important;
        }

        .goog-logo-link,
        .goog-te-gadget img {
            display: none !important;
            visibility: hidden !important;
        }
    </style>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en'
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>
<body>
    <div class="sidebar">
        <div class="logo-section">
            <img src="include/homepage_slider/aarogya_logo.svg" alt="AAROGYA Logo" onerror="this.src='include/homepage_slider/logo.png'" onload="this.onerror=null;">
            <p class="portal-label">Admin Portal</p>
        </div>
        
        <!-- Language Selector -->
        <div class="language-selector">
            <div id="google_translate_element" style="transform: scale(0.8); transform-origin: left;"></div>
        </div>
        
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" id="nav-dashboard" href="#" onclick="showSection('dashboard', this)">
                        <span class="nav-symbol" aria-hidden="true">&#8962;</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="nav-patients" href="#" onclick="showSection('patients', this)">
                        <span class="nav-symbol" aria-hidden="true">&#9877;</span>
                        <span>Patient Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="nav-appointments" href="#" onclick="showSection('appointments', this)">
                        <span class="nav-symbol" aria-hidden="true">&#128197;</span>
                        <span>Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="nav-doctors" href="#" onclick="showSection('doctors', this)">
                        <span class="nav-symbol" aria-hidden="true">&#128300;</span>
                        <span>Doctor Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('staff', this)">
                        <span class="nav-symbol" aria-hidden="true">&#128188;</span>
                        <span>Staff Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('health', this)">
                        <span class="nav-symbol" aria-hidden="true">&#128204;</span>
                        <span>Patient Health Data</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="nav-billing" href="#" onclick="showSection('billing', this)">
                        <span class="nav-symbol" aria-hidden="true">&#128181;</span>
                        <span>Billing & Inventory</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('cms', this)">
                        <span class="nav-symbol" aria-hidden="true">&#9998;</span>
                        <span>Content Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('reports', this)">
                        <span class="nav-symbol" aria-hidden="true">&#128200;</span>
                        <span>Reports & Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <span class="nav-symbol" aria-hidden="true">&#10162;</span>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <!-- Dashboard Section -->
        <div id="dashboard-section">
            <h1 class="mb-4 dashboard-heading"><i class="far fa-clipboard" aria-hidden="true"></i>Dashboard Overview</h1>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card summary-card metric-card--neutral" role="button" tabindex="0" onclick="showSection('appointments', document.getElementById('nav-appointments'))" onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); showSection('appointments', document.getElementById('nav-appointments')); }">
                        <h3 class="metric-value"><?php echo $appointments_today; ?></h3>
                        <p class="metric-label"><i class="fas fa-calendar-day" aria-hidden="true"></i>Today's Appointments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card summary-card metric-card--mint" role="button" tabindex="0" onclick="showSection('patients', document.getElementById('nav-patients'))" onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); showSection('patients', document.getElementById('nav-patients')); }">
                        <h3 class="metric-value"><?php echo $patients_today; ?></h3>
                        <p class="metric-label"><i class="fas fa-user-plus" aria-hidden="true"></i>New Patients Today</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card summary-card metric-card--lavender" role="button" tabindex="0" onclick="showSection('doctors', document.getElementById('nav-doctors'))" onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); showSection('doctors', document.getElementById('nav-doctors')); }">
                        <h3 class="metric-value"><?php echo $doctors_count; ?></h3>
                        <p class="metric-label"><i class="fas fa-user-md" aria-hidden="true"></i>Active Doctors</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card summary-card metric-card--revenue revenue-card" role="button" tabindex="0" onclick="showSection('billing', document.getElementById('nav-billing'))" onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); showSection('billing', document.getElementById('nav-billing')); }">
                        <h3 class="metric-value">&#8377;<?php echo number_format($revenue_today); ?></h3>
                        <p class="metric-label"><i class="fas fa-wallet" aria-hidden="true"></i>Today's Revenue</p>
                    </div>
                </div>
            </div>

            <?php if ($pending_count > 0): ?>
            <div class="emergency-alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Emergency Alert:</strong> <?php echo $pending_count; ?> pending appointments require immediate attention!
                <button class="btn btn-light btn-sm float-end" onclick="showSection('appointments')">View Appointments</button>
            </div>
            <?php endif; ?>

            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="stat-card health-select-card">
                        <h4 class="panel-title"><i class="fas fa-check-circle panel-icon" aria-hidden="true"></i><span>Weekly Performance</span></h4>
                        <div class="chart-controls" role="tablist" aria-label="Weekly chart toggles">
                            <button class="chart-btn active" type="button" data-metric="appointments">Appointments</button>
                            <button class="chart-btn" type="button" data-metric="revenue">Revenue</button>
                            <button class="chart-btn" type="button" data-metric="wait">Avg. Wait</button>
                        </div>
                        <div class="chart-wrap">
                            <canvas id="weeklyChart" height="120"></canvas>
                            <div id="weeklyTooltip" class="chart-tooltip"></div>
                        </div>
                        <div class="performance-summary">
                            <span class="performance-chip">Appointments: 128</span>
                            <span class="performance-chip">Cancellations: 9</span>
                            <span class="performance-chip">Avg. Wait: 12 min</span>
                            <span class="performance-chip">Satisfaction: 4.6/5</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h4 class="panel-title"><i class="fas fa-bell panel-icon" aria-hidden="true"></i><span>Recent Notifications</span></h4>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">New patient registered: John Doe</li>
                            <li class="list-group-item">Dr. Smith's appointment confirmed</li>
                            <li class="list-group-item">Medicine stock low: Paracetamol</li>
                            <li class="list-group-item">Lab report uploaded for patient #123</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Other sections will be loaded dynamically -->
        <div id="patients-section" style="display: none;">
            <h1 class="mb-4"><span class="text-icon me-2" aria-hidden="true">&#9877;</span>Patient Management</h1>
            <div class="row">
                <div class="col-12">
                    <div class="stat-card">
                        <h4 id="patientFormTitle"><span class="text-icon me-2" aria-hidden="true">&#10133;</span>Add New Patient</h4>
                        <form id="patientForm">
                            <div id="patientFormMessage" class="form-message" aria-live="polite"></div>
                            <input type="hidden" name="edit_patient_id" id="editPatientId" value="">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="firstname" placeholder="First Name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="lastname" placeholder="Last Name" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="dob" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <input type="number" class="form-control" name="height" placeholder="Height (cm)" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <input type="number" class="form-control" name="weight" placeholder="Weight (kg)" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <input type="text" class="form-control" name="bloodgroup" placeholder="Blood Group" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Email" required>
                            </div>

                            <div class="mb-3">
                                <input type="password" class="form-control" name="password" placeholder="Password" required id="patientPassword">
                            </div>

                            <div class="mb-3">
                                <input type="tel" class="form-control" name="contact_no" placeholder="Contact Number" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="state" placeholder="State">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="country" placeholder="Country">
                                </div>
                            </div>

                            <div class="mb-3">
                                <textarea class="form-control" name="allergies" placeholder="Allergies (if any)" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <textarea class="form-control" name="medical_conditions" placeholder="Medical Conditions (if any)" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="emergency_contact" placeholder="Emergency Contact Name">
                            </div>

                            <div class="mb-3">
                                <input type="tel" class="form-control" name="emergency_phone" placeholder="Emergency Contact Phone">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary w-100" id="patientFormSubmit">
                                        <i class="fas fa-user-plus me-1"></i>Add Patient
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-secondary w-100" id="patientFormCancel" style="display: none;">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-12">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128196;</span>Patient Records</h4>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="patientSearch" placeholder="Search by name, email, or ID">
                            <button class="btn btn-primary" type="button" onclick="searchPatient()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Blood Group</th>
                                    </tr>
                                </thead>
                                <tbody id="patientListBody">
                                    <?php
                                    $patients_query = $conn->query("SELECT pid, firstname, lastname, email, contact_no, bloodgroup FROM patient ORDER BY pid DESC LIMIT 20");
                                    while ($patient = $patients_query->fetch_assoc()) {
                                        echo '<tr data-patient-id="' . $patient['pid'] . '">';
                                        echo '<td>' . $patient['pid'] . '</td>';
                                        echo '<td class="patient-name">' . htmlspecialchars($patient['firstname'] . ' ' . $patient['lastname']) . '</td>';
                                        echo '<td class="patient-email">' . htmlspecialchars($patient['email']) . '</td>';
                                        echo '<td class="patient-contact">' . htmlspecialchars($patient['contact_no']) . '</td>';
                                        echo '<td class="patient-blood">' . htmlspecialchars($patient['bloodgroup']) . '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="appointments-section" style="display: none;">
            <h1 class="mb-4"><span class="text-icon me-2" aria-hidden="true">&#128197;</span>Appointment Management</h1>
            <div class="row">
                <div class="col-md-8">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128467;</span>Calendar View</h4>
                        <div id="calendar"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#9881;</span>Quick Status Update</h4>
                        <div class="mb-3">
                            <label class="form-label">Appointment ID</label>
                            <input type="text" class="form-control" id="appointmentId">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="appointmentStatus">
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" onclick="updateAppointmentStatus()">
                            <i class="fas fa-sync me-1"></i>Update Status
                        </button>
                        <div id="appointmentUpdateMessage" class="form-message mt-3" aria-live="polite"></div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128221;</span>Recent Appointment Requests</h4>
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="adminAppointmentListBody">
                                    <?php
                                    $adminAppointments = mysqli_query(
                                        $conn,
                                        "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status,
                                                p.firstname, p.lastname, d.f_name, d.l_name
                                         FROM appointment a
                                         LEFT JOIN patient p ON a.pid = p.pid
                                         LEFT JOIN doctor d ON a.did = d.d_id
                                         ORDER BY CASE WHEN a.status = 'Pending' THEN 0 ELSE 1 END, a.appointment_id DESC
                                         LIMIT 25"
                                    );

                                    if ($adminAppointments && mysqli_num_rows($adminAppointments) > 0) {
                                        while ($appointment = mysqli_fetch_assoc($adminAppointments)) {
                                            $appointmentId = (int) ($appointment['appointment_id'] ?? 0);
                                            $appointmentStatus = trim((string) ($appointment['status'] ?? 'Pending'));
                                            $patientName = trim(((string) ($appointment['firstname'] ?? '')) . ' ' . ((string) ($appointment['lastname'] ?? '')));
                                            $doctorName = trim(((string) ($appointment['f_name'] ?? '')) . ' ' . ((string) ($appointment['l_name'] ?? '')));

                                            if ($patientName === '') {
                                                $patientName = 'Unknown patient';
                                            }
                                            if ($doctorName === '') {
                                                $doctorName = 'Doctor not assigned';
                                            } else {
                                                $doctorName = 'Dr. ' . $doctorName;
                                            }

                                            $statusBadgeClass = 'bg-secondary';
                                            if ($appointmentStatus === 'Pending') {
                                                $statusBadgeClass = 'bg-warning text-dark';
                                            } elseif ($appointmentStatus === 'Confirmed') {
                                                $statusBadgeClass = 'bg-success';
                                            } elseif ($appointmentStatus === 'Completed') {
                                                $statusBadgeClass = 'bg-info text-dark';
                                            } elseif ($appointmentStatus === 'Cancelled') {
                                                $statusBadgeClass = 'bg-danger';
                                            }

                                            echo '<tr data-appointment-id="' . $appointmentId . '">';
                                            echo '<td>' . $appointmentId . '</td>';
                                            echo '<td>' . htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') . '</td>';
                                            echo '<td>' . htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') . '</td>';
                                            echo '<td>' . htmlspecialchars((string) ($appointment['appointment_date'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                                            echo '<td>' . htmlspecialchars((string) ($appointment['appointment_time'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                                            echo '<td class="appointment-row-status"><span class="badge ' . $statusBadgeClass . '">' . htmlspecialchars($appointmentStatus, ENT_QUOTES, 'UTF-8') . '</span></td>';
                                            echo '<td class="appointment-row-actions"><div class="table-actions">';
                                            echo '<button type="button" class="btn btn-outline-primary btn-sm" onclick="prefillAppointmentUpdate(' . $appointmentId . ', \'' . htmlspecialchars($appointmentStatus, ENT_QUOTES, 'UTF-8') . '\')">Use ID</button>';
                                            if ($appointmentStatus === 'Pending') {
                                                echo '<button type="button" class="btn btn-outline-success btn-sm" onclick="updateAppointmentStatus(' . $appointmentId . ', \'Confirmed\')">Confirm</button>';
                                                echo '<button type="button" class="btn btn-outline-danger btn-sm" onclick="updateAppointmentStatus(' . $appointmentId . ', \'Cancelled\')">Cancel</button>';
                                            }
                                            echo '</div></td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center text-muted">No appointments found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="doctors-section" style="display: none;">
            <div class="section-header-actions">
                <h1><span class="text-icon me-2" aria-hidden="true">&#128300;</span>Doctor Management</h1>
                <div class="doctor-header-actions">
                    <button type="button" id="toggleDoctorFormBtn" class="btn btn-primary" aria-controls="doctorFormWrapper" aria-expanded="false" onclick="toggleDoctorForm()">
                        <i class="fas fa-user-plus me-1"></i>Add New Doctor
                    </button>
                </div>
            </div>

            <div id="doctorFormWrapper" class="doctor-form-wrapper" aria-hidden="true">
                <div class="stat-card doctor-form-card">
                    <div class="doctor-form-header">
                        <div>
                            <h4 id="doctorFormTitle"><span class="text-icon me-2" aria-hidden="true">&#10133;</span>Add New Doctor</h4>
                            <p class="doctor-form-subtitle">Complete the full registration profile for a new doctor.</p>
                        </div>
                        <div class="doctor-id-pill">
                            <span>Doctor ID</span>
                            <strong id="doctorIdDisplay">DOC-0001</strong>
                        </div>
                    </div>
                    <div class="doctor-form-body">
                    <form id="doctorForm" novalidate>
                        <div id="doctorFormMessage" class="form-message" aria-live="polite"></div>
                        <input type="hidden" name="edit_doctor_id" id="editDoctorId" value="">
                        <input type="hidden" name="f_name" id="doctorFirstName" value="">
                        <input type="hidden" name="l_name" id="doctorLastName" value="">
                        <div class="doctor-section-grid">
                            <div class="doctor-section-card section-wide">
                                <div class="doctor-section-title">
                                    <i class="fas fa-id-card"></i>
                                    <span>Basic Information</span>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Doctor ID<span class="required">*</span></label>
                                        <input type="text" class="form-control" name="doctor_id" id="doctorIdInput" readonly>
                                        <div class="field-error" data-error-for="doctor_id"></div>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Full Name<span class="required">*</span></label>
                                        <input type="text" class="form-control" name="full_name" id="doctorFullName" placeholder="Dr. Aditi Sharma" required>
                                        <div class="field-error" data-error-for="full_name"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Gender<span class="required">*</span></label>
                                        <select class="form-select" name="gender" id="doctorGender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <div class="field-error" data-error-for="gender"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date of Birth<span class="required">*</span></label>
                                        <input type="date" class="form-control" name="dob" id="doctorDob" required>
                                        <div class="field-error" data-error-for="dob"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Mobile Number<span class="required">*</span></label>
                                        <input type="tel" class="form-control" name="contact_no" id="doctorMobile" placeholder="9876543210" required>
                                        <div class="field-error" data-error-for="contact_no"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email<span class="required">*</span></label>
                                        <input type="email" class="form-control" name="email" id="doctorEmail" placeholder="doctor@hospital.com" required>
                                        <div class="field-error" data-error-for="email"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Profile Photo Upload</label>
                                        <input type="file" class="form-control" name="photo" id="doctorPhoto" accept="image/*">
                                        <small class="text-muted">JPG, PNG, or WebP (Max 5MB)</small>
                                        <div id="photoPreview" style="margin-top: 10px; display: none;">
                                            <img id="photoPreviewImg" src="" alt="Photo Preview" style="max-width: 150px; border-radius: 8px;">
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" id="doctorAddress" rows="2" placeholder="Clinic / hospital address"></textarea>
                                        <div class="field-error" data-error-for="address"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" id="doctorCity" placeholder="City">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="state" id="doctorState" placeholder="State">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Country</label>
                                        <input type="text" class="form-control" name="country" id="doctorCountry" placeholder="Country">
                                    </div>
                                </div>
                            </div>

                            <div class="doctor-section-card">
                                <div class="doctor-section-title">
                                    <i class="fas fa-user-md"></i>
                                    <span>Professional Information</span>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Specialization<span class="required">*</span></label>
                                        <select class="form-select" name="specialization" id="doctorSpecialization" required>
                                            <option value="">Select Specialization</option>
                                            <option value="General Physician">General Physician</option>
                                            <option value="Cardiologist">Cardiologist</option>
                                            <option value="Dermatologist">Dermatologist</option>
                                            <option value="Neurologist">Neurologist</option>
                                            <option value="Neurosurgeon">Neurosurgeon</option>
                                            <option value="Orthopedic Surgeon">Orthopedic Surgeon</option>
                                            <option value="Pediatrician">Pediatrician</option>
                                            <option value="Gynecologist">Gynecologist</option>
                                            <option value="Obstetrician">Obstetrician</option>
                                            <option value="ENT Specialist">ENT Specialist</option>
                                            <option value="Ophthalmologist">Ophthalmologist</option>
                                            <option value="Dentist">Dentist</option>
                                            <option value="Psychiatrist">Psychiatrist</option>
                                            <option value="Psychologist">Psychologist</option>
                                            <option value="Urologist">Urologist</option>
                                            <option value="Nephrologist">Nephrologist</option>
                                            <option value="Gastroenterologist">Gastroenterologist</option>
                                            <option value="Endocrinologist">Endocrinologist</option>
                                            <option value="Pulmonologist">Pulmonologist</option>
                                            <option value="Oncologist">Oncologist</option>
                                            <option value="Radiologist">Radiologist</option>
                                            <option value="Pathologist">Pathologist</option>
                                            <option value="General Surgeon">General Surgeon</option>
                                            <option value="Laparoscopic Surgeon">Laparoscopic Surgeon</option>
                                            <option value="Anesthesiologist">Anesthesiologist</option>
                                            <option value="Rheumatologist">Rheumatologist</option>
                                            <option value="Immunologist">Immunologist</option>
                                            <option value="Physiotherapist">Physiotherapist</option>
                                            <option value="Emergency Medicine Specialist">Emergency Medicine Specialist</option>
                                            <option value="ICU Specialist">ICU Specialist</option>
                                            <option value="Family Medicine">Family Medicine</option>
                                            <option value="Internal Medicine">Internal Medicine</option>
                                            <option value="Vascular Surgeon">Vascular Surgeon</option>
                                            <option value="Plastic Surgeon">Plastic Surgeon</option>
                                            <option value="Cosmetologist">Cosmetologist</option>
                                            <option value="Sexologist">Sexologist</option>
                                            <option value="Nutritionist">Nutritionist</option>
                                            <option value="Diabetologist">Diabetologist</option>
                                            <option value="Pain Management Specialist">Pain Management Specialist</option>
                                        </select>
                                        <div class="field-error" data-error-for="specialization"></div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Department<span class="required">*</span></label>
                                        <select class="form-select" name="department" id="doctorDepartment" required>
                                            <option value="">Select Department</option>
                                            <option value="General Medicine">General Medicine</option>
                                            <option value="Cardiology">Cardiology</option>
                                            <option value="Dermatology">Dermatology</option>
                                            <option value="Neurology">Neurology</option>
                                            <option value="Neurosurgery">Neurosurgery</option>
                                            <option value="Orthopedics">Orthopedics</option>
                                            <option value="Pediatrics">Pediatrics</option>
                                            <option value="Gynecology">Gynecology</option>
                                            <option value="Obstetrics">Obstetrics</option>
                                            <option value="ENT">ENT</option>
                                            <option value="Ophthalmology">Ophthalmology</option>
                                            <option value="Dental">Dental</option>
                                            <option value="Psychiatry">Psychiatry</option>
                                            <option value="Psychology">Psychology</option>
                                            <option value="Urology">Urology</option>
                                            <option value="Nephrology">Nephrology</option>
                                            <option value="Gastroenterology">Gastroenterology</option>
                                            <option value="Endocrinology">Endocrinology</option>
                                            <option value="Pulmonology">Pulmonology</option>
                                            <option value="Oncology">Oncology</option>
                                            <option value="Radiology">Radiology</option>
                                            <option value="Pathology">Pathology</option>
                                            <option value="General Surgery">General Surgery</option>
                                            <option value="Laparoscopic Surgery">Laparoscopic Surgery</option>
                                            <option value="Anesthesia">Anesthesia</option>
                                            <option value="Rheumatology">Rheumatology</option>
                                            <option value="Immunology">Immunology</option>
                                            <option value="Physiotherapy">Physiotherapy</option>
                                            <option value="Emergency">Emergency</option>
                                            <option value="ICU">ICU</option>
                                            <option value="Family Medicine">Family Medicine</option>
                                            <option value="Internal Medicine">Internal Medicine</option>
                                            <option value="Vascular Surgery">Vascular Surgery</option>
                                            <option value="Plastic Surgery">Plastic Surgery</option>
                                            <option value="Cosmetology">Cosmetology</option>
                                            <option value="Nutrition & Dietetics">Nutrition & Dietetics</option>
                                            <option value="Diabetology">Diabetology</option>
                                            <option value="Pain Management">Pain Management</option>
                                        </select>
                                        <div class="field-error" data-error-for="department"></div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Qualification<span class="required">*</span></label>
                                        <input type="text" class="form-control" name="qualification" id="doctorQualification" placeholder="MBBS, MD, MS" required>
                                        <div class="field-error" data-error-for="qualification"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Experience in Years</label>
                                        <input type="number" class="form-control" name="experience" id="doctorExperience" min="0" max="60" placeholder="10">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Medical License Number<span class="required">*</span></label>
                                        <input type="text" class="form-control" name="license_number" id="doctorLicense" placeholder="MED-123456" required>
                                        <div class="field-error" data-error-for="license_number"></div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Registration Council Name</label>
                                        <input type="text" class="form-control" name="registration_council" id="doctorCouncil" placeholder="Medical Council Name">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Joining Date<span class="required">*</span></label>
                                        <input type="date" class="form-control" name="joining_date" id="doctorJoiningDate" required>
                                        <div class="field-error" data-error-for="joining_date"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Doctor Type / Role</label>
                                        <select class="form-select" name="doctor_type" id="doctorType">
                                            <option value="Consultant">Consultant</option>
                                            <option value="Surgeon">Surgeon</option>
                                            <option value="Visiting Doctor">Visiting Doctor</option>
                                            <option value="Resident Doctor">Resident Doctor</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="doctor-section-card">
                                <div class="doctor-section-title">
                                    <i class="fas fa-hospital"></i>
                                    <span>Hospital Work Settings</span>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Consultation Fees</label>
                                        <input type="number" class="form-control" name="consultation_fees" id="doctorFees" min="0" step="0.01" placeholder="500">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="doctor-toggle">
                                            <input type="checkbox" id="doctorEmergency" name="emergency_available" value="1">
                                            <label for="doctorEmergency">Emergency Availability</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="doctor-toggle">
                                            <input type="checkbox" id="doctorOnline" name="online_consultation" value="1">
                                            <label for="doctorOnline">Online Consultation Available</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="doctor-toggle">
                                            <input type="checkbox" id="doctorPrescription" name="can_write_prescription" value="1">
                                            <label for="doctorPrescription">Can Write Prescription</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Digital Signature Upload</label>
                                        <input type="file" class="form-control" name="digital_signature" id="doctorSignature" accept="image/*">
                                    </div>
                                </div>
                            </div>

                            <div class="doctor-section-card">
                                <div class="doctor-section-title">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Availability</span>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Working Days</label>
                                        <div class="working-days">
                                            <label><input type="checkbox" name="working_days[]" value="Mon">Mon</label>
                                            <label><input type="checkbox" name="working_days[]" value="Tue">Tue</label>
                                            <label><input type="checkbox" name="working_days[]" value="Wed">Wed</label>
                                            <label><input type="checkbox" name="working_days[]" value="Thu">Thu</label>
                                            <label><input type="checkbox" name="working_days[]" value="Fri">Fri</label>
                                            <label><input type="checkbox" name="working_days[]" value="Sat">Sat</label>
                                            <label><input type="checkbox" name="working_days[]" value="Sun">Sun</label>
                                        </div>
                                        <div class="field-error" data-error-for="working_days"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" class="form-control" name="available_from" id="doctorStartTime">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End Time</label>
                                        <input type="time" class="form-control" name="available_to" id="doctorEndTime">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Break Start Time</label>
                                        <input type="time" class="form-control" name="break_start" id="doctorBreakStart">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Break End Time</label>
                                        <input type="time" class="form-control" name="break_end" id="doctorBreakEnd">
                                    </div>
                                </div>
                            </div>

                            <div class="doctor-section-card">
                                <div class="doctor-section-title">
                                    <i class="fas fa-key"></i>
                                    <span>Login Credentials</span>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" id="doctorUsername" placeholder="auto-suggested">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Password<span class="required">*</span></label>
                                        <input type="password" class="form-control" name="password" id="doctorPassword" required>
                                        <div class="field-error" data-error-for="password"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirm Password<span class="required">*</span></label>
                                        <input type="password" class="form-control" name="confirm_password" id="doctorConfirmPassword" required>
                                        <div class="field-error" data-error-for="confirm_password"></div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="Doctor" readonly>
                                        <input type="hidden" name="role" value="Doctor">
                                    </div>
                                </div>
                            </div>

                            <div class="doctor-section-card section-wide">
                                <div class="doctor-section-title">
                                    <i class="fas fa-folder-open"></i>
                                    <span>Documents</span>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Degree Certificate Upload</label>
                                        <input type="file" class="form-control" name="degree_certificate" accept=".pdf,image/*">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Medical License Proof Upload</label>
                                        <input type="file" class="form-control" name="license_proof" accept=".pdf,image/*">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ID Proof Upload</label>
                                        <input type="file" class="form-control" name="id_proof" accept=".pdf,image/*">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Experience Certificate Upload</label>
                                        <input type="file" class="form-control" name="experience_certificate" accept=".pdf,image/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="doctor-actions">
                            <button type="submit" id="doctorFormSubmit" class="btn btn-success">
                                <i class="fas fa-user-plus me-1"></i>Save Doctor
                            </button>
                            <button type="reset" id="doctorFormReset" class="btn btn-outline-secondary">Reset</button>
                            <button type="button" id="doctorFormCancel" class="btn btn-outline-primary">Cancel</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>

            <div class="stat-card doctor-list-card" id="doctorListColumn">
                <h4><span class="text-icon me-2" aria-hidden="true">&#9776;</span>Doctor List</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Doctor ID</th>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Department</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="doctorListBody">
                            <?php
                            $doctors = mysqli_query($conn, "SELECT * FROM doctor ORDER BY d_id DESC LIMIT 10");
                            while($doc = mysqli_fetch_assoc($doctors)) {
                                $status = strtolower(trim($doc['approval'])) === 'approved' ? 'Active' : 'Inactive';
                                $badgeClass = $status === 'Active' ? 'bg-success' : 'bg-warning';
                                $toggleLabel = $status === 'Active' ? 'Deactivate' : 'Activate';
                                $doctorCode = $doc['doctor_id'] ?? '';
                                $doctorCode = $doctorCode !== '' ? $doctorCode : 'DOC-' . str_pad((string) $doc['d_id'], 4, '0', STR_PAD_LEFT);
                                $department = trim((string) ($doc['department'] ?? ''));
                                if ($department === '') {
                                    $department = infer_department_from_specialization($doc['specialization'] ?? '');
                                }
                                echo "<tr data-doctor-id='{$doc['d_id']}'>
                                    <td class='doctor-code'>{$doctorCode}</td>
                                    <td class='doctor-name'>Dr. {$doc['f_name']} {$doc['l_name']}</td>
                                    <td class='doctor-spec'>{$doc['specialization']}</td>
                                    <td class='doctor-dept'>{$department}</td>
                                    <td class='doctor-contact'>{$doc['contact_no']}</td>
                                    <td class='doctor-email'>{$doc['email']}</td>
                                    <td class='doctor-status'><span class='badge {$badgeClass}'>{$status}</span></td>
                                    <td>
                                        <div class='table-actions'>
                                            <button class='btn btn-outline-primary btn-sm' onclick='editDoctor({$doc['d_id']}, this)'>Edit</button>
                                            <button class='btn btn-outline-success btn-sm' onclick='toggleDoctorStatus({$doc['d_id']}, this)'>{$toggleLabel}</button>
                                        </div>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="doctorToast" class="doctor-toast" role="status" aria-live="polite">Doctor added successfully.</div>
        </div>

        <!-- STAFF MANAGEMENT SECTION -->
        <div id="staff-section" style="display: none;">
            <h1 class="mb-4"><span class="text-icon me-2" aria-hidden="true">&#128188;</span>Staff Management</h1>
            <div class="row">
                <div class="col-md-5">
                    <div class="stat-card">
                        <h4 id="staffFormTitle"><span class="text-icon me-2" aria-hidden="true">&#10133;</span>Add New Staff</h4>
                        <form id="staffForm">
                            <div id="staffFormMessage" class="form-message" aria-live="polite"></div>
                            <input type="hidden" name="edit_staff_id" id="editStaffId" value="">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="fname" placeholder="First Name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="lname" placeholder="Last Name" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Email" required>
                            </div>

                            <div class="mb-3">
                                <input type="password" class="form-control" name="password" placeholder="Password" required id="staffPassword">
                            </div>

                            <div class="mb-3">
                                <select class="form-select" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="receptionist">Receptionist</option>
                                    <option value="nurse">Nurse</option>
                                    <option value="pharmacist">Pharmacist</option>
                                    <option value="lab_technician">Lab Technician</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="department" placeholder="Department">
                            </div>

                            <div class="mb-3">
                                <input type="tel" class="form-control" name="phone" placeholder="Contact Number">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Hire Date</label>
                                <input type="date" class="form-control" name="hire_date">
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="qualifications" placeholder="Qualifications (e.g., BSc, MSc)">
                            </div>

                            <div class="mb-3">
                                <input type="number" class="form-control" name="experience" placeholder="Experience (years)" min="0" max="60">
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="license_number" placeholder="License Number (if applicable)">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">License Expiry</label>
                                <input type="date" class="form-control" name="license_expiry">
                            </div>

                            <div class="mb-3">
                                <select class="form-select" name="shift">
                                    <option value="flexible">Flexible</option>
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="night">Night</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <input type="number" class="form-control" name="salary" placeholder="Monthly Salary" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="emergency_contact" placeholder="Emergency Contact Name">
                            </div>

                            <div class="mb-3">
                                <input type="tel" class="form-control" name="emergency_phone" placeholder="Emergency Contact Phone">
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary w-100 mb-2" id="staffFormSubmit">
                                        <i class="fas fa-user-plus me-1"></i>Add Staff
                                    </button>
                                    <button type="button" class="btn btn-secondary w-100" id="staffFormCancel" style="display: none;">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128188;</span>Staff Directory</h4>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="staffSearch" placeholder="Search by name, email, or role">
                            <button class="btn btn-primary" type="button" onclick="searchStaff()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div style="max-height: 650px; overflow-y: auto;">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="staffListBody">
                                    <?php
                                    $staff_query = $conn->query(
                                        "SELECT u.user_id, u.fname, u.lname, u.email, u.role, u.department, u.phone, u.is_active, sp.experience_years
                                         FROM users u 
                                         LEFT JOIN staff_profiles sp ON u.user_id = sp.user_id
                                         WHERE u.user_type = 'staff' OR u.role IN ('receptionist', 'nurse', 'pharmacist', 'lab_technician')
                                         ORDER BY u.user_id DESC LIMIT 20"
                                    );
                                    if ($staff_query && $staff_query->num_rows > 0) {
                                        while ($staff = $staff_query->fetch_assoc()) {
                                            $statusBadge = $staff['is_active'] ? 'success' : 'danger';
                                            $statusText = $staff['is_active'] ? 'Active' : 'Inactive';
                                            echo '<tr data-staff-id="' . $staff['user_id'] . '">';
                                            echo '<td class="staff-name">' . htmlspecialchars($staff['fname'] . ' ' . $staff['lname']) . '</td>';
                                            echo '<td class="staff-email">' . htmlspecialchars($staff['email']) . '</td>';
                                            echo '<td class="staff-role"><span class="badge bg-info">' . htmlspecialchars($staff['role'] ?? 'N/A') . '</span></td>';
                                            echo '<td class="staff-dept">' . htmlspecialchars($staff['department'] ?? 'N/A') . '</td>';
                                            echo '<td class="staff-phone">' . htmlspecialchars($staff['phone'] ?? 'N/A') . '</td>';
                                            echo '<td><span class="badge bg-' . $statusBadge . '">' . $statusText . '</span></td>';
                                            echo '<td style="white-space: nowrap;">
                                                    <button class="btn btn-outline-primary btn-sm" onclick="editStaff(' . $staff['user_id'] . ', this)">✏️</button>
                                                    <button class="btn btn-outline-danger btn-sm" onclick="removeStaff(' . $staff['user_id'] . ', this)">⛔</button>
                                                    <br>
                                                    <button class="btn btn-outline-' . ($staff['is_active'] ? 'warning' : 'success') . ' btn-sm" onclick="toggleStaffStatus(' . $staff['user_id'] . ', this)" style="margin-top: 5px; width: 100%;">' . ($staff['is_active'] ? 'Deactivate' : 'Activate') . '</button>
                                                </td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PATIENT HEALTH DATA SECTION -->
        <div id="health-section" style="display: none;">
            <h1 class="mb-4"><span class="text-icon me-2" aria-hidden="true">&#128204;</span>Patient Health Data Management</h1>
            <div class="mb-3" id="healthOverviewCard"></div>
            <div class="row">
                <div class="col-md-12">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128201;</span>Select Patient</h4>
                        <div class="mb-2">
                            <select class="form-select" id="healthPatientSelect" required>
                                <option value="">-- Select Patient --</option>
                                <?php
                                $patients_query = $conn->query("SELECT pid, firstname, lastname, email FROM patient ORDER BY firstname LIMIT 50");
                                if ($patients_query) {
                                    while ($patient = $patients_query->fetch_assoc()) {
                                        echo '<option value="' . $patient['pid'] . '">' . htmlspecialchars($patient['firstname'] . ' ' . $patient['lastname']) . ' (' . htmlspecialchars($patient['email']) . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <button class="btn btn-info w-100" onclick="loadPatientHealthData()">Load Health Data</button>

                        <div class="table-responsive mt-2">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Email</th>
                                        <th>Condition</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="patientListBody">
                                    <tr>
                                        <td colspan="4" class="text-muted">Loading patients...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="stat-card h-100">
                        <h4 id="healthDataTitle"><span class="text-icon me-2" aria-hidden="true">&#128170;</span>Latest Health Data</h4>
                        <div id="healthDataDisplay" class="alert alert-info">Select a patient to view their health data</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="billing-section" style="display: none;">
            <h1 class="mb-4"><span class="text-icon me-2" aria-hidden="true">&#128181;</span>Billing & Inventory</h1>
            <div class="row">
                <div class="col-12">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128196;</span>Generate Invoice</h4>
                        <form id="invoiceForm">
                            <div class="mb-3">
                                <label class="form-label">Patient ID (Completed Appointments)</label>
                                <select class="form-select" id="invoicePatientId" required>
                                    <option value="">-- Select Patient --</option>
                                    <?php
                                    $invoicePatients = [];

                                    $completedSql = "SELECT a.appointment_id, a.pid, a.status, p.firstname, p.lastname, p.email, d.consultation_fees
                                                     FROM appointment a
                                                     INNER JOIN patient p ON p.pid = a.pid
                                                     LEFT JOIN doctor d ON d.d_id = a.did
                                                     WHERE a.status = 'Completed'
                                                     ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.appointment_id DESC";
                                    $completedResult = $conn->query($completedSql);

                                    if ($completedResult && $completedResult->num_rows > 0) {
                                        while ($row = $completedResult->fetch_assoc()) {
                                            $pid = (int) ($row['pid'] ?? 0);
                                            if ($pid <= 0 || isset($invoicePatients[$pid])) {
                                                continue;
                                            }
                                            $feeRaw = (string) ($row['consultation_fees'] ?? '0');
                                            $feeNumeric = (float) preg_replace('/[^0-9.]/', '', $feeRaw);
                                            $invoicePatients[$pid] = [
                                                'pid' => $pid,
                                                'name' => trim((string)($row['firstname'] ?? '') . ' ' . (string)($row['lastname'] ?? '')),
                                                'email' => (string)($row['email'] ?? ''),
                                                'appointment_id' => (int)($row['appointment_id'] ?? 0),
                                                'status' => (string)($row['status'] ?? 'Completed'),
                                                'consultation_fee' => $feeNumeric > 0 ? round($feeNumeric, 2) : 0,
                                                'half_amount' => $feeNumeric > 0 ? round($feeNumeric / 2, 2) : 0
                                            ];
                                        }
                                    }

                                    // Fallback for legacy data where "Completed" may not exist in appointment status enum.
                                    if (empty($invoicePatients)) {
                                        $confirmedSql = "SELECT a.appointment_id, a.pid, a.status, p.firstname, p.lastname, p.email, d.consultation_fees
                                                         FROM appointment a
                                                         INNER JOIN patient p ON p.pid = a.pid
                                                         LEFT JOIN doctor d ON d.d_id = a.did
                                                         WHERE a.status = 'Confirmed'
                                                         ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.appointment_id DESC";
                                        $confirmedResult = $conn->query($confirmedSql);
                                        if ($confirmedResult && $confirmedResult->num_rows > 0) {
                                            while ($row = $confirmedResult->fetch_assoc()) {
                                                $pid = (int) ($row['pid'] ?? 0);
                                                if ($pid <= 0 || isset($invoicePatients[$pid])) {
                                                    continue;
                                                }
                                                $feeRaw = (string) ($row['consultation_fees'] ?? '0');
                                                $feeNumeric = (float) preg_replace('/[^0-9.]/', '', $feeRaw);
                                                $invoicePatients[$pid] = [
                                                    'pid' => $pid,
                                                    'name' => trim((string)($row['firstname'] ?? '') . ' ' . (string)($row['lastname'] ?? '')),
                                                    'email' => (string)($row['email'] ?? ''),
                                                    'appointment_id' => (int)($row['appointment_id'] ?? 0),
                                                    'status' => (string)($row['status'] ?? 'Confirmed'),
                                                    'consultation_fee' => $feeNumeric > 0 ? round($feeNumeric, 2) : 0,
                                                    'half_amount' => $feeNumeric > 0 ? round($feeNumeric / 2, 2) : 0
                                                ];
                                            }
                                        }
                                    }

                                    foreach ($invoicePatients as $patientOption) {
                                    echo '<option value="' . $patientOption['pid'] . '" data-half-amount="' . number_format((float)$patientOption['half_amount'], 2, '.', '') . '" data-consultation-fee="' . number_format((float)($patientOption['consultation_fee'] ?? 0), 2, '.', '') . '">'
                                            . 'PID-' . $patientOption['pid'] . ' | '
                                            . htmlspecialchars($patientOption['name']) . ' (' . htmlspecialchars($patientOption['email']) . ') '
                                            . '| Appt #' . $patientOption['appointment_id']
                                            . ' | ' . htmlspecialchars($patientOption['status'])
                                            . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Services</label>
                                <input type="text" class="form-control" id="invoiceServices" placeholder="Services" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount (Auto from prescription medicines)</label>
                                <input type="number" class="form-control" id="invoiceAmount" placeholder="Amount" step="0.01" required>
                            </div>
                            <div id="invoicePrescriptionPreview" class="medicine-preview-card mb-3" style="display:none;"></div>
                            <div class="mb-3">
                                <label class="form-label">Payment Status (Admin)</label>
                                <select class="form-select" id="invoicePaymentStatus" required>
                                    <option value="not_paid">Not Paid</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-file-invoice me-1"></i>Generate Invoice
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128138;</span>Medicine Inventory</h4>
                        <div class="mb-3">
                            <label class="form-label">Medicine Dropdown with Cost</label>
                            <select class="form-select" id="inventoryMedicineSelect">
                                <option value="">-- Select medicine --</option>
                                <?php if (!empty($medicineCatalog)): ?>
                                    <?php foreach ($medicineCatalog as $medicine): ?>
                                        <?php
                                            $medicineId = (int) ($medicine['drug_id'] ?? 0);
                                            $medicineName = (string) ($medicine['medicine_name'] ?? '');
                                            $medicineStock = (int) ($medicine['stock_level'] ?? 0);
                                            $medicinePrice = (float) ($medicine['unit_price'] ?? 0);
                                        ?>
                                        <option
                                            value="<?php echo htmlspecialchars($medicineName, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-medicine-id="<?php echo (int) $medicineId; ?>"
                                            data-name="<?php echo htmlspecialchars($medicineName, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-stock="<?php echo (int) $medicineStock; ?>"
                                            data-price="<?php echo number_format($medicinePrice, 2, '.', ''); ?>"
                                        >
                                            <?php echo htmlspecialchars($medicineName . ' - Rs. ' . number_format($medicinePrice, 2), ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No medicines found</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div id="inventoryMedicinePreview" class="medicine-preview-card mb-3">
                            <div class="medicine-preview-empty">Select a medicine to view its cost, stock, and status.</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Cost</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryTableBody">
                                    <?php if (!empty($medicineCatalog)): ?>
                                        <?php foreach ($medicineCatalog as $medicine): ?>
                                            <?php
                                                $medicineId = (int) ($medicine['drug_id'] ?? 0);
                                                $medicineName = (string) ($medicine['medicine_name'] ?? '');
                                                $medicineStock = (int) ($medicine['stock_level'] ?? 0);
                                                $medicinePrice = (float) ($medicine['unit_price'] ?? 0);
                                                $inventoryStatus = medc_inventory_status($medicineStock);
                                            ?>
                                            <tr data-medicine-id="<?php echo (int) $medicineId; ?>">
                                                <td class="medicine-name"><?php echo htmlspecialchars($medicineName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm medicine-cost-input" min="0" step="0.01" value="<?php echo number_format($medicinePrice, 2, '.', ''); ?>">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm medicine-stock-input" min="0" step="1" value="<?php echo (int) $medicineStock; ?>">
                                                </td>
                                                <td class="medicine-status">
                                                    <span class="badge <?php echo htmlspecialchars($inventoryStatus['className'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($inventoryStatus['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </td>
                                                <td><button type="button" class="btn btn-primary btn-sm" onclick="saveInventoryRow(this)">Save</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-muted text-center">No medicines available in inventory.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="cms-section" style="display: none;">
            <h1 class="mb-4"><span class="text-icon me-2" aria-hidden="true">&#9998;</span>Content Management</h1>
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128444;</span>Update Website Banners</h4>
                        <div class="mb-3">
                            <label class="form-label">Banner Text</label>
                            <textarea class="form-control" rows="3" placeholder="Enter banner content..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Banner Image</label>
                            <input type="file" class="form-control">
                        </div>
                        <button class="btn btn-primary">Update Banner</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128227;</span>Notice Board</h4>
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Notice Title">
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="3" placeholder="Notice Content..."></textarea>
                        </div>
                        <button class="btn btn-info">Post Notice</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="reports-section" style="display: none;">
            <h1 class="mb-4"><span class="text-icon me-2" aria-hidden="true">&#128200;</span>Reports & Analytics</h1>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128181;</span>Monthly Revenue</h4>
                        <h2 class="text-success">&#8377;4,50,000</h2>
                        <p>Compared to last month: <span class="text-success">+15%</span></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#9873;</span>Department Performance</h4>
                        <p>Cardiology: <strong>25%</strong> of total appointments</p>
                        <p>Orthopedics: <strong>20%</strong> of total appointments</p>
                        <p>Pediatrics: <strong>18%</strong> of total appointments</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h4><span class="text-icon me-2" aria-hidden="true">&#128172;</span>Patient Feedback</h4>
                        <p>Average Rating: <strong>4.5/5</strong></p>
                        <p>Total Reviews: <strong>127</strong></p>
                        <p>Positive Feedback: <strong>92%</strong></p>
                    </div>
                </div>
            </div>
            <div class="stat-card mt-4">
                <h4><span class="text-icon me-2" aria-hidden="true">&#128221;</span>Detailed Reports</h4>
                <button class="btn btn-outline-primary me-2" onclick="downloadReport('monthly')">Monthly Report</button>
                <button class="btn btn-outline-success me-2" onclick="downloadReport('weekly')">Weekly Report</button>
                <button class="btn btn-outline-info" onclick="downloadReport('custom')">Custom Report</button>
            </div>
        </div>
    </div>

<script>
const billingPrescriptionMap = <?php echo json_encode($billingPrescriptionMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function showSection(sectionId, triggerEl) {
            // Hide all sections
            document.querySelectorAll('[id$="-section"]').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            const selectedSection = document.getElementById(sectionId + '-section');
            if (!selectedSection) {
                return;
            }
            selectedSection.style.display = 'block';
            selectedSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

            if (sectionId === 'doctors') {
                setDoctorFormVisibility(false);
            }
            
            // Update active nav link
            if (triggerEl) {
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                triggerEl.classList.add('active');
            }
        }

        function clearDoctorPhotoPreview() {
            const preview = document.getElementById('photoPreview');
            const img = document.getElementById('photoPreviewImg');

            if (img) {
                img.src = '';
            }
            if (preview) {
                preview.style.display = 'none';
            }
        }

        function formatDoctorCode(value) {
            if (!value) {
                return '';
            }
            const stringValue = String(value);
            if (stringValue.toUpperCase().startsWith('DOC-')) {
                return stringValue.toUpperCase();
            }
            const numeric = parseInt(stringValue.replace(/\D/g, ''), 10);
            if (Number.isNaN(numeric)) {
                return stringValue;
            }
            return 'DOC-' + String(numeric).padStart(4, '0');
        }

        function inferDepartmentFromSpecialization(specialization) {
            const value = String(specialization || '').trim().toLowerCase();
            if (!value) {
                return '-';
            }

            const mapping = {
                'general physician': 'General Medicine',
                'cardiologist': 'Cardiology',
                'dermatologist': 'Dermatology',
                'neurologist': 'Neurology',
                'neurosurgeon': 'Neurosurgery',
                'orthopedic surgeon': 'Orthopedics',
                'pediatrician': 'Pediatrics',
                'gynecologist': 'Gynecology',
                'obstetrician': 'Obstetrics',
                'ent specialist': 'ENT',
                'ophthalmologist': 'Ophthalmology',
                'dentist': 'Dental',
                'psychiatrist': 'Psychiatry',
                'psychologist': 'Psychology',
                'urologist': 'Urology',
                'nephrologist': 'Nephrology',
                'gastroenterologist': 'Gastroenterology',
                'endocrinologist': 'Endocrinology',
                'pulmonologist': 'Pulmonology',
                'oncologist': 'Oncology',
                'radiologist': 'Radiology',
                'pathologist': 'Pathology',
                'general surgeon': 'General Surgery',
                'laparoscopic surgeon': 'Laparoscopic Surgery',
                'anesthesiologist': 'Anesthesia',
                'rheumatologist': 'Rheumatology',
                'immunologist': 'Immunology',
                'physiotherapist': 'Physiotherapy',
                'emergency medicine specialist': 'Emergency',
                'icu specialist': 'ICU',
                'family medicine': 'Family Medicine',
                'internal medicine': 'Internal Medicine',
                'vascular surgeon': 'Vascular Surgery',
                'plastic surgeon': 'Plastic Surgery',
                'cosmetologist': 'Cosmetology',
                'sexologist': 'General Medicine',
                'nutritionist': 'Nutrition & Dietetics',
                'diabetologist': 'Diabetology',
                'pain management specialist': 'Pain Management'
            };

            return mapping[value] || '-';
        }

        function setDoctorIdDisplay(value) {
            const display = document.getElementById('doctorIdDisplay');
            const input = document.getElementById('doctorIdInput');
            const formatted = formatDoctorCode(value);
            if (display) {
                display.textContent = formatted || 'DOC-0001';
            }
            if (input) {
                input.value = formatted;
            }
        }

        function fetchNextDoctorId() {
            return fetch('get_next_doctor_id.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.doctor_id) {
                        setDoctorIdDisplay(data.doctor_id);
                    }
                })
                .catch(() => {
                    setDoctorIdDisplay('DOC-0001');
                });
        }

        function setDoctorFormMode(isEdit) {
            const form = document.getElementById('doctorForm');
            const title = document.getElementById('doctorFormTitle');
            const submitBtn = document.getElementById('doctorFormSubmit');
            const cancelBtn = document.getElementById('doctorFormCancel');
            const password = document.getElementById('doctorPassword');
            const confirmPassword = document.getElementById('doctorConfirmPassword');
            const requiredAddFields = ['full_name', 'contact_no', 'email', 'specialization', 'department', 'qualification', 'license_number', 'joining_date'];

            if (title) {
                title.innerHTML = isEdit
                    ? '<span class="text-icon me-2" aria-hidden="true">&#9998;</span>Edit Doctor'
                    : '<span class="text-icon me-2" aria-hidden="true">&#10133;</span>Add New Doctor';
            }

            if (submitBtn) {
                submitBtn.innerHTML = isEdit
                    ? '<i class="fas fa-save me-1"></i>Save Changes'
                    : '<i class="fas fa-user-plus me-1"></i>Save Doctor';
            }

            if (cancelBtn) {
                cancelBtn.style.display = 'inline-block';
            }

            if (!form) {
                return;
            }

            requiredAddFields.forEach(fieldName => {
                const field = form.elements[fieldName];
                if (!field) {
                    return;
                }
                if (isEdit) {
                    field.removeAttribute('required');
                } else {
                    field.setAttribute('required', 'required');
                }
            });

            if (password && confirmPassword) {
                if (isEdit) {
                    password.removeAttribute('required');
                    confirmPassword.removeAttribute('required');
                } else {
                    password.setAttribute('required', 'required');
                    confirmPassword.setAttribute('required', 'required');
                }
            }
        }

        function resetDoctorFormState() {
            const form = document.getElementById('doctorForm');
            const message = document.getElementById('doctorFormMessage');
            const editId = document.getElementById('editDoctorId');

            if (form) {
                form.reset();
            }
            if (message) {
                message.textContent = '';
                message.className = 'form-message';
            }
            if (editId) {
                editId.value = '';
            }

            clearDoctorPhotoPreview();
            setDoctorFormMode(false);
            clearDoctorFieldErrors();
            fetchNextDoctorId();
        }

        function isDoctorFormVisible() {
            const wrapper = document.getElementById('doctorFormWrapper');
            return !!wrapper && window.getComputedStyle(wrapper).display !== 'none';
        }

        function setDoctorFormVisibility(visible) {
            const wrapper = document.getElementById('doctorFormWrapper');
            const toggleBtn = document.getElementById('toggleDoctorFormBtn');

            if (wrapper) {
                wrapper.style.display = visible ? 'block' : 'none';
                wrapper.setAttribute('aria-hidden', visible ? 'false' : 'true');
            }
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', visible ? 'true' : 'false');
                toggleBtn.innerHTML = visible
                    ? '<i class="fas fa-times me-1"></i>Hide Doctor Form'
                    : '<i class="fas fa-user-plus me-1"></i>Add New Doctor';
            }

            if (!visible) {
                resetDoctorFormState();
            } else if (!isDoctorFormEditMode()) {
                fetchNextDoctorId();
            }
        }

        function toggleDoctorForm() {
            if (isDoctorFormVisible()) {
                setDoctorFormVisibility(false);
                return;
            }

            resetDoctorFormState();
            setDoctorFormVisibility(true);
            const fullNameInput = document.getElementById('doctorFullName');
            if (fullNameInput) {
                fullNameInput.focus();
            }
        }

        function isDoctorFormEditMode() {
            const editId = document.getElementById('editDoctorId');
            return !!(editId && editId.value);
        }

        function clearDoctorFieldErrors() {
            document.querySelectorAll('#doctorForm .field-error').forEach(node => {
                node.textContent = '';
            });
            document.querySelectorAll('#doctorForm .is-invalid').forEach(node => {
                node.classList.remove('is-invalid');
            });
        }

        function setDoctorFieldError(fieldName, message) {
            const field = document.querySelector('#doctorForm [name="' + fieldName + '"]');
            const error = document.querySelector('#doctorForm [data-error-for="' + fieldName + '"]');
            if (field) {
                field.classList.add('is-invalid');
            }
            if (error) {
                error.textContent = message;
            }
        }

        function splitDoctorName(fullName) {
            const parts = fullName.trim().split(/\s+/).filter(Boolean);
            if (parts.length === 0) {
                return { first: '', last: '' };
            }
            if (parts.length === 1) {
                return { first: parts[0], last: 'NA' };
            }
            return { first: parts[0], last: parts.slice(1).join(' ') };
        }

        function syncDoctorNameFields() {
            const fullNameInput = document.getElementById('doctorFullName');
            const firstName = document.getElementById('doctorFirstName');
            const lastName = document.getElementById('doctorLastName');
            if (!fullNameInput || !firstName || !lastName) {
                return;
            }
            const split = splitDoctorName(fullNameInput.value || '');
            firstName.value = split.first;
            lastName.value = split.last;
        }

        function suggestDoctorUsername() {
            const username = document.getElementById('doctorUsername');
            const email = document.getElementById('doctorEmail');
            const fullName = document.getElementById('doctorFullName');
            if (!username) {
                return;
            }
            if (username.value.trim() !== '') {
                return;
            }
            let value = '';
            if (email && email.value.includes('@')) {
                value = email.value.split('@')[0];
            }
            if (!value && fullName) {
                value = fullName.value.toLowerCase().replace(/[^a-z0-9]+/g, '.').replace(/^\.|\.$/g, '');
            }
            username.value = value;
        }

        function validateDoctorForm() {
            const form = document.getElementById('doctorForm');
            if (!form) {
                return false;
            }
            clearDoctorFieldErrors();

            const isEdit = isDoctorFormEditMode();
            let valid = true;

            const fullName = form.elements.full_name ? form.elements.full_name.value.trim() : '';
            if (!fullName) {
                setDoctorFieldError('full_name', 'Full name is required.');
                valid = false;
            }

            const mobile = form.elements.contact_no ? form.elements.contact_no.value.trim() : '';
            if (!mobile) {
                setDoctorFieldError('contact_no', 'Mobile number is required.');
                valid = false;
            } else if (!/^\d{10,15}$/.test(mobile.replace(/\s+/g, ''))) {
                setDoctorFieldError('contact_no', 'Enter a valid mobile number.');
                valid = false;
            }

            const email = form.elements.email ? form.elements.email.value.trim() : '';
            if (!email) {
                setDoctorFieldError('email', 'Email is required.');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                setDoctorFieldError('email', 'Enter a valid email address.');
                valid = false;
            }

            if (!isEdit) {
                const specialization = form.elements.specialization ? form.elements.specialization.value.trim() : '';
                const department = form.elements.department ? form.elements.department.value.trim() : '';
                const qualification = form.elements.qualification ? form.elements.qualification.value.trim() : '';
                const license = form.elements.license_number ? form.elements.license_number.value.trim() : '';
                const joining = form.elements.joining_date ? form.elements.joining_date.value.trim() : '';

                if (!specialization) {
                    setDoctorFieldError('specialization', 'Specialization is required.');
                    valid = false;
                }
                if (!department) {
                    setDoctorFieldError('department', 'Department is required.');
                    valid = false;
                }
                if (!qualification) {
                    setDoctorFieldError('qualification', 'Qualification is required.');
                    valid = false;
                }
                if (!license) {
                    setDoctorFieldError('license_number', 'License number is required.');
                    valid = false;
                }
                if (!joining) {
                    setDoctorFieldError('joining_date', 'Joining date is required.');
                    valid = false;
                }
            }

            const password = form.elements.password ? form.elements.password.value : '';
            const confirm = form.elements.confirm_password ? form.elements.confirm_password.value : '';
            if (!isEdit) {
                if (!password) {
                    setDoctorFieldError('password', 'Password is required.');
                    valid = false;
                }
                if (!confirm) {
                    setDoctorFieldError('confirm_password', 'Confirm your password.');
                    valid = false;
                } else if (password !== confirm) {
                    setDoctorFieldError('confirm_password', 'Passwords do not match.');
                    valid = false;
                }
            } else if (password || confirm) {
                if (password !== confirm) {
                    setDoctorFieldError('confirm_password', 'Passwords do not match.');
                    valid = false;
                }
            }

            const doctorIdInput = document.getElementById('doctorIdInput');
            if (doctorIdInput && !doctorIdInput.value) {
                setDoctorFieldError('doctor_id', 'Doctor ID is required.');
                valid = false;
            }

            return valid;
        }
        
        function searchPatient() {
            const searchTerm = document.getElementById('patientSearch').value;
            // Implement patient search logic
            alert('Searching for: ' + searchTerm);
        }
        
        function addNewPatient() {
            // Implement add patient logic
            alert('Add new patient form will open');
        }
        
        function exportPatients() {
            // Implement export logic
            alert('Exporting patient data');
        }
        
        function prefillAppointmentUpdate(appointmentId, status) {
            const appointmentIdInput = document.getElementById('appointmentId');
            const appointmentStatusInput = document.getElementById('appointmentStatus');
            const appointmentMessage = document.getElementById('appointmentUpdateMessage');

            if (appointmentIdInput) {
                appointmentIdInput.value = appointmentId;
                appointmentIdInput.focus();
            }
            if (appointmentStatusInput && status) {
                appointmentStatusInput.value = status;
            }
            if (appointmentMessage) {
                appointmentMessage.textContent = '';
                appointmentMessage.className = 'form-message mt-3';
            }
        }

        function getAppointmentBadgeClass(status) {
            switch (status) {
                case 'Pending':
                    return 'bg-warning text-dark';
                case 'Confirmed':
                    return 'bg-success';
                case 'Completed':
                    return 'bg-info text-dark';
                case 'Cancelled':
                    return 'bg-danger';
                default:
                    return 'bg-secondary';
            }
        }

        function renderAppointmentActionButtons(appointmentId, status) {
            let html = '<div class="table-actions">';
            html += '<button type="button" class="btn btn-outline-primary btn-sm" onclick="prefillAppointmentUpdate(' + appointmentId + ', \'' + status + '\')">Use ID</button>';

            if (status === 'Pending') {
                html += '<button type="button" class="btn btn-outline-success btn-sm" onclick="updateAppointmentStatus(' + appointmentId + ', \'Confirmed\')">Confirm</button>';
                html += '<button type="button" class="btn btn-outline-danger btn-sm" onclick="updateAppointmentStatus(' + appointmentId + ', \'Cancelled\')">Cancel</button>';
            }

            html += '</div>';
            return html;
        }

        function syncAppointmentRow(appointmentId, status) {
            const row = document.querySelector('#adminAppointmentListBody tr[data-appointment-id="' + appointmentId + '"]');
            if (!row) {
                return;
            }

            const statusCell = row.querySelector('.appointment-row-status');
            const actionsCell = row.querySelector('.appointment-row-actions');

            if (statusCell) {
                statusCell.innerHTML = '<span class="badge ' + getAppointmentBadgeClass(status) + '">' + status + '</span>';
            }
            if (actionsCell) {
                actionsCell.innerHTML = renderAppointmentActionButtons(appointmentId, status);
            }
        }

        function updateAppointmentStatus(appointmentIdOverride, statusOverride) {
            const appointmentIdInput = document.getElementById('appointmentId');
            const appointmentStatusInput = document.getElementById('appointmentStatus');
            const appointmentMessage = document.getElementById('appointmentUpdateMessage');
            const appointmentId = appointmentIdOverride ? parseInt(appointmentIdOverride, 10) : parseInt(appointmentIdInput.value, 10);
            const status = statusOverride || appointmentStatusInput.value;

            if (!appointmentId || appointmentId <= 0) {
                if (appointmentMessage) {
                    appointmentMessage.textContent = 'Please enter a valid Appointment ID.';
                    appointmentMessage.className = 'form-message error mt-3';
                } else {
                    alert('Please enter a valid Appointment ID');
                }
                return;
            }

            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            formData.append('status', status);

            if (appointmentMessage) {
                appointmentMessage.textContent = 'Updating appointment status...';
                appointmentMessage.className = 'form-message mt-3';
            }

            fetch('update_admin_appointment_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to update appointment status');
                }

                if (appointmentIdInput) {
                    appointmentIdInput.value = data.appointment_id;
                }
                if (appointmentStatusInput) {
                    appointmentStatusInput.value = data.status;
                }
                if (appointmentMessage) {
                    appointmentMessage.textContent = data.message || 'Appointment status updated successfully';
                    appointmentMessage.className = 'form-message success mt-3';
                }

                syncAppointmentRow(data.appointment_id, data.status);
            })
            .catch(error => {
                if (appointmentMessage) {
                    appointmentMessage.textContent = error.message || 'Error updating appointment status';
                    appointmentMessage.className = 'form-message error mt-3';
                } else {
                    alert(error.message || 'Error updating appointment status');
                }
            });
        }

        function renderWeeklyChart(metric) {
            const canvas = document.getElementById('weeklyChart');
            const tooltip = document.getElementById('weeklyTooltip');
            if (!canvas) {
                return;
            }
            const ctx = canvas.getContext('2d');
            const parentWidth = canvas.parentElement ? canvas.parentElement.clientWidth : canvas.width;
            const ratio = window.devicePixelRatio || 1;
            const desiredHeight = 140;
            canvas.width = Math.max(parentWidth, 320) * ratio;
            canvas.height = desiredHeight * ratio;
            canvas.style.width = Math.max(parentWidth, 320) + 'px';
            canvas.style.height = desiredHeight + 'px';
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            const series = {
                appointments: { label: 'Appointments', values: [12, 19, 14, 22, 28, 24, 30], color: '#0d6efd', unit: '' },
                revenue: { label: 'Revenue (Rs k)', values: [45, 52, 48, 60, 72, 66, 80], color: '#16a34a', unit: 'k' },
                wait: { label: 'Avg. Wait (min)', values: [14, 12, 16, 11, 10, 13, 9], color: '#f59e0b', unit: ' min' }
            };
            const activeMetric = series[metric] ? metric : 'appointments';
            const values = series[activeMetric].values;
            const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const padding = 20;
            const width = canvas.width / ratio;
            const height = canvas.height / ratio;
            const maxValue = Math.max(...values, 1);

            ctx.clearRect(0, 0, width, height);
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, height - padding);
            ctx.lineTo(width - padding, height - padding);
            ctx.stroke();

            ctx.strokeStyle = series[activeMetric].color;
            ctx.lineWidth = 2;
            ctx.beginPath();
            const points = values.map((value, index) => {
                const x = padding + (index * (width - padding * 2) / (values.length - 1));
                const y = height - padding - (value / maxValue) * (height - padding * 2);
                return { x, y, value, label: labels[index] };
            });
            points.forEach((pt, index) => {
                if (index === 0) {
                    ctx.moveTo(pt.x, pt.y);
                } else {
                    ctx.lineTo(pt.x, pt.y);
                }
            });
            ctx.stroke();

            ctx.fillStyle = series[activeMetric].color;
            points.forEach(pt => {
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, 3, 0, Math.PI * 2);
                ctx.fill();
            });

            ctx.fillStyle = '#111827';
            ctx.font = '11px Arial';
            labels.forEach((label, index) => {
                const x = padding + (index * (width - padding * 2) / (labels.length - 1));
                ctx.fillText(label, x - 8, height - 6);
            });

            const onMove = event => {
                const rect = canvas.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                const hit = points.find(pt => Math.hypot(pt.x - x, pt.y - y) <= 6);
                if (hit && tooltip) {
                    tooltip.style.left = hit.x + 'px';
                    tooltip.style.top = hit.y + 'px';
                    tooltip.textContent = hit.label + ': ' + hit.value + series[activeMetric].unit;
                    tooltip.style.display = 'block';
                } else if (tooltip) {
                    tooltip.style.display = 'none';
                }
            };

            canvas.onmousemove = onMove;
            canvas.onmouseleave = () => {
                if (tooltip) {
                    tooltip.style.display = 'none';
                }
            };
        }

        function renderCalendar() {
            const calendar = document.getElementById('calendar');
            if (!calendar) {
                return;
            }
            const today = new Date();
            const year = today.getFullYear();
            const month = today.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const monthName = today.toLocaleString('en-US', { month: 'long' });
            const weeks = [];
            let day = 1 - firstDay;

            while (day <= daysInMonth) {
                const week = [];
                for (let i = 0; i < 7; i += 1) {
                    week.push(day);
                    day += 1;
                }
                weeks.push(week);
            }

            let html = '<div class="mb-3"><strong>' + monthName + ' ' + year + '</strong></div>';
            html += '<table class="calendar-grid">';
            html += '<thead><tr>';
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(label => {
                html += '<th>' + label + '</th>';
            });
            html += '</tr></thead><tbody>';
            weeks.forEach(week => {
                html += '<tr>';
                week.forEach(dayNumber => {
                    const isToday = dayNumber === today.getDate();
                    const inMonth = dayNumber >= 1 && dayNumber <= daysInMonth;
                    const cellClass = inMonth ? 'calendar-day' : 'calendar-day calendar-muted';
                    html += '<td class="' + cellClass + '">';
                    if (inMonth) {
                        html += '<span class="' + (isToday ? 'calendar-today' : '') + '">' + dayNumber + '</span>';
                    }
                    html += '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table>';

            calendar.innerHTML = html;
        }

        function downloadReport(type) {
            const now = new Date();
            const dateLabel = now.toISOString().slice(0, 10);
            let title = 'Report';
            let rows = [
                ['Date', 'Appointments', 'Revenue']
            ];

            if (type === 'monthly') {
                title = 'Monthly Report';
                rows.push([dateLabel, '420', '450000']);
            } else if (type === 'weekly') {
                title = 'Weekly Report';
                rows.push([dateLabel, '128', '120000']);
            } else {
                title = 'Custom Report';
                const from = prompt('From date (YYYY-MM-DD)', dateLabel);
                const to = prompt('To date (YYYY-MM-DD)', dateLabel);
                rows.push([from || dateLabel, '96', '82000']);
                rows.push([to || dateLabel, '112', '91000']);
            }

            const csv = rows.map(row => row.join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = title.replace(' ', '_').toLowerCase() + '_' + dateLabel + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function editDoctor(doctorId, buttonEl) {
            const form = document.getElementById('doctorForm');
            const editId = document.getElementById('editDoctorId');
            const message = document.getElementById('doctorFormMessage');

            if (!form || !editId) {
                return;
            }

            setDoctorFormVisibility(true);
            setDoctorFormMode(true);

            if (message) {
                message.textContent = 'Loading doctor details...';
                message.className = 'form-message';
            }

            const formData = new FormData();
            formData.append('action', 'fetch');
            formData.append('doctor_id', doctorId);

            fetch('fetch_doctor.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.doctor) {
                        throw new Error(data.message || 'Unable to load doctor details');
                    }

                    const doctor = data.doctor;
                    const fullName = (doctor.f_name || '') + ' ' + (doctor.l_name || '');
                    if (form.full_name) {
                        form.full_name.value = fullName.trim();
                    }
                    if (form.email) {
                        form.email.value = doctor.email || '';
                    }
                    if (form.specialization) {
                        form.specialization.value = doctor.specialization || '';
                    }
                    if (form.department) {
                        form.department.value = doctor.department || '';
                    }
                    if (form.qualification) {
                        form.qualification.value = doctor.qualification || '';
                    }
                    if (form.license_number) {
                        form.license_number.value = doctor.license_number || '';
                    }
                    if (form.registration_council) {
                        form.registration_council.value = doctor.registration_council || '';
                    }
                    if (form.joining_date) {
                        form.joining_date.value = doctor.joining_date || '';
                    }
                    if (form.doctor_type) {
                        form.doctor_type.value = doctor.doctor_type || '';
                    }
                    if (form.experience) {
                        form.experience.value = doctor.experience || '';
                    }
                    if (form.consultation_fees) {
                        form.consultation_fees.value = doctor.consultation_fees || '';
                    }
                    if (form.contact_no) {
                        form.contact_no.value = doctor.contact_no || '';
                    }
                    if (form.address) {
                        form.address.value = doctor.address || '';
                    }
                    if (form.city) {
                        form.city.value = doctor.city || '';
                    }
                    if (form.state) {
                        form.state.value = doctor.state || '';
                    }
                    if (form.country) {
                        form.country.value = doctor.country || '';
                    }
                    if (form.dob) {
                        form.dob.value = doctor.dob || '';
                    }
                    if (form.gender) {
                        form.gender.value = doctor.gender || '';
                    }
                    if (form.username) {
                        form.username.value = doctor.username || '';
                    }

                    setDoctorIdDisplay(doctor.doctor_id || doctor.d_id || '');
                    syncDoctorNameFields();

                    const emergencyToggle = document.getElementById('doctorEmergency');
                    const onlineToggle = document.getElementById('doctorOnline');
                    const prescriptionToggle = document.getElementById('doctorPrescription');
                    if (emergencyToggle) {
                        emergencyToggle.checked = doctor.emergency_available === '1' || doctor.emergency_available === 1 || doctor.emergency_available === true;
                    }
                    if (onlineToggle) {
                        onlineToggle.checked = doctor.online_consultation === '1' || doctor.online_consultation === 1 || doctor.online_consultation === true;
                    }
                    if (prescriptionToggle) {
                        prescriptionToggle.checked = doctor.can_write_prescription === '1' || doctor.can_write_prescription === 1 || doctor.can_write_prescription === true;
                    }

                    const workingDays = (doctor.working_days || '').split(',').map(day => day.trim()).filter(Boolean);
                    document.querySelectorAll('input[name="working_days[]"]').forEach(input => {
                        input.checked = workingDays.includes(input.value);
                    });

                    if (form.available_from) {
                        form.available_from.value = doctor.available_from || '';
                    }
                    if (form.available_to) {
                        form.available_to.value = doctor.available_to || '';
                    }
                    if (form.break_start) {
                        form.break_start.value = doctor.break_start || '';
                    }
                    if (form.break_end) {
                        form.break_end.value = doctor.break_end || '';
                    }

                    if (form.elements.password) {
                        form.elements.password.value = '';
                    }
                    if (form.elements.confirm_password) {
                        form.elements.confirm_password.value = '';
                    }

                    if (doctor.photo_path) {
                        const preview = document.getElementById('photoPreview');
                        const img = document.getElementById('photoPreviewImg');
                        if (img && preview) {
                            img.src = doctor.photo_path;
                            preview.style.display = 'block';
                        }
                    } else {
                        clearDoctorPhotoPreview();
                    }

                    editId.value = doctorId;

                    if (message) {
                        message.textContent = '';
                        message.className = 'form-message';
                    }
                })
                .catch(error => {
                    if (message) {
                        message.textContent = error.message || 'Unable to load doctor details';
                        message.className = 'form-message error';
                    }
                });
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function toggleDoctorStatus(doctorId, buttonEl) {
            const row = buttonEl.closest('tr');
            if (!row) {
                return;
            }
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('doctor_id', doctorId);

            fetch('update_doctor.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Status update failed');
                    }
                    const badge = row.querySelector('.badge');
                    if (badge) {
                        badge.textContent = data.doctor.status;
                        badge.className = 'badge ' + (data.doctor.status === 'Active' ? 'bg-success' : 'bg-warning');
                    }
                    buttonEl.textContent = data.doctor.status === 'Active' ? 'Deactivate' : 'Activate';
                })
                .catch(error => {
                    alert(error.message);
                });
        }
        
        // Form submission for doctor
        document.getElementById('doctorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const message = document.getElementById('doctorFormMessage');
            const editId = document.getElementById('editDoctorId');
            const submitBtn = document.getElementById('doctorFormSubmit');
            const toast = document.getElementById('doctorToast');

            syncDoctorNameFields();
            suggestDoctorUsername();

            if (!validateDoctorForm()) {
                return;
            }

            const formData = new FormData(form);

            if (message) {
                message.textContent = 'Saving doctor...';
                message.className = 'form-message';
            }

            const isEdit = editId && editId.value !== '';
            if (isEdit) {
                formData.append('action', 'edit');
                formData.append('doctor_id', editId.value);
            }

            fetch(isEdit ? 'update_doctor.php' : 'add_doctor.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Unable to save doctor');
                    }

                    if (message) {
                        message.textContent = isEdit ? 'Doctor updated successfully.' : 'Doctor added successfully.';
                        message.className = 'form-message success';
                    }
                    if (!isEdit && toast) {
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 3000);
                    }

                    const tbody = document.getElementById('doctorListBody');
                    if (tbody && data.doctor) {
                        const doctorCode = data.doctor.doctor_id_display || formatDoctorCode(data.doctor.doctor_id || data.doctor.id);
                        const department = data.doctor.department || inferDepartmentFromSpecialization(data.doctor.specialization);

                        if (isEdit) {
                            const existingRow = tbody.querySelector('tr[data-doctor-id="' + editId.value + '"]');
                            if (existingRow) {
                                const codeCell = existingRow.querySelector('.doctor-code');
                                const nameCell = existingRow.querySelector('.doctor-name');
                                const specCell = existingRow.querySelector('.doctor-spec');
                                const deptCell = existingRow.querySelector('.doctor-dept');
                                const emailCell = existingRow.querySelector('.doctor-email');
                                const contactCell = existingRow.querySelector('.doctor-contact');

                                if (codeCell) codeCell.textContent = doctorCode;
                                if (nameCell) nameCell.textContent = 'Dr. ' + data.doctor.f_name + ' ' + data.doctor.l_name;
                                if (specCell) specCell.textContent = data.doctor.specialization;
                                if (deptCell) deptCell.textContent = department;
                                if (emailCell) emailCell.textContent = data.doctor.email;
                                if (contactCell) contactCell.textContent = data.doctor.contact_no;
                            }
                        } else {
                            const row = document.createElement('tr');
                            row.setAttribute('data-doctor-id', data.doctor.id);
                            const codeCell = document.createElement('td');
                            const nameCell = document.createElement('td');
                            const specCell = document.createElement('td');
                            const deptCell = document.createElement('td');
                            const contactCell = document.createElement('td');
                            const emailCell = document.createElement('td');
                            const statusCell = document.createElement('td');
                            const actionsCell = document.createElement('td');

                            codeCell.textContent = doctorCode;
                            nameCell.textContent = 'Dr. ' + data.doctor.f_name + ' ' + data.doctor.l_name;
                            specCell.textContent = data.doctor.specialization;
                            deptCell.textContent = department;
                            contactCell.textContent = data.doctor.contact_no;
                            emailCell.textContent = data.doctor.email;

                            codeCell.className = 'doctor-code';
                            nameCell.className = 'doctor-name';
                            specCell.className = 'doctor-spec';
                            deptCell.className = 'doctor-dept';
                            contactCell.className = 'doctor-contact';
                            emailCell.className = 'doctor-email';
                            statusCell.className = 'doctor-status';

                            const badge = document.createElement('span');
                            badge.className = 'badge bg-success';
                            badge.textContent = 'Active';
                            statusCell.appendChild(badge);

                            const actionsWrap = document.createElement('div');
                            actionsWrap.className = 'table-actions';

                            const editBtn = document.createElement('button');
                            editBtn.className = 'btn btn-outline-primary btn-sm';
                            editBtn.textContent = 'Edit';
                            editBtn.onclick = function() {
                                editDoctor(data.doctor.id, editBtn);
                            };

                            const toggleBtn = document.createElement('button');
                            toggleBtn.className = 'btn btn-outline-success btn-sm';
                            toggleBtn.textContent = 'Deactivate';
                            toggleBtn.onclick = function() {
                                toggleDoctorStatus(data.doctor.id, toggleBtn);
                            };

                            actionsWrap.appendChild(editBtn);
                            actionsWrap.appendChild(toggleBtn);
                            actionsCell.appendChild(actionsWrap);

                            row.appendChild(codeCell);
                            row.appendChild(nameCell);
                            row.appendChild(specCell);
                            row.appendChild(deptCell);
                            row.appendChild(contactCell);
                            row.appendChild(emailCell);
                            row.appendChild(statusCell);
                            row.appendChild(actionsCell);
                            tbody.prepend(row);
                        }
                    }

                    form.reset();
                    if (editId) {
                        editId.value = '';
                    }
                    clearDoctorPhotoPreview();
                    setDoctorFormMode(false);
                    clearDoctorFieldErrors();
                    fetchNextDoctorId();
                })
                .catch(error => {
                    if (message) {
                        message.textContent = error.message;
                        message.className = 'form-message error';
                    }
                });
        });

        document.getElementById('doctorFormCancel').addEventListener('click', function() {
            setDoctorFormVisibility(false);
            const list = document.getElementById('doctorListColumn');
            if (list) {
                list.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        // ==================== PATIENT MANAGEMENT FUNCTIONS ====================

        function editPatient(patientId, buttonEl) {
            const row = buttonEl.closest('tr');
            if (!row) {
                return;
            }

            const form = document.getElementById('patientForm');
            const editId = document.getElementById('editPatientId');
            const title = document.getElementById('patientFormTitle');
            const submitBtn = document.getElementById('patientFormSubmit');
            const cancelBtn = document.getElementById('patientFormCancel');
            const passwordField = document.getElementById('patientPassword');

            if (!form || !editId || !title || !submitBtn || !cancelBtn) {
                return;
            }

            // Fetch patient details
            fetch('fetch_patient_details.php?pid=' + patientId)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error loading patient details: ' + data.message);
                        return;
                    }

                    const patient = data.patient;
                    form.firstname.value = patient.firstname || '';
                    form.lastname.value = patient.lastname || '';
                    form.email.value = patient.email || '';
                    form.dob.value = patient.dob || '';
                    form.gender.value = patient.gender || '';
                    form.height.value = patient.height || '';
                    form.weight.value = patient.weight || '';
                    form.bloodgroup.value = patient.bloodgroup || '';
                    form.contact_no.value = patient.contact_no || '';
                    form.state.value = patient.state || '';
                    form.country.value = patient.country || '';
                    form.allergies.value = patient.allergies || '';
                    form.medical_conditions.value = patient.medical_conditions || '';
                    form.emergency_contact.value = patient.emergency_contact || '';
                    form.emergency_phone.value = patient.emergency_phone || '';

                    editId.value = patientId;
                    passwordField.removeAttribute('required');
                    passwordField.placeholder = 'Password (leave blank to keep current)';

                    title.innerHTML = '<span class="text-icon me-2" aria-hidden="true">&#9998;</span>Edit Patient';
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Changes';
                    cancelBtn.style.display = 'inline-block';

                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        function deletePatient(patientId, buttonEl) {
            if (!confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('patient_id', patientId);

            fetch('update_patient.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Error deleting patient');
                        return;
                    }

                    const row = buttonEl.closest('tr');
                    if (row) {
                        row.remove();
                    }
                    alert('Patient deleted successfully');
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        function searchPatient() {
            const searchInput = document.getElementById('patientSearch');
            const searchTerm = searchInput.value.trim().toLowerCase();

            if (!searchTerm) {
                alert('Please enter a search term');
                return;
            }

            const tbody = document.getElementById('patientListBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const nameCell = row.querySelector('.patient-name');
                const emailCell = row.querySelector('.patient-email');
                const idCell = row.querySelector('td:first-child');

                const name = nameCell ? nameCell.textContent.toLowerCase() : '';
                const email = emailCell ? emailCell.textContent.toLowerCase() : '';
                const id = idCell ? idCell.textContent.toLowerCase() : '';

                if (name.includes(searchTerm) || email.includes(searchTerm) || id.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Form submission for patient
        document.getElementById('patientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const message = document.getElementById('patientFormMessage');
            const editId = document.getElementById('editPatientId');
            const title = document.getElementById('patientFormTitle');
            const submitBtn = document.getElementById('patientFormSubmit');
            const cancelBtn = document.getElementById('patientFormCancel');
            const passwordField = document.getElementById('patientPassword');
            const formData = new FormData(form);

            if (message) {
                message.textContent = 'Saving patient...';
                message.className = 'form-message';
            }

            const isEdit = editId && editId.value !== '';
            if (isEdit) {
                formData.append('action', 'edit');
                formData.append('patient_id', editId.value);
            }

            fetch(isEdit ? 'update_patient.php' : 'add_patient.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Unable to save patient');
                    }

                    if (message) {
                        message.textContent = isEdit ? 'Patient updated successfully.' : 'Patient added successfully.';
                        message.className = 'form-message success';
                    }

                    const tbody = document.getElementById('patientListBody');
                    if (tbody && data.patient) {
                        if (isEdit) {
                            const existingRow = tbody.querySelector('tr[data-patient-id="' + editId.value + '"]');
                            if (existingRow) {
                                const nameCell = existingRow.querySelector('.patient-name');
                                const emailCell = existingRow.querySelector('.patient-email');
                                const contactCell = existingRow.querySelector('.patient-contact');
                                const bloodCell = existingRow.querySelector('.patient-blood');

                                if (nameCell) nameCell.textContent = data.patient.firstname + ' ' + data.patient.lastname;
                                if (emailCell) emailCell.textContent = data.patient.email;
                                if (contactCell) contactCell.textContent = data.patient.contact_no;
                                if (bloodCell) bloodCell.textContent = data.patient.bloodgroup;
                            }
                        } else {
                            const row = document.createElement('tr');
                            row.setAttribute('data-patient-id', data.patient.pid);

                            const idCell = document.createElement('td');
                            const nameCell = document.createElement('td');
                            const emailCell = document.createElement('td');
                            const contactCell = document.createElement('td');
                            const bloodCell = document.createElement('td');

                            idCell.textContent = data.patient.pid;
                            nameCell.textContent = data.patient.firstname + ' ' + data.patient.lastname;
                            emailCell.textContent = data.patient.email;
                            contactCell.textContent = data.patient.contact_no;
                            bloodCell.textContent = data.patient.bloodgroup;

                            nameCell.className = 'patient-name';
                            emailCell.className = 'patient-email';
                            contactCell.className = 'patient-contact';
                            bloodCell.className = 'patient-blood';

                            row.appendChild(idCell);
                            row.appendChild(nameCell);
                            row.appendChild(emailCell);
                            row.appendChild(contactCell);
                            row.appendChild(bloodCell);
                            tbody.prepend(row);
                        }
                    }

                    form.reset();
                    if (editId) {
                        editId.value = '';
                    }
                    if (title && submitBtn && cancelBtn && passwordField) {
                        title.innerHTML = '<span class="text-icon me-2" aria-hidden="true">&#10133;</span>Add New Patient';
                        submitBtn.innerHTML = '<i class="fas fa-user-plus me-1"></i>Add Patient';
                        cancelBtn.style.display = 'none';
                        passwordField.setAttribute('required', 'required');
                        passwordField.placeholder = 'Password';
                    }
                })
                .catch(error => {
                    if (message) {
                        message.textContent = error.message;
                        message.className = 'form-message error';
                    }
                });
        });

        document.getElementById('patientFormCancel').addEventListener('click', function() {
            const form = document.getElementById('patientForm');
            const message = document.getElementById('patientFormMessage');
            const editId = document.getElementById('editPatientId');
            const title = document.getElementById('patientFormTitle');
            const submitBtn = document.getElementById('patientFormSubmit');
            const passwordField = document.getElementById('patientPassword');

            if (form) {
                form.reset();
            }
            if (message) {
                message.textContent = '';
                message.className = 'form-message';
            }
            if (editId) {
                editId.value = '';
            }
            if (title && submitBtn && passwordField) {
                title.innerHTML = '<span class="text-icon me-2" aria-hidden="true">&#10133;</span>Add New Patient';
                submitBtn.innerHTML = '<i class="fas fa-user-plus me-1"></i>Add Patient';
                passwordField.setAttribute('required', 'required');
                passwordField.placeholder = 'Password';
            }
            this.style.display = 'none';
        });

        // ===== STAFF MANAGEMENT FUNCTIONS =====
        function editStaff(staffId, buttonEl) {
            const form = document.getElementById('staffForm');
            const editId = document.getElementById('editStaffId');
            const passwordField = document.getElementById('staffPassword');
            const title = document.getElementById('staffFormTitle');
            const submitBtn = document.getElementById('staffFormSubmit');
            const cancelBtn = document.getElementById('staffFormCancel');

            fetch('fetch_patient_details.php?sid=' + staffId)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error: ' + (data.message || 'Unable to fetch staff details'));
                        return;
                    }

                    const staff = data.staff;
                    form.fname.value = staff.fname || '';
                    form.lname.value = staff.lname || '';
                    form.email.value = staff.email || '';
                    form.role.value = staff.role || '';
                    form.department.value = staff.department || '';
                    form.phone.value = staff.phone || '';
                    form.hire_date.value = staff.hire_date || '';
                    form.qualifications.value = staff.qualifications || '';
                    form.experience.value = staff.experience_years || '';
                    form.license_number.value = staff.license_number || '';
                    form.license_expiry.value = staff.license_expiry || '';
                    form.shift.value = staff.shift || 'flexible';
                    form.salary.value = staff.salary || '';
                    form.emergency_contact.value = staff.emergency_contact || '';
                    form.emergency_phone.value = staff.emergency_phone || '';

                    editId.value = staffId;
                    passwordField.removeAttribute('required');
                    passwordField.placeholder = 'Password (leave blank to keep current)';

                    title.innerHTML = '<span class="text-icon me-2" aria-hidden="true">&#9998;</span>Edit Staff';
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Changes';
                    cancelBtn.style.display = 'inline-block';

                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        function toggleStaffStatus(staffId, buttonEl) {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('staff_id', staffId);

            fetch('add_staff.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Error updating staff status');
                        return;
                    }

                    const row = buttonEl.closest('tr');
                    if (row) {
                        const statusBadge = row.querySelector('td:nth-child(6) span');
                        if (statusBadge) {
                            if (statusBadge.textContent === 'Active') {
                                statusBadge.textContent = 'Inactive';
                                statusBadge.className = 'badge bg-danger';
                                buttonEl.textContent = 'Activate';
                                buttonEl.className = 'btn btn-outline-success btn-sm';
                            } else {
                                statusBadge.textContent = 'Active';
                                statusBadge.className = 'badge bg-success';
                                buttonEl.textContent = 'Deactivate';
                                buttonEl.className = 'btn btn-outline-warning btn-sm';
                            }
                        }
                    }
                    alert('Staff status updated successfully');
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        function removeStaff(staffId, buttonEl) {
            if (!confirm('Are you sure you want to permanently remove this staff member? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('staff_id', staffId);

            fetch('add_staff.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Error removing staff member');
                        return;
                    }

                    const row = buttonEl.closest('tr');
                    if (row) {
                        row.style.transition = 'opacity 0.3s ease';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            alert('Staff member removed successfully');
                        }, 300);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        function searchStaff() {
            const searchInput = document.getElementById('staffSearch');
            const searchTerm = searchInput.value.trim().toLowerCase();

            if (!searchTerm) {
                alert('Please enter a search term');
                return;
            }

            const tbody = document.getElementById('staffListBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const nameCell = row.querySelector('.staff-name');
                const emailCell = row.querySelector('.staff-email');
                const roleCell = row.querySelector('.staff-role');

                const name = nameCell ? nameCell.textContent.toLowerCase() : '';
                const email = emailCell ? emailCell.textContent.toLowerCase() : '';
                const role = roleCell ? roleCell.textContent.toLowerCase() : '';

                if (name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Form submission for staff
        document.getElementById('staffForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const message = document.getElementById('staffFormMessage');
            const editId = document.getElementById('editStaffId');
            const title = document.getElementById('staffFormTitle');
            const submitBtn = document.getElementById('staffFormSubmit');
            const passwordField = document.getElementById('staffPassword');
            const formData = new FormData(form);

            if (message) {
                message.textContent = 'Saving staff member...';
                message.className = 'form-message';
            }

            const isEdit = editId && editId.value !== '';
            if (isEdit) {
                formData.append('action', 'update');
                formData.append('staff_id', editId.value);
            } else {
                formData.append('action', 'add');
            }

            fetch('add_staff.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Unable to save staff member');
                    }

                    if (message) {
                        message.textContent = isEdit ? 'Staff member updated successfully.' : 'Staff member added successfully.';
                        message.className = 'form-message success';
                    }

                    const tbody = document.getElementById('staffListBody');
                    if (tbody && data.staff) {
                        if (isEdit) {
                            const existingRow = tbody.querySelector('tr[data-staff-id="' + editId.value + '"]');
                            if (existingRow) {
                                const nameCell = existingRow.querySelector('.staff-name');
                                const emailCell = existingRow.querySelector('.staff-email');
                                const roleCell = existingRow.querySelector('.staff-role');
                                const deptCell = existingRow.querySelector('.staff-dept');
                                const phoneCell = existingRow.querySelector('.staff-phone');

                                if (nameCell) nameCell.textContent = data.staff.fname + ' ' + data.staff.lname;
                                if (emailCell) emailCell.textContent = data.staff.email;
                                if (roleCell) roleCell.innerHTML = '<span class="badge bg-info">' + (data.staff.role || 'N/A') + '</span>';
                                if (deptCell) deptCell.textContent = data.staff.department || 'N/A';
                                if (phoneCell) phoneCell.textContent = data.staff.phone || 'N/A';
                            }
                        } else {
                            const row = document.createElement('tr');
                            row.setAttribute('data-staff-id', data.staff.user_id);

                            const nameCell = document.createElement('td');
                            const emailCell = document.createElement('td');
                            const roleCell = document.createElement('td');
                            const deptCell = document.createElement('td');
                            const phoneCell = document.createElement('td');
                            const statusCell = document.createElement('td');
                            const actionsCell = document.createElement('td');

                            nameCell.textContent = data.staff.fname + ' ' + data.staff.lname;
                            emailCell.textContent = data.staff.email;
                            roleCell.innerHTML = '<span class="badge bg-info">' + (data.staff.role || 'N/A') + '</span>';
                            deptCell.textContent = data.staff.department || 'N/A';
                            phoneCell.textContent = data.staff.phone || 'N/A';
                            statusCell.innerHTML = '<span class="badge bg-success">Active</span>';

                            nameCell.className = 'staff-name';
                            emailCell.className = 'staff-email';
                            roleCell.className = 'staff-role';
                            deptCell.className = 'staff-dept';
                            phoneCell.className = 'staff-phone';

                            const actionsWrap = document.createElement('div');
                            actionsWrap.className = 'table-actions';

                            const editBtn = document.createElement('button');
                            editBtn.className = 'btn btn-outline-primary btn-sm';
                            editBtn.textContent = 'Edit';
                            editBtn.onclick = function() {
                                editStaff(data.staff.user_id, editBtn);
                            };

                            const deactBtn = document.createElement('button');
                            deactBtn.className = 'btn btn-outline-warning btn-sm';
                            deactBtn.textContent = 'Deactivate';
                            deactBtn.onclick = function() {
                                toggleStaffStatus(data.staff.user_id, deactBtn);
                            };

                            actionsWrap.appendChild(editBtn);
                            actionsWrap.appendChild(deactBtn);
                            actionsCell.appendChild(actionsWrap);

                            row.appendChild(nameCell);
                            row.appendChild(emailCell);
                            row.appendChild(roleCell);
                            row.appendChild(deptCell);
                            row.appendChild(phoneCell);
                            row.appendChild(statusCell);
                            row.appendChild(actionsCell);
                            tbody.prepend(row);
                        }
                    }

                    form.reset();
                    if (editId) {
                        editId.value = '';
                    }
                    if (title && submitBtn && passwordField) {
                        title.innerHTML = '<span class="text-icon me-2" aria-hidden="true">&#10133;</span>Add New Staff';
                        submitBtn.innerHTML = '<i class="fas fa-user-plus me-1"></i>Add Staff';
                        passwordField.setAttribute('required', 'required');
                        passwordField.placeholder = 'Password';
                    }
                })
                .catch(error => {
                    if (message) {
                        message.textContent = error.message;
                        message.className = 'form-message error';
                    }
                });
        });

        document.getElementById('staffFormCancel').addEventListener('click', function() {
            const form = document.getElementById('staffForm');
            const message = document.getElementById('staffFormMessage');
            const editId = document.getElementById('editStaffId');
            const title = document.getElementById('staffFormTitle');
            const submitBtn = document.getElementById('staffFormSubmit');
            const passwordField = document.getElementById('staffPassword');

            if (form) {
                form.reset();
            }
            if (message) {
                message.textContent = '';
                message.className = 'form-message';
            }
            if (editId) {
                editId.value = '';
            }
            if (title && submitBtn && passwordField) {
                title.innerHTML = '<span class="text-icon me-2" aria-hidden="true">&#10133;</span>Add New Staff';
                submitBtn.innerHTML = '<i class="fas fa-user-plus me-1"></i>Add Staff';
                passwordField.setAttribute('required', 'required');
                passwordField.placeholder = 'Password';
            }
            this.style.display = 'none';
        });

        // ===== PATIENT HEALTH DATA FUNCTIONS =====
        let selectedHealthPatientId = null;
        let healthSearchDebounce = null;

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, function(ch) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[ch];
            });
        }

        function conditionMeta(status) {
            const normalized = String(status || 'stable').toLowerCase();
            if (normalized === 'critical') {
                return { label: 'Critical', badge: 'bg-danger' };
            }
            if (normalized === 'recovering') {
                return { label: 'Recovering', badge: 'bg-warning text-dark' };
            }
            return { label: 'Stable', badge: 'bg-success' };
        }

        function toTitleCase(value) {
            return String(value || '')
                .toLowerCase()
                .replace(/\b\w/g, function(ch) { return ch.toUpperCase(); });
        }

        function postHealthAction(action, dataObj) {
            const formData = new FormData();
            formData.append('action', action);
            Object.keys(dataObj || {}).forEach(function(key) {
                if (dataObj[key] !== undefined && dataObj[key] !== null) {
                    formData.append(key, dataObj[key]);
                }
            });

            return fetch('manage_patient_health.php', {
                method: 'POST',
                body: formData
            }).then(function(response) {
                return response.text().then(function(raw) {
                    if (!raw) {
                        throw new Error('Empty response from server');
                    }
                    let parsed;
                    try {
                        parsed = JSON.parse(raw);
                    } catch (e) {
                        throw new Error('Invalid server response');
                    }
                    if (!response.ok || !parsed.success) {
                        throw new Error(parsed.message || 'Request failed');
                    }
                    return parsed;
                });
            });
        }

        function refreshPatientList() {
            const search = (document.getElementById('patientSearchInput') || {}).value || '';
            const searchField = (document.getElementById('patientSearchField') || {}).value || 'all';
            const condition = (document.getElementById('patientConditionFilter') || {}).value || '';
            const tbody = document.getElementById('patientListBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-muted">Loading patients...</td></tr>';
            }

            postHealthAction('list_patients', { search: search, search_field: searchField, condition: condition })
                .then(function(data) {
                    populateSearchSuggestions(data.patients || []);
                    renderPatientList(data.patients || []);
                    updateHealthOverview(data.overview || {});
                })
                .catch(function(error) {
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
                    }
                });
        }

        function loadAllPatientsIntoDropdown() {
            postHealthAction('list_patients', { search: '', search_field: 'all', condition: '' })
                .then(function(data) {
                    populatePatientDropdown(data.patients || []);
                    populateSearchSuggestions(data.patients || []);
                })
                .catch(function() {
                    // Keep existing options if fetch fails
                });
        }

        function populatePatientDropdown(patients) {
            const select = document.getElementById('healthPatientSelect');
            if (!select) {
                return;
            }

            const currentValue = select.value;
            const options = ['<option value="">-- Select Patient --</option>'];

            patients.forEach(function(p) {
                options.push('<option value="' + Number(p.pid) + '">' + escapeHtml(p.full_name) + ' (' + escapeHtml(p.email || '-') + ')</option>');
            });

            select.innerHTML = options.join('');
            if (currentValue && patients.some(function(p) { return String(p.pid) === String(currentValue); })) {
                select.value = currentValue;
            }
        }

        function populateSearchSuggestions(patients) {
            const datalist = document.getElementById('patientSearchSuggestions');
            if (!datalist) {
                return;
            }

            const unique = new Set();
            const options = [];
            patients.forEach(function(p) {
                const name = (p.full_name || '').trim();
                const email = (p.email || '').trim();

                if (name && !unique.has(name)) {
                    unique.add(name);
                    options.push('<option value="' + escapeHtml(name) + '"></option>');
                }
                if (email && !unique.has(email)) {
                    unique.add(email);
                    options.push('<option value="' + escapeHtml(email) + '"></option>');
                }
            });

            datalist.innerHTML = options.join('');
        }

        function updateHealthOverview(overview) {
            const card = document.getElementById('healthOverviewCard');
            if (!card) {
                return;
            }
            card.innerHTML = '<div class="row g-2">'
                + '<div class="col-md-4"><div class="alert alert-light border mb-0"><strong>Total Patients:</strong> ' + (overview.total_patients || 0) + '</div></div>'
                + '<div class="col-md-4"><div class="alert alert-light border mb-0"><strong>Critical Patients:</strong> <span class="text-danger fw-bold">' + (overview.critical_patients || 0) + '</span></div></div>'
                + '<div class="col-md-4"><div class="alert alert-light border mb-0"><strong>Updated (7d):</strong> ' + (overview.recent_updates || 0) + '</div></div>'
                + '</div>';
        }

        function renderPatientList(patients) {
            const tbody = document.getElementById('patientListBody');

            if (!tbody) {
                return;
            }
            if (!patients.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-muted">No patients found</td></tr>';
                return;
            }

            tbody.innerHTML = patients.map(function(p) {
                const meta = conditionMeta(p.patient_condition);
                return '<tr>'
                    + '<td>' + escapeHtml(p.full_name) + '</td>'
                    + '<td>' + escapeHtml(p.email || '-') + '</td>'
                    + '<td><span class="badge ' + meta.badge + '">' + meta.label + '</span></td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-primary" onclick="selectPatientFromList(' + Number(p.pid) + ')">Select</button></td>'
                    + '</tr>';
            }).join('');
        }

        function selectPatientFromList(pid) {
            const select = document.getElementById('healthPatientSelect');
            if (select) {
                select.value = String(pid);
            }
            loadPatientHealthData();
        }

        function loadPatientHealthData() {
            const patientId = document.getElementById('healthPatientSelect').value;
            if (!patientId) {
                alert('Please select a patient');
                return;
            }

            selectedHealthPatientId = patientId;
            const staticWeightPatientId = document.getElementById('weightPatientId');
            if (staticWeightPatientId) {
                staticWeightPatientId.value = patientId;
            }

            const title = document.getElementById('healthDataTitle');
            const display = document.getElementById('healthDataDisplay');
            if (display) {
                display.innerHTML = '<div class="text-muted">Loading patient records...</div>';
            }

            postHealthAction('get_patient_health', { patient_id: patientId })
                .then(function(data) {
                    const patient = data.patient || {};
                    const meta = conditionMeta(patient.patient_condition);
                    if (title) {
                        title.innerHTML = '<span class="text-icon me-2" aria-hidden="true">&#128170;</span>Health Data for ' + escapeHtml(toTitleCase(patient.firstname || '')) + ' ' + escapeHtml(toTitleCase(patient.lastname || ''));
                    }

                    const alerts = (data.alerts || []).map(function(a) {
                        return '<li class="mb-1">' + escapeHtml(a) + '</li>';
                    }).join('');

                    const weightsRows = (data.weights || []).map(function(w) {
                        return '<tr>'
                            + '<td>' + escapeHtml(w.recorded_date) + '</td>'
                            + '<td>' + escapeHtml(w.weight) + ' kg</td>'
                            + '<td class="text-end">'
                            + '<button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="editWeightRecord(' + Number(w.weight_id || 0) + ', \'' + escapeHtml(w.weight) + '\', \'' + escapeHtml(w.recorded_date) + '\')">Edit</button>'
                            + '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWeightRecord(' + Number(w.weight_id || 0) + ')">Delete</button>'
                            + '</td></tr>';
                    }).join('') || '<tr><td colspan="3" class="text-muted">No weight records</td></tr>';

                    const vitalsRows = (data.vitals || []).map(function(v) {
                        return '<tr>'
                            + '<td>' + escapeHtml(v.recorded_date) + '</td>'
                            + '<td>' + escapeHtml(v.systolic_bp) + '/' + escapeHtml(v.diastolic_bp) + '</td>'
                            + '<td>' + escapeHtml(v.heart_rate) + '</td>'
                            + '<td class="text-end">'
                            + '<button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="editVitalsRecord(' + Number(v.vitals_id || 0) + ', \'' + escapeHtml(v.systolic_bp) + '\', \'' + escapeHtml(v.diastolic_bp) + '\', \'' + escapeHtml(v.heart_rate) + '\', \'' + escapeHtml(v.recorded_date) + '\')">Edit</button>'
                            + '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteVitalsRecord(' + Number(v.vitals_id || 0) + ')">Delete</button>'
                            + '</td></tr>';
                    }).join('') || '<tr><td colspan="4" class="text-muted">No vitals records</td></tr>';

                    const notesRows = (data.notes || []).map(function(n) {
                        return '<div class="border rounded p-2 mb-2">'
                            + '<div class="d-flex justify-content-between mb-1"><strong>' + escapeHtml(n.author_name || 'Admin') + '</strong><small class="text-muted">' + escapeHtml(n.created_at) + '</small></div>'
                            + '<div class="mb-2">' + escapeHtml(n.note_text) + '</div>'
                            + '<div class="text-end">'
                            + '<button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="editPatientNote(' + Number(n.note_id || 0) + ', \'' + escapeHtml(n.note_text) + '\')">Edit</button>'
                            + '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePatientNote(' + Number(n.note_id || 0) + ')">Delete</button>'
                            + '</div></div>';
                    }).join('') || '<div class="text-muted">No notes yet</div>';

                    const fileRows = (data.files || []).map(function(f) {
                        return '<tr>'
                            + '<td>' + escapeHtml(f.file_type) + '</td>'
                            + '<td><a href="' + escapeHtml(f.file_path) + '" target="_blank" rel="noopener">' + escapeHtml(f.original_name) + '</a></td>'
                            + '<td>' + escapeHtml(f.uploaded_at) + '</td>'
                            + '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePatientFile(' + Number(f.file_id || 0) + ')">Delete</button></td>'
                            + '</tr>';
                    }).join('') || '<tr><td colspan="4" class="text-muted">No files uploaded</td></tr>';

                    const timelineRows = (data.timeline || []).map(function(t) {
                        return '<li class="mb-2"><strong>' + escapeHtml(t.event_date) + '</strong> - ' + escapeHtml(t.description) + '</li>';
                    }).join('') || '<li class="text-muted">No history available</li>';

                    display.innerHTML = ''
                        + '<div class="health-hero">'
                        + '<div><strong>Patient Condition:</strong> <span class="badge ' + meta.badge + ' ms-2" id="currentConditionBadge">' + meta.label + '</span></div>'
                        + '<div class="health-hero-actions">'
                        + '<button type="button" class="btn btn-sm btn-outline-primary" onclick="exportPatientData(\'csv\')">CSV</button>'
                        + '<button type="button" class="btn btn-sm btn-outline-primary" onclick="exportPatientData(\'excel\')">Excel</button>'
                        + '<button type="button" class="btn btn-sm btn-outline-primary" onclick="printPatientReport()">PDF</button>'
                        + '</div></div>'
                        + '<div class="health-panel-grid row g-3">'
                        + '<div class="col-12"><div class="health-card">'
                        + '<h6>Alerts & Indicators</h6>'
                        + (alerts ? '<ul class="mb-0 text-danger">' + alerts + '</ul>' : '<div class="text-success">No active alerts</div>')
                        + '</div></div>'
                        + '<div class="col-lg-6"><div class="health-card">'
                        + '<h6>Update Condition (Admin Only)</h6>'
                        + '<div class="row g-2"><div class="col-md-8"><select class="form-select" id="patientConditionSelect">'
                        + '<option value="critical" ' + (meta.label === 'Critical' ? 'selected' : '') + '>Critical</option>'
                        + '<option value="stable" ' + (meta.label === 'Stable' ? 'selected' : '') + '>Stable</option>'
                        + '<option value="recovering" ' + (meta.label === 'Recovering' ? 'selected' : '') + '>Recovering</option>'
                        + '</select></div>'
                        + '<div class="col-md-4"><button type="button" class="btn btn-primary w-100" onclick="savePatientCondition()">Save</button></div></div>'
                        + '<div id="conditionMessage" class="form-message mt-2" aria-live="polite"></div>'
                        + '</div></div>'
                        + '<div class="col-lg-6"><div class="health-card">'
                        + '<h6>Add Weight Record</h6>'
                        + '<input type="hidden" id="weightPatientId" value="' + Number(patientId) + '">'
                        + '<div class="row g-2"><div class="col-md-5"><input type="number" class="form-control" id="add_weight_val" step="0.1" placeholder="Weight (kg)"></div>'
                        + '<div class="col-md-4"><input type="date" class="form-control" id="weight_date"></div>'
                        + '<div class="col-md-3"><button type="button" class="btn btn-primary w-100" onclick="addWeightData()">Add</button></div></div>'
                        + '<div id="weightMessage" class="form-message mt-2" aria-live="polite"></div>'
                        + '</div></div>'
                        + '<div class="col-12"><div class="health-card"><h6>Weight Records</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Weight</th><th class="text-end">Actions</th></tr></thead><tbody>' + weightsRows + '</tbody></table></div></div></div>'
                        + '<div class="col-12"><div class="health-card">'
                        + '<h6>Vital Signs</h6><div class="row g-2 mb-2">'
                        + '<div class="col-md-3"><input id="vitalSystolic" type="number" class="form-control" placeholder="Systolic BP"></div>'
                        + '<div class="col-md-3"><input id="vitalDiastolic" type="number" class="form-control" placeholder="Diastolic BP"></div>'
                        + '<div class="col-md-2"><input id="vitalHeartRate" type="number" class="form-control" placeholder="Heart Rate"></div>'
                        + '<div class="col-md-2"><input id="vitalDate" type="date" class="form-control"></div>'
                        + '<div class="col-md-2"><button type="button" class="btn btn-outline-primary w-100" onclick="addVitalsRecord()">Add</button></div>'
                        + '</div><div id="vitalMessage" class="form-message mb-2"></div><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>BP</th><th>HR</th><th class="text-end">Actions</th></tr></thead><tbody>' + vitalsRows + '</tbody></table></div>'
                        + '</div></div>'
                        + '<div class="col-lg-6"><div class="health-card"><h6>Doctor/Admin Notes</h6><div class="input-group mb-2"><input id="adminNoteText" class="form-control" placeholder="Add note"><button type="button" class="btn btn-outline-primary" onclick="addPatientNote()">Save</button></div><div id="noteMessage" class="form-message mb-2"></div>' + notesRows + '</div></div>'
                        + '<div class="col-lg-6"><div class="health-card"><h6>File Uploads</h6><div class="row g-2 mb-2">'
                        + '<div class="col-md-4"><select id="uploadFileType" class="form-select"><option value="report">Report (PDF)</option><option value="prescription">Prescription</option><option value="image">Medical Image</option></select></div>'
                        + '<div class="col-md-5"><input id="uploadHealthFile" type="file" class="form-control"></div>'
                        + '<div class="col-md-3"><button type="button" class="btn btn-outline-primary w-100" onclick="uploadPatientFile()">Upload</button></div>'
                        + '</div><div id="uploadMessage" class="form-message mb-2"></div><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Type</th><th>File</th><th>Date</th><th class="text-end">Actions</th></tr></thead><tbody>' + fileRows + '</tbody></table></div></div></div>'
                        + '<div class="col-12"><div class="health-card"><h6>Medical History Timeline</h6><ul class="timeline-list mb-0 ps-3">' + timelineRows + '</ul></div></div>'
                        + '</div>';

                    updateHealthOverview(data.overview || {});
                })
                .catch(function(error) {
                    if (display) {
                        display.innerHTML = '<div class="text-danger">' + escapeHtml(error.message) + '</div>';
                    }
                });
        }

        function savePatientCondition() {
            const patientId = selectedHealthPatientId || document.getElementById('healthPatientSelect').value;
            const conditionSelect = document.getElementById('patientConditionSelect');
            const message = document.getElementById('conditionMessage');

            if (!patientId || !conditionSelect) {
                alert('Please load a patient first');
                return;
            }

            if (!confirm('Confirm condition update?')) {
                return;
            }

            if (message) {
                message.textContent = 'Saving condition...';
                message.className = 'form-message';
            }

            postHealthAction('update_patient_condition', {
                patient_id: patientId,
                patient_condition: conditionSelect.value
            })
                .then(function() {
                    if (message) {
                        message.textContent = 'Condition updated successfully';
                        message.className = 'form-message success';
                    }
                    refreshPatientList();
                    loadPatientHealthData();
                })
                .catch(function(error) {
                    if (message) {
                        message.textContent = error.message;
                        message.className = 'form-message error';
                    }
                });
        }

        function addWeightData() {
            const patientId = document.getElementById('weightPatientId').value;
            const weight = document.getElementById('add_weight_val').value;
            const date = document.getElementById('weight_date').value;
            const message = document.getElementById('weightMessage');

            if (!patientId) {
                alert('Please load a patient first');
                return;
            }
            if (!weight || Number(weight) <= 0) {
                message.textContent = 'Please enter a valid weight';
                message.className = 'form-message error';
                return;
            }
            if (!date) {
                message.textContent = 'Please select a date';
                message.className = 'form-message error';
                return;
            }
            if (!confirm('Add this weight record?')) {
                return;
            }

            message.textContent = 'Saving weight...';
            message.className = 'form-message';

            postHealthAction('add_weight', {
                patient_id: patientId,
                weight: weight,
                date: date
            })
                .then(function() {
                    message.textContent = 'Weight record added successfully!';
                    message.className = 'form-message success';
                    const weightInput = document.getElementById('add_weight_val');
                    const dateInput = document.getElementById('weight_date');
                    if (weightInput) {
                        weightInput.value = '';
                    }
                    if (dateInput) {
                        dateInput.value = '';
                    }
                    loadPatientHealthData();
                    refreshPatientList();
                })
                .catch(function(error) {
                    message.textContent = error.message;
                    message.className = 'form-message error';
                });
        }

        function editWeightRecord(weightId, weight, recordedDate) {
            if (!weightId) {
                alert('This record cannot be edited because it has no unique ID.');
                return;
            }
            const newWeight = prompt('Enter new weight (kg):', weight);
            if (newWeight === null) {
                return;
            }
            const newDate = prompt('Enter date (YYYY-MM-DD):', recordedDate);
            if (newDate === null) {
                return;
            }
            if (!confirm('Save weight changes?')) {
                return;
            }
            postHealthAction('update_weight', {
                patient_id: selectedHealthPatientId,
                weight_id: weightId,
                weight: newWeight,
                date: newDate
            }).then(loadPatientHealthData).catch(function(error) { alert(error.message); });
        }

        function deleteWeightRecord(weightId) {
            if (!weightId) {
                alert('This record cannot be deleted because it has no unique ID.');
                return;
            }
            if (!confirm('Delete this weight record?')) {
                return;
            }
            postHealthAction('delete_weight', {
                patient_id: selectedHealthPatientId,
                weight_id: weightId
            }).then(loadPatientHealthData).catch(function(error) { alert(error.message); });
        }

        function addVitalsRecord() {
            if (!selectedHealthPatientId) {
                alert('Please load a patient first');
                return;
            }
            const systolic = document.getElementById('vitalSystolic').value;
            const diastolic = document.getElementById('vitalDiastolic').value;
            const heartRate = document.getElementById('vitalHeartRate').value;
            const date = document.getElementById('vitalDate').value || new Date().toISOString().slice(0, 10);
            const message = document.getElementById('vitalMessage');

            if (!systolic || !diastolic || !heartRate) {
                message.textContent = 'Please fill all vital fields';
                message.className = 'form-message error';
                return;
            }
            if (!confirm('Add new vitals record?')) {
                return;
            }

            postHealthAction('add_vitals', {
                patient_id: selectedHealthPatientId,
                systolic_bp: systolic,
                diastolic_bp: diastolic,
                heart_rate: heartRate,
                date: date
            })
                .then(function() {
                    message.textContent = 'Vitals added successfully';
                    message.className = 'form-message success';
                    loadPatientHealthData();
                })
                .catch(function(error) {
                    message.textContent = error.message;
                    message.className = 'form-message error';
                });
        }

        function editVitalsRecord(vitalsId, systolic, diastolic, heartRate, date) {
            const ns = prompt('Systolic BP:', systolic);
            if (ns === null) return;
            const nd = prompt('Diastolic BP:', diastolic);
            if (nd === null) return;
            const nh = prompt('Heart Rate:', heartRate);
            if (nh === null) return;
            const ndt = prompt('Date (YYYY-MM-DD):', date);
            if (ndt === null) return;
            if (!confirm('Save vitals changes?')) {
                return;
            }
            postHealthAction('update_vitals', {
                patient_id: selectedHealthPatientId,
                vitals_id: vitalsId,
                systolic_bp: ns,
                diastolic_bp: nd,
                heart_rate: nh,
                date: ndt
            }).then(loadPatientHealthData).catch(function(error) { alert(error.message); });
        }

        function deleteVitalsRecord(vitalsId) {
            if (!confirm('Delete this vitals record?')) {
                return;
            }
            postHealthAction('delete_vitals', {
                patient_id: selectedHealthPatientId,
                vitals_id: vitalsId
            }).then(loadPatientHealthData).catch(function(error) { alert(error.message); });
        }

        function addPatientNote() {
            const noteText = (document.getElementById('adminNoteText') || {}).value || '';
            const message = document.getElementById('noteMessage');
            if (!selectedHealthPatientId) {
                alert('Please load a patient first');
                return;
            }
            if (!noteText.trim()) {
                message.textContent = 'Please enter a note';
                message.className = 'form-message error';
                return;
            }
            if (!confirm('Save this note?')) {
                return;
            }
            postHealthAction('add_note', {
                patient_id: selectedHealthPatientId,
                note_text: noteText.trim()
            }).then(function() {
                message.textContent = 'Note saved';
                message.className = 'form-message success';
                document.getElementById('adminNoteText').value = '';
                loadPatientHealthData();
            }).catch(function(error) {
                message.textContent = error.message;
                message.className = 'form-message error';
            });
        }

        function editPatientNote(noteId, currentText) {
            const updated = prompt('Edit note:', currentText);
            if (updated === null) return;
            if (!confirm('Save note changes?')) return;
            postHealthAction('update_note', {
                patient_id: selectedHealthPatientId,
                note_id: noteId,
                note_text: updated
            }).then(loadPatientHealthData).catch(function(error) { alert(error.message); });
        }

        function deletePatientNote(noteId) {
            if (!confirm('Delete this note?')) return;
            postHealthAction('delete_note', {
                patient_id: selectedHealthPatientId,
                note_id: noteId
            }).then(loadPatientHealthData).catch(function(error) { alert(error.message); });
        }

        function uploadPatientFile() {
            if (!selectedHealthPatientId) {
                alert('Please load a patient first');
                return;
            }
            const fileInput = document.getElementById('uploadHealthFile');
            const fileType = (document.getElementById('uploadFileType') || {}).value || 'report';
            const message = document.getElementById('uploadMessage');

            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                message.textContent = 'Please choose a file';
                message.className = 'form-message error';
                return;
            }
            if (!confirm('Upload selected file?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_patient_file');
            formData.append('patient_id', selectedHealthPatientId);
            formData.append('file_type', fileType);
            formData.append('health_file', fileInput.files[0]);

            fetch('manage_patient_health.php', {
                method: 'POST',
                body: formData
            })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (!data.success) {
                        throw new Error(data.message || 'Upload failed');
                    }
                    message.textContent = 'File uploaded successfully';
                    message.className = 'form-message success';
                    fileInput.value = '';
                    loadPatientHealthData();
                })
                .catch(function(error) {
                    message.textContent = error.message;
                    message.className = 'form-message error';
                });
        }

        function deletePatientFile(fileId) {
            if (!confirm('Delete this file?')) return;
            postHealthAction('delete_patient_file', {
                patient_id: selectedHealthPatientId,
                file_id: fileId
            }).then(loadPatientHealthData).catch(function(error) { alert(error.message); });
        }

        function exportPatientData(format) {
            if (!selectedHealthPatientId) {
                alert('Please load a patient first');
                return;
            }
            postHealthAction('export_patient_data', {
                patient_id: selectedHealthPatientId,
                format: format
            }).then(function(data) {
                const byteChars = atob(data.base64_content || '');
                const byteNumbers = new Array(byteChars.length);
                for (let i = 0; i < byteChars.length; i++) {
                    byteNumbers[i] = byteChars.charCodeAt(i);
                }
                const blob = new Blob([new Uint8Array(byteNumbers)], { type: data.mime_type || 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = data.file_name || ('patient_' + selectedHealthPatientId + '.csv');
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            }).catch(function(error) { alert(error.message); });
        }

        function printPatientReport() {
            const container = document.getElementById('healthDataDisplay');
            if (!container) return;
            const printWindow = window.open('', '_blank');
            if (!printWindow) return;
            printWindow.document.write('<html><head><title>Patient Health Report</title></head><body>' + container.innerHTML + '</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }

        function getInventoryStatus(stockValue) {
            const stock = Number(stockValue) || 0;
            if (stock > 100) {
                return { label: 'Good', className: 'bg-success' };
            }
            if (stock > 40) {
                return { label: 'Low', className: 'bg-warning' };
            }
            return { label: 'Critical', className: 'bg-danger' };
        }

        function updateInventoryMedicinePreview() {
            const select = document.getElementById('inventoryMedicineSelect');
            const preview = document.getElementById('inventoryMedicinePreview');

            if (!select || !preview) {
                return;
            }

            const option = select.options[select.selectedIndex];
            if (!option || !option.value) {
                preview.innerHTML = '<div class="medicine-preview-empty">Select a medicine to view its cost, stock, and status.</div>';
                return;
            }

            const stock = parseInt(option.getAttribute('data-stock') || '0', 10);
            const price = parseFloat(option.getAttribute('data-price') || '0');
            const name = option.getAttribute('data-name') || option.textContent || '';
            const status = getInventoryStatus(stock);

            preview.innerHTML = ''
                + '<div class="medicine-preview-title">' + name + '</div>'
                + '<div class="medicine-preview-meta">'
                + '<span class="medicine-preview-pill"><span class="label">Stock</span>' + stock + '</span>'
                + '<span class="medicine-preview-pill"><span class="label">Status</span>' + status.label + '</span>'
                + '<span class="medicine-preview-pill"><span class="label">Price</span>Rs. ' + price.toFixed(2) + '</span>'
                + '</div>';
        }

        function saveInventoryRow(buttonEl) {
            const row = buttonEl ? buttonEl.closest('tr') : null;
            if (!row) {
                return;
            }

            const medicineId = parseInt(row.getAttribute('data-medicine-id') || '0', 10);
            const medicineCell = row.querySelector('.medicine-name');
            const costInput = row.querySelector('.medicine-cost-input');
            const stockInput = row.querySelector('.medicine-stock-input');
            const statusBadge = row.querySelector('.medicine-status .badge');

            if (!medicineCell || !costInput || !stockInput || !statusBadge) {
                return;
            }

            const currentName = medicineCell.textContent.trim();
            const updatedCost = parseFloat(costInput.value || '0');
            const updatedStock = parseInt(stockInput.value || '0', 10);

            if (!currentName || Number.isNaN(updatedCost) || updatedCost < 0 || Number.isNaN(updatedStock) || updatedStock < 0) {
                alert('Please enter valid stock and cost values.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_inventory_medicine');
            formData.append('medicine_id', String(medicineId));
            formData.append('medicine_name', currentName);
            formData.append('unit_price', updatedCost.toFixed(2));
            formData.append('stock_level', String(updatedStock));

            fetch('manage_inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(async function(response) {
                const raw = await response.text();
                let data = null;
                try {
                    data = raw ? JSON.parse(raw) : null;
                } catch (error) {
                    throw new Error(raw || 'Unexpected server response');
                }

                if (!response.ok) {
                    throw new Error((data && data.message) || 'Unable to update medicine.');
                }

                return data;
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Unable to update medicine.');
                }

                const finalName = data.medicine_name || currentName;
                const finalCost = Number(data.unit_price ?? updatedCost);
                const finalStock = Number(data.stock_level ?? updatedStock);
                const finalId = Number(data.medicine_id ?? medicineId);

                medicineCell.textContent = finalName;
                costInput.value = finalCost.toFixed(2);
                stockInput.value = String(finalStock);

                const select = document.getElementById('inventoryMedicineSelect');
                if (select) {
                    const option = Array.from(select.options).find(function(opt) {
                        return (opt.getAttribute('data-name') || opt.value || '') === finalName;
                    });
                    if (option) {
                        option.textContent = finalName + ' - Rs. ' + finalCost.toFixed(2);
                        option.setAttribute('data-name', finalName);
                        option.setAttribute('data-price', finalCost.toFixed(2));
                        option.setAttribute('data-stock', String(finalStock));
                        option.setAttribute('data-medicine-id', String(finalId));
                    }
                }

                updateInventoryMedicinePreview();

                const status = getInventoryStatus(finalStock);
                statusBadge.className = 'badge ' + status.className;
                statusBadge.textContent = status.label;

                row.setAttribute('data-medicine-id', String(finalId));
            })
            .catch(error => {
                alert(error.message || 'Failed to update medicine.');
            });
        }

        function addInventoryMedicine(event) {
            if (event) {
                event.preventDefault();
            }

            const nameInput = document.getElementById('newMedicineName');
            const stockInput = document.getElementById('newMedicineStock');
            const tableBody = document.getElementById('inventoryTableBody');
            const message = document.getElementById('inventoryMessage');

            if (!nameInput || !stockInput || !tableBody) {
                return;
            }

            const name = (nameInput.value || '').trim();
            const stock = parseInt(stockInput.value || '', 10);

            if (!name || Number.isNaN(stock) || stock < 0) {
                if (message) {
                    message.textContent = 'Please enter a valid medicine name and stock quantity.';
                    message.className = 'form-message error';
                }
                return;
            }

            const status = getInventoryStatus(stock);
            const row = document.createElement('tr');
            row.innerHTML = ''
                + '<td class="medicine-name"></td>'
                + '<td><input type="number" class="form-control form-control-sm medicine-cost-input" min="0" step="0.01" value="0.00"></td>'
                + '<td><input type="number" class="form-control form-control-sm medicine-stock-input" min="0" step="1" value="' + stock + '"></td>'
                + '<td class="medicine-status"><span class="badge"></span></td>'
                + '<td><button type="button" class="btn btn-primary btn-sm" onclick="saveInventoryRow(this)">Save</button></td>';

            const medicineCell = row.querySelector('.medicine-name');
            const costInput = row.querySelector('.medicine-cost-input');
            const stockInputEl = row.querySelector('.medicine-stock-input');
            const statusBadge = row.querySelector('.medicine-status .badge');

            medicineCell.textContent = name;
            costInput.value = '0.00';
            stockInputEl.value = String(stock);
            statusBadge.className = 'badge ' + status.className;
            statusBadge.textContent = status.label;

            tableBody.prepend(row);

            nameInput.value = '';
            stockInput.value = '';

            if (message) {
                message.textContent = 'New medicine added successfully.';
                message.className = 'form-message success';
            }
        }

        function updateInvoiceAmountFromPatient() {
            const patientSelect = document.getElementById('invoicePatientId');
            const amountInput = document.getElementById('invoiceAmount');
            const servicesInput = document.getElementById('invoiceServices');
            const preview = document.getElementById('invoicePrescriptionPreview');

            if (!patientSelect || !amountInput) {
                return;
            }

            const selectedOption = patientSelect.options[patientSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                amountInput.value = '';
                if (preview) {
                    preview.style.display = 'none';
                    preview.innerHTML = '';
                }
                return;
            }

            const pid = selectedOption.value;
            const prescription = billingPrescriptionMap[pid] || null;
            const halfAmount = parseFloat(selectedOption.getAttribute('data-half-amount') || '0');
            const consultationFee = parseFloat(selectedOption.getAttribute('data-consultation-fee') || '0');
            const baseAmount = Number.isFinite(halfAmount) ? halfAmount : 0;
            const prescriptionTotal = prescription ? parseFloat(prescription.total || '0') : 0;
            const finalAmount = (prescription && prescriptionTotal > 0 ? prescriptionTotal : baseAmount) + (Number.isFinite(consultationFee) ? consultationFee : 0);
            amountInput.value = finalAmount > 0 ? finalAmount.toFixed(2) : '';

            if (servicesInput) {
                if (prescription && prescription.items && prescription.items.length > 0) {
                    servicesInput.value = 'Prescription medicines';
                } else if (!servicesInput.value.trim()) {
                    servicesInput.value = 'Consultation (50% billing)';
                }
            }

            if (preview) {
                if (prescription && prescription.items && prescription.items.length > 0) {
                    const rows = prescription.items.map(function(item) {
                        return '<tr>'
                            + '<td>' + item.medicine_name + '</td>'
                            + '<td>' + (Number(item.quantity || 1)).toFixed(0) + '</td>'
                            + '<td>Rs. ' + Number(item.unit_price || 0).toFixed(2) + '</td>'
                            + '<td>Rs. ' + Number(item.amount || 0).toFixed(2) + '</td>'
                            + '</tr>';
                    }).join('');

                    preview.style.display = 'block';
                    preview.innerHTML = ''
                        + '<div class="medicine-preview-title">Prescription medicines for ' + (prescription.patient_name || 'patient') + '</div>'
                        + '<div class="medicine-preview-meta mb-3">'
                        + '<span class="medicine-preview-pill"><span class="label">Doctor</span>' + (prescription.doctor_name || 'Doctor') + '</span>'
                        + '<span class="medicine-preview-pill"><span class="label">Rx Total</span>Rs. ' + prescriptionTotal.toFixed(2) + '</span>'
                        + '<span class="medicine-preview-pill"><span class="label">Doctor Fee</span>Rs. ' + consultationFee.toFixed(2) + '</span>'
                        + '</div>'
                        + '<div class="table-responsive">'
                        + '<table class="table table-sm align-middle mb-0">'
                        + '<thead><tr><th>Medicine</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>'
                        + '<tbody>' + rows + '</tbody>'
                        + '</table>'
                        + '</div>';
                } else {
                    preview.style.display = 'block';
                    preview.innerHTML = '<div class="medicine-preview-empty">No prescription medicines found for this patient. Bill will use consultation-based amount.</div>';
                }
            }
        }

        function handleInvoiceGenerate(event) {
            event.preventDefault();

            const patientSelect = document.getElementById('invoicePatientId');
            const servicesInput = document.getElementById('invoiceServices');
            const amountInput = document.getElementById('invoiceAmount');
            const paymentStatusInput = document.getElementById('invoicePaymentStatus');

            if (!patientSelect || !patientSelect.value) {
                alert('Please select a patient from completed appointments.');
                return;
            }
            if (!servicesInput || !servicesInput.value.trim()) {
                alert('Please enter services.');
                return;
            }
            if (!amountInput || !amountInput.value || Number(amountInput.value) <= 0) {
                alert('Please enter a valid amount.');
                return;
            }

            const selectedOption = patientSelect.options[patientSelect.selectedIndex];
            const optionText = selectedOption ? selectedOption.textContent || '' : '';
            const paymentStatus = paymentStatusInput ? paymentStatusInput.value : 'not_paid';
            const prescription = billingPrescriptionMap[patientSelect.value] || null;
            const consultationFee = selectedOption ? parseFloat(selectedOption.getAttribute('data-consultation-fee') || '0') : 0;

            const target = 'invoice_patient.php'
                + '?patient_id=' + encodeURIComponent(patientSelect.value)
                + '&patient_label=' + encodeURIComponent(optionText)
                + '&services=' + encodeURIComponent(servicesInput.value.trim())
                + '&amount=' + encodeURIComponent(Number(amountInput.value).toFixed(2))
                + '&prescription_id=' + encodeURIComponent(prescription ? prescription.prescription_id : '')
                + '&consultation_fee=' + encodeURIComponent(Number.isFinite(consultationFee) ? consultationFee.toFixed(2) : '0.00')
                + '&payment_status=' + encodeURIComponent(paymentStatus)
                + '&generated_on=' + encodeURIComponent(new Date().toISOString());

            window.location.href = target;
        }
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            const pageParams = new URLSearchParams(window.location.search);
            const initialSection = pageParams.get('section') || (pageParams.get('appointment_id') ? 'appointments' : 'dashboard');
            const initialNavLink = Array.from(document.querySelectorAll('.nav-link')).find(function(link) {
                const handler = link.getAttribute('onclick') || '';
                return handler.includes("showSection('" + initialSection + "'");
            });

            showSection(initialSection, initialNavLink || undefined);
            if (pageParams.get('openDoctorForm') === '1') {
                showSection('doctors', initialNavLink || undefined);
                toggleDoctorForm();
            }
            renderWeeklyChart('appointments');
            document.querySelectorAll('.chart-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.chart-btn').forEach(el => el.classList.remove('active'));
                    this.classList.add('active');
                    renderWeeklyChart(this.dataset.metric);
                });
            });
            renderCalendar();

            const inventoryMedicineSelect = document.getElementById('inventoryMedicineSelect');
            if (inventoryMedicineSelect) {
                inventoryMedicineSelect.addEventListener('change', updateInventoryMedicinePreview);
                updateInventoryMedicinePreview();
            }

            const initialAppointmentId = parseInt(pageParams.get('appointment_id') || '', 10);
            if (initialAppointmentId > 0) {
                prefillAppointmentUpdate(initialAppointmentId, pageParams.get('status') || 'Pending');
            }

            // Photo preview for doctor form
            const doctorPhotoInput = document.getElementById('doctorPhoto');
            if (doctorPhotoInput) {
                doctorPhotoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const preview = document.getElementById('photoPreview');
                            const img = document.getElementById('photoPreviewImg');
                            if (preview && img) {
                                img.src = event.target.result;
                                preview.style.display = 'block';
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            const doctorFormReset = document.getElementById('doctorFormReset');
            if (doctorFormReset) {
                doctorFormReset.addEventListener('click', function() {
                    clearDoctorFieldErrors();
                    fetchNextDoctorId();
                });
            }

            const fullNameInput = document.getElementById('doctorFullName');
            if (fullNameInput) {
                fullNameInput.addEventListener('input', function() {
                    syncDoctorNameFields();
                });
                fullNameInput.addEventListener('blur', function() {
                    syncDoctorNameFields();
                    suggestDoctorUsername();
                });
            }

            const emailInput = document.getElementById('doctorEmail');
            if (emailInput) {
                emailInput.addEventListener('blur', suggestDoctorUsername);
            }

            const searchInput = document.getElementById('patientSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(healthSearchDebounce);
                    healthSearchDebounce = setTimeout(refreshPatientList, 300);
                });
            }
            const conditionFilter = document.getElementById('patientConditionFilter');
            if (conditionFilter) {
                conditionFilter.addEventListener('change', refreshPatientList);
            }
            const searchFieldFilter = document.getElementById('patientSearchField');
            if (searchFieldFilter) {
                searchFieldFilter.addEventListener('change', refreshPatientList);
            }

            const invoicePatientSelect = document.getElementById('invoicePatientId');
            if (invoicePatientSelect) {
                invoicePatientSelect.addEventListener('change', updateInvoiceAmountFromPatient);
                updateInvoiceAmountFromPatient();
            }

            const invoiceForm = document.getElementById('invoiceForm');
            if (invoiceForm) {
                invoiceForm.addEventListener('submit', handleInvoiceGenerate);
            }

            loadAllPatientsIntoDropdown();
            refreshPatientList();
        });
    </script>
</body>
</html>

