<?php
// book_appointment.php - Book, reschedule, or cancel appointments (JSON API)
session_start();
header('Content-Type: application/json');

require_once 'connection/config.php';

// Check authentication
if (!isset($_SESSION['user_type'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Determine user role and patient ID
$userType = $_SESSION['user_type'];
$userId = $_SESSION['user_id'] ?? 0;
$isAdmin = $userType === 'admin';
$isDoctor = $userType === 'doctor';
$isPatient = $userType === 'patient';

// Allow admins, doctors, and patients
if (!$isAdmin && !$isDoctor && !$isPatient) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: homepage.php');
    exit();
}

$action = trim($_POST['action'] ?? '');
$patient_id = intval($_POST['patient_id'] ?? 0);
$doctor_id = intval($_POST['doctor_id'] ?? 0);
$appointment_date = trim($_POST['appointment_date'] ?? '');
$appointment_time = trim($_POST['appointment_time'] ?? '');
$appointment_id = intval($_POST['appointment_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Resolve patient_id from session when not provided
if ($patient_id <= 0) {
    $resolvedPatientId = 0;
    $sessionEmail = trim($_SESSION['email'] ?? '');

    if (!empty($sessionEmail)) {
        $getPatientBySessionEmail = $conn->prepare("SELECT pid FROM patient WHERE email = ? LIMIT 1");
        if ($getPatientBySessionEmail) {
            $getPatientBySessionEmail->bind_param('s', $sessionEmail);
            $getPatientBySessionEmail->execute();
            $patientResult = $getPatientBySessionEmail->get_result();
            if ($patientResult && $patientResult->num_rows > 0) {
                $patientData = $patientResult->fetch_assoc();
                $resolvedPatientId = intval($patientData['pid'] ?? 0);
            }
            $getPatientBySessionEmail->close();
        }
    }

    if ($resolvedPatientId <= 0 && $userId > 0) {
        $getPatientByPid = $conn->prepare("SELECT pid FROM patient WHERE pid = ? LIMIT 1");
        if ($getPatientByPid) {
            $getPatientByPid->bind_param('i', $userId);
            $getPatientByPid->execute();
            $patientByPidResult = $getPatientByPid->get_result();
            if ($patientByPidResult && $patientByPidResult->num_rows > 0) {
                $patientByPid = $patientByPidResult->fetch_assoc();
                $resolvedPatientId = intval($patientByPid['pid'] ?? 0);
            }
            $getPatientByPid->close();
        }
    }

    if ($resolvedPatientId <= 0 && $userId > 0) {
        $getUserEmail = $conn->prepare("SELECT email FROM users WHERE user_id = ? LIMIT 1");
        if ($getUserEmail) {
            $getUserEmail->bind_param('i', $userId);
            $getUserEmail->execute();
            $userResult = $getUserEmail->get_result();
            if ($userResult && $userResult->num_rows > 0) {
                $userData = $userResult->fetch_assoc();
                $userEmail = trim($userData['email'] ?? '');
                if (!empty($userEmail)) {
                    $getPatientId = $conn->prepare("SELECT pid FROM patient WHERE email = ? LIMIT 1");
                    if ($getPatientId) {
                        $getPatientId->bind_param('s', $userEmail);
                        $getPatientId->execute();
                        $patientResult = $getPatientId->get_result();
                        if ($patientResult && $patientResult->num_rows > 0) {
                            $patientData = $patientResult->fetch_assoc();
                            $resolvedPatientId = intval($patientData['pid'] ?? 0);
                        }
                        $getPatientId->close();
                    }
                }
            }
            $getUserEmail->close();
        }
    }

    if ($resolvedPatientId <= 0 && $isPatient) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Patient profile not found. Please complete your patient registration.']);
        exit();
    }

    if ($resolvedPatientId > 0) {
        $patient_id = $resolvedPatientId;
    }
}

// Validation
if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action required']);
    exit();
}

if ($action === 'book') {
    if (!$isPatient && $patient_id <= 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Only a patient account can book an appointment from this page.'
        ]);
        exit();
    }

    // Normalize date/time from UI values
    if (!empty($appointment_date)) {
        $parsedDate = date_create($appointment_date);
        if ($parsedDate !== false) {
            $appointment_date = $parsedDate->format('Y-m-d');
        }
    }

    if (!empty($appointment_time)) {
        $parsedTime = date_create($appointment_time);
        if ($parsedTime !== false) {
            $appointment_time = $parsedTime->format('H:i:s');
        }
    }

    if ($patient_id <= 0 || $doctor_id <= 0 || empty($appointment_date) || empty($appointment_time)) {
        $details = [];
        if ($patient_id <= 0) $details[] = 'patient';
        if ($doctor_id <= 0) $details[] = 'doctor';
        if (empty($appointment_date)) $details[] = 'date';
        if (empty($appointment_time)) $details[] = 'time';

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid input parameters: ' . implode(', ', $details)
        ]);
        exit();
    }

    // Check date is future
    $appointmentDateTime = strtotime($appointment_date . ' ' . $appointment_time);
    if ($appointmentDateTime <= time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot book past appointments']);
        exit();
    }

    // Check doctor availability only if doctor_schedule exists and doctor has schedule entries
    $scheduleTableExists = false;
    $tableCheck = $conn->query("SHOW TABLES LIKE 'doctor_schedule'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $scheduleTableExists = true;
    }

    if ($scheduleTableExists) {
        $doctorScheduleCount = 0;
        $countScheduleStmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM doctor_schedule WHERE doctor_id = ?");
        if ($countScheduleStmt) {
            $countScheduleStmt->bind_param('i', $doctor_id);
            $countScheduleStmt->execute();
            $countResult = $countScheduleStmt->get_result();
            if ($countResult && $countResult->num_rows > 0) {
                $countRow = $countResult->fetch_assoc();
                $doctorScheduleCount = intval($countRow['total_rows'] ?? 0);
            }
            $countScheduleStmt->close();
        }

        if ($doctorScheduleCount > 0) {
            $dayOfWeek = date('l', strtotime($appointment_date));
            $checkSchedule = $conn->prepare(
                "SELECT schedule_id FROM doctor_schedule WHERE doctor_id = ? AND day_of_week = ? AND is_available = 1"
            );
            if ($checkSchedule) {
                $checkSchedule->bind_param('is', $doctor_id, $dayOfWeek);
                $checkSchedule->execute();
                $scheduleResult = $checkSchedule->get_result();
                if (!$scheduleResult || $scheduleResult->num_rows === 0) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Doctor not available on this day']);
                    exit();
                }
                $checkSchedule->close();
            }
        }
    }

    // Check if slot already booked
    $checkSlot = $conn->prepare(
        "SELECT appointment_id FROM appointment WHERE did = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Cancelled'"
    );
    $checkSlot->bind_param('iss', $doctor_id, $appointment_date, $appointment_time);
    $checkSlot->execute();
    if ($checkSlot->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Slot already booked']);
        exit();
    }
    $checkSlot->close();

    // Insert appointment
    $status = 'Pending';
    $insertAppt = $conn->prepare(
        "INSERT INTO appointment (pid, did, appointment_date, appointment_time, status, reason, notes, user_id, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    
    $userId = $_SESSION['user_id'];
    $insertAppt->bind_param('iisssssi', $patient_id, $doctor_id, $appointment_date, $appointment_time, $status, $reason, $notes, $userId);
    
    if (!$insertAppt->execute()) {
        $insertErrorCode = $insertAppt->errno;
        $insertAppt->close();

        if ($insertErrorCode === 1062) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'This date and time slot is already booked. Please choose another slot.']);
            exit();
        }

        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error booking appointment']);
        exit();
    }

    $apptId = $insertAppt->insert_id;
    $insertAppt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully',
        'appointment_id' => $apptId,
        'status' => $status,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time
    ]);

} elseif ($action === 'reschedule') {
    if ($appointment_id <= 0 || empty($appointment_date) || empty($appointment_time)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
        exit();
    }

    // Fetch original appointment
    $getAppt = $conn->prepare("SELECT did, pid FROM appointment WHERE appointment_id = ? AND status != 'Cancelled'");
    $getAppt->bind_param('i', $appointment_id);
    $getAppt->execute();
    $apptResult = $getAppt->get_result();
    
    if ($apptResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit();
    }

    $appt = $apptResult->fetch_assoc();
    $doctorId = $appt['did'];
    $appointmentPatientId = $appt['pid'];
    $getAppt->close();

    // Patients can only reschedule their own appointments
    if ($isPatient && $appointmentPatientId != $patient_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Cannot reschedule another patient\'s appointment']);
        exit();
    }

    // Check new slot availability
    $checkSlot = $conn->prepare(
        "SELECT appointment_id FROM appointment WHERE did = ? AND appointment_date = ? AND appointment_time = ? AND appointment_id != ? AND status != 'Cancelled'"
    );
    $checkSlot->bind_param('issi', $doctorId, $appointment_date, $appointment_time, $appointment_id);
    $checkSlot->execute();
    if ($checkSlot->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'New slot already booked']);
        exit();
    }
    $checkSlot->close();

    // Update appointment
    $updateAppt = $conn->prepare(
        "UPDATE appointment SET appointment_date = ?, appointment_time = ? WHERE appointment_id = ?"
    );
    $updateAppt->bind_param('ssi', $appointment_date, $appointment_time, $appointment_id);
    
    if (!$updateAppt->execute()) {
        $updateErrorCode = $updateAppt->errno;
        $updateAppt->close();

        if ($updateErrorCode === 1062) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'This date and time slot is already booked. Please choose another slot.']);
            exit();
        }

        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error rescheduling appointment']);
        exit();
    }
    $updateAppt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment rescheduled successfully'
    ]);

} elseif ($action === 'cancel') {
    if ($appointment_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
        exit();
    }

    // Fetch appointment to verify ownership (for patients)
    $getAppt = $conn->prepare("SELECT pid FROM appointment WHERE appointment_id = ?");
    $getAppt->bind_param('i', $appointment_id);
    $getAppt->execute();
    $apptResult = $getAppt->get_result();
    
    if ($apptResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit();
    }

    $appt = $apptResult->fetch_assoc();
    $appointmentPatientId = $appt['pid'];
    $getAppt->close();

    // Patients can only cancel their own appointments
    if ($isPatient && $appointmentPatientId != $patient_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Cannot cancel another patient\'s appointment']);
        exit();
    }

    $cancelStatus = 'Cancelled';
    $updateAppt = $conn->prepare("UPDATE appointment SET status = ? WHERE appointment_id = ?");
    $updateAppt->bind_param('si', $cancelStatus, $appointment_id);
    
    if (!$updateAppt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error cancelling appointment']);
        exit();
    }
    $updateAppt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment cancelled successfully'
    ]);


} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>

