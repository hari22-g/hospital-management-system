<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();

function send_json_error($message, $code = 500)
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    send_json_error('Server error: ' . $message . ' on line ' . $line, 500);
});

set_exception_handler(function ($exception) {
    send_json_error('Server exception: ' . $exception->getMessage(), 500);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        $message = $error['message'] ?? 'Unknown error';
        $line = $error['line'] ?? 0;
        send_json_error('Fatal error: ' . $message . ' on line ' . $line, 500);
    }
    $output = ob_get_contents();
    if ($output !== false) {
        ob_end_flush();
    }
});
require_once 'connection/config.php';

function column_exists($conn, $table, $column)
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $safeColumn = $conn->real_escape_string((string) $column);
    $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
    $result = $conn->query($sql);
    if (!$result) {
        return false;
    }
    return $result->num_rows > 0;
}

function get_column_type($conn, $table, $column)
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $safeColumn = $conn->real_escape_string((string) $column);
    $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
    $result = $conn->query($sql);
    if (!$result || $result->num_rows === 0) {
        return null;
    }
    $row = $result->fetch_assoc();
    return $row['Type'] ?? null;
}

function normalize_doctor_id($doctorId, $columnType)
{
    if (!$doctorId) {
        return '';
    }
    if ($columnType && stripos($columnType, 'int') !== false) {
        $numeric = (int) preg_replace('/\D+/', '', (string) $doctorId);
        return $numeric > 0 ? $numeric : 0;
    }
    return strtoupper((string) $doctorId);
}

function medc_is_admin_session(): bool
{
    return isset($_SESSION['user_type']) && strtolower(trim((string) $_SESSION['user_type'])) === 'admin';
}

$hasPhotoPath = column_exists($conn, 'doctor', 'photo_path');

