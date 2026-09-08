<?php
// Test database connection and admin login
require_once 'connection/config.php';

echo "<h2>Database Connection Test</h2>";

// Test connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
}

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows > 0) {
    echo "<p style='color: green;'>✓ Users table exists</p>";
    
    // Check for admin user
    $admin_check = $conn->query("SELECT * FROM users WHERE email = 'admin@arogya.com'");
    if ($admin_check->num_rows > 0) {
        $admin = $admin_check->fetch_assoc();
        echo "<p style='color: green;'>✓ Admin user found:</p>";
        echo "<ul>";
        echo "<li>Email: " . $admin['email'] . "</li>";
        echo "<li>User Type: " . $admin['user_type'] . "</li>";
        echo "<li>Name: " . $admin['name'] . "</li>";
        echo "</ul>";
        
        // Test password verification
        if (password_verify('admin@123', $admin['password'])) {
            echo "<p style='color: green;'>✓ Password verification successful</p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification failed</p>";
            // Update password
            $new_hash = password_hash('admin@123', PASSWORD_DEFAULT);
            $update = $conn->query("UPDATE users SET password = '$new_hash' WHERE email = 'admin@arogya.com'");
            if ($update) {
                echo "<p style='color: green;'>✓ Password updated successfully</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠ Admin user not found. Creating...</p>";
        $hashed_password = password_hash('admin@123', PASSWORD_DEFAULT);
        $create_admin = $conn->query("INSERT INTO users (name, email, password, user_type) VALUES ('Admin User', 'admin@arogya.com', '$hashed_password', 'admin')");
        if ($create_admin) {
            echo "<p style='color: green;'>✓ Admin user created successfully</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creating admin: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>✗ Users table does not exist</p>";
}

// Check other required tables
$required_tables = ['hospital_info', 'doctor', 'patient', 'appointment'];
foreach ($required_tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        echo "<p style='color: green;'>✓ $table table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ $table table missing</p>";
    }
}

$conn->close();

echo "<br><h3>Login Instructions:</h3>";
echo "<p>Go to: <a href='login.php'>Login Page</a></p>";
echo "<p>Select 'Admin' as user type</p>";
echo "<p>Email: admin@arogya.com</p>";
echo "<p>Password: admin@123</p>";
?>