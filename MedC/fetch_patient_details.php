<?php
header('Content-Type: application/json');
include("connection/config.php");
session_start();

// Handle GET request for staff details (for admin portal staff edit)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sid'])) {
    $staff_id = intval($_GET['sid']);

    if ($staff_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
        exit();
    }

    // Fetch staff details including profile
    $fetch_staff = $conn->prepare(
        "SELECT u.user_id, u.fname, u.lname, u.email, u.role, u.phone, u.department, u.is_active,
                sp.qualifications, sp.experience_years, sp.license_number, sp.license_expiry, sp.shift, sp.salary, sp.emergency_contact, sp.emergency_phone
         FROM users u
         LEFT JOIN staff_profiles sp ON u.user_id = sp.user_id
         WHERE u.user_id = ? AND (u.user_type = 'staff' OR u.role IN ('receptionist', 'nurse', 'pharmacist', 'lab_technician'))"
    );
    $fetch_staff->bind_param('i', $staff_id);
    $fetch_staff->execute();
    $result = $fetch_staff->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Staff not found']);
        exit();
    }

    $staff_data = $result->fetch_assoc();
    $fetch_staff->close();

    $response = [
        'success' => true,
        'staff' => [
            'user_id' => $staff_data['user_id'],
            'fname' => $staff_data['fname'],
            'lname' => $staff_data['lname'],
            'email' => $staff_data['email'],
            'role' => $staff_data['role'],
            'phone' => $staff_data['phone'],
            'department' => $staff_data['department'],
            'is_active' => $staff_data['is_active'],
            'qualifications' => $staff_data['qualifications'] ?? '',
            'experience_years' => $staff_data['experience_years'] ?? 0,
            'license_number' => $staff_data['license_number'] ?? '',
            'license_expiry' => $staff_data['license_expiry'] ?? '',
            'shift' => $staff_data['shift'] ?? 'flexible',
            'salary' => $staff_data['salary'] ?? 0,
            'emergency_contact' => $staff_data['emergency_contact'] ?? '',
            'emergency_phone' => $staff_data['emergency_phone'] ?? '',
            'hire_date' => ''
        ]
    ];

    http_response_code(200);
    echo json_encode($response);
    exit();
}

// Handle GET request for patient basic details (for admin portal)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pid'])) {
    $patient_id = intval($_GET['pid']);

    if ($patient_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
        exit();
    }

    // Fetch patient basic details
    $fetch_patient = $conn->prepare(
        "SELECT pid, firstname, lastname, dob, gender, height, weight, bloodgroup, contact_no, state, country, email FROM patient WHERE pid = ?"
    );
    $fetch_patient->bind_param('i', $patient_id);
    $fetch_patient->execute();
    $result = $fetch_patient->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit();
    }

    $patient_data = $result->fetch_assoc();
    $fetch_patient->close();

    $response = [
        'success' => true,
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
            'allergies' => '',
            'medical_conditions' => '',
            'emergency_contact' => '',
            'emergency_phone' => ''
        ]
    ];

    http_response_code(200);
    echo json_encode($response);
    exit();
}

// Handle POST request for detailed patient information (original functionality)
if (isset($_POST['pid'])) {
    $pid = $_POST['pid'];

    // Fetch Medications
    $medicationsQuery = "SELECT brand_name, generic_name, strength, form, duration FROM medications WHERE user_id = ?";
    $stmt = $conn->prepare($medicationsQuery);
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $medicationsResult = $stmt->get_result();

    // Fetch Reports
    $reportsQuery = "SELECT report_type, referred_by, report_date, file_path FROM reports WHERE user_id = ?";
    $stmt = $conn->prepare($reportsQuery);
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $reportsResult = $stmt->get_result();

    // Fetch Biomarkers
    $biomarkerQuery = "SELECT total_cholesterol, hdl_cholesterol, ldl_cholesterol, triglycerides, systolic_blood_pressure, diastolic_blood_pressure, non_fasting_glucose, glucose_post_fast, hba1c FROM biomarker WHERE user_id = ?";
    $stmt = $conn->prepare($biomarkerQuery);
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $biomarkerResult = $stmt->get_result();

    // Output the styled content
    $output = '<div class="patient-details-container">';

    // Medications Section
    $output .= '<h3 class="section-title">Medications</h3><ul class="medications-list">';
    while ($med = $medicationsResult->fetch_assoc()) {
        $output .= "<li><b>{$med['brand_name']}</b> ({$med['generic_name']}) - {$med['strength']} {$med['form']} for {$med['duration']}</li>";
    }
    $output .= '</ul>';

    // Reports Section
    $output .= '<h3 class="section-title">Reports</h3><ul class="reports-list">';
    while ($rep = $reportsResult->fetch_assoc()) {
        $output .= "<li>{$rep['report_type']} - <b>Referred By:</b> {$rep['referred_by']} ({$rep['report_date']}) [<a href='" . $localhost . "uploads/Patient_Reports/" . $rep['file_path'] . "' target='_blank' class='report-link'>View Report</a>]</li>";
    }
    $output .= '</ul>';

    // Biomarkers Section
    if ($biomarker = $biomarkerResult->fetch_assoc()) {
        $output .= '<h3 class="section-title">Biomarkers</h3><ul class="biomarkers-list">';
        foreach ($biomarker as $key => $value) {
            $output .= "<li><b>" . ucfirst(str_replace("_", " ", $key)) . ":</b> {$value}</li>";
        }
        $output .= '</ul>';
    } else {
        $output .= '<p class="no-data">No biomarker data available.</p>';
    }

    $output .= '</div>'; // Close container div

    echo $output;
} else {
    echo "<p class='error-message'>Error: Patient ID not provided.</p>";
}
?>
