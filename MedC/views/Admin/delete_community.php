<?php
session_start();
include("../../connection/config.php");

if (isset($_POST['id'])) {
    $community_id = $_POST['id'];

    $sql = "DELETE FROM communities WHERE community_id = '$community_id'";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "invalid";
}
?>
