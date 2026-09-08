<?php
session_start();
include("../../connection/config.php");

// Check if ID is provided
if (!isset($_GET['community_id'])) {
    echo "<script>alert('Invalid request!'); window.location.href='Community_List.php';</script>";
    exit();
}

$community_id = $_GET['community_id'];

// Fetch community details
$sql = "SELECT * FROM communities WHERE community_id = $community_id";
$result = mysqli_query($conn, $sql);
$community = mysqli_fetch_assoc($result);

// If community doesn't exist
if (!$community) {
    echo "<script>alert('Community not found!'); window.location.href='Community_List.php';</script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Edit Community</h2>
        <form action="updateCommunity.php" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="community_id" value="<?= $community['community_id'] ?>">

            <!-- Community Name -->
            <div class="mb-3">
                <label class="form-label">Community Name</label>
                <input type="text" class="form-control" name="community_name" value="<?= htmlspecialchars($community['name']) ?>" required>
            </div>

            <!-- Community Description -->
            <div class="mb-3">
                <label class="form-label">Community Description</label>
                <textarea class="form-control" name="community_desc" rows="4" required><?= htmlspecialchars($community['description']) ?></textarea>
            </div>

            <!-- Current Image -->
            <div class="mb-3">
                <label class="form-label">Current Image</label>
                <div>
                    <img src="../../uploads/Communities/<?= $community['image'] ?>" width="100" height="70" class="border rounded">
                </div>
            </div>

            <!-- Upload New Image -->
            <div class="mb-3">
                <label class="form-label">Upload New Image (Optional)</label>
                <input type="file" class="form-control" name="community_img" accept="image/*">
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100" name="update">Update Community</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
