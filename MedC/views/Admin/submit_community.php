<?php
session_start();
include("../../connection/config.php"); // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $community_name = mysqli_real_escape_string($conn, $_POST['community_name']);
    $community_desc = mysqli_real_escape_string($conn, $_POST['community_desc']);

    // Image Upload
    $target_dir = "../../uploads/Communities/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["community_img"]["name"]);
    $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png'];

    if (in_array($image_type, $allowed_types)) {
        if (move_uploaded_file($_FILES["community_img"]["tmp_name"], $target_file)) {
            $image_name = basename($_FILES["community_img"]["name"]);
        } else {
            echo "<script>alert('Error uploading image!'); window.location.href='addCommunityForm.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid image format! Only JPG, JPEG, and PNG allowed.'); window.location.href='addCommunityForm.php';</script>";
        exit();
    }

    // Insert into the database
    $sql = "INSERT INTO communities (name, description, image) VALUES ('$community_name', '$community_desc', '$image_name')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
            alert('Community updated successfully!');
            window.location.href='" . $localhost . "admin_portal.php';
        </script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

mysqli_close($conn);
