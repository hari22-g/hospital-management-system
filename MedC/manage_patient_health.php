<?php
// manage_patient_health.php - Admin endpoint to manage patient health data
error_reporting(E_ALL);
ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
ob_start();

register_shutdown_function(function () {
    $error = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if ($error && in_array($error['type'], $fatalTypes, true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Fatal server error: ' . ($error['message'] ?? 'Unknown error')
        ]);
    }
});

require_once 'connection/config.php';

/**
 * Returns the first existing column name from a whitelist for a given table.
 */
function firstExistingColumn(mysqli $conn, string $table, array $candidates): ?string {
    $allowedTables = ['biomarker', 'weight_tracker', 'patient'];
    if (!in_array($table, $allowedTables, true)) {
        return null;
    }

    foreach ($candidates as $column) {
        if (!preg_match('/^[a-z_]+$/', $column)) {
            continue;
        }
        $query = "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $column;
        }
    }

    return null;
}

function normalizePatientCondition(string $value): string {
    $normalized = strtolower(trim($value));
    if ($normalized === 'good') {
        $normalized = 'stable';
    }
    if ($normalized === 'medium') {
        $normalized = 'recovering';
    }
    $allowed = ['critical', 'stable', 'recovering'];
    return in_array($normalized, $allowed, true) ? $normalized : 'stable';
}

function ensurePatientConditionColumn(mysqli $conn): bool {
    $existing = firstExistingColumn($conn, 'patient', ['patient_condition']);
    if (!$existing) {
        return (bool) $conn->query(
            "ALTER TABLE patient ADD COLUMN patient_condition VARCHAR(20) NOT NULL DEFAULT 'stable'"
        );
    }

    $conn->query("UPDATE patient SET patient_condition = 'stable' WHERE LOWER(patient_condition) = 'good'");
    $conn->query("UPDATE patient SET patient_condition = 'recovering' WHERE LOWER(patient_condition) = 'medium'");
    $conn->query("UPDATE patient SET patient_condition = 'stable' WHERE patient_condition IS NULL OR patient_condition = ''");
    return true;
}

