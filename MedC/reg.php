<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <title>Registration Form</title>
    <style>
        body {
            background: linear-gradient(180deg, #eef4ff 0%, #f7faff 45%, #ffffff 100%);
            min-height: 100vh;
        }

        .main-container {
            width: 100%;
            max-width: 1080px;
        }

        .form-container {
            width: 100%;
            padding: 0;
            margin-bottom: 50px;
            border: 1px solid #dce8fb;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 24px 60px rgba(15, 45, 92, 0.12);
        }

        .regbtn {
            min-width: 180px;
            border-radius: 999px;
            padding: 10px 24px;
            font-weight: 700;
        }


        .regbtn:hover {
            background-color: darkblue;
            color: white;
        }

        .page-hero {
            padding: 18px 0 22px;
            text-align: center;
        }

        .page-hero h1 {
            color: #0b3ea8;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .page-hero p {
            color: #5f6f8a;
            margin-bottom: 0;
        }

        .form-head {
            padding: 22px 28px 10px;
            border-bottom: 1px solid #edf2fb;
        }

        .form-head .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #eef5ff;
            color: #0b3ea8;
            font-weight: 700;
            font-size: 0.88rem;
            margin-bottom: 12px;
        }

        .form-head h2 {
            margin-bottom: 4px;
            color: #10213a;
            font-weight: 800;
        }

        .form-head p {
            margin-bottom: 0;
            color: #5f6f8a;
        }

        .form-body {
            padding: 28px;
        }

        label {
            font-size: 15px;
            font-weight: 700;
            color: #183153;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 14px;
            border-color: #d8e5f7;
            padding: 12px 14px;
            box-shadow: none;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
        }

        .register-note {
            color: #6a768e;
            font-size: 0.95rem;
        }
    </style>

</head>

<body>
    <div class="container mt-5 main-container">
        <div class="page-hero">
            <h1>Patient Registration</h1>
            <p>Create your patient profile to book appointments, view prescriptions, and manage your health records.</p>
        </div>

        <div class="form-container">
            <div class="form-head">
                <div class="tag"><i class="fas fa-user-plus"></i> New Patient</div>
                <h2>Join Arogya Hospital</h2>
                <p>Fill in your details below. Doctor registration has been removed from this page.</p>
            </div>
            <div class="form-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="firstname">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="lastname">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="dob">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="gender">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="height">Height</label>
                        <input type="text" class="form-control" id="height" name="height" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="weight">Weight</label>
                        <input type="text" class="form-control" id="weight" name="weight" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="mb-2" for="bloodgroup">Blood Group</label>
                            <select class="form-select" id="bloodgroup" name="bloodgroup" required>
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="mb-2" for="contact_no">Contact Number</label>
                            <input type="tel" class="form-control" id="contact_no" name="contact_no" required>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="state">State</label>
                        <input type="text" class="form-control" id="state" name="state">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="country">Country</label>
                        <input type="text" class="form-control" id="country" name="country">
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="mb-2" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary regbtn" name="submit_p">Register</button>
                </div>
                <div class="text-center mt-3">
                    <p class="register-note mb-0">Already registered? <a href="login.php" class="text-decoration-none">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>

</html>


<?php
// Include the database connection
include('connection/config.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect the data from the form
    if (isset($_POST['submit_p'])) {
        // For Patient Registration
        if (isset($_POST['firstname']) && !empty($_POST['firstname'])) {
            // Collect form data
            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $dob = $_POST['dob'];
            $gender = $_POST['gender'];
            $height = $_POST['height'];
            $weight = $_POST['weight'];
            $blood_group = $_POST['bloodgroup'];
            $contact_no = $_POST['contact_no'];
            $state = $_POST['state'];
            $country = $_POST['country'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Encrypt the password

            // Check if the email already exists in the users table
            $email_check_sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($email_check_sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Email already exists, show alert message
                echo "<script type='text/javascript'>alert('This email is already registered. Please use a different email.');</script>";
            } else {
                // Prepare the INSERT query for the patient table
                $patient_sql = "INSERT INTO patient (firstname, lastname, dob, gender, height, weight, bloodgroup, contact_no, state, country, email, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($patient_sql);
                $stmt->bind_param("sssssiisssss", $firstname, $lastname, $dob, $gender, $height, $weight, $blood_group, $contact_no, $state, $country, $email, $password);
                if ($stmt->execute()) {
                    // Prepare the INSERT query for the users table
                    $user_sql = "INSERT INTO users (email, password, fname, lname, user_type) VALUES (?, ?, ?, ?, 'patient')";
                    $stmt = $conn->prepare($user_sql);
                    $stmt->bind_param("ssss", $email, $password, $firstname, $lastname);
                    if ($stmt->execute()) {
                        // After successful registration, redirect to login page
                        echo "<script type='text/javascript'>window.location.href = 'login.php';</script>";
                    } else {
                        echo "Error: " . $stmt->error;
                    }
                } else {
                    echo "Error: " . $stmt->error;
                }
            }
        }
    }
}
?>
