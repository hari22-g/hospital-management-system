<?php
session_start();
include("../../connection/config.php");

if (!isset($_GET['disease_id'])) {
    echo "<script>alert('Invalid request!'); window.location.href='" . $localhost . "admin_portal.php';</script>";
    exit();
}

$disease_id = $_GET['disease_id'];
$sql = "SELECT * FROM disease_information WHERE disease_id = $disease_id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo "<script>alert('Disease not found!'); window.location.href='" . $localhost . "admin_portal.php';</script>";
    exit();
}

// Decode JSON for videos and infographics
$d_name = $row["disease_name"];
$videos = json_decode($row['videos'], true);
$infographics = json_decode($row['infographics'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Disease</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Update Disease</h2>
        <form action="update_disease.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="disease_id" value="<?= $row['disease_id'] ?>">

            <div class="mb-3">
                <label for="diseaseName" class="form-label">Disease Name</label>
                <input type="text" class="form-control" id="diseaseName" name="diseaseName" value="<?= $row['disease_name'] ?>" required>
            </div>

            <div class="mb-3">
                <label for="organ_name" class="form-label">Related Organ</label>
                <input type="text" class="form-control" id="organ_name" name="organ_name" value="<?= $row['organ_name'] ?>" required>
            </div>

            <div class="mb-3">
                <label for="overview" class="form-label">Overview</label>
                <textarea class="form-control" id="overview" name="overview" rows="3" required><?= $row['overview'] ?></textarea>
            </div>

            <div class="mb-3">
                <label for="symptoms" class="form-label">Symptoms</label>
                <textarea class="form-control" id="symptoms" name="symptoms" rows="3" required><?= $row['symptoms'] ?></textarea>
            </div>

            <div class="mb-3">
                <label for="causes" class="form-label">Causes</label>
                <textarea class="form-control" id="causes" name="causes" rows="3" required><?= $row['causes'] ?></textarea>
            </div>

            <!-- Video URLs -->
            <div class="mb-3">
                <label class="form-label">Videos</label>
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <input type="text" class="form-control mb-2" name="videos[]" value="<?= $videos[$i] ?? '' ?>">
                <?php endfor; ?>
            </div>

            <!-- Display Existing Images -->
            <div class="mb-3">
                <label class="form-label">Current Infographics</label>
                <div class="d-flex flex-wrap">
                    <?php foreach ($infographics as $image): ?>
                        <img src="<?php echo $localhost?>uploads/Diseases_img/<?php echo $d_name?>/<?= $image ?>" alt="Infographic" class="img-thumbnail me-2" width="100">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Upload New Images -->
            <div class="mb-3">
                <label for="images" class="form-label">Upload New Infographics (Optional)</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
            </div>

            <button type="submit" class="btn btn-success w-100" name="update">Update</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