function ensureHealthTables(mysqli $conn): void {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS patient_vitals (
            vitals_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            systolic_bp INT DEFAULT NULL,
            diastolic_bp INT DEFAULT NULL,
            heart_rate INT DEFAULT NULL,
            recorded_date DATE NOT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_patient_date (patient_id, recorded_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS admin_patient_notes (
            note_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            author_id INT DEFAULT NULL,
            author_name VARCHAR(255) DEFAULT NULL,
            note_text TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_patient_notes (patient_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS patient_condition_logs (
            log_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            condition_status VARCHAR(20) NOT NULL,
            changed_by INT DEFAULT NULL,
            changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_patient_condition (patient_id, changed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS patient_health_files (
            file_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            file_type VARCHAR(30) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            uploaded_by INT DEFAULT NULL,
            uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_patient_files (patient_id, uploaded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $weightIdColumn = firstExistingColumn($conn, 'weight_tracker', ['weight_id']);
    if (!$weightIdColumn) {
        $conn->query("ALTER TABLE weight_tracker ADD COLUMN weight_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$action = trim($_POST['action'] ?? '');
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
$isPatient = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient');

if (!$isAdmin && !$isPatient) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$patientReadActions = ['get_patient_health', 'export_patient_data'];
if (!$isAdmin) {
    if (!in_array($action, $patientReadActions, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit();
    }

    $requestedPatientId = intval($_POST['patient_id'] ?? 0);
    $sessionPatientId = intval($_SESSION['user_id'] ?? 0);
    if ($requestedPatientId <= 0 || $requestedPatientId !== $sessionPatientId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only view your own data']);
        exit();
    }
}

try {
    if (!ensurePatientConditionColumn($conn)) {
        throw new Exception('Unable to prepare patient condition column');
    }
    ensureHealthTables($conn);

    if ($action === 'list_patients') {
        $search = trim($_POST['search'] ?? '');
        $searchField = strtolower(trim($_POST['search_field'] ?? 'all'));
        $conditionFilter = trim($_POST['condition'] ?? '');

        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            if ($searchField === 'name') {
                $where[] = '(p.firstname LIKE ? OR p.lastname LIKE ?)';
                $types .= 'ss';
                $params[] = $like;
                $params[] = $like;
            } elseif ($searchField === 'email') {
                $where[] = 'p.email LIKE ?';
                $types .= 's';
                $params[] = $like;
            } else {
                $where[] = '(p.firstname LIKE ? OR p.lastname LIKE ? OR p.email LIKE ?)';
                $types .= 'sss';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
        }

        if ($conditionFilter !== '') {
            $where[] = 'LOWER(p.patient_condition) = ?';
            $types .= 's';
            $params[] = normalizePatientCondition($conditionFilter);
        }

        $query = "SELECT p.pid, p.firstname, p.lastname, p.email, p.patient_condition FROM patient p";
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        $query .= ' ORDER BY p.firstname, p.lastname LIMIT 200';

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Unable to prepare patient list: ' . $conn->error);
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $row['patient_condition'] = normalizePatientCondition((string)($row['patient_condition'] ?? 'stable'));
            $row['full_name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            $patients[] = $row;
        }
        $stmt->close();

        $totalPatients = 0;
        $criticalPatients = 0;
        $recentUpdates = 0;

        $countResult = $conn->query('SELECT COUNT(*) AS total FROM patient');
        if ($countResult) {
            $totalPatients = (int)($countResult->fetch_assoc()['total'] ?? 0);
        }

        $criticalResult = $conn->query("SELECT COUNT(*) AS total FROM patient WHERE LOWER(patient_condition) = 'critical'");
        if ($criticalResult) {
            $criticalPatients = (int)($criticalResult->fetch_assoc()['total'] ?? 0);
        }

        $recentResult = $conn->query("SELECT (
            (SELECT COUNT(*) FROM weight_tracker WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) +
            (SELECT COUNT(*) FROM patient_vitals WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) +
            (SELECT COUNT(*) FROM admin_patient_notes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) +
            (SELECT COUNT(*) FROM patient_condition_logs WHERE changed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY))
        ) AS total");
        if ($recentResult) {
            $recentUpdates = (int)($recentResult->fetch_assoc()['total'] ?? 0);
        }

        echo json_encode([
            'success' => true,
            'patients' => $patients,
            'overview' => [
                'total_patients' => $totalPatients,
                'critical_patients' => $criticalPatients,
                'recent_updates' => $recentUpdates
            ]
        ]);
    } elseif ($action === 'get_patient_health') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        if ($patient_id <= 0) {
            throw new Exception('Invalid patient ID');
        }

        $patientStmt = $conn->prepare('SELECT pid, firstname, lastname, email, patient_condition FROM patient WHERE pid = ?');
        $patientStmt->bind_param('i', $patient_id);
        $patientStmt->execute();
        $patientResult = $patientStmt->get_result();
        if ($patientResult->num_rows === 0) {
            throw new Exception('Patient not found');
        }
        $patient = $patientResult->fetch_assoc();
        $patient['patient_condition'] = normalizePatientCondition((string)($patient['patient_condition'] ?? 'stable'));
        $patientStmt->close();

        $weightDateColumn = firstExistingColumn($conn, 'weight_tracker', ['recorded_date', 'month', 'created_at']);
        if (!$weightDateColumn) {
            throw new Exception('Weight date column not found');
        }
        $weightIdColumn = firstExistingColumn($conn, 'weight_tracker', ['weight_id', 'id']);
        $weightIdSelect = $weightIdColumn ? "{$weightIdColumn} AS weight_id," : 'NULL AS weight_id,';

        $weightsStmt = $conn->prepare("SELECT {$weightIdSelect} weight, {$weightDateColumn} AS recorded_date, created_at FROM weight_tracker WHERE user_id = ? ORDER BY {$weightDateColumn} DESC, created_at DESC LIMIT 50");
        $weightsStmt->bind_param('i', $patient_id);
        $weightsStmt->execute();
        $weightsResult = $weightsStmt->get_result();
        $weights = [];
        while ($row = $weightsResult->fetch_assoc()) {
            $weights[] = $row;
        }
        $weightsStmt->close();

        $vitalsStmt = $conn->prepare('SELECT vitals_id, systolic_bp, diastolic_bp, heart_rate, recorded_date, created_at FROM patient_vitals WHERE patient_id = ? ORDER BY recorded_date DESC, created_at DESC LIMIT 50');
        $vitalsStmt->bind_param('i', $patient_id);
        $vitalsStmt->execute();
        $vitalsResult = $vitalsStmt->get_result();
        $vitals = [];
        while ($row = $vitalsResult->fetch_assoc()) {
            $vitals[] = $row;
        }
        $vitalsStmt->close();

        $notesStmt = $conn->prepare('SELECT note_id, author_name, note_text, created_at FROM admin_patient_notes WHERE patient_id = ? ORDER BY created_at DESC LIMIT 100');
        $notesStmt->bind_param('i', $patient_id);
        $notesStmt->execute();
        $notesResult = $notesStmt->get_result();
        $notes = [];
        while ($row = $notesResult->fetch_assoc()) {
            $notes[] = $row;
        }
        $notesStmt->close();

        $filesStmt = $conn->prepare('SELECT file_id, file_type, original_name, file_path, uploaded_at FROM patient_health_files WHERE patient_id = ? ORDER BY uploaded_at DESC LIMIT 100');
        $filesStmt->bind_param('i', $patient_id);
        $filesStmt->execute();
        $filesResult = $filesStmt->get_result();
        $files = [];
        while ($row = $filesResult->fetch_assoc()) {
            $files[] = $row;
        }
        $filesStmt->close();

        $conditionLogsStmt = $conn->prepare('SELECT condition_status, changed_at FROM patient_condition_logs WHERE patient_id = ? ORDER BY changed_at DESC LIMIT 100');
        $conditionLogsStmt->bind_param('i', $patient_id);
        $conditionLogsStmt->execute();
        $conditionLogsResult = $conditionLogsStmt->get_result();
        $conditionLogs = [];
        while ($row = $conditionLogsResult->fetch_assoc()) {
            $conditionLogs[] = $row;
        }
        $conditionLogsStmt->close();

        $alerts = [];
        if ($patient['patient_condition'] === 'critical') {
            $alerts[] = 'Critical condition flag is active.';
        }
        if (count($weights) >= 2) {
            $latest = floatval($weights[0]['weight']);
            $previous = floatval($weights[1]['weight']);
            if (abs($latest - $previous) >= 5) {
                $alerts[] = 'Sudden weight change detected (>= 5 kg between last two records).';
            }
        }
        if (!empty($vitals)) {
            $lastVitals = $vitals[0];
            $sys = intval($lastVitals['systolic_bp']);
            $dia = intval($lastVitals['diastolic_bp']);
            $hr = intval($lastVitals['heart_rate']);
            if ($sys > 140 || $dia > 90 || $sys < 90 || $dia < 60) {
                $alerts[] = 'Blood pressure is outside normal range.';
            }
            if ($hr > 110 || $hr < 50) {
                $alerts[] = 'Heart rate is outside normal range.';
            }
        }

        $timeline = [];
        foreach ($weights as $w) {
            $timeline[] = [
                'event_date' => $w['recorded_date'],
                'description' => 'Weight updated to ' . $w['weight'] . ' kg'
            ];
        }
        foreach ($vitals as $v) {
            $timeline[] = [
                'event_date' => $v['recorded_date'],
                'description' => 'Vitals recorded: BP ' . $v['systolic_bp'] . '/' . $v['diastolic_bp'] . ', HR ' . $v['heart_rate']
            ];
        }
        foreach ($conditionLogs as $c) {
            $timeline[] = [
                'event_date' => substr((string)$c['changed_at'], 0, 10),
                'description' => 'Condition set to ' . ucfirst(normalizePatientCondition((string)$c['condition_status']))
            ];
        }
        foreach ($notes as $n) {
            $timeline[] = [
                'event_date' => substr((string)$n['created_at'], 0, 10),
                'description' => 'Admin note added'
            ];
        }

        usort($timeline, function($a, $b) {
            return strcmp((string)$b['event_date'], (string)$a['event_date']);
        });

        $totalPatients = 0;
        $criticalPatients = 0;
        $recentUpdates = 0;
        $c1 = $conn->query('SELECT COUNT(*) AS total FROM patient');
        if ($c1) {
            $totalPatients = (int)($c1->fetch_assoc()['total'] ?? 0);
        }
        $c2 = $conn->query("SELECT COUNT(*) AS total FROM patient WHERE LOWER(patient_condition) = 'critical'");
        if ($c2) {
            $criticalPatients = (int)($c2->fetch_assoc()['total'] ?? 0);
        }
        $recentUpdates = count(array_filter($timeline, function($t) {
            return strtotime((string)$t['event_date']) >= strtotime('-7 days');
        }));

        echo json_encode([
            'success' => true,
            'patient' => $patient,
            'weights' => $weights,
            'vitals' => $vitals,
            'notes' => $notes,
            'files' => $files,
            'alerts' => $alerts,
            'timeline' => $timeline,
            'overview' => [
                'total_patients' => $totalPatients,
                'critical_patients' => $criticalPatients,
                'recent_updates' => $recentUpdates
            ]
        ]);
    } elseif ($action === 'update_patient_condition') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $condition = normalizePatientCondition((string)($_POST['patient_condition'] ?? 'stable'));
        if ($patient_id <= 0) {
            throw new Exception('Invalid patient ID');
        }

        $update = $conn->prepare('UPDATE patient SET patient_condition = ? WHERE pid = ?');
        $update->bind_param('si', $condition, $patient_id);
        if (!$update->execute()) {
            throw new Exception('Unable to update condition: ' . $update->error);
        }
        $update->close();

        $changedBy = intval($_SESSION['user_id'] ?? 0);
        $log = $conn->prepare('INSERT INTO patient_condition_logs (patient_id, condition_status, changed_by) VALUES (?, ?, ?)');
        $log->bind_param('isi', $patient_id, $condition, $changedBy);
        $log->execute();
        $log->close();

        echo json_encode(['success' => true, 'patient_condition' => $condition, 'message' => 'Condition updated']);
    } elseif ($action === 'add_weight') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $weight = floatval($_POST['weight'] ?? 0);
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        if ($patient_id <= 0 || $weight <= 0) {
            throw new Exception('Invalid patient ID or weight');
        }

        $weightDateColumn = firstExistingColumn($conn, 'weight_tracker', ['recorded_date', 'month', 'created_at']);
        if (!$weightDateColumn) {
            throw new Exception('Weight tracker date column not found');
        }
        $stmt = $conn->prepare("INSERT INTO weight_tracker (user_id, weight, {$weightDateColumn}) VALUES (?, ?, ?)");
        $stmt->bind_param('ids', $patient_id, $weight, $date);
        if (!$stmt->execute()) {
            throw new Exception('Unable to add weight: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Weight added']);
    } elseif ($action === 'update_weight') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $weight_id = intval($_POST['weight_id'] ?? 0);
        $weight = floatval($_POST['weight'] ?? 0);
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        if ($patient_id <= 0 || $weight_id <= 0 || $weight <= 0) {
            throw new Exception('Invalid weight update request');
        }
        $weightDateColumn = firstExistingColumn($conn, 'weight_tracker', ['recorded_date', 'month', 'created_at']);
        $weightIdColumn = firstExistingColumn($conn, 'weight_tracker', ['weight_id', 'id']);
        if (!$weightDateColumn || !$weightIdColumn) {
            throw new Exception('Weight edit requires unique weight ID column');
        }
        $stmt = $conn->prepare("UPDATE weight_tracker SET weight = ?, {$weightDateColumn} = ? WHERE {$weightIdColumn} = ? AND user_id = ?");
        $stmt->bind_param('dsii', $weight, $date, $weight_id, $patient_id);
        if (!$stmt->execute()) {
            throw new Exception('Unable to update weight: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Weight updated']);
    } elseif ($action === 'delete_weight') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $weight_id = intval($_POST['weight_id'] ?? 0);
        if ($patient_id <= 0 || $weight_id <= 0) {
            throw new Exception('Invalid weight delete request');
        }
        $weightIdColumn = firstExistingColumn($conn, 'weight_tracker', ['weight_id', 'id']);
        if (!$weightIdColumn) {
            throw new Exception('Weight delete requires unique weight ID column');
        }
        $stmt = $conn->prepare("DELETE FROM weight_tracker WHERE {$weightIdColumn} = ? AND user_id = ?");
        $stmt->bind_param('ii', $weight_id, $patient_id);
        if (!$stmt->execute()) {
            throw new Exception('Unable to delete weight: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Weight deleted']);
    } elseif ($action === 'add_vitals') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $systolic = intval($_POST['systolic_bp'] ?? 0);
        $diastolic = intval($_POST['diastolic_bp'] ?? 0);
        $heartRate = intval($_POST['heart_rate'] ?? 0);
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        if ($patient_id <= 0 || $systolic <= 0 || $diastolic <= 0 || $heartRate <= 0) {
            throw new Exception('Invalid vitals data');
        }
        $createdBy = intval($_SESSION['user_id'] ?? 0);
        $stmt = $conn->prepare('INSERT INTO patient_vitals (patient_id, systolic_bp, diastolic_bp, heart_rate, recorded_date, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iiiisi', $patient_id, $systolic, $diastolic, $heartRate, $date, $createdBy);
        if (!$stmt->execute()) {
            throw new Exception('Unable to add vitals: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Vitals added']);
    } elseif ($action === 'update_vitals') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $vitals_id = intval($_POST['vitals_id'] ?? 0);
        $systolic = intval($_POST['systolic_bp'] ?? 0);
        $diastolic = intval($_POST['diastolic_bp'] ?? 0);
        $heartRate = intval($_POST['heart_rate'] ?? 0);
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        $stmt = $conn->prepare('UPDATE patient_vitals SET systolic_bp = ?, diastolic_bp = ?, heart_rate = ?, recorded_date = ? WHERE vitals_id = ? AND patient_id = ?');
        $stmt->bind_param('iiisii', $systolic, $diastolic, $heartRate, $date, $vitals_id, $patient_id);
        if (!$stmt->execute()) {
            throw new Exception('Unable to update vitals: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Vitals updated']);
    } elseif ($action === 'delete_vitals') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $vitals_id = intval($_POST['vitals_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM patient_vitals WHERE vitals_id = ? AND patient_id = ?');
        $stmt->bind_param('ii', $vitals_id, $patient_id);
        if (!$stmt->execute()) {
            throw new Exception('Unable to delete vitals: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Vitals deleted']);
    } elseif ($action === 'add_note') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $note = trim($_POST['note_text'] ?? '');
        if ($patient_id <= 0 || $note === '') {
            throw new Exception('Invalid note');
        }
        $authorId = intval($_SESSION['user_id'] ?? 0);
        $authorName = trim((string)($_SESSION['username'] ?? $_SESSION['email'] ?? 'Admin'));
        $stmt = $conn->prepare('INSERT INTO admin_patient_notes (patient_id, author_id, author_name, note_text) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiss', $patient_id, $authorId, $authorName, $note);
        if (!$stmt->execute()) {
            throw new Exception('Unable to add note: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Note added']);
    } elseif ($action === 'update_note') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $note_id = intval($_POST['note_id'] ?? 0);
        $note = trim($_POST['note_text'] ?? '');
        $stmt = $conn->prepare('UPDATE admin_patient_notes SET note_text = ? WHERE note_id = ? AND patient_id = ?');
        $stmt->bind_param('sii', $note, $note_id, $patient_id);
        if (!$stmt->execute()) {
            throw new Exception('Unable to update note: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Note updated']);
    } elseif ($action === 'delete_note') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $note_id = intval($_POST['note_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM admin_patient_notes WHERE note_id = ? AND patient_id = ?');
        $stmt->bind_param('ii', $note_id, $patient_id);
        if (!$stmt->execute()) {
            throw new Exception('Unable to delete note: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Note deleted']);
    } elseif ($action === 'upload_patient_file') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $fileType = trim($_POST['file_type'] ?? 'report');
        if ($patient_id <= 0) {
            throw new Exception('Invalid patient ID');
        }
        if (!isset($_FILES['health_file']) || !is_uploaded_file($_FILES['health_file']['tmp_name'])) {
            throw new Exception('No file uploaded');
        }

        $allowedTypeMap = [
            'report' => ['pdf'],
            'prescription' => ['pdf', 'png', 'jpg', 'jpeg'],
            'image' => ['png', 'jpg', 'jpeg', 'webp']
        ];
        if (!isset($allowedTypeMap[$fileType])) {
            $fileType = 'report';
        }

        $originalName = basename((string)$_FILES['health_file']['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypeMap[$fileType], true)) {
            throw new Exception('Invalid file type for selected category');
        }

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'patient_health' . DIRECTORY_SEPARATOR . $patient_id;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception('Unable to create upload directory');
        }

        $storedName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($_FILES['health_file']['tmp_name'], $targetPath)) {
            throw new Exception('Failed to save uploaded file');
        }

        $relativePath = 'uploads/patient_health/' . $patient_id . '/' . $storedName;
        $uploadedBy = intval($_SESSION['user_id'] ?? 0);
        $stmt = $conn->prepare('INSERT INTO patient_health_files (patient_id, file_type, original_name, stored_name, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssi', $patient_id, $fileType, $originalName, $storedName, $relativePath, $uploadedBy);
        if (!$stmt->execute()) {
            throw new Exception('Unable to save file metadata: ' . $stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'File uploaded', 'file_path' => $relativePath]);
    } elseif ($action === 'delete_patient_file') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $file_id = intval($_POST['file_id'] ?? 0);
        $stmt = $conn->prepare('SELECT file_path FROM patient_health_files WHERE file_id = ? AND patient_id = ? LIMIT 1');
        $stmt->bind_param('ii', $file_id, $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('File not found');
        }
        $filePath = (string)$result->fetch_assoc()['file_path'];
        $stmt->close();

        $deleteStmt = $conn->prepare('DELETE FROM patient_health_files WHERE file_id = ? AND patient_id = ?');
        $deleteStmt->bind_param('ii', $file_id, $patient_id);
        if (!$deleteStmt->execute()) {
            throw new Exception('Unable to delete file metadata: ' . $deleteStmt->error);
        }
        $deleteStmt->close();

        $absolute = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }

        echo json_encode(['success' => true, 'message' => 'File deleted']);
    } elseif ($action === 'export_patient_data') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $format = strtolower(trim($_POST['format'] ?? 'csv'));
        if ($patient_id <= 0) {
            throw new Exception('Invalid patient ID');
        }

        $patientStmt = $conn->prepare('SELECT pid, firstname, lastname, email, patient_condition FROM patient WHERE pid = ?');
        $patientStmt->bind_param('i', $patient_id);
        $patientStmt->execute();
        $patientResult = $patientStmt->get_result();
        if ($patientResult->num_rows === 0) {
            throw new Exception('Patient not found');
        }
        $patient = $patientResult->fetch_assoc();
        $patientStmt->close();

        $weightDateColumn = firstExistingColumn($conn, 'weight_tracker', ['recorded_date', 'month', 'created_at']);
        $weights = [];
        if ($weightDateColumn) {
            $wStmt = $conn->prepare("SELECT weight, {$weightDateColumn} AS recorded_date FROM weight_tracker WHERE user_id = ? ORDER BY {$weightDateColumn} DESC LIMIT 200");
            $wStmt->bind_param('i', $patient_id);
            $wStmt->execute();
            $wRes = $wStmt->get_result();
            while ($r = $wRes->fetch_assoc()) {
                $weights[] = $r;
            }
            $wStmt->close();
        }

        $vitals = [];
        $vStmt = $conn->prepare('SELECT systolic_bp, diastolic_bp, heart_rate, recorded_date FROM patient_vitals WHERE patient_id = ? ORDER BY recorded_date DESC LIMIT 200');
        $vStmt->bind_param('i', $patient_id);
        $vStmt->execute();
        $vRes = $vStmt->get_result();
        while ($r = $vRes->fetch_assoc()) {
            $vitals[] = $r;
        }
        $vStmt->close();

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Patient ID', $patient['pid']]);
        fputcsv($stream, ['Name', trim($patient['firstname'] . ' ' . $patient['lastname'])]);
        fputcsv($stream, ['Email', $patient['email']]);
        fputcsv($stream, ['Condition', normalizePatientCondition((string)$patient['patient_condition'])]);
        fputcsv($stream, []);
        fputcsv($stream, ['Weight History']);
        fputcsv($stream, ['Date', 'Weight (kg)']);
        foreach ($weights as $w) {
            fputcsv($stream, [$w['recorded_date'], $w['weight']]);
        }
        fputcsv($stream, []);
        fputcsv($stream, ['Vitals History']);
        fputcsv($stream, ['Date', 'Systolic', 'Diastolic', 'Heart Rate']);
        foreach ($vitals as $v) {
            fputcsv($stream, [$v['recorded_date'], $v['systolic_bp'], $v['diastolic_bp'], $v['heart_rate']]);
        }
        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $isExcel = ($format === 'excel' || $format === 'xls');
        $fileName = 'patient_' . $patient_id . '_records.' . ($isExcel ? 'xls' : 'csv');
        $mimeType = $isExcel ? 'application/vnd.ms-excel' : 'text/csv';

        echo json_encode([
            'success' => true,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'base64_content' => base64_encode($csvContent)
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server exception: ' . $e->getMessage()
    ]);
}
?>
