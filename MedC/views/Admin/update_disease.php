<?php
include("../../connection/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $disease_id = mysqli_real_escape_string($conn, $_POST['disease_id']);
    $disease_name = mysqli_real_escape_string($conn, $_POST['diseaseName']);
    $organ_name = mysqli_real_escape_string($conn, $_POST['organ_name']);
    $overview = mysqli_real_escape_string($conn, $_POST['overview']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $causes = mysqli_real_escape_string($conn, $_POST['causes']);

    // Get updated videos
    $videos = json_encode($_POST['videos']);

    // Fetch old images from DB
    $sql_fetch = "SELECT infographics FROM disease_information WHERE disease_id='$disease_id'";
    $result = mysqli_query($conn, $sql_fetch);
    $row = mysqli_fetch_assoc($result);
    $existing_images = json_decode($row['infographics'], true) ?? [];

    // Handle new image uploads
    $folder_name = "../../uploads/Diseases_img/" . preg_replace('/[^A-Za-z0-9]/', '_', $disease_name);
    if (!is_dir($folder_name)) {
        mkdir($folder_name, 0777, true);
    }

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            $target_file = $folder_name . "/" . basename($name);
            $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if (in_array($image_type, ['jpg', 'jpeg', 'png'])) {
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file)) {
                    $existing_images[] = basename($name);
                }
            }
        }
    }

    // Convert updated images to JSON
    $infographics = json_encode($existing_images);

    // Update in the database
    $sql = "UPDATE disease_information 
            SET disease_name='$disease_name', organ_name='$organ_name', 
                overview='$overview', symptoms='$symptoms', causes='$causes', 
                videos='$videos', infographics='$infographics'
            WHERE disease_id='$disease_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
            alert('Disease updated successfully!');
            window.location.href='" . $localhost . "admin_portal.php';
        </script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

mysqli_close($conn);
?>
