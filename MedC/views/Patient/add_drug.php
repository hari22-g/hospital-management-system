<?php
session_start();
include '../../connection/config.php'; // Include your DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $brand_name = $_POST['brand_name'];
    $generic_name = $_POST['generic_name'];
    $strength = $_POST['strength'];
    $form = $_POST['form'];
    $duration = $_POST['duration'];

    $sql = "INSERT INTO medications (user_id, brand_name, generic_name, strength, form, duration) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $brand_name, $generic_name, $strength, $form, $duration);

    if ($stmt->execute()) {
        echo "1"; // Success
    } else {
        echo "0"; // Failure
    }

    $stmt->close();
    $conn->close();
}
?>
