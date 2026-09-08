<?php
header('Content-Type: text/plain');

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'medc';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    echo "Connected successfully\n";

    // Check if the hospital_info table exists
    $result = $conn->query("SHOW TABLES LIKE 'hospital_info'");
    if ($result->num_rows == 0) {
        echo "Creating hospital_info table...\n";
        
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
            echo "Table hospital_info created successfully.\n";
            
            // Insert default data
            $insertSql = "INSERT INTO hospital_info (hospital_name, phone_number, address, google_maps_link, emergency_helpline, hospital_timings, opd_timings, specialties) VALUES ('MedC Hospital', '+91-9876543210', '123 Healthcare Avenue, Medical City, India', 'https://maps.google.com/?q=hospital', '+91-9876543211', '24x7', 'Mon-Sat: 8AM-8PM', 'Cardiology,Orthopedics,Pediatrics,Dermatology,Ophthalmology')";
            
            if ($conn->query($insertSql) === TRUE) {
                echo "Default hospital info inserted successfully.\n";
            } else {
                echo "Error inserting default data: " . $conn->error . "\n";
            }
        } else {
            echo "Error creating table: " . $conn->error . "\n";
        }
    } else {
        echo "Table hospital_info already exists.\n";
    }

    // Check if the doctor_schedule table exists
    $result2 = $conn->query("SHOW TABLES LIKE 'doctor_schedule'");
    if ($result2->num_rows == 0) {
        echo "Creating doctor_schedule table...\n";
        
        $sql2 = "CREATE TABLE doctor_schedule (
          schedule_id int(11) NOT NULL AUTO_INCREMENT,
          doctor_id int(11) NOT NULL,
          day_of_week varchar(10) NOT NULL,
          available_from time NOT NULL,
          available_to time NOT NULL,
          is_available tinyint(1) DEFAULT 1,
          created_at timestamp NOT NULL DEFAULT current_timestamp(),
          updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (schedule_id),
          FOREIGN KEY (doctor_id) REFERENCES doctor(d_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        if ($conn->query($sql2) === TRUE) {
            echo "Table doctor_schedule created successfully.\n";
        } else {
            echo "Error creating table: " . $conn->error . "\n";
        }
    } else {
        echo "Table doctor_schedule already exists.\n";
    }

    $conn->close();
    echo "Database setup completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>