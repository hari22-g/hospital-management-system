<?php
session_start();
include("../../connection/config.php"); // Ensure correct database connection

header('Content-Type: application/json');

$user_id = $_SESSION['user_id']; // Ensure it's an integer for security

if (!isset($user_id)) {
    echo json_encode(["error" => "User ID not received."]);
    exit;
}

// Fetch only the last recorded biomarker data
$query = "SELECT * 
          FROM biomarker 
          WHERE user_id = '$user_id' 
          ORDER BY created_at DESC LIMIT 1";

$result = mysqli_query($conn, $query);

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(["error" => "No heart health data found."]);
}
?>
