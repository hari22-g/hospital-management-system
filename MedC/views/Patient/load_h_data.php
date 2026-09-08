<?php
session_start();
include("../../connection/config.php");

// Ensure user_id is set and secure it
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = intval($_SESSION['user_id']); // Convert to integer for security

// Fetch glucose data ordered by created_at date
$sql = "SELECT DATE(created_at) AS date, total_cholesterol, hdl_cholesterol, ldl_cholesterol, triglycerides, systolic_blood_pressure, diastolic_blood_pressure  
        FROM biomarker 
        WHERE user_id = '$user_id' 
        ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);

$data = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
} else {
    $data = ["error" => "No glucose data found"];
}

// Return JSON response
echo json_encode($data);
?>
