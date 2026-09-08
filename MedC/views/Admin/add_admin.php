<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2 class="text-center mb-4">Add Admin</h2>
            <form action="" method="POST" onsubmit="return validateForm()">
                
                <!-- CSRF Protection -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="mb-3">
                    <label for="admin_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>

                <div class="mb-3">
                    <label for="pass" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="pass" name="pass" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                            <i id="eye-icon" class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Password must be at least 6 characters long and contain one uppercase, one lowercase, and one number.</div>
                </div>

                <button type="submit" class="btn btn-success w-100" name="submit">Submit</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript for Validation & Show Password -->
    <script>
        function togglePassword() {
            const passwordField = document.getElementById("pass");
            const eyeIcon = document.getElementById("eye-icon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.remove("bi-eye");
                eyeIcon.classList.add("bi-eye-slash");
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove("bi-eye-slash");
                eyeIcon.classList.add("bi-eye");
            }
        }

        function validateForm() {
            const email = document.getElementById("admin_email");
            const password = document.getElementById("pass");

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{6,}$/;

            let valid = true;

            if (!emailPattern.test(email.value)) {
                email.classList.add("is-invalid");
                valid = false;
            } else {
                email.classList.remove("is-invalid");
            }

            if (!passwordPattern.test(password.value)) {
                password.classList.add("is-invalid");
                valid = false;
            } else {
                password.classList.remove("is-invalid");
            }

            return valid;
        }
    </script>

</body>

</html>

<?php
session_start();
include("../../connection/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

    $admin_email = mysqli_real_escape_string($conn, $_POST['admin_email']);
    $password = mysqli_real_escape_string($conn, $_POST['pass']);

    // Check if email already exists
    $check_email = "SELECT * FROM users WHERE email = '$admin_email'";
    $result = mysqli_query($conn, $check_email);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Error: Email already registered!'); window.location.href='add_admin.php';</script>";
        exit();
    }

    // Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert into the database
    $sql = "INSERT INTO users (email, password, user_type) VALUES ('$admin_email', '$hashed_password', 'admin')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Admin added successfully!'); window.location.href='../../login.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

mysqli_close($conn);
?>
