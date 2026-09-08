<?php
session_start();
include '../../connection/config.php'; // Ensure you have a database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve values from the AJAX request
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $total_cholesterol = isset($_POST['total_cholesterol']) ? $_POST['total_cholesterol'] : null;
    $hdl_cholesterol = isset($_POST['hdl_cholesterol']) ? $_POST['hdl_cholesterol'] : null;
    $ldl_cholesterol = isset($_POST['ldl_cholesterol']) ? $_POST['ldl_cholesterol'] : null;
    $triglycerides = isset($_POST['triglycerides']) ? $_POST['triglycerides'] : null;
    $systolic_blood_pressure = isset($_POST['systolic_blood_pressure']) ? $_POST['systolic_blood_pressure'] : null;
    $diastolic_blood_pressure = isset($_POST['diastolic_blood_pressure']) ? $_POST['diastolic_blood_pressure'] : null;
    $non_fasting_glucose = isset($_POST['non_fasting_glucose']) ? $_POST['non_fasting_glucose'] : null;
    $glucose_post_fast = isset($_POST['glucose_post_fast']) ? $_POST['glucose_post_fast'] : null;
    $hba1c = isset($_POST['hba1c']) ? $_POST['hba1c'] : null;

    // Ensure user_id is valid
    if ($user_id == 0) {
        echo "Invalid user ID";
        exit;
    }

    // Insert data into biomarker table
    $sql = "INSERT INTO biomarker (user_id, total_cholesterol, hdl_cholesterol, ldl_cholesterol, triglycerides, systolic_blood_pressure, diastolic_blood_pressure, non_fasting_glucose, glucose_post_fast, hba1c)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iddddddddd", $user_id, $total_cholesterol, $hdl_cholesterol, $ldl_cholesterol, $triglycerides, $systolic_blood_pressure, $diastolic_blood_pressure, $non_fasting_glucose, $glucose_post_fast, $hba1c);

    if ($stmt->execute()) {
        echo 1; // Success
    } else {
        echo 0; // Failure
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request";
}
?>
