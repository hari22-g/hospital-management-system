<?php
session_start();
include("../../connection/config.php");

if (isset($_POST['id'])) {
    $disease_id = $_POST['id'];

    $sql = "DELETE FROM disease_information WHERE disease_id = '$disease_id'";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "invalid";
}
?>
