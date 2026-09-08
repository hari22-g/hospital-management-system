<?php
// update_patient.php - Backend endpoint for updating patient info and toggling status
session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Include database connection
require_once 'connection/config.php';

$action = trim($_POST['action'] ?? '');
$patient_id = intval($_POST['patient_id'] ?? 0);

if ($patient_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
    exit();
}

// Verify patient exists
$check_patient = $mysqli->prepare("SELECT pid FROM patient WHERE pid = ?");
$check_patient->bind_param('i', $patient_id);
$check_patient->execute();
$result = $check_patient->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Patient not found']);
    exit();
}
$check_patient->close();

if ($action === 'edit') {
    // Update patient information
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $height = trim($_POST['height'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $bloodgroup = trim($_POST['bloodgroup'] ?? '');
    $contact_no = trim($_POST['contact_no'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $medical_conditions = trim($_POST['medical_conditions'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $emergency_phone = trim($_POST['emergency_phone'] ?? '');

    // Validation
    $required_fields = [
        'firstname' => 'First Name',
        'lastname' => 'Last Name',
        'dob' => 'Date of Birth',
        'gender' => 'Gender',
        'height' => 'Height',
        'weight' => 'Weight',
        'bloodgroup' => 'Blood Group',
        'contact_no' => 'Contact Number',
        'email' => 'Email'
    ];

    $errors = [];

    foreach ($required_fields as $field => $label) {
        if (empty($$field)) {
            $errors[] = $label . ' is required';
        }
    }

    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Validate contact number
    if (!empty($contact_no) && !preg_match('/^[0-9]{10,15}$/', $contact_no)) {
        $errors[] = 'Contact number must be 10-15 digits';
    }

    // Validate height and weight
    if (!empty($height) && (!is_numeric($height) || $height <= 0)) {
        $errors[] = 'Height must be a positive number';
    }

    if (!empty($weight) && (!is_numeric($weight) || $weight <= 0)) {
        $errors[] = 'Weight must be a positive number';
    }

    // Validate DOB
    if (!empty($dob)) {
        $dob_timestamp = strtotime($dob);
        if ($dob_timestamp === false || $dob_timestamp > time()) {
            $errors[] = 'Date of Birth must be in the past';
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit();
    }

    // Check if new email is unique (excluding current patient)
    if (!empty($email)) {
        $check_email = $mysqli->prepare("SELECT pid FROM patient WHERE email = ? AND pid != ?");
        $check_email->bind_param('si', $email, $patient_id);
        $check_email->execute();
        $result = $check_email->get_result();

        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already in use by another patient']);
            exit();
        }
        $check_email->close();
    }

    // Update patient record
    $update_patient = $mysqli->prepare(
        "UPDATE patient 
         SET firstname = ?, lastname = ?, dob = ?, gender = ?, height = ?, weight = ?, 
             bloodgroup = ?, contact_no = ?, state = ?, country = ?, email = ?
         WHERE pid = ?"
    );

    if (!$update_patient) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
        exit();
    }

    $update_patient->bind_param(
        'ssssiisisssi',
        $firstname,
        $lastname,
        $dob,
        $gender,
        $height,
        $weight,
        $bloodgroup,
        $contact_no,
        $state,
        $country,
        $email,
        $patient_id
    );

    if (!$update_patient->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating patient: ' . $update_patient->error]);
        exit();
    }
    $update_patient->close();

    // Fetch and return updated patient data
    $fetch_patient = $mysqli->prepare(
        "SELECT pid, firstname, lastname, dob, gender, height, weight, bloodgroup, contact_no, state, country, email FROM patient WHERE pid = ?"
    );
    $fetch_patient->bind_param('i', $patient_id);
    $fetch_patient->execute();
    $result = $fetch_patient->get_result();
    $patient_data = $result->fetch_assoc();
    $fetch_patient->close();

    echo json_encode([
        'success' => true,
        'message' => 'Patient updated successfully',
        'patient' => [
            'pid' => $patient_data['pid'],
            'firstname' => $patient_data['firstname'],
            'lastname' => $patient_data['lastname'],
            'dob' => $patient_data['dob'],
            'gender' => $patient_data['gender'],
            'height' => $patient_data['height'],
            'weight' => $patient_data['weight'],
            'bloodgroup' => $patient_data['bloodgroup'],
            'contact_no' => $patient_data['contact_no'],
            'state' => $patient_data['state'],
            'country' => $patient_data['country'],
            'email' => $patient_data['email'],
            'allergies' => $allergies,
            'medical_conditions' => $medical_conditions,
            'emergency_contact' => $emergency_contact,
            'emergency_phone' => $emergency_phone
        ]
    ]);

} elseif ($action === 'delete') {
    // Delete patient record
    $delete_patient = $mysqli->prepare("DELETE FROM patient WHERE pid = ?");
    $delete_patient->bind_param('i', $patient_id);

    if (!$delete_patient->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting patient: ' . $delete_patient->error]);
        exit();
    }
    $delete_patient->close();

    echo json_encode([
        'success' => true,
        'message' => 'Patient deleted successfully'
    ]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
