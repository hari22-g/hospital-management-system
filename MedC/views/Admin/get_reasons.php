<?php
// get_reasons.php
include("../../connection/config.php");

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];

    $sql = "SELECT cr.reason, u.fname, u.lname 
            FROM communityreports cr
            JOIN users u ON cr.user_id = u.user_id
            WHERE cr.post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reasons = [];
    while ($row = $result->fetch_assoc()) {
        $reasons[] = $row['reason'] . " - Reported by: " . $row['fname'] . " " . $row['lname'];
    }

    echo json_encode($reasons);
}


?>