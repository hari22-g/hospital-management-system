<?php
// add_patient.php - Backend endpoint for adding new patient
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

// Get POST data
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
$password = trim($_POST['password'] ?? '');
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
    'email' => 'Email',
    'password' => 'Password'
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

// Validate contact number (should be numeric, 10-15 digits)
if (!empty($contact_no) && !preg_match('/^[0-9]{10,15}$/', $contact_no)) {
    $errors[] = 'Contact number must be 10-15 digits';
}

// Validate height and weight are positive numbers
if (!empty($height) && (!is_numeric($height) || $height <= 0)) {
    $errors[] = 'Height must be a positive number';
}

if (!empty($weight) && (!is_numeric($weight) || $weight <= 0)) {
    $errors[] = 'Weight must be a positive number';
}

// Validate DOB is in past
if (!empty($dob)) {
    $dob_timestamp = strtotime($dob);
    if ($dob_timestamp === false || $dob_timestamp > time()) {
        $errors[] = 'Date of Birth must be in the past';
    }
}

// If validation fails, return error response
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit();
}

// Check if email already exists
$check_email = $mysqli->prepare("SELECT pid FROM patient WHERE email = ?");
if (!$check_email) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit();
}

$check_email->bind_param('s', $email);
$check_email->execute();
$result = $check_email->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Patient with this email already exists']);
    exit();
}
$check_email->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert patient record
$insert_patient = $mysqli->prepare(
    "INSERT INTO patient (firstname, lastname, dob, gender, height, weight, bloodgroup, contact_no, state, country, email, password) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$insert_patient) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit();
}

$insert_patient->bind_param(
    'ssssiisssss',
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
    $hashed_password
);

if (!$insert_patient->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error adding patient: ' . $insert_patient->error]);
    exit();
}

$patient_id = $insert_patient->insert_id;
$insert_patient->close();

// Prepare response with all patient details
$response = [
    'success' => true,
    'message' => 'Patient added successfully',
    'patient' => [
        'pid' => $patient_id,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'dob' => $dob,
        'gender' => $gender,
        'height' => $height,
        'weight' => $weight,
        'bloodgroup' => $bloodgroup,
        'contact_no' => $contact_no,
        'state' => $state,
        'country' => $country,
        'email' => $email,
        'allergies' => $allergies,
        'medical_conditions' => $medical_conditions,
        'emergency_contact' => $emergency_contact,
        'emergency_phone' => $emergency_phone
    ]
];

http_response_code(201);
echo json_encode($response);
?>
