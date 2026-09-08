<?php
// Quick fix for admin login
require_once 'connection/config.php';

echo "<h2>Admin Login Fix</h2>";

// Ensure users table exists
$create_users_table = "CREATE TABLE IF NOT EXISTS users (
    user_id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    user_type varchar(50) NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_users_table) === TRUE) {
    echo "<p style='color: green;'>✓ Users table ready</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating users table: " . $conn->error . "</p>";
}

// Detect existing columns in users table to avoid schema mismatches
$columns = [];
$columns_result = $conn->query("SHOW COLUMNS FROM users");
if ($columns_result) {
    while ($col = $columns_result->fetch_assoc()) {
        $columns[$col['Field']] = true;
    }
} else {
    echo "<p style='color: red;'>✗ Error reading users table columns: " . $conn->error . "</p>";
}

$has_name = isset($columns['name']);
$has_fname = isset($columns['fname']);
$has_lname = isset($columns['lname']);

// Create or update admin user
$hashed_password = password_hash('admin@123', PASSWORD_DEFAULT);
$email = 'admin@arogya.com';

// Check if admin exists
$check_admin = $conn->query("SELECT * FROM users WHERE email = '$email'");

if ($check_admin->num_rows > 0) {
    // Update existing admin
    $set_parts = [
        "password = '$hashed_password'",
        "user_type = 'admin'"
    ];
    if ($has_name) {
        $set_parts[] = "name = 'Admin User'";
    }
    if ($has_fname) {
        $set_parts[] = "fname = 'Admin'";
    }
    if ($has_lname) {
        $set_parts[] = "lname = 'User'";
    }

    $update_admin = $conn->query("UPDATE users SET " . implode(', ', $set_parts) . " WHERE email = '$email'");
    if ($update_admin) {
        echo "<p style='color: green;'>✓ Admin user updated successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error updating admin: " . $conn->error . "</p>";
    }
} else {
    // Create new admin
    $fields = ["email", "password", "user_type"];
    $values = ["'$email'", "'$hashed_password'", "'admin'"];
    if ($has_name) {
        $fields[] = "name";
        $values[] = "'Admin User'";
    }
    if ($has_fname) {
        $fields[] = "fname";
        $values[] = "'Admin'";
    }
    if ($has_lname) {
        $fields[] = "lname";
        $values[] = "'User'";
    }

    $create_admin = $conn->query("INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")");
    if ($create_admin) {
        echo "<p style='color: green;'>✓ Admin user created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating admin: " . $conn->error . "</p>";
    }
}

// Verify the admin user
$verify_admin = $conn->query("SELECT * FROM users WHERE email = '$email'");
if ($verify_admin->num_rows > 0) {
    $admin = $verify_admin->fetch_assoc();
    echo "<h3>Admin Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> " . $admin['email'] . "</li>";
    echo "<li><strong>Password:</strong> admin@123</li>";
    echo "<li><strong>User Type:</strong> " . $admin['user_type'] . "</li>";
    echo "</ul>";
    
    // Test password
    if (password_verify('admin@123', $admin['password'])) {
        echo "<p style='color: green;'>✓ Password verification: SUCCESS</p>";
    } else {
        echo "<p style='color: red;'>✗ Password verification: FAILED</p>";
    }
}

$conn->close();

echo "<br><h3>Next Steps:</h3>";
echo "<p>1. Go to: <a href='login.php'>Login Page</a></p>";
echo "<p>2. Select 'Admin' from user type dropdown</p>";
echo "<p>3. Enter email: <strong>admin@arogya.com</strong></p>";
echo "<p>4. Enter password: <strong>admin@123</strong></p>";
echo "<p>5. You should be redirected to the Admin Portal</p>";

echo "<br><p><a href='admin_portal.php' class='btn btn-primary'>Go to Admin Portal</a></p>";
?>