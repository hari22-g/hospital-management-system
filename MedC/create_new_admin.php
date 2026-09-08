<?php
require_once 'connection/config.php';

echo "<h2 style='color: blue;'>Admin User Generator</h2>";

// New credentials
$new_email = "admin@hospital.com";
$new_password = "admin123";
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Drop and recreate users table
$drop_table = "DROP TABLE IF EXISTS users";
if ($conn->query($drop_table) === TRUE) {
    echo "<p style='color: green;'>✓ Old users table removed</p>";
}

// Create fresh users table
$create_table = "CREATE TABLE users (
    user_id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    user_type varchar(50) NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (user_id),
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_table) === TRUE) {
    echo "<p style='color: green;'>✓ New users table created</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating table: " . $conn->error . "</p>";
    exit;
}

// Insert new admin user
$insert_admin = "INSERT INTO users (name, email, password, user_type) 
                 VALUES ('Hospital Admin', '$new_email', '$hashed_password', 'admin')";

if ($conn->query($insert_admin) === TRUE) {
    echo "<p style='color: green;'>✓ New Admin Created Successfully!</p>";
    echo "<hr>";
    echo "<h3>🎉 नए Credentials:</h3>";
    echo "<table border='1' cellpadding='10' style='text-align: left;'>";
    echo "<tr><td><strong>Email:</strong></td><td style='color: blue; font-weight: bold;'>$new_email</td></tr>";
    echo "<tr><td><strong>Password:</strong></td><td style='color: blue; font-weight: bold;'>$new_password</td></tr>";
    echo "<tr><td><strong>User Type:</strong></td><td style='color: blue; font-weight: bold;'>Admin</td></tr>";
    echo "</table>";
    echo "<hr>";
    echo "<a href='login.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: blue; color: white; text-decoration: none; border-radius: 5px;'>Login करो →</a>";
} else {
    echo "<p style='color: red;'>✗ Error creating admin: " . $conn->error . "</p>";
}

$conn->close();
?>
