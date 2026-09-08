<?php
session_start();
include("../connection/config.php");

header("Content-Type: application/json"); // Ensure proper JSON response

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];
$reason = $_POST['reason'] ?? 'No reason provided';

// Validate post_id
if (!isset($post_id) || empty($post_id) || !is_numeric($post_id)) {
    echo json_encode(["status" => "error", "message" => "Invalid post ID"]);
    exit();
}

// Check if the user has already reported this post
$check_sql = "SELECT * FROM communityreports WHERE user_id = ? AND post_id = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(["status" => "already_reported"]);
    exit();
}

// Insert the report into the communityreports table
$insert_sql = "INSERT INTO communityreports (user_id, post_id, reason) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param($stmt, "iis", $user_id, $post_id, $reason);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Insert failed"]);
}

exit(); // Ensure no extra output
?>
