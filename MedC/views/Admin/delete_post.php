<?php
include("../../connection/config.php");

if (isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];

    // Delete from likes table first
    $delete_likes = "DELETE FROM likes WHERE post_id = ?";
    $stmt1 = $conn->prepare($delete_likes);
    $stmt1->bind_param("i", $post_id);
    $stmt1->execute();

    // Delete from communityreports table
    $delete_reports = "DELETE FROM communityreports WHERE post_id = ?";
    $stmt2 = $conn->prepare($delete_reports);
    $stmt2->bind_param("i", $post_id);
    $stmt2->execute();

    // Now delete from posts table
    $delete_post = "DELETE FROM posts WHERE post_id = ?";
    $stmt3 = $conn->prepare($delete_post);
    $stmt3->bind_param("i", $post_id);
    $stmt3->execute();

    // Check if deletion was successful
    if ($stmt3->affected_rows > 0) {
        echo "success";
    } else {
        echo "error: " . $conn->error;
    }

    $stmt1->close();
    $stmt2->close();
    $stmt3->close();
    $conn->close();
} else {
    echo "error: No post ID received";
}
?>
