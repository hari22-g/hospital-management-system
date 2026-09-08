<?php
session_start();
include("../connection/config.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid post ID"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);

// Check if user already liked the post
$check_sql = "SELECT * FROM likes WHERE user_id = ? AND post_id = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // Unlike the post
    $delete_sql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    $action = "unliked";
} else {
    // Like the post
    $insert_sql = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    $action = "liked";
}

// Get updated like count
$count_sql = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?";
$stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$like_count = $row['like_count'];

echo json_encode(["status" => $action, "like_count" => $like_count]);
?>
