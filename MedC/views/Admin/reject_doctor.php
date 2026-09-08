<?php
session_start();
include("../../connection/config.php");

if (isset($_POST['id'])) {
    $d_id = $_POST['id'];
    $rejected = 'rejected';

    // Corrected variable name ($reject → $rejected)
    $sql = "UPDATE doctor SET approval = '$rejected' WHERE d_id = '$d_id'";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn); // Debugging output
    }
} else {
    echo "invalid";
}
?>
