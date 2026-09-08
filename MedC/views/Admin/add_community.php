<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Add Community</h2>
        <form action="submit_community.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            
            <!-- Community Name -->
            <div class="mb-3">
                <label for="community_name" class="form-label">Community Name</label>
                <input type="text" class="form-control" id="community_name" name="community_name" required>
                <div class="invalid-feedback">Please enter a community name.</div>
            </div>

            <!-- Community Description -->
            <div class="mb-3">
                <label for="community_desc" class="form-label">Community Description</label>
                <textarea class="form-control" id="community_desc" name="community_desc" rows="4" required></textarea>
                <div class="invalid-feedback">Please enter a description.</div>
            </div>

            <!-- Upload Image -->
            <div class="mb-3">
                <label for="community_img" class="form-label">Community Image</label>
                <input type="file" class="form-control" id="community_img" name="community_img" accept="image/*" required>
                <div class="invalid-feedback">Please upload an image.</div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-success w-100" name="submit">Add Community</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Form Validation Script -->
<script>
    function validateForm() {
        let name = document.getElementById("community_name");
        let desc = document.getElementById("community_desc");
        let img = document.getElementById("community_img");

        let valid = true;

        if (name.value.trim() === "") {
            name.classList.add("is-invalid");
            valid = false;
        } else {
            name.classList.remove("is-invalid");
        }

        if (desc.value.trim() === "") {
            desc.classList.add("is-invalid");
            valid = false;
        } else {
            desc.classList.remove("is-invalid");
        }

        if (img.files.length === 0) {
            img.classList.add("is-invalid");
            valid = false;
        } else {
            img.classList.remove("is-invalid");
        }

        return valid;
    }
</script>

</body>
</html>
