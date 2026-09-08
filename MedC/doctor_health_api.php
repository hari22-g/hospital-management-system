<?php
session_start();
header('Content-Type: application/json');

include('connection/config.php');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_type'] ?? '') !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
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

$doctorId = resolveDoctorId($conn);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function safeJson($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function firstExistingColumn($conn, $table, $candidates)
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $res = $conn->query("SHOW COLUMNS FROM `{$safeTable}`");
    $cols = [];
    while ($row = $res->fetch_assoc()) {
        $cols[] = strtolower($row['Field']);
    }
    foreach ($candidates as $candidate) {
        if (in_array(strtolower($candidate), $cols, true)) {
            return $candidate;
        }
    }
    return null;
}

function bindDynamicParams($stmt, $types, &$params)
{
    if ($types === '' || empty($params)) {
        return;
    }
    $bindArgs = [$types];
    foreach ($params as $k => &$v) {
        $bindArgs[] = &$v;
    }
    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}

function columnExists($conn, $table, $column)
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if ($safeTable === '' || $safeColumn === '') {
        return false;
    }
    $res = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $res && $res->num_rows > 0;
}

function tableExists($conn, $table)
{
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $res && $res->num_rows > 0;
}

function normalizeCondition($value)
{
    $value = strtolower(trim((string) $value));
    if (in_array($value, ['critical', 'high', 'severe'], true)) {
        return 'Critical';
    }
    if (in_array($value, ['stable', 'good', 'normal'], true)) {
        return 'Stable';
    }
    return 'Moderate';
}

