<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
session_start();
include("../../connection/config.php");
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
} else {
    $user_id = $_SESSION["user_id"];
    $email = $_SESSION["email"];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $reportType = $_POST['report_type'];
        $referredBy = $_POST['referred_by'];
        $reportDate = $_POST['report_date'];

        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $fileSize = $_FILES['file']['size'];
            $fileType = $_FILES['file']['type'];

            // Create a directory for the user if it doesn't exist
            $uploadDir = '../../uploads/Patient_Reports/' . $user_id . '_' . $email . '/'; // Unique folder for each user
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Create the folder with appropriate permissions
            }
            $temp_path = $user_id . '_' . $email . '/' . $fileName;
            // Generate a new file path in the user's folder
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($fileTmpPath, $filePath)) {
                // Prepare SQL statement to insert data into the database
                $sql = "INSERT INTO reports (report_type, referred_by, report_date, file_path, user_id) 
                    VALUES ('$reportType', '$referredBy', '$reportDate', '$temp_path', '$user_id')";

                if ($conn->query($sql) === TRUE) {
                    echo "<script type='text/javascript'>alert('Report is added successfully.');</script>";
                    header('Location: patient_dashboard.php');
                    exit();
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            } else {
                echo "Error uploading the file.";
            }
        } else {
            echo "No file uploaded or there was an error during upload.";
        }
    }
}

// Close the connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <title>Add Form</title>
    <style>
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #EFF3EA;
        }

        .submitbtn {
            width: 100%;
        }

        .form-container {
            max-width: 400px;
            margin: auto auto;
            padding: 20px;
            border: 1px solid #fafafa;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: darkblue;
            border: none;
        }

        .btn-primary:hover {
            background-color: #002060;
        }

        label {
            font-size: 17px;
            font-weight: 600;
        }

        .custom-select,
        .ref {
            display: block;
            padding: 5px;
            border-radius: 10px;
            border: 1px solid rgb(69, 68, 68);
        }

        .text-center {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center">Add Report</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-row align-items-center">
                    <div class="col-auto my-2 d-grid">
                        <label class="mr-sm-2" for="inlineFormCustomSelect">Type</label>
                        <select class="custom-select mr-sm-2 mt-2 ref" id="inlineFormCustomSelect" name="report_type">
                            <option selected>Choose the report you wish to add</option>
                            <option value="Blood Report">Blood Report</option>
                            <option value="Sugar Report">Sugar Report</option>
                            <option value="Cholesterol Report">Cholesterol Report</option>
                        </select>
                    </div>
                    <div class="col mt-4 d-grid">
                        <label class="mb-2">Referred By</label>
                        <input type="text" class="ref" name="referred_by">
                    </div>
                    <div class="col-6 my-3">
                        <label class="mb-2" for="dob">Date of Report</label>
                        <input type="date" class="ref" id="dob" name="report_date" required>
                    </div>
                    <div class="custom-file mt-4">
                        <label class="custom-file-label mb-2" for="validatedCustomFile">Upload Document</label>
                        <input type="file" class="custom-file-input" id="validatedCustomFile" name="file" required>
                        <div class="invalid-feedback">Example invalid custom file feedback</div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary submitbtn">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>

</html>