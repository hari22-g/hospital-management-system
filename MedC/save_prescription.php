<?php
session_start();
include 'connection/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'doctor') {
    die('Error: Access denied.');
}

function resolveDoctorId(mysqli $conn): int
{
    if (!empty($_SESSION['doctor_id'])) {
        return (int) $_SESSION['doctor_id'];
    }

    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email === '') {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    $stmt = $conn->prepare('SELECT d_id FROM doctor WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return (int) ($_SESSION['user_id'] ?? 0);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($doctorId);
    $resolved = $stmt->fetch() ? (int) $doctorId : (int) ($_SESSION['user_id'] ?? 0);
    $stmt->close();

    return $resolved;
}

$doctor_id = resolveDoctorId($conn);
$appointment_id = (int) ($_POST['appointment_id'] ?? 0);
$action = $_POST['action'] ?? 'save';

if ($appointment_id <= 0) {
    die('Invalid appointment.');
}

$appointmentStmt = $conn->prepare(
    "SELECT appointment_id, pid, did FROM appointment WHERE appointment_id = ? AND did = ?"
);
$appointmentStmt->bind_param('ii', $appointment_id, $doctor_id);
$appointmentStmt->execute();
$appointment = $appointmentStmt->get_result()->fetch_assoc();
$appointmentStmt->close();

if (!$appointment) {
    die('Appointment not found.');
}

$patient_id = (int) $appointment['pid'];
$symptoms = trim((string) ($_POST['symptoms'] ?? ''));
$diagnosis = trim((string) ($_POST['diagnosis'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));
$follow_up_date = trim((string) ($_POST['follow_up_date'] ?? ''));
$follow_up_date = $follow_up_date !== '' ? $follow_up_date : null;

$test_names = $_POST['test_name'] ?? [];
$test_notes = $_POST['test_notes'] ?? [];
$test_summary = [];
foreach ($test_names as $index => $name) {
    $name = trim((string) $name);
    if ($name !== '') {
        $test_summary[] = $name;
    }
}
$recommended_tests = !empty($test_summary) ? implode(', ', $test_summary) : null;

$conn->begin_transaction();
try {
    $insertPrescription = $conn->prepare(
        "INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, symptoms, diagnosis, notes, recommended_tests, follow_up_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
    );
    $insertPrescription->bind_param(
        'iiisssss',
        $appointment_id,
        $patient_id,
        $doctor_id,
        $symptoms,
        $diagnosis,
        $notes,
        $recommended_tests,
        $follow_up_date
    );
    $insertPrescription->execute();
    $prescription_id = $conn->insert_id;
    $insertPrescription->close();

    $medNames = $_POST['medicine_name'] ?? [];
    $genericNames = $_POST['generic_name'] ?? [];
    $dosages = $_POST['dosage'] ?? [];
    $frequencies = $_POST['frequency'] ?? [];
    $foodTimings = $_POST['food_timing'] ?? [];
    $durationDays = $_POST['duration_days'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $instructions = $_POST['instructions'] ?? [];

    $insertMedicine = $conn->prepare(
        "INSERT INTO prescription_medicines
            (prescription_id, medicine_name, generic_name, dosage, frequency, food_timing, duration_days, quantity, instructions)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($medNames as $index => $name) {
        $name = trim((string) $name);
        if ($name === '') {
            continue;
        }

        $generic = trim((string) ($genericNames[$index] ?? ''));
        $dosage = trim((string) ($dosages[$index] ?? ''));
        $frequency = trim((string) ($frequencies[$index] ?? ''));
        $food = trim((string) ($foodTimings[$index] ?? 'After Food'));
        $duration = (int) ($durationDays[$index] ?? 0);
        $quantity = (int) ($quantities[$index] ?? 0);
        $instruction = trim((string) ($instructions[$index] ?? ''));

        $insertMedicine->bind_param(
            'isssssiis',
            $prescription_id,
            $name,
            $generic,
            $dosage,
            $frequency,
            $food,
            $duration,
            $quantity,
            $instruction
        );
        $insertMedicine->execute();
    }
    $insertMedicine->close();

    if (!empty($test_names)) {
        $insertTest = $conn->prepare(
            "INSERT INTO prescription_tests (prescription_id, test_name, notes) VALUES (?, ?, ?)"
        );

        foreach ($test_names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $note = trim((string) ($test_notes[$index] ?? ''));
            $insertTest->bind_param('iss', $prescription_id, $name, $note);
            $insertTest->execute();
        }
        $insertTest->close();
    }

    $newStatus = $action === 'complete' ? 'Completed' : 'In Consultation';
    $updateAppointment = $conn->prepare("UPDATE appointment SET status = ? WHERE appointment_id = ?");
    $updateAppointment->bind_param('si', $newStatus, $appointment_id);
    $updateAppointment->execute();
    $updateAppointment->close();

    $conn->commit();

    header('Location: doctor_prescriptions.php?success=1');
    exit();
} catch (Exception $e) {
    $conn->rollback();
    echo 'Error saving prescription: ' . $e->getMessage();
}
?>
