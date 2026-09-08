<?php
require_once 'connection/config.php';

// Update hospital name in the database
$sql = "UPDATE hospital_info SET hospital_name = 'AROGYA' WHERE id = 1";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo 'Hospital name updated to AROGYA successfully.' . PHP_EOL;
} else {
    echo 'Error updating hospital name: ' . mysqli_error($conn) . PHP_EOL;
}

// Create an admin user if it doesn't exist
$admin_check = "SELECT * FROM users WHERE user_type = 'admin'";
$result = mysqli_query($conn, $admin_check);

if (mysqli_num_rows($result) == 0) {
    // No admin found, create one
    $hashed_password = password_hash('password', PASSWORD_DEFAULT);
    $admin_sql = "INSERT INTO users (name, email, password, user_type) VALUES ('Admin', 'admin@arogya.com', '$hashed_password', 'admin')";
    $result2 = mysqli_query($conn, $admin_sql);
    
    if ($result2) {
        echo 'Admin user created successfully.' . PHP_EOL;
        echo 'Admin Login Credentials:' . PHP_EOL;
        echo 'Email: admin@arogya.com' . PHP_EOL;
        echo 'Password: password' . PHP_EOL;
    } else {
        echo 'Error creating admin user: ' . mysqli_error($conn) . PHP_EOL;
    }
} else {
    echo 'Admin user already exists.' . PHP_EOL;
}

// Also check doctor table for admin
$doctor_check = "SELECT * FROM doctor WHERE email = 'admin@arogya.com'";
$result = mysqli_query($conn, $doctor_check);

if (mysqli_num_rows($result) == 0) {
    $admin_doc_sql = "INSERT INTO doctor (f_name, l_name, dob, gender, doctor_id, experience, specialization, consultation_fees, contact_no, address, city, state, country, email, pass, approval) VALUES ('Admin', 'User', '1980-01-01', 'male', 9999, 10, 'Admin', '0', 9999999999, 'Hospital Address', 'City', 'State', 'Country', 'admin@arogya.com', '$hashed_password', 'approved')";
    $result3 = mysqli_query($conn, $admin_doc_sql);
    
    if ($result3) {
        echo 'Admin doctor account created successfully.' . PHP_EOL;
    }
}

$conn->close();
echo 'Setup completed.' . PHP_EOL;
?>