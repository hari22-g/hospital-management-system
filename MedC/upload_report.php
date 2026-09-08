<?php
session_start();
include("connection/config.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if file was uploaded without errors
    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $file_name = $_FILES['report_file']['name'];
        $file_tmp = $_FILES['report_file']['tmp_name'];
        $file_size = $_FILES['report_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file type
        if (!in_array($file_ext, $allowed_types)) {
            echo json_encode(["success" => false, "message" => "Invalid file type. Only PDF, JPG, JPEG, PNG, DOC, DOCX files are allowed."]);
            exit();
        }

        // Validate file size (10MB max)
        if ($file_size > 10 * 1024 * 1024) {
            echo json_encode(["success" => false, "message" => "File size exceeds 10MB limit."]);
            exit();
        }

        // Create upload directory structure
        $upload_dir = "uploads/Patient_Reports/" . $user_id . "_" . $_SESSION['email'] . "/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $unique_filename = uniqid() . '_' . basename($file_name);
        $upload_path = $upload_dir . $unique_filename;

        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // Insert report record into database
            $report_type = $_POST['report_type'];
            $referred_by = $_POST['referred_by'];
            $report_date = $_POST['report_date'];

            $stmt = $conn->prepare("INSERT INTO reports (user_id, report_type, referred_by, report_date, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $report_type, $referred_by, $report_date, $upload_path);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Report uploaded successfully"]);
            } else {
                // Delete file if DB insert fails
                unlink($upload_path);
                echo json_encode(["success" => false, "message" => "Error saving report to database"]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Error uploading file"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "No file uploaded or upload error"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
?>