<?php
include("../../connection/config.php"); // Include database connection file
session_start();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // Get user ID from session
    $weight = isset($_POST['weight']) ? trim($_POST['weight']) : ''; // Get weight input

    // Validate input: Ensure weight is a valid number
    if ($weight === "" || !is_numeric($weight) || $weight <= 0) {
        echo json_encode(["status" => 0, "message" => "Invalid weight value"]);
        exit;
    }

    // Prepare SQL statement to insert data
    $stmt = $conn->prepare("INSERT INTO weight_tracker (user_id, weight, month) VALUES (?, ?, NOW())");
    $stmt->bind_param("id", $user_id, $weight); // "i" -> integer, "d" -> double

    if ($stmt->execute()) {
        echo json_encode(["status" => 1, "message" => "Weight inserted successfully"]);
    } else {
        echo json_encode(["status" => 0, "message" => "Database error"]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => 0, "message" => "Invalid request"]);
}
?>
