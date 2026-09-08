<?php
session_start();

require_once 'connection/config.php';
require_once 'include/admin_auth.php';

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_portal.php');
    exit();
}

$defaultAdmin = medc_get_default_admin_credentials();
$emailValue = $defaultAdmin['email'];
$passwordValue = $defaultAdmin['password'];
$alertClass = '';
$alertMessage = '';

$adminSetup = medc_ensure_default_admin($conn);

if (!$adminSetup['success']) {
    $alertClass = 'danger';
    $alertMessage = $adminSetup['message'];
} elseif (!empty($adminSetup['created'])) {
    $alertClass = 'success';
    $alertMessage = $adminSetup['message'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['repair_admin'])) {
        $adminSetup = medc_ensure_default_admin($conn, true);
        $alertClass = $adminSetup['success'] ? 'success' : 'danger';
        $alertMessage = $adminSetup['message'];
        $emailValue = $defaultAdmin['email'];
        $passwordValue = $defaultAdmin['password'];
    } elseif (isset($_POST['admin_login'])) {
        $emailValue = trim((string) ($_POST['email'] ?? ''));
        $passwordValue = trim((string) ($_POST['password'] ?? ''));

        if ($emailValue === '' || $passwordValue === '') {
            $alertClass = 'danger';
            $alertMessage = 'Email and password are required.';
        } else {
            $loginResult = medc_attempt_admin_login($conn, $emailValue, $passwordValue);

            if ($loginResult['success']) {
                session_regenerate_id(true);
                $_SESSION['email'] = $loginResult['user']['email'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['user_id'] = $loginResult['user']['user_id'];
                $_SESSION['logged_in'] = true;

                header('Location: admin_portal.php');
                exit();
            }

            $alertClass = 'danger';
            $alertMessage = $loginResult['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <title>Admin Login</title>
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top, rgba(25, 118, 210, 0.18), transparent 35%),
                linear-gradient(135deg, #eef6ff 0%, #dfeeff 45%, #f8fbff 100%);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-card {
            width: 100%;
            max-width: 480px;
            padding: 32px;
            border: 0;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 24px 60px rgba(15, 76, 129, 0.18);
        }

        .eyebrow {
            margin-bottom: 8px;
            color: #1f6fb2;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin-bottom: 8px;
            color: #16324f;
            font-size: 30px;
            font-weight: 700;
        }

        .subtitle {
            margin-bottom: 24px;
            color: #58718d;
            font-size: 15px;
        }

        .form-label {
            color: #26435f;
            font-weight: 600;
        }

        .form-control {
            min-height: 48px;
            border-radius: 12px;
            border-color: #cbdceb;
        }

        .form-control:focus {
            border-color: #3a8dde;
            box-shadow: 0 0 0 0.2rem rgba(58, 141, 222, 0.16);
        }

        .btn-primary,
        .btn-outline-secondary {
            min-height: 48px;
            border-radius: 12px;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1b7bd8 0%, #155ea7 100%);
            border: 0;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #176dbf 0%, #124f8d 100%);
        }

        .admin-help {
            margin-top: 22px;
            padding: 16px 18px;
            border-radius: 14px;
            background: #f3f8fe;
            color: #31516e;
        }

        .admin-help strong {
            color: #16324f;
        }

        .footer-link {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }

        .footer-link a {
            color: #1b7bd8;
            text-decoration: none;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-card">
        <div class="eyebrow">Admin Access</div>
        <h1>Admin Panel Login</h1>
        <p class="subtitle">Use the dedicated admin login below. If the default admin account is missing, repair it with one click.</p>

        <?php if ($alertMessage !== ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                <?php echo htmlspecialchars($alertMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label" for="email">Admin Email</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    value="<?php echo htmlspecialchars($passwordValue, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <div class="d-grid gap-2">
                <button type="submit" name="admin_login" class="btn btn-primary">Login to Admin Portal</button>
                <button type="submit" name="repair_admin" class="btn btn-outline-secondary">Create or Repair Default Admin</button>
            </div>
        </form>

        <div class="admin-help">
            <div><strong>Default admin email:</strong> <?php echo htmlspecialchars($defaultAdmin['email'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Default admin password:</strong> <?php echo htmlspecialchars($defaultAdmin['password'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="mt-2">If login fails because the account was changed or deleted, click "Create or Repair Default Admin" and then log in again.</div>
        </div>

        <div class="footer-link">
            <a href="login.php">Back to main login</a>
        </div>
    </div>
</body>
</html>
