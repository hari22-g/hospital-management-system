<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <title>Registration Forms</title>
    <style>
        body {
            background-color: #EFF3EA;
        }

        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100%;

        }

        .form-container {

            padding: 20px;
            margin-bottom: 50px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .regbtn {
            width: 100%;
        }


        .regbtn:hover {
            background-color: darkblue;
            color: white;
        }

    h3{
        text-align: center;

    }
        label {
            font-size: 17px;
            font-weight: 600;
        }
    </style>

</head>

<body>

        <div class="main-container"><div id="patientForm" class="form-container">

<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <div class="row"><h3>Add Family Member</h3></div><hr>
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
                <label class="mb-2" for="relation">Relation</label>
                <input type="tel" class="form-control" id="relation" name="relation" required>
            </div>
        </div>
    </div>


    <div class="text-center">
        <button type="submit" class="btn " name="submit" style="width:100%; background-color:darkgreen; color: white; font-weight:600; font-size:25px">Register</button>
    </div>
</form>
</div>
</div>
        
        
</body>

</html>


<?php
if (isset($_POST["submit"])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "healthcare";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_no = $_POST['contact_no'];
    $email = $_POST['email'];
    $password = $_POST['password']; // Hash the password before storing

    $state = $_POST['state'];
    $country = $_POST['country'];

    // Insert data into the database
    $sql = "INSERT INTO Patient (firstname, lastname, dob, gender, contact_no, email, password, location, street, house_no, state, country) 
                    VALUES ('$firstname', '$lastname', '$dob', '$gender', '$contact_no', '$email', '$password', '$location', '$street', '$house_no', '$state', '$country')";

    if ($conn->query($sql) === TRUE) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
