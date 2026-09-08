<?php

if (!defined('MEDC_DEFAULT_ADMIN_EMAIL')) {
    define('MEDC_DEFAULT_ADMIN_EMAIL', 'admin@arogya.com');
}

if (!defined('MEDC_DEFAULT_ADMIN_PASSWORD')) {
    define('MEDC_DEFAULT_ADMIN_PASSWORD', 'admin@123');
}

function medc_get_default_admin_credentials(): array
{
    return [
        'email' => MEDC_DEFAULT_ADMIN_EMAIL,
        'password' => MEDC_DEFAULT_ADMIN_PASSWORD,
    ];
}

function medc_users_table_exists(mysqli $conn): bool
{
    $result = $conn->query("SHOW TABLES LIKE 'users'");

    return $result !== false && $result->num_rows > 0;
}

function medc_get_users_columns(mysqli $conn): array
{
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM users");

    if ($result === false) {
        return $columns;
    }

    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }

    return $columns;
}

function medc_sql_literal(mysqli $conn, $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return "'" . $conn->real_escape_string((string) $value) . "'";
}

function medc_build_default_admin_values(array $columns, string $hashedPassword): array
{
    $values = [];

    if (isset($columns['email'])) {
        $values['email'] = MEDC_DEFAULT_ADMIN_EMAIL;
    }

    if (isset($columns['password'])) {
        $values['password'] = $hashedPassword;
    }

    if (isset($columns['user_type'])) {
        $values['user_type'] = 'admin';
    }

    if (isset($columns['name'])) {
        $values['name'] = 'Admin User';
    }

    if (isset($columns['fname'])) {
        $values['fname'] = 'Admin';
    }

    if (isset($columns['lname'])) {
        $values['lname'] = 'User';
    }

    if (isset($columns['role'])) {
        $values['role'] = 'admin';
    }

    if (isset($columns['department'])) {
        $values['department'] = 'Administration';
    }

    if (isset($columns['is_active'])) {
        $values['is_active'] = 1;
    }

    return $values;
}

function medc_ensure_default_admin(mysqli $conn, bool $forceResetPassword = false): array
{
    if (!medc_users_table_exists($conn)) {
        return [
            'success' => false,
            'created' => false,
            'message' => 'Users table not found. Run the database setup first.',
        ];
    }

    $columns = medc_get_users_columns($conn);

    foreach (['email', 'password', 'user_type'] as $requiredColumn) {
        if (!isset($columns[$requiredColumn])) {
            return [
                'success' => false,
                'created' => false,
                'message' => "Users table is missing the '{$requiredColumn}' column.",
            ];
        }
    }

    $email = $conn->real_escape_string(MEDC_DEFAULT_ADMIN_EMAIL);
    $hashedPassword = password_hash(MEDC_DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
    $existingUser = $conn->query("SELECT user_id FROM users WHERE email = '{$email}' LIMIT 1");

    if ($existingUser === false) {
        return [
            'success' => false,
            'created' => false,
            'message' => 'Could not check the default admin account.',
        ];
    }

    if ($existingUser->num_rows > 0) {
        $updates = [];

        if ($forceResetPassword) {
            $updates['password'] = $hashedPassword;
        }

        $updates['user_type'] = 'admin';

        if (isset($columns['name'])) {
            $updates['name'] = 'Admin User';
        }

        if (isset($columns['fname'])) {
            $updates['fname'] = 'Admin';
        }

        if (isset($columns['lname'])) {
            $updates['lname'] = 'User';
        }

        if (isset($columns['role'])) {
            $updates['role'] = 'admin';
        }

        if (isset($columns['department'])) {
            $updates['department'] = 'Administration';
        }

        if (isset($columns['is_active'])) {
            $updates['is_active'] = 1;
        }

        $setParts = [];

        foreach ($updates as $field => $value) {
            $setParts[] = "`{$field}` = " . medc_sql_literal($conn, $value);
        }

        if (!empty($setParts)) {
            $updateSql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE email = '{$email}'";

            if ($conn->query($updateSql) !== true) {
                return [
                    'success' => false,
                    'created' => false,
                    'message' => 'Default admin exists, but the account could not be repaired.',
                ];
            }
        }

        return [
            'success' => true,
            'created' => false,
            'message' => $forceResetPassword
                ? 'Default admin account repaired and password reset.'
                : 'Default admin account is ready.',
        ];
    }

    $values = medc_build_default_admin_values($columns, $hashedPassword);

    $fieldSql = implode(', ', array_map(static function ($field) {
        return "`{$field}`";
    }, array_keys($values)));
    $valueSql = implode(', ', array_map(static function ($value) use ($conn) {
        return medc_sql_literal($conn, $value);
    }, array_values($values)));

    $insertSql = "INSERT INTO users ({$fieldSql}) VALUES ({$valueSql})";

    if ($conn->query($insertSql) !== true) {
        return [
            'success' => false,
            'created' => false,
            'message' => 'Default admin account could not be created.',
        ];
    }

    return [
        'success' => true,
        'created' => true,
        'message' => 'Default admin account created successfully.',
    ];
}

function medc_attempt_admin_login(mysqli $conn, string $email, string $password): array
{
    if (!medc_users_table_exists($conn)) {
        return [
            'success' => false,
            'message' => 'Users table not found. Run the database setup first.',
        ];
    }

    $columns = medc_get_users_columns($conn);
    $selectColumns = ['user_id', 'email', 'password', 'user_type'];

    if (isset($columns['is_active'])) {
        $selectColumns[] = 'is_active';
    }

    $sql = "SELECT " . implode(', ', $selectColumns) . " FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'success' => false,
            'message' => 'Admin login is temporarily unavailable.',
        ];
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user || ($user['user_type'] ?? '') !== 'admin') {
        return [
            'success' => false,
            'message' => 'Admin account not found for this email.',
        ];
    }

    if (isset($user['is_active']) && (int) $user['is_active'] !== 1) {
        return [
            'success' => false,
            'message' => 'This admin account is inactive.',
        ];
    }

    if (!password_verify($password, (string) $user['password'])) {
        return [
            'success' => false,
            'message' => 'Invalid admin email or password.',
        ];
    }

    return [
        'success' => true,
        'message' => 'Admin login successful.',
        'user' => $user,
    ];
}

