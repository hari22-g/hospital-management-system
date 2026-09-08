<?php
require_once 'connection/config.php';

// Drop users table if exists to recreate with correct schema
$conn->query("DROP TABLE IF EXISTS users");

// Create required tables if they don't exist
$tables = [
    "CREATE TABLE IF NOT EXISTS hospital_info (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    "CREATE TABLE IF NOT EXISTS appointment (
        id int(11) NOT NULL AUTO_INCREMENT,
        pid int(11) NOT NULL,
        did int(11) NOT NULL,
        appointment_date date NOT NULL,
        appointment_time time NOT NULL,
        status varchar(50) DEFAULT 'Pending',
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    "CREATE TABLE IF NOT EXISTS patient (
        p_id int(11) NOT NULL AUTO_INCREMENT,
        p_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(20) NOT NULL,
        address text,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (p_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    "CREATE TABLE IF NOT EXISTS doctor (
        d_id int(11) NOT NULL AUTO_INCREMENT,
        f_name varchar(100) NOT NULL,
        l_name varchar(100) NOT NULL,
        email varchar(255) NOT NULL,
        specialization varchar(100) NOT NULL,
        experience int(11) NOT NULL,
        approval varchar(20) DEFAULT 'approved',
        pass varchar(255) NOT NULL,
        PRIMARY KEY (d_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    "CREATE TABLE IF NOT EXISTS users (
        user_id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        password varchar(255) NOT NULL,
        user_type varchar(50) NOT NULL,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
];

echo "Creating database tables...\n";

foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

// Insert default hospital info
$check_hospital = "SELECT * FROM hospital_info LIMIT 1";
$result = $conn->query($check_hospital);

if ($result->num_rows == 0) {
    $insert_hospital = "INSERT INTO hospital_info (hospital_name, phone_number, address, google_maps_link, emergency_helpline, hospital_timings, opd_timings, specialties) VALUES ('AROGYA', '+91-9876543210', '123 Healthcare Avenue, Medical City, India', 'https://maps.google.com/?q=hospital', '+91-9876543211', '24x7', 'Mon-Sat: 8AM-8PM', 'Cardiology,Orthopedics,Pediatrics,Dermatology,Ophthalmology')";
    if ($conn->query($insert_hospital) === TRUE) {
        echo "Default hospital info inserted.\n";
    }
}

// Create admin user if not exists
$check_admin = "SELECT * FROM users WHERE email = 'admin@arogya.com'";
$result = $conn->query($check_admin);

if ($result->num_rows == 0) {
    $hashed_password = password_hash('admin@123', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO users (name, email, password, user_type) VALUES ('Admin User', 'admin@arogya.com', '$hashed_password', 'admin')";
    if ($conn->query($insert_admin) === TRUE) {
        echo "Admin user created successfully!\n";
        echo "Email: admin@arogya.com\n";
        echo "Password: admin@123\n";
    }
}

$conn->close();
echo "Database setup completed!\n";
?>