<?php
include("../../connection/config.php"); // Database connection

if (isset($_POST['submit'])) {
    $disease_name = mysqli_real_escape_string($conn, $_POST['diseaseName']);
    $organ_name = mysqli_real_escape_string($conn, $_POST['organ_name']);
    $overview = mysqli_real_escape_string($conn, $_POST['overview']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $causes = mysqli_real_escape_string($conn, $_POST['causes']);
    $video1 = mysqli_real_escape_string($conn, $_POST['video1']);
    $video2 = mysqli_real_escape_string($conn, $_POST['video2']);
    $video3 = mysqli_real_escape_string($conn, $_POST['video3']);

    // Combine videos into a JSON format
    $videos = json_encode([$video1, $video2, $video3]);

    // Generate unique folder name based on disease name and timestamp
    $folder_name = "../../uploads/Diseases_img/" . $disease_name;
    if (!is_dir($folder_name)) {
        mkdir($folder_name, 0777, true); // Create folder
    }

    // Handling image uploads
    $image_paths = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            $target_file = $folder_name . "/" . basename($name);
            $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if (in_array($image_type, ['jpg', 'jpeg', 'png'])) {
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file)) {
                    $image_paths[] =  basename($name);
                }
            }
        }
    }

    // Convert image paths to JSON for database storage
    $infographics = json_encode($image_paths);

    // Insert data into the database
    $sql = "INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name) 
            VALUES ('$disease_name', '$overview', '$symptoms', '$causes', '$videos', '$infographics', '$organ_name')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
    alert('Disease information added successfully!');
    window.location.href='" . $localhost . "admin_portal.php';
</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

mysqli_close($conn);
