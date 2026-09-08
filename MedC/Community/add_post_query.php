<?php
include('../connection/config.php');  // Include your database connection

// Get data from AJAX
$community_id = $_POST["community_id"];  // You can change this as per your needs
$user_id = $_POST["user_id"];
$fname = $_POST["fname"];
$lname = $_POST["lname"];
$content = $_POST["content"];
$media_url = '';

// File upload validation
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];  // Add more allowed types if necessary
$max_size = 10 * 1024 * 1024;  // Max file size of 10 MB

// Check if the file is being uploaded
if (isset($_FILES['media_url']) && $_FILES['media_url']['error'] == 0) {
    // Check file size
    if ($_FILES['media_url']['size'] > $max_size) {
        echo "File size exceeds the limit of 10MB.";
        exit;  // Exit if file is too large
    }

    // Check file type
    if (!in_array($_FILES['media_url']['type'], $allowed_types)) {
        echo "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
        exit;  // Exit if file type is not allowed
    }

    // If file is valid, handle the upload
    $folder_name = "../uploads/Posts/" . preg_replace('/[^A-Za-z0-9]/', '_', $user_id) . "/";
    if (!is_dir($folder_name)) {
        mkdir($folder_name, 0777, true);  // Create folder if it doesn't exist
    }
    $target_file = $folder_name . basename($_FILES['media_url']['name']);

    if (move_uploaded_file($_FILES['media_url']['tmp_name'], $target_file)) {
        $media_url = preg_replace('/[^A-Za-z0-9]/', '_', $user_id) . "/" . basename($_FILES['media_url']['name']);
    } else {
        echo "Error uploading file.";
        exit;  // Exit if file upload fails
    }
}

// Get the current timestamp for the post
$created_at = date('Y-m-d H:i:s');

// Insert the post into the database
$sql = "INSERT INTO posts (user_id, community_id, fname, lname, content, media_url, created_at) 
        VALUES ('$user_id', '$community_id', '$fname', '$lname', '$content', '$media_url', '$created_at')";

if (mysqli_query($conn, $sql)) {
    echo 1;  // Success
} else {
    echo 0;  // Failure
}

// Close the database connection
mysqli_close($conn);
?>
