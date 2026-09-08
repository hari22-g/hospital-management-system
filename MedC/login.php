<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (isset($_SESSION['email']) && isset($_SESSION['user_type'])) {
    $redirectPath = $_SESSION['user_type'] === 'admin' ? 'admin_portal.php' : 'homepage.php';
    header("Location: " . $redirectPath);
    exit();
}

require_once 'connection/config.php';
require_once 'include/admin_auth.php';

$alert_msg = '';
$email = '';
$user_type = 'patient';
$allowedUserTypes = ['patient', 'doctor', 'admin'];
$adminSetup = medc_ensure_default_admin($conn);

if (!$adminSetup['success']) {
    $alert_msg = '<div class="alert alert-warning">' . htmlspecialchars($adminSetup['message'], ENT_QUOTES, 'UTF-8') . '</div>';
}

if (isset($_POST['submit'])) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $user_type = trim((string) ($_POST['user_type'] ?? ''));

    if (!in_array($user_type, $allowedUserTypes, true)) {
        $alert_msg = '<div class="alert alert-danger">Please select a valid user type.</div>';
    }
    if ($alert_msg === '') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_type = ? LIMIT 1");

        if (!$stmt) {
            $alert_msg = '<div class="alert alert-danger">Login is temporarily unavailable. Please try again.</div>';
        } else {
            $stmt->bind_param('ss', $email, $user_type);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$row) {
                $alert_msg = '<div class="alert alert-danger">No user found with that email.</div>';
            } elseif (!password_verify($password, $row['password'])) {
                $alert_msg = '<div class="alert alert-danger">Email and password do not match.</div>';
            } else {
                session_regenerate_id(true);

                if ($user_type === 'patient') {
                    $patientStmt = $conn->prepare("SELECT firstname, lastname FROM patient WHERE email = ? LIMIT 1");

                    if ($patientStmt) {
                        $patientStmt->bind_param('s', $email);
                        $patientStmt->execute();
                        $patientResult = $patientStmt->get_result();
                        $patientRow = $patientResult ? $patientResult->fetch_assoc() : null;

                        if ($patientRow) {
                            $_SESSION['f_name'] = $patientRow['firstname'];
                            $_SESSION['l_name'] = $patientRow['lastname'];
                        }

                        $patientStmt->close();
                    }
                } elseif ($user_type === 'doctor') {
                    $doctorStmt = $conn->prepare("SELECT d_id, f_name, l_name FROM doctor WHERE email = ? LIMIT 1");

                    if ($doctorStmt) {
                        $doctorStmt->bind_param('s', $email);
                        $doctorStmt->execute();
                        $doctorResult = $doctorStmt->get_result();
                        $doctorRow = $doctorResult ? $doctorResult->fetch_assoc() : null;

                        if ($doctorRow) {
                            $_SESSION['doctor_id'] = (int) $doctorRow['d_id'];
                            $_SESSION['f_name'] = $doctorRow['f_name'];
                            $_SESSION['l_name'] = $doctorRow['l_name'];
                        }

                        $doctorStmt->close();
                    }
                }

                $_SESSION['email'] = $email;
                $_SESSION['user_type'] = $row['user_type'];
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['logged_in'] = true;

                $redirectPath = $user_type === 'admin' ? 'admin_portal.php' : 'homepage.php';
                header("Location: " . $redirectPath);
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Aarogya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Tiro+Devanagari+Sanskrit:ital@0;1&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --page-bg: #eef7ff;
            --card-bg: rgba(255, 255, 255, 0.92);
            --card-border: rgba(125, 161, 227, 0.22);
            --text-primary: #102247;
            --text-secondary: #4d628d;
            --input-bg: #edf4ff;
            --input-border: rgba(100, 140, 214, 0.2);
            --button-start: #1e7bff;
            --button-end: #0458ff;
            --shadow-soft: 0 28px 80px rgba(58, 96, 160, 0.18);
            --shadow-input: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 10px 30px rgba(113, 146, 204, 0.12);
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: clamp(18px, 4vw, 40px);
            overflow-x: hidden;
            color: var(--text-primary);
            background:
                radial-gradient(circle at 18% 18%, rgba(182, 218, 255, 0.95), transparent 28%),
                radial-gradient(circle at 84% 16%, rgba(228, 244, 255, 0.95), transparent 24%),
                radial-gradient(circle at 10% 88%, rgba(204, 232, 255, 0.78), transparent 26%),
                linear-gradient(180deg, #f8fdff 0%, var(--page-bg) 55%, #edf6ff 100%);
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            position: relative;
            display: grid;
            place-items: center;
        }

        body::before,
        body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(18px);
            z-index: 0;
            animation: floatGlow 10s ease-in-out infinite;
        }

        body::before {
            width: 270px;
            height: 270px;
            top: 8%;
            left: 8%;
            background: rgba(197, 228, 255, 0.9);
        }

        body::after {
            width: 330px;
            height: 330px;
            right: 6%;
            bottom: 4%;
            background: rgba(225, 241, 255, 0.95);
            animation-delay: -4s;
        }

        .auth-page {
            position: relative;
            z-index: 1;
            width: min(100%, 820px);
        }

        .auth-page::before,
        .auth-page::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            z-index: -1;
            pointer-events: none;
        }

        .auth-page::before {
            width: 140px;
            height: 140px;
            right: -34px;
            bottom: 28px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.96) 0%, rgba(219, 237, 255, 0.2) 62%, transparent 70%);
            opacity: 0.9;
        }

        .auth-page::after {
            width: 12px;
            height: 12px;
            top: 18%;
            right: 12%;
            background: linear-gradient(180deg, #ffffff 0%, #d8ebff 100%);
            box-shadow:
                0 0 0 5px rgba(255, 255, 255, 0.15),
                0 0 42px rgba(255, 255, 255, 0.7);
            transform: rotate(45deg);
        }

        .login-card {
            position: relative;
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 34px;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: clamp(28px, 5vw, 48px);
            overflow: hidden;
            animation: cardEnter 0.7s ease both;
        }

        .login-card::before {
            content: "";
            position: absolute;
            inset: 1px;
            border-radius: 33px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0.72) 100%);
            z-index: -1;
        }

        .login-card::after {
            content: "";
            position: absolute;
            inset: auto -18% -38% 38%;
            height: 180px;
            background: radial-gradient(circle, rgba(35, 120, 255, 0.14) 0%, rgba(35, 120, 255, 0) 70%);
            pointer-events: none;
        }

        .brand {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo {
            width: min(100%, 360px);
            height: auto;
            display: block;
            margin: 0 auto 12px;
            filter: drop-shadow(0 12px 26px rgba(184, 45, 104, 0.12));
        }

        .brand-mantra {
            margin: 0;
            font-family: 'Tiro Devanagari Sanskrit', serif;
            font-size: clamp(1.25rem, 2vw, 1.9rem);
            color: #111111;
            line-height: 1.35;
        }

        .login-title {
            margin: 16px 0 0;
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 600;
            color: #0f3d85;
            letter-spacing: -0.04em;
        }

        .auth-form {
            margin: 0 auto;
            width: min(100%, 560px);
        }

        .field {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 12px;
            font-size: 1rem;
            font-weight: 500;
            color: #17284d;
        }

        .form-control,
        .form-select {
            width: 100%;
            min-height: 62px;
            border-radius: 18px;
            border: 1px solid var(--input-border);
            background: linear-gradient(180deg, #f4f8ff 0%, var(--input-bg) 100%);
            color: var(--text-primary);
            padding: 1rem 1.2rem;
            box-shadow: var(--shadow-input);
            font: inherit;
            transition: border-color 0.22s ease, box-shadow 0.22s ease, transform 0.22s ease;
            appearance: none;
        }

        .form-control::placeholder {
            color: #7387af;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: rgba(44, 120, 255, 0.48);
            box-shadow:
                0 0 0 5px rgba(44, 120, 255, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 16px 36px rgba(54, 108, 192, 0.16);
            transform: translateY(-1px);
        }

        .form-select {
            padding-right: 3.4rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 18 18' fill='none'%3E%3Cpath d='M4.5 6.75L9 11.25L13.5 6.75' stroke='%230f3d85' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 20px) center;
            background-size: 18px 18px;
        }

        .form-select option {
            color: #102247;
            background: #ffffff;
        }

        .alert {
            margin-bottom: 20px;
            padding: 14px 18px;
            border-radius: 18px;
            border: 1px solid transparent;
            font-size: 0.96rem;
            line-height: 1.5;
        }

        .alert-danger {
            color: #9f2140;
            background: rgba(255, 234, 240, 0.9);
            border-color: rgba(221, 78, 116, 0.22);
        }

        .alert-warning {
            color: #8b5a00;
            background: rgba(255, 245, 214, 0.95);
            border-color: rgba(210, 162, 26, 0.25);
        }

        .submitbtn {
            width: 100%;
            min-height: 62px;
            border: none;
            border-radius: 18px;
            font: inherit;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            color: #ffffff;
            background: linear-gradient(135deg, var(--button-start) 0%, var(--button-end) 100%);
            box-shadow: 0 22px 30px rgba(4, 88, 255, 0.22);
            cursor: pointer;
            transition: transform 0.22s ease, box-shadow 0.22s ease, filter 0.22s ease;
        }

        .submitbtn:hover,
        .submitbtn:focus-visible {
            transform: translateY(-2px);
            box-shadow: 0 26px 34px rgba(4, 88, 255, 0.28);
            filter: saturate(1.08);
            outline: none;
        }

        .submitbtn:active {
            transform: translateY(0);
        }

        .register-text {
            margin: 24px 0 0;
            color: var(--text-secondary);
            font-size: 1rem;
            text-align: center;
        }

        .quick-link-text {
            margin: 14px 0 0;
            color: var(--text-secondary);
            font-size: 1rem;
            text-align: center;
        }

        .quick-link-text a {
            color: #2b68d2;
            text-decoration: underline;
            text-decoration-thickness: 1.5px;
            text-underline-offset: 2px;
            font-weight: 500;
        }

        .quick-link-text a:hover {
            color: #174cb1;
        }

        .register-text a {
            color: #2b68d2;
            text-decoration: underline;
            text-decoration-thickness: 1.5px;
            text-underline-offset: 2px;
            font-weight: 500;
        }

        .register-text a:hover {
            color: #174cb1;
        }

        @keyframes cardEnter {
            from {
                opacity: 0;
                transform: translateY(26px) scale(0.985);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes floatGlow {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
            }

            50% {
                transform: translate3d(0, -10px, 0);
            }
        }

        @media (max-width: 767.98px) {
            body {
                padding: 16px;
            }

            .login-card {
                border-radius: 28px;
                padding: 24px 18px 22px;
            }

            .brand-logo {
                width: min(100%, 300px);
            }

            .brand-mantra {
                font-size: 1.18rem;
            }

            .login-title {
                font-size: 2.2rem;
            }

            .form-control,
            .form-select,
            .submitbtn {
                min-height: 58px;
                border-radius: 16px;
            }
        }
    </style>
</head>
<body>
    <main class="auth-page">
        <section class="login-card" aria-labelledby="login-title">
            <header class="brand">
                <img src="include/homepage_slider/aarogya_logo.svg" alt="Aarogya" class="brand-logo">
                <p class="brand-mantra" lang="sa">||&#2360;&#2352;&#2381;&#2357;&#2375; &#2360;&#2344;&#2381;&#2340;&#2369; &#2344;&#2367;&#2352;&#2366;&#2350;&#2351;&#2366;&#2307;||</p>
                <h1 class="login-title" id="login-title">Login</h1>
            </header>

            <form method="POST" class="auth-form">
                <?php if ($alert_msg !== '') {
                    echo $alert_msg;
                } ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                        autocomplete="email"
                        placeholder="admin@arogya.com"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <div class="field">
                    <label for="user_type">User Type</label>
                    <select class="form-select" id="user_type" name="user_type" required>
                        <option value="patient" <?php echo $user_type === 'patient' ? 'selected' : ''; ?>>Patient</option>
                        <option value="doctor" <?php echo $user_type === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                        <option value="admin" <?php echo $user_type === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <button type="submit" class="submitbtn" name="submit">Login</button>

                <p class="quick-link-text"><a href="homepage.php">Visit the hospital</a></p>
                <p class="register-text">Not registered yet? <a href="reg.php">Register here</a></p>
            </form>
        </section>
    </main>
</body>
</html>
