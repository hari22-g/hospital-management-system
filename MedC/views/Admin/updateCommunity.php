<?php
session_start();
include("../../connection/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $community_id = $_POST['community_id'];
    $community_name = mysqli_real_escape_string($conn, $_POST['community_name']);
    $community_desc = mysqli_real_escape_string($conn, $_POST['community_desc']);
    $image_name = "";

    // Check if a new image is uploaded
    if (!empty($_FILES["community_img"]["name"])) {
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

                // Get old image to delete
                $sql_old = "SELECT image FROM communities WHERE community_id = $community_id";
                $result_old = mysqli_query($conn, $sql_old);
                $row_old = mysqli_fetch_assoc($result_old);

                if ($row_old && !empty($row_old['image'])) {
                    $old_image_path = "../../uploads/Communities/" . $row_old['image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path); // Delete old image
                    }
                }
            } else {
                echo "<script>alert('Error uploading image!'); window.location.href='updateCommunityForm.php?community_id=$community_id';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Invalid image format! Only JPG, JPEG, and PNG allowed.'); window.location.href='updateCommunityForm.php?community_id=$community_id';</script>";
            exit();
        }
    }

    // Prepare SQL query
    if ($image_name) {
        $sql = "UPDATE communities SET name='$community_name', description='$community_desc', image='$image_name' WHERE community_id=$community_id";
    } else {
        $sql = "UPDATE communities SET name='$community_name', description='$community_desc' WHERE community_id=$community_id";
    }

    // Execute update query
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
?>
