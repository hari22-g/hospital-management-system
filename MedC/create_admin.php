<?php
require_once 'connection/config.php';

// Check if hospital_info table exists
$result = $conn->query("SHOW TABLES LIKE 'hospital_info'");
if ($result->num_rows == 0) {
    echo 'Creating hospital_info table...' . PHP_EOL;
    $sql = "CREATE TABLE hospital_info (
      id int(11) NOT NULL AUTO_INCREMENT,
      hospital_name varchar(255) NOT NULL,
      phone_number varchar(20) NOT NULL,
      address text NOT NULL,
      google_maps_link varchar(500) DEFAULT NULL,
      emergency_helpline varchar(20) DEFAULT NULL,
      hospital_timings varchar(100) DEFAULT NULL,
      opd_timings varchar(100) DEFAULT NULL,
      specialties text,
      facilities text,
      accreditations text,
      insurance_accepted text,
      created_at timestamp NOT NULL DEFAULT current_timestamp(),
      updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($sql) === TRUE) {
        echo 'hospital_info table created successfully.' . PHP_EOL;
        // Insert default data
        $insertSql = "INSERT INTO hospital_info (hospital_name, phone_number, address, google_maps_link, emergency_helpline, hospital_timings, opd_timings, specialties) VALUES ('AROGYA', '+91-9876543210', '123 Healthcare Avenue, Medical City, India', 'https://maps.google.com/?q=hospital', '+91-9876543211', '24x7', 'Mon-Sat: 8AM-8PM', 'Cardiology,Orthopedics,Pediatrics,Dermatology,Ophthalmology')";
        $conn->query($insertSql);
        echo 'Default hospital info inserted.' . PHP_EOL;
    }
}

// Check if users table exists
$result2 = $conn->query("SHOW TABLES LIKE 'users'");
if ($result2->num_rows == 0) {
    echo 'users table does not exist. Please check your database setup.' . PHP_EOL;
} else {
    // Create admin user
    $hashed_password = password_hash('admin@123', PASSWORD_DEFAULT);
    
    // Check if admin already exists
    $check_admin = "SELECT * FROM users WHERE email = 'admin@arogya.com'";
    $result = $conn->query($check_admin);
    
    if ($result->num_rows == 0) {
        $insert_admin = "INSERT INTO users (name, email, password, user_type) VALUES ('Admin User', 'admin@arogya.com', '$hashed_password', 'admin')";
        if ($conn->query($insert_admin) === TRUE) {
            echo 'Admin user created successfully!' . PHP_EOL;
            echo 'Email: admin@arogya.com' . PHP_EOL;
            echo 'Password: admin@123' . PHP_EOL;
        } else {
            echo 'Error creating admin: ' . $conn->error . PHP_EOL;
        }
    } else {
        echo 'Admin user already exists.' . PHP_EOL;
        // Update existing admin password
        $update_admin = "UPDATE users SET password = '$hashed_password' WHERE email = 'admin@arogya.com'";
        if ($conn->query($update_admin) === TRUE) {
            echo 'Admin password updated successfully!' . PHP_EOL;
            echo 'Email: admin@arogya.com' . PHP_EOL;
            echo 'Password: admin@123' . PHP_EOL;
        }
    }
}

$conn->close();
?>