function ensureDoctorTables($conn)
{
    $conn->query("CREATE TABLE IF NOT EXISTS doctor_patient_notes (
        note_id INT AUTO_INCREMENT PRIMARY KEY,
        pid INT NOT NULL,
        did INT NOT NULL,
        note_text TEXT,
        diagnosis TEXT,
        treatment_plan TEXT,
        follow_up_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_pid (pid),
        INDEX idx_did (did)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS doctor_prescriptions (
        prescription_id INT AUTO_INCREMENT PRIMARY KEY,
        pid INT NOT NULL,
        did INT NOT NULL,
        medicine_name VARCHAR(255) NOT NULL,
        dosage VARCHAR(255) NOT NULL,
        duration VARCHAR(255) NOT NULL,
        instructions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pid (pid),
        INDEX idx_did (did)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS doctor_patient_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        pid INT NOT NULL,
        did INT NOT NULL,
        message_text TEXT NOT NULL,
        status VARCHAR(30) DEFAULT 'Sent',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pid (pid),
        INDEX idx_did (did)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS patient_health_files (
        file_id INT AUTO_INCREMENT PRIMARY KEY,
        pid INT NOT NULL,
        uploaded_by_role VARCHAR(30) DEFAULT 'doctor',
        uploaded_by_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(80) DEFAULT 'report',
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pid (pid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS doctor_activity_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        did INT NOT NULL,
        pid INT DEFAULT NULL,
        action_name VARCHAR(80) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_did_created (did, created_at),
        INDEX idx_pid_created (pid, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $res = $conn->query("SHOW COLUMNS FROM patient LIKE 'patient_condition'");
    if (!$res || $res->num_rows === 0) {
        $conn->query("ALTER TABLE patient ADD COLUMN patient_condition VARCHAR(30) DEFAULT 'Moderate'");
    }
}

function logDoctorActivity($conn, $did, $pid, $actionName, $details)
{
    if (!tableExists($conn, 'doctor_activity_logs')) {
        return;
    }
    $pidVal = $pid > 0 ? $pid : null;
    $stmt = $conn->prepare("INSERT INTO doctor_activity_logs (did, pid, action_name, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiss', $did, $pidVal, $actionName, $details);
    $stmt->execute();
}

function isAssignedPatient($conn, $doctorId, $patientId)
{
    $stmt = $conn->prepare("SELECT 1 FROM appointment WHERE did = ? AND pid = ? LIMIT 1");
    $stmt->bind_param('ii', $doctorId, $patientId);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->num_rows > 0;
}

function parseDurationDays($duration)
{
    if (!preg_match('/\d+/', (string) $duration, $matches)) {
        return null;
    }
    $days = (int) $matches[0];
    return $days > 0 ? $days : null;
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal server error',
            'error' => $error['message']
        ]);
    }
});

try {
    ensureDoctorTables($conn);

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action === 'overview') {
        $assignedSql = "SELECT COUNT(DISTINCT pid) AS cnt FROM appointment WHERE did = ?";
        $stmt = $conn->prepare($assignedSql);
        $stmt->bind_param('i', $doctorId);
        $stmt->execute();
        $assigned = (int) ($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);

        $summaryStmt = $conn->prepare("SELECT COALESCE(p.patient_condition, 'Moderate') AS c, COUNT(DISTINCT p.pid) AS cnt
            FROM patient p
            JOIN appointment a ON a.pid = p.pid
            WHERE a.did = ?
            GROUP BY c");
        $summaryStmt->bind_param('i', $doctorId);
        $summaryStmt->execute();
        $summaryRes = $summaryStmt->get_result();

        $critical = 0;
        $stable = 0;
        $moderate = 0;

        while ($row = $summaryRes->fetch_assoc()) {
            $condition = normalizeCondition($row['c']);
            if ($condition === 'Critical') {
                $critical += (int) $row['cnt'];
            } elseif ($condition === 'Stable') {
                $stable += (int) $row['cnt'];
            } else {
                $moderate += (int) $row['cnt'];
            }
        }

        $recentStmt = $conn->prepare("SELECT
            (SELECT COUNT(*) FROM doctor_patient_notes WHERE did = ? AND created_at >= (NOW() - INTERVAL 7 DAY)) +
            (SELECT COUNT(*) FROM doctor_prescriptions WHERE did = ? AND created_at >= (NOW() - INTERVAL 7 DAY)) +
            (SELECT COUNT(*) FROM doctor_patient_messages WHERE did = ? AND created_at >= (NOW() - INTERVAL 7 DAY)) AS recent_updates");
        $recentStmt->bind_param('iii', $doctorId, $doctorId, $doctorId);
        $recentStmt->execute();
        $recentUpdates = (int) ($recentStmt->get_result()->fetch_assoc()['recent_updates'] ?? 0);

        $priorityStmt = $conn->prepare("SELECT p.pid, p.firstname, p.lastname, p.patient_condition,
                MAX(CONCAT(a.appointment_date, ' ', a.appointment_time)) AS latest_slot
            FROM patient p
            JOIN appointment a ON a.pid = p.pid
            WHERE a.did = ?
            GROUP BY p.pid, p.firstname, p.lastname, p.patient_condition
            ORDER BY (CASE
                WHEN LOWER(COALESCE(p.patient_condition, 'moderate')) IN ('critical','high','severe') THEN 1
                WHEN LOWER(COALESCE(p.patient_condition, 'moderate')) IN ('moderate','medium') THEN 2
                ELSE 3
            END), latest_slot DESC
            LIMIT 6");
        $priorityStmt->bind_param('i', $doctorId);
        $priorityStmt->execute();
        $priorityRes = $priorityStmt->get_result();

        $priority = [];
        while ($row = $priorityRes->fetch_assoc()) {
            $row['patient_condition'] = normalizeCondition($row['patient_condition'] ?? 'Moderate');
            $priority[] = $row;
        }

        safeJson([
            'success' => true,
            'data' => [
                'assigned' => $assigned,
                'critical' => $critical,
                'stable' => $stable,
                'moderate' => $moderate,
                'recent_updates' => $recentUpdates,
                'priority_patients' => $priority
            ]
        ]);
    }

    if ($action === 'list_patients') {
        $search = trim($_POST['search'] ?? '');
        $searchField = $_POST['search_field'] ?? 'all';
        $condition = $_POST['condition'] ?? '';

        $where = ["a.did = ?"];
        $params = [$doctorId];
        $types = 'i';

        if ($search !== '') {
            if ($searchField === 'name') {
                $where[] = "(p.firstname LIKE ? OR p.lastname LIKE ?)";
                $kw = '%' . $search . '%';
                $params[] = $kw;
                $params[] = $kw;
                $types .= 'ss';
            } elseif ($searchField === 'id') {
                $where[] = "CAST(p.pid AS CHAR) LIKE ?";
                $params[] = '%' . $search . '%';
                $types .= 's';
            } elseif ($searchField === 'condition') {
                $where[] = "LOWER(COALESCE(p.patient_condition, 'moderate')) LIKE ?";
                $params[] = '%' . strtolower($search) . '%';
                $types .= 's';
            } else {
                $where[] = "(p.firstname LIKE ? OR p.lastname LIKE ? OR CAST(p.pid AS CHAR) LIKE ? OR LOWER(COALESCE(p.patient_condition, 'moderate')) LIKE ?)";
                $kw = '%' . $search . '%';
                $params[] = $kw;
                $params[] = $kw;
                $params[] = $kw;
                $params[] = '%' . strtolower($search) . '%';
                $types .= 'ssss';
            }
        }

        if ($condition !== '' && strtolower($condition) !== 'all') {
            $where[] = "LOWER(COALESCE(p.patient_condition, 'moderate')) = ?";
            $params[] = strtolower($condition);
            $types .= 's';
        }

        $sql = "SELECT p.pid, p.firstname, p.lastname, p.patient_condition,
                    MAX(CONCAT(a.appointment_date, ' ', a.appointment_time)) AS next_slot,
                    SUM(CASE WHEN a.status = 'Pending' THEN 1 ELSE 0 END) AS pending_count
                FROM patient p
                JOIN appointment a ON a.pid = p.pid
                WHERE " . implode(' AND ', $where) . "
                GROUP BY p.pid, p.firstname, p.lastname, p.patient_condition
                ORDER BY (CASE
                    WHEN LOWER(COALESCE(p.patient_condition, 'moderate')) IN ('critical','high','severe') THEN 1
                    WHEN LOWER(COALESCE(p.patient_condition, 'moderate')) IN ('moderate','medium') THEN 2
                    ELSE 3
                END), p.firstname ASC";

        $stmt = $conn->prepare($sql);
        bindDynamicParams($stmt, $types, $params);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $row['patient_condition'] = normalizeCondition($row['patient_condition'] ?? 'Moderate');
            $rows[] = $row;
        }

        safeJson(['success' => true, 'data' => $rows]);
    }

    if ($action === 'patient_details') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        $profileCols = ['p.pid', 'p.firstname', 'p.lastname'];
        if (columnExists($conn, 'patient', 'email')) {
            $profileCols[] = 'p.email';
        }
        if (columnExists($conn, 'patient', 'gender')) {
            $profileCols[] = 'p.gender';
        }
        if (columnExists($conn, 'patient', 'age')) {
            $profileCols[] = 'p.age';
        }
        if (columnExists($conn, 'patient', 'mobile')) {
            $profileCols[] = 'p.mobile';
        }
        if (columnExists($conn, 'patient', 'phone')) {
            $profileCols[] = 'p.phone AS mobile';
        }
        if (columnExists($conn, 'patient', 'patient_condition')) {
            $profileCols[] = "COALESCE(p.patient_condition, 'Moderate') AS patient_condition";
        } else {
            $profileCols[] = "'Moderate' AS patient_condition";
        }

        $profileSql = 'SELECT ' . implode(', ', $profileCols) . ' FROM patient p WHERE p.pid = ? LIMIT 1';
        $profileStmt = $conn->prepare($profileSql);
        $profileStmt->bind_param('i', $pid);
        $profileStmt->execute();
        $profile = $profileStmt->get_result()->fetch_assoc();
        if (!$profile) {
            safeJson(['success' => false, 'message' => 'Patient not found'], 404);
        }
        $profile['patient_condition'] = normalizeCondition($profile['patient_condition'] ?? 'Moderate');

        $weightPidCol = firstExistingColumn($conn, 'weight_tracker', ['pid', 'patient_id', 'user_id']);
        $weightDateCol = firstExistingColumn($conn, 'weight_tracker', ['recorded_date', 'month', 'created_at']);
        $weightCol = firstExistingColumn($conn, 'weight_tracker', ['weight', 'weight_kg']);

        $weights = [];
        if ($weightPidCol && $weightDateCol && $weightCol && tableExists($conn, 'weight_tracker')) {
            $wSql = "SELECT {$weightDateCol} AS recorded_on, {$weightCol} AS weight
                FROM weight_tracker WHERE {$weightPidCol} = ? ORDER BY {$weightDateCol} DESC LIMIT 20";
            $wStmt = $conn->prepare($wSql);
            $wStmt->bind_param('i', $pid);
            $wStmt->execute();
            $wRes = $wStmt->get_result();
            while ($row = $wRes->fetch_assoc()) {
                $weights[] = $row;
            }
        }

        $vitals = [];
        if (tableExists($conn, 'patient_vitals')) {
            $vPid = firstExistingColumn($conn, 'patient_vitals', ['pid', 'patient_id']);
            $vDate = firstExistingColumn($conn, 'patient_vitals', ['recorded_at', 'recorded_date', 'created_at']);
            $vHr = firstExistingColumn($conn, 'patient_vitals', ['heart_rate']);
            $vTemp = firstExistingColumn($conn, 'patient_vitals', ['temperature']);
            $vOxy = firstExistingColumn($conn, 'patient_vitals', ['oxygen_level']);
            $vBp = firstExistingColumn($conn, 'patient_vitals', ['blood_pressure']);
            $vSys = firstExistingColumn($conn, 'patient_vitals', ['systolic_bp']);
            $vDia = firstExistingColumn($conn, 'patient_vitals', ['diastolic_bp']);

            if ($vPid && $vDate) {
                $bpExpr = "'-'";
                if ($vBp) {
                    $bpExpr = $vBp;
                } elseif ($vSys && $vDia) {
                    $bpExpr = "CONCAT(COALESCE({$vSys}, ''), '/', COALESCE({$vDia}, ''))";
                }
                $vSql = "SELECT {$bpExpr} AS blood_pressure, " .
                    ($vHr ? "{$vHr}" : "NULL") . " AS heart_rate, " .
                    ($vTemp ? "{$vTemp}" : "NULL") . " AS temperature, " .
                    ($vOxy ? "{$vOxy}" : "NULL") . " AS oxygen_level, " .
                    "{$vDate} AS recorded_at " .
                    "FROM patient_vitals WHERE {$vPid} = ? ORDER BY {$vDate} DESC LIMIT 20";

                $vStmt = $conn->prepare($vSql);
                $vStmt->bind_param('i', $pid);
                $vStmt->execute();
                $vRes = $vStmt->get_result();
                while ($row = $vRes->fetch_assoc()) {
                    $vitals[] = $row;
                }
            }
        }

        $notes = [];
        $nStmt = $conn->prepare("SELECT note_id, note_text, diagnosis, treatment_plan, follow_up_date, created_at, updated_at
            FROM doctor_patient_notes
            WHERE did = ? AND pid = ?
            ORDER BY created_at DESC LIMIT 30");
        $nStmt->bind_param('ii', $doctorId, $pid);
        $nStmt->execute();
        $nRes = $nStmt->get_result();
        while ($row = $nRes->fetch_assoc()) {
            $notes[] = $row;
        }

        $alerts = [];
        foreach ($vitals as $vital) {
            $hr = (float) ($vital['heart_rate'] ?? 0);
            $temp = (float) ($vital['temperature'] ?? 0);
            $oxy = (float) ($vital['oxygen_level'] ?? 0);
            if ($hr > 110 || $hr < 50 || $temp > 100.4 || $oxy < 92) {
                $alerts[] = [
                    'severity' => ($oxy < 90 || $temp > 102 || $hr > 125 || $hr < 45) ? 'Critical' : 'Moderate',
                    'message' => 'Abnormal vitals detected at ' . ($vital['recorded_at'] ?? ''),
                    'recorded_at' => $vital['recorded_at'] ?? ''
                ];
            }
        }
        if ($profile['patient_condition'] === 'Critical') {
            $alerts[] = [
                'severity' => 'Critical',
                'message' => 'Patient condition marked as Critical',
                'recorded_at' => date('Y-m-d H:i:s')
            ];
        }

        safeJson([
            'success' => true,
            'data' => [
                'profile' => $profile,
                'weights' => $weights,
                'vitals' => $vitals,
                'alerts' => $alerts,
                'notes' => $notes
            ]
        ]);
    }

    if ($action === 'add_note') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        $noteText = trim($_POST['note_text'] ?? '');
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $treatment = trim($_POST['treatment_plan'] ?? '');
        $followUp = trim($_POST['follow_up_date'] ?? '');
        $followUpVal = $followUp !== '' ? $followUp : null;

        if ($noteText === '' && $diagnosis === '' && $treatment === '') {
            safeJson(['success' => false, 'message' => 'Enter note or diagnosis details'], 422);
        }

        $stmt = $conn->prepare("INSERT INTO doctor_patient_notes (pid, did, note_text, diagnosis, treatment_plan, follow_up_date)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iissss', $pid, $doctorId, $noteText, $diagnosis, $treatment, $followUpVal);
        $stmt->execute();
        logDoctorActivity($conn, $doctorId, $pid, 'add_note', 'Created a new clinical note');

        safeJson(['success' => true, 'message' => 'Note saved']);
    }

    if ($action === 'update_note') {
        $noteId = (int) ($_POST['note_id'] ?? 0);
        $noteText = trim($_POST['note_text'] ?? '');
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $treatment = trim($_POST['treatment_plan'] ?? '');
        $followUp = trim($_POST['follow_up_date'] ?? '');
        $followUpVal = $followUp !== '' ? $followUp : null;

        $stmt = $conn->prepare("UPDATE doctor_patient_notes
            SET note_text = ?, diagnosis = ?, treatment_plan = ?, follow_up_date = ?
            WHERE note_id = ? AND did = ?");
        $stmt->bind_param('ssssii', $noteText, $diagnosis, $treatment, $followUpVal, $noteId, $doctorId);
        $stmt->execute();
        logDoctorActivity($conn, $doctorId, 0, 'update_note', 'Updated note #' . $noteId);

        safeJson(['success' => true, 'message' => 'Note updated']);
    }

    if ($action === 'delete_note') {
        $noteId = (int) ($_POST['note_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM doctor_patient_notes WHERE note_id = ? AND did = ?");
        $stmt->bind_param('ii', $noteId, $doctorId);
        $stmt->execute();
        logDoctorActivity($conn, $doctorId, 0, 'delete_note', 'Deleted note #' . $noteId);

        safeJson(['success' => true, 'message' => 'Note deleted']);
    }

    if ($action === 'appointments') {
        $stmt = $conn->prepare("SELECT appointment_id, pid, appointment_date, appointment_time, status
            FROM appointment
            WHERE did = ? AND DATE(appointment_date) >= CURDATE()
            ORDER BY appointment_date ASC, appointment_time ASC
            LIMIT 100");
        $stmt->bind_param('i', $doctorId);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        safeJson(['success' => true, 'data' => $rows]);
    }

    if ($action === 'update_appointment') {
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $newDate = trim($_POST['appointment_date'] ?? '');
        $newTime = trim($_POST['appointment_time'] ?? '');

        $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rescheduled'];
        if (!in_array($status, $allowed, true)) {
            safeJson(['success' => false, 'message' => 'Invalid status'], 422);
        }

        $ownerStmt = $conn->prepare("SELECT 1 FROM appointment WHERE appointment_id = ? AND did = ? LIMIT 1");
        $ownerStmt->bind_param('ii', $appointmentId, $doctorId);
        $ownerStmt->execute();
        if ($ownerStmt->get_result()->num_rows === 0) {
            safeJson(['success' => false, 'message' => 'Appointment not found'], 404);
        }

        if ($newDate !== '' && $newTime !== '') {
            $stmt = $conn->prepare("UPDATE appointment SET status = ?, appointment_date = ?, appointment_time = ? WHERE appointment_id = ? AND did = ?");
            $stmt->bind_param('sssii', $status, $newDate, $newTime, $appointmentId, $doctorId);
        } else {
            $stmt = $conn->prepare("UPDATE appointment SET status = ? WHERE appointment_id = ? AND did = ?");
            $stmt->bind_param('sii', $status, $appointmentId, $doctorId);
        }
        $stmt->execute();
        logDoctorActivity($conn, $doctorId, 0, 'update_appointment', 'Updated appointment #' . $appointmentId . ' to ' . $status);

        safeJson(['success' => true, 'message' => 'Appointment updated']);
    }

    if ($action === 'add_prescription') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        $medicine = trim($_POST['medicine_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $duration = trim($_POST['duration'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');

        if ($medicine === '' || $dosage === '' || $duration === '') {
            safeJson(['success' => false, 'message' => 'Medicine, dosage and duration are required'], 422);
        }

        $durationDays = parseDurationDays($duration);

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO doctor_prescriptions (pid, did, medicine_name, dosage, duration, instructions)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iissss', $pid, $doctorId, $medicine, $dosage, $duration, $instructions);
            $stmt->execute();
            $stmt->close();

            if (tableExists($conn, 'prescriptions')) {
                $prescriptionStmt = $conn->prepare(
                    "INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, symptoms, diagnosis, notes, recommended_tests, follow_up_date, status)
                     VALUES (NULL, ?, ?, '', '', ?, NULL, NULL, 'Pending')"
                );
                $notesForPatient = $instructions !== '' ? $instructions : 'Prescription issued by doctor';
                $prescriptionStmt->bind_param('iis', $pid, $doctorId, $notesForPatient);
                $prescriptionStmt->execute();
                $prescriptionId = $conn->insert_id;
                $prescriptionStmt->close();

                if ($prescriptionId > 0 && tableExists($conn, 'prescription_medicines')) {
                    $medicineStmt = $conn->prepare(
                        "INSERT INTO prescription_medicines
                            (prescription_id, medicine_name, generic_name, dosage, frequency, food_timing, duration_days, quantity, dispensed_quantity, availability, unit_price, instructions)
                         VALUES (?, ?, NULL, ?, ?, 'After Food', ?, 0, 0, 'Available', 0, ?)"
                    );
                    $frequencyLabel = 'As directed';
                    $medicineInstructions = $instructions;
                    $medicineStmt->bind_param(
                        'isssis',
                        $prescriptionId,
                        $medicine,
                        $dosage,
                        $frequencyLabel,
                        $durationDays,
                        $medicineInstructions
                    );
                    $medicineStmt->execute();
                    $medicineStmt->close();
                }
            }

            $conn->commit();
            logDoctorActivity($conn, $doctorId, $pid, 'add_prescription', 'Created prescription for ' . $medicine);

            safeJson(['success' => true, 'message' => 'Prescription created']);
        } catch (Throwable $e) {
            $conn->rollback();
            safeJson(['success' => false, 'message' => 'Failed to save prescription'], 500);
        }
    }

    if ($action === 'list_prescriptions') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        $stmt = $conn->prepare("SELECT prescription_id, medicine_name, dosage, duration, instructions, created_at
            FROM doctor_prescriptions WHERE pid = ? AND did = ? ORDER BY created_at DESC");
        $stmt->bind_param('ii', $pid, $doctorId);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        safeJson(['success' => true, 'data' => $rows]);
    }

    if ($action === 'prescription_print_html') {
        $prescriptionId = (int) ($_GET['prescription_id'] ?? 0);
        if ($prescriptionId <= 0) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>Invalid prescription id</h3>';
            exit;
        }

        $stmt = $conn->prepare("SELECT pr.*, p.firstname, p.lastname
            FROM doctor_prescriptions pr
            JOIN patient p ON p.pid = pr.pid
            WHERE pr.prescription_id = ? AND pr.did = ? LIMIT 1");
        $stmt->bind_param('ii', $prescriptionId, $doctorId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        header('Content-Type: text/html; charset=utf-8');
        if (!$row) {
            echo '<h3>Prescription not found</h3>';
            exit;
        }

        $patientName = htmlspecialchars(trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')));
        $medicine = htmlspecialchars($row['medicine_name'] ?? '');
        $dosage = htmlspecialchars($row['dosage'] ?? '');
        $duration = htmlspecialchars($row['duration'] ?? '');
        $instructions = nl2br(htmlspecialchars($row['instructions'] ?? ''));
        $created = htmlspecialchars($row['created_at'] ?? '');

        echo "<!DOCTYPE html><html><head><title>Prescription</title><style>body{font-family:Arial;padding:24px;}h1{color:#0b3d91} .card{border:1px solid #ccc;border-radius:8px;padding:16px;} .line{margin:8px 0;}</style></head><body><h1>Prescription</h1><div class='card'><div class='line'><strong>Patient:</strong> {$patientName}</div><div class='line'><strong>Medicine:</strong> {$medicine}</div><div class='line'><strong>Dosage:</strong> {$dosage}</div><div class='line'><strong>Duration:</strong> {$duration}</div><div class='line'><strong>Instructions:</strong><br>{$instructions}</div><div class='line'><strong>Created:</strong> {$created}</div></div><script>window.print();</script></body></html>";
        exit;
    }

    if ($action === 'send_message') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        $message = trim($_POST['message_text'] ?? '');
        if ($message === '') {
            safeJson(['success' => false, 'message' => 'Message is required'], 422);
        }

        $stmt = $conn->prepare("INSERT INTO doctor_patient_messages (pid, did, message_text, status) VALUES (?, ?, ?, 'Sent')");
        $stmt->bind_param('iis', $pid, $doctorId, $message);
        $stmt->execute();
        logDoctorActivity($conn, $doctorId, $pid, 'send_message', 'Sent instruction message');

        safeJson(['success' => true, 'message' => 'Message sent']);
    }

    if ($action === 'list_messages') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        $stmt = $conn->prepare("SELECT message_id, message_text, status, created_at
            FROM doctor_patient_messages
            WHERE pid = ? AND did = ?
            ORDER BY created_at DESC LIMIT 40");
        $stmt->bind_param('ii', $pid, $doctorId);
        $stmt->execute();

        $rows = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        safeJson(['success' => true, 'data' => $rows]);
    }

    if ($action === 'upload_file') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        if (!isset($_FILES['health_file']) || $_FILES['health_file']['error'] !== UPLOAD_ERR_OK) {
            safeJson(['success' => false, 'message' => 'File upload failed'], 422);
        }

        $fileType = trim($_POST['file_type'] ?? 'report');
        $allowed = ['report', 'xray', 'scan', 'prescription', 'other'];
        if (!in_array($fileType, $allowed, true)) {
            $fileType = 'other';
        }

        $baseName = basename($_FILES['health_file']['name']);
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $baseName);
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $allowedExt = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'doc', 'docx', 'txt'];
        if (!in_array($ext, $allowedExt, true)) {
            safeJson(['success' => false, 'message' => 'Unsupported file type'], 422);
        }

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'health_files';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $finalName = time() . '_' . mt_rand(1000, 9999) . '_' . $safeName;
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $finalName;
        if (!move_uploaded_file($_FILES['health_file']['tmp_name'], $dest)) {
            safeJson(['success' => false, 'message' => 'Could not save file'], 500);
        }

        $publicPath = 'uploads/health_files/' . $finalName;
        $pidColumn = columnExists($conn, 'patient_health_files', 'pid') ? 'pid' : 'patient_id';
        $nameColumn = columnExists($conn, 'patient_health_files', 'file_name') ? 'file_name' : 'original_name';
        $storedColumn = columnExists($conn, 'patient_health_files', 'stored_name') ? ', stored_name' : '';
        $storedPlaceholder = columnExists($conn, 'patient_health_files', 'stored_name') ? ', ?' : '';
        $roleColumn = columnExists($conn, 'patient_health_files', 'uploaded_by_role') ? ', uploaded_by_role' : '';
        $rolePlaceholder = columnExists($conn, 'patient_health_files', 'uploaded_by_role') ? ", 'doctor'" : '';
        $byIdColumn = columnExists($conn, 'patient_health_files', 'uploaded_by_id') ? ', uploaded_by_id' : '';
        $byIdPlaceholder = columnExists($conn, 'patient_health_files', 'uploaded_by_id') ? ', ?' : '';
        $byColumn = columnExists($conn, 'patient_health_files', 'uploaded_by') ? ', uploaded_by' : '';
        $byPlaceholder = columnExists($conn, 'patient_health_files', 'uploaded_by') ? ', ?' : '';

        $sql = "INSERT INTO patient_health_files ({$pidColumn}, file_type, {$nameColumn}, file_path{$storedColumn}{$roleColumn}{$byIdColumn}{$byColumn})
            VALUES (?, ?, ?, ?{$storedPlaceholder}{$rolePlaceholder}{$byIdPlaceholder}{$byPlaceholder})";
        $stmt = $conn->prepare($sql);

        if ($storedColumn !== '' && $byIdColumn !== '' && $byColumn !== '') {
            $stmt->bind_param('issssii', $pid, $fileType, $baseName, $publicPath, $finalName, $doctorId, $doctorId);
        } elseif ($storedColumn !== '' && $byIdColumn !== '') {
            $stmt->bind_param('issssi', $pid, $fileType, $baseName, $publicPath, $finalName, $doctorId);
        } elseif ($storedColumn !== '' && $byColumn !== '') {
            $stmt->bind_param('issssi', $pid, $fileType, $baseName, $publicPath, $finalName, $doctorId);
        } elseif ($storedColumn !== '') {
            $stmt->bind_param('issss', $pid, $fileType, $baseName, $publicPath, $finalName);
        } elseif ($byIdColumn !== '' && $byColumn !== '') {
            $stmt->bind_param('isssii', $pid, $fileType, $baseName, $publicPath, $doctorId, $doctorId);
        } elseif ($byIdColumn !== '' || $byColumn !== '') {
            $stmt->bind_param('isssi', $pid, $fileType, $baseName, $publicPath, $doctorId);
        } else {
            $stmt->bind_param('isss', $pid, $fileType, $baseName, $publicPath);
        }
        $stmt->execute();
        logDoctorActivity($conn, $doctorId, $pid, 'upload_file', 'Uploaded ' . $baseName . ' (' . $fileType . ')');

        safeJson(['success' => true, 'message' => 'File uploaded']);
    }

    if ($action === 'list_files') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        $pidColumn = columnExists($conn, 'patient_health_files', 'pid') ? 'pid' : 'patient_id';
        $nameExpr = columnExists($conn, 'patient_health_files', 'file_name') ? 'file_name' : 'original_name AS file_name';
        $roleExpr = columnExists($conn, 'patient_health_files', 'uploaded_by_role') ? 'uploaded_by_role' : "'doctor' AS uploaded_by_role";

        $stmt = $conn->prepare("SELECT file_id, {$nameExpr}, file_path, file_type, {$roleExpr}, uploaded_at
            FROM patient_health_files WHERE {$pidColumn} = ? ORDER BY uploaded_at DESC");
        $stmt->bind_param('i', $pid);
        $stmt->execute();

        $rows = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        safeJson(['success' => true, 'data' => $rows]);
    }

    if ($action === 'export_csv') {
        $pid = (int) ($_POST['pid'] ?? 0);
        if ($pid <= 0 || !isAssignedPatient($conn, $doctorId, $pid)) {
            safeJson(['success' => false, 'message' => 'Patient not assigned to you'], 403);
        }

        $profileStmt = $conn->prepare("SELECT pid, firstname, lastname, patient_condition FROM patient WHERE pid = ? LIMIT 1");
        $profileStmt->bind_param('i', $pid);
        $profileStmt->execute();
        $profile = $profileStmt->get_result()->fetch_assoc();

        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Patient ID', 'Name', 'Condition']);
        fputcsv($out, [
            $profile['pid'] ?? '',
            trim(($profile['firstname'] ?? '') . ' ' . ($profile['lastname'] ?? '')),
            normalizeCondition($profile['patient_condition'] ?? 'Moderate')
        ]);

        fputcsv($out, []);
        fputcsv($out, ['Notes']);
        fputcsv($out, ['Date', 'Diagnosis', 'Treatment Plan', 'Note']);

        $nStmt = $conn->prepare("SELECT created_at, diagnosis, treatment_plan, note_text FROM doctor_patient_notes WHERE did = ? AND pid = ? ORDER BY created_at DESC");
        $nStmt->bind_param('ii', $doctorId, $pid);
        $nStmt->execute();
        $nRes = $nStmt->get_result();
        while ($row = $nRes->fetch_assoc()) {
            fputcsv($out, [$row['created_at'], $row['diagnosis'], $row['treatment_plan'], $row['note_text']]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        safeJson([
            'success' => true,
            'file_name' => 'patient_' . $pid . '_summary.csv',
            'content_base64' => base64_encode($csv)
        ]);
    }

    safeJson(['success' => false, 'message' => 'Invalid action'], 400);
} catch (Throwable $e) {
    safeJson([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ], 500);
}