if (!medc_is_admin_session()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$action = $_POST['action'] ?? '';
$doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;

if ($doctor_id <= 0 || !in_array($action, ['edit', 'toggle', 'fetch'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

if ($action === 'fetch') {
    $selectSql = 'SELECT d_id, f_name, l_name, email, dob, gender, doctor_id, specialization, experience, consultation_fees, contact_no, address, city, state, country' . ($hasPhotoPath ? ', photo_path' : '') . ', approval FROM doctor WHERE d_id = ?';
    $stmt = $conn->prepare($selectSql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare fetch query']);
        exit();
    }
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    if ($hasPhotoPath) {
        $stmt->bind_result(
            $d_id,
            $df_name,
            $dl_name,
            $demail,
            $ddob,
            $dgender,
            $ddoctor_id,
            $dspecialization,
            $dexperience,
            $dfees,
            $dcontact,
            $daddress,
            $dcity,
            $dstate,
            $dcountry,
            $dphoto,
            $dapproval
        );
    } else {
        $stmt->bind_result(
            $d_id,
            $df_name,
            $dl_name,
            $demail,
            $ddob,
            $dgender,
            $ddoctor_id,
            $dspecialization,
            $dexperience,
            $dfees,
            $dcontact,
            $daddress,
            $dcity,
            $dstate,
            $dcountry,
            $dapproval
        );
        $dphoto = null;
    }
    $doctor = null;
    if ($stmt->fetch()) {
        $doctor = [
            'd_id' => $d_id,
            'f_name' => $df_name,
            'l_name' => $dl_name,
            'email' => $demail,
            'dob' => $ddob,
            'gender' => $dgender,
            'doctor_id' => $ddoctor_id,
            'specialization' => $dspecialization,
            'experience' => $dexperience,
            'consultation_fees' => $dfees,
            'contact_no' => $dcontact,
            'address' => $daddress,
            'city' => $dcity,
            'state' => $dstate,
            'country' => $dcountry,
            'photo_path' => $hasPhotoPath ? $dphoto : null,
            'approval' => $dapproval
        ];
    }
    $stmt->close();

    if (!$doctor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'doctor' => $doctor
    ]);
    exit();
}

if ($action === 'toggle') {
    $current = $conn->prepare('SELECT approval FROM doctor WHERE d_id = ?');
    $current->bind_param('i', $doctor_id);
    $current->execute();
    $current->bind_result($currentApproval);
    $row = null;
    if ($current->fetch()) {
        $row = ['approval' => $currentApproval];
    }
    $current->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit();
    }

    $nextApproval = strtolower(trim($row['approval'])) === 'approved' ? 'pending' : 'approved';
    $update = $conn->prepare('UPDATE doctor SET approval = ? WHERE d_id = ?');
    $update->bind_param('si', $nextApproval, $doctor_id);

    if (!$update->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Status update failed']);
        $update->close();
        exit();
    }
    $update->close();

    $status = $nextApproval === 'approved' ? 'Active' : 'Inactive';
    echo json_encode([
        'success' => true,
        'doctor' => [
            'status' => $status
        ]
    ]);
    exit();
}

$f_name = trim($_POST['f_name'] ?? '');
$l_name = trim($_POST['l_name'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$email = trim($_POST['email'] ?? '');
$contact_no = trim($_POST['contact_no'] ?? '');
$consultation_fees = trim($_POST['consultation_fees'] ?? '');
$city = trim($_POST['city'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$doctor_code = trim($_POST['doctor_id'] ?? '');
$address = trim($_POST['address'] ?? '');
$state = trim($_POST['state'] ?? '');
$country = trim($_POST['country'] ?? '');
$newPassword = trim((string) ($_POST['password'] ?? ''));
$hashedPassword = $newPassword !== '' ? password_hash($newPassword, PASSWORD_DEFAULT) : null;
$photo_path = null;

// Handle photo upload
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/Doctor_photos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_tmp = $_FILES['photo']['tmp_name'];
    $file_name = basename($_FILES['photo']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, WebP']);
        exit();
    }
    
    // Validate file size (5MB max)
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
        exit();
    }
    
    // Create unique filename
    $unique_name = 'doctor_' . time() . '_' . uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_name;
    
    if (move_uploaded_file($file_tmp, $file_path)) {
        $photo_path = $file_path;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload photo']);
        exit();
    }
}

if ($f_name === '' || $l_name === '' || $specialization === '' || $experience === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit();
}

$currentEmail = null;
$currentDoctor = null;
$emailLookup = $conn->prepare('SELECT email, dob, gender, doctor_id, address, city, state, country, contact_no, consultation_fees, specialization, experience FROM doctor WHERE d_id = ?');
$emailLookup->bind_param('i', $doctor_id);
$emailLookup->execute();
$emailLookup->bind_result(
    $emailValue,
    $dobValue,
    $genderValue,
    $doctorIdValue,
    $addressValue,
    $cityValue,
    $stateValue,
    $countryValue,
    $contactValue,
    $feesValue,
    $specValue,
    $experienceValue
);
if ($emailLookup->fetch()) {
    $currentDoctor = [
        'email' => $emailValue,
        'dob' => $dobValue,
        'gender' => $genderValue,
        'doctor_id' => $doctorIdValue,
        'address' => $addressValue,
        'city' => $cityValue,
        'state' => $stateValue,
        'country' => $countryValue,
        'contact_no' => $contactValue,
        'consultation_fees' => $feesValue,
        'specialization' => $specValue,
        'experience' => $experienceValue
    ];
    $currentEmail = $emailValue;
}
$emailLookup->close();

$doctorIdType = get_column_type($conn, 'doctor', 'doctor_id');
$doctor_code_db = normalize_doctor_id($doctor_code, $doctorIdType);

if ($currentDoctor) {
    $dob = $dob !== '' ? $dob : (string) ($currentDoctor['dob'] ?? '');
    $gender = $gender !== '' ? $gender : (string) ($currentDoctor['gender'] ?? '');
    $doctor_code = $doctor_code !== '' ? $doctor_code : (string) ($currentDoctor['doctor_id'] ?? '');
    $doctor_code_db = normalize_doctor_id($doctor_code, $doctorIdType);
    $address = $address !== '' ? $address : (string) ($currentDoctor['address'] ?? '');
    $city = $city !== '' ? $city : (string) ($currentDoctor['city'] ?? $city);
    $state = $state !== '' ? $state : (string) ($currentDoctor['state'] ?? '');
    $country = $country !== '' ? $country : (string) ($currentDoctor['country'] ?? '');
    $contact_no = $contact_no !== '' ? $contact_no : (string) ($currentDoctor['contact_no'] ?? '');
    $consultation_fees = $consultation_fees !== '' ? $consultation_fees : (string) ($currentDoctor['consultation_fees'] ?? '');
    $specialization = $specialization !== '' ? $specialization : (string) ($currentDoctor['specialization'] ?? '');
    $experience = $experience !== '' ? $experience : (string) ($currentDoctor['experience'] ?? '');
}

$updateFields = [
    'f_name' => $f_name,
    'l_name' => $l_name,
    'email' => $email,
    'contact_no' => $contact_no,
    'specialization' => $specialization,
    'experience' => $experience,
    'consultation_fees' => $consultation_fees,
    'city' => $city,
    'dob' => $dob,
    'gender' => $gender,
    'doctor_id' => $doctor_code_db,
    'address' => $address,
    'state' => $state,
    'country' => $country
];

$optionalUpdates = [
    'department' => trim($_POST['department'] ?? ''),
    'qualification' => trim($_POST['qualification'] ?? ''),
    'license_number' => trim($_POST['license_number'] ?? ''),
    'registration_council' => trim($_POST['registration_council'] ?? ''),
    'joining_date' => trim($_POST['joining_date'] ?? ''),
    'doctor_type' => trim($_POST['doctor_type'] ?? ''),
    'emergency_available' => isset($_POST['emergency_available']) ? 1 : 0,
    'online_consultation' => isset($_POST['online_consultation']) ? 1 : 0,
    'can_write_prescription' => isset($_POST['can_write_prescription']) ? 1 : 0,
    'working_days' => isset($_POST['working_days']) && is_array($_POST['working_days']) ? implode(',', $_POST['working_days']) : '',
    'available_from' => trim($_POST['available_from'] ?? ''),
    'available_to' => trim($_POST['available_to'] ?? ''),
    'break_start' => trim($_POST['break_start'] ?? ''),
    'break_end' => trim($_POST['break_end'] ?? ''),
    'username' => trim($_POST['username'] ?? '')
];

foreach ($optionalUpdates as $column => $value) {
    if (column_exists($conn, 'doctor', $column)) {
        $updateFields[$column] = $value;
    }
}

if ($hashedPassword !== null) {
    $updateFields['pass'] = $hashedPassword;
}
if ($photo_path && $hasPhotoPath) {
    $updateFields['photo_path'] = $photo_path;
}

$setClauses = [];
$types = '';
$values = [];
foreach ($updateFields as $column => $value) {
    $setClauses[] = $column . ' = ?';
    $types .= 's';
    $values[] = $value;
}
$types .= 'i';
$values[] = $doctor_id;

$updateSql = 'UPDATE doctor SET ' . implode(', ', $setClauses) . ' WHERE d_id = ?';
$update = $conn->prepare($updateSql);
$bindValues = [];
foreach ($values as $key => $value) {
    $bindValues[$key] = &$values[$key];
}
array_unshift($bindValues, $types);
call_user_func_array([$update, 'bind_param'], $bindValues);

if (!$update->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Update failed']);
    $update->close();
    exit();
}
$update->close();

if ($currentEmail) {
    $userUpdateSql = 'UPDATE users SET email = ?, fname = ?, lname = ?';
    if ($hashedPassword !== null) {
        $userUpdateSql .= ', password = ?';
    }
    $userUpdateSql .= ' WHERE email = ? AND user_type = "doctor"';
    $userUpdate = $conn->prepare($userUpdateSql);
    if ($hashedPassword !== null) {
        $userUpdate->bind_param('sssss', $email, $f_name, $l_name, $hashedPassword, $currentEmail);
    } else {
        $userUpdate->bind_param('ssss', $email, $f_name, $l_name, $currentEmail);
    }
    $userUpdate->execute();
    $userUpdate->close();
}

echo json_encode([
    'success' => true,
    'doctor' => [
        'id' => $doctor_id,
        'f_name' => $f_name,
        'l_name' => $l_name,
        'email' => $email,
        'contact_no' => $contact_no,
        'specialization' => $specialization,
        'department' => $updateFields['department'] ?? '',
        'joining_date' => $updateFields['joining_date'] ?? '',
        'experience' => $experience,
        'consultation_fees' => $consultation_fees,
        'city' => $city,
        'dob' => $dob,
        'gender' => $gender,
        'doctor_id' => $doctor_code_db,
        'doctor_id_display' => strtoupper((string) $doctor_code),
        'address' => $address,
        'state' => $state,
        'country' => $country,
        'photo_path' => $hasPhotoPath ? $photo_path : null
    ]
]);
