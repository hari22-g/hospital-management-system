<?php
session_start();
include('../connection/config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    $post_id = mysqli_real_escape_string($conn, $_POST['post_id']);
    $comment_text = mysqli_real_escape_string($conn, $_POST['comment_text']);
    $user_id = $_SESSION['user_id'] ?? 0;
    $created_at = date('Y-m-d H:i:s');

    if ($user_id == 0) {
        echo "error: User not logged in.";
        exit;
    }

    $sql = "INSERT INTO comments (post_id, user_id, content, created_at) VALUES ('$post_id', '$user_id', '$comment_text', '$created_at')";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
?>
