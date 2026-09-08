<?php
// setup_database.php - Execute SQL to create all PMS tables

// Direct connection (CLI mode doesn't have $_SERVER)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medc";

$mysqli = new mysqli($servername, $username, $password, $dbname);
if ($mysqli->connect_error) {
    die("Connection Failed: " . $mysqli->connect_error);
}

$sqlFile = 'sql/setup_complete_pms.sql';

if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile");
}

$sqlContent = file_get_contents($sqlFile);

// Split by statement separately (MySQL doesn't handle multiple statements in one query by default)
$statements = array_filter(array_map('trim', preg_split('/;[\r\n]+/', $sqlContent)));

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    if ($mysqli->query($statement)) {
        $successCount++;
    } else {
        $errorCount++;
        $errors[] = "Error: " . $mysqli->error . "\n Statement: " . substr($statement, 0, 100) . "...";
    }
}

echo "Database Setup Results:\n";
echo "======================\n";
echo "✓ Successful: $successCount statements\n";
echo "✗ Errors: $errorCount statements\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\nSetup Complete!\n";
?>
