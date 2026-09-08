<?php
$localhost = "http://" . $_SERVER['SERVER_NAME'] . "/MedC/MedC/";
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medc";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection Failed" . $conn->connect_error);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include hospital information
include_once('hospital_info.php');
$hospital_info = getHospitalInfo();

// Check if user is logged in
$email = $_SESSION["email"] ?? null;
$user_type = $_SESSION["user_type"] ?? null;
$approval = null;

// Only query approval if a doctor is logged in
if ($email && $user_type === "doctor") {
    $approval_query = "SELECT approval FROM doctor WHERE email = '$email'";
    $approval_result = mysqli_query($conn, $approval_query);

    if ($approval_result && mysqli_num_rows($approval_result) > 0) {
        $approval_row = mysqli_fetch_assoc($approval_result);
        $approval = $approval_row["approval"] ?? null;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link active me-2 rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Responsive Navbar</title>

    <style>
        /* Custom CSS for Navbar */
        .navbar {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: rgb(0, 0, 139);
            padding: 0;
            height: 65px;

        }

        .navbar-brand {
            font-weight: bold;
            font-size: 2.15rem;
        }

        nav ul {
            list-style: none;
            padding: 0;
            display: flex;
            justify-content: flex-end;
            margin: 0;
        }

        nav ul li {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 65px;
            margin-left: 15px;
        }

        nav ul li a {
            text-decoration: none;
            color: white;
            font-weight: bold;
        }

        /* Default styles for nav-link */
        .nav-link {
            color: white;
            font-weight: 500;
            transition: color 0.15s ease, border-bottom 0.15s ease;
            padding: 20px;
            white-space: nowrap;
        }

        /* Border-bottom effect on hover */
        .nav-link:hover {
            color: #f8d7da;
            border-bottom: 4px solid white;
        }

        /* Optional: Border for the active nav-link */
        .nav-link.selected_nav {
            border-bottom: 4px solid white;
        }

        .navbar-toggler {
            border-color: #f8f9fa;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml;charset=UTF8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23fff' viewBox='0 0 30 30'%3E%3Cpath stroke='rgba(255, 255, 255, 1)' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
        }

        .btn-danger {
            font-size: 1rem;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 88px;
        }

        .btn-danger:hover {
            background-color: #a71d2a;
        }

        .dropdown-menu {
            border-radius: 18px;
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.16);
            border: 1px solid rgba(13, 110, 253, 0.14);
            overflow: hidden;
            padding: 0;
            min-width: 320px;
            background: #ffffff;
        }

        .dropdown-item:hover {
            background-color: #e6f7ff;
        }

        .contact-dropdown {
            padding: 0;
        }

        .contact-dropdown-header {
            padding: 16px 18px 14px;
            background: linear-gradient(135deg, #0d6efd 0%, #0b3ea8 100%);
            color: #ffffff;
        }

        .contact-dropdown-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .contact-dropdown-subtitle {
            font-size: 0.84rem;
            opacity: 0.9;
        }

        .contact-dropdown-body {
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .contact-mini-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .contact-pill-link,
        .contact-info-link {
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 14px;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .contact-pill-link {
            padding: 12px 14px;
            font-weight: 700;
            justify-content: center;
            color: #0b3ea8;
            background: #eef5ff;
            border: 1px solid #d8e6ff;
        }

        .contact-pill-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(11, 62, 168, 0.12);
            background: #e4efff;
        }

        .contact-pill-link.emergency {
            color: #b42318;
            background: #fff1f1;
            border-color: #ffd5d5;
        }

        .contact-pill-link.emergency:hover {
            background: #ffe8e8;
            box-shadow: 0 10px 20px rgba(180, 35, 24, 0.12);
        }

        .contact-info-link {
            padding: 10px 12px;
            color: #183153;
            background: #f8fbff;
            border: 1px solid #e3ecfb;
            width: 100%;
        }

        .contact-info-link:hover {
            background: #eef5ff;
            transform: translateY(-1px);
        }

        .contact-info-link i,
        .contact-pill-link i {
            width: 18px;
            text-align: center;
        }

        .contact-info-text {
            display: grid;
            line-height: 1.2;
        }

        .contact-info-label {
            font-size: 0.76rem;
            color: #66748f;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .contact-info-value {
            font-size: 0.94rem;
            color: #10213a;
            font-weight: 600;
            word-break: break-word;
        }

        .contact-note {
            padding: 0 14px 14px;
            color: #66748f;
            font-size: 0.83rem;
            text-align: center;
        }

        @media (max-width: 576px) {
            .dropdown-menu {
                min-width: 280px;
            }

            .contact-mini-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Style Google Translate Dropdown */
        #google_translate_element {
            display: flex;
            align-items: center;
            height: 65px;
        }

        /* Style the Google Translate frame */
        .goog-te-gadget {
            color: white !important;
            font-size: 16px;
            display: flex;
            align-items: center;
            margin-top: 3px;
        }

        /* Hide the "powered by Google" text */
        .goog-te-gadget span {
            display: none;
        }

        /* Style the dropdown button */
        .goog-te-combo {
            background-color: transparent;
            color: white !important;
            width: 160px;
            border: 2px solid white;
            border-radius: 5px;
            padding: 5px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Change dropdown color on hover */
        .goog-te-combo:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Style the translate toolbar */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        .goog-te-combo option {
            color: black !important;
            /* Ensure dropdown options are visible */
            background-color: white !important;
            /* Background for dropdown */
        }

        .goog-te-gadget span,
        .goog-te-gadget img {
            display: none !important;
        }

        /* Hide the Google Translate toolbar */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        /* Style Google Translate Dropdown */
        #google_translate_element {
            display: flex;
            align-items: center;
            height: 65px;
        }

        /* Hide all text elements in the Google Translate gadget except the dropdown */
        .goog-te-gadget {
            color: transparent !important;
            font-size: 0 !important;
        }

        /* Ensure the select box remains visible */
        .goog-te-combo {
            color: white !important;
            font-size: 16px !important;
            border: 2px solid white;
            border-radius: 5px;
            padding: 5px;
        }

        /* Completely hide the Google Translate footer */
        .goog-te-footer {
            display: none !important;
        }

        /* Hide any remaining logos or links */
        .goog-logo-link,
        .goog-te-gadget img {
            display: none !important;
            visibility: hidden !important;
        }
    </style>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en'
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href = "<?php echo $localhost ?>homepage.php">AROGYA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Check if the current page matches and add the active class -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'health_info.php') ? 'selected_nav' : ''; ?>" href="<?php echo $localhost; ?>health_info.php">Health Topics</a>
                    </li>

                    <?php
                    // Check if user_type exists before accessing it
                    $user_type = $_SESSION['user_type'] ?? null;
                    if ($user_type === 'patient') { ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'patient_dashboard.php') ? 'selected_nav' : ''; ?>"
                                href="<?php echo $localhost; ?>views/Patient/patient_dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'doctors_directory.php') ? 'selected_nav' : ''; ?>"
                                href="<?php echo $localhost; ?>doctors_directory.php">Book Appointment</a>
                        </li>
                    <?php } elseif ($user_type === 'doctor') { ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'doctor_health_dashboard.php') ? 'selected_nav' : ''; ?>"
                                href="<?php echo $localhost; ?>doctor_health_dashboard.php">Dashboard</a>
                        </li>
                    <?php } ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'community.html') ? 'selected_nav' : ''; ?>" href="<?php echo $localhost; ?>Community/Community_list.php">Community</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'about_us.php') ? 'selected_nav' : ''; ?>" href="<?php echo $localhost; ?>About_Us.php">About Us</a>
                    </li>
                    
                    <!-- Contact Info in Navigation -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-phone-alt me-1"></i>Contact Us
                        </a>
                        <div class="dropdown-menu contact-dropdown">
                            <div class="contact-dropdown-header">
                                <div class="contact-dropdown-title">
                                    <i class="fas fa-hospital"></i>
                                    <span>Arogya</span>
                                </div>
                                <div class="contact-dropdown-subtitle">Fast contact and visit details</div>
                            </div>
                            <div class="contact-dropdown-body">
                                <div class="contact-mini-grid">
                                    <a class="contact-pill-link" href="tel:<?php echo htmlspecialchars($hospital_info['phone_number']); ?>">
                                        <i class="fas fa-phone"></i>
                                        <span>Call Now</span>
                                    </a>
                                    <a class="contact-pill-link emergency" href="tel:<?php echo htmlspecialchars($hospital_info['emergency_helpline']); ?>">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        <span>Emergency</span>
                                    </a>
                                </div>

                                <a class="contact-info-link" href="<?php echo htmlspecialchars($hospital_info['google_maps_link']); ?>" target="_blank">
                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                    <span class="contact-info-text">
                                        <span class="contact-info-label">Address</span>
                                        <span class="contact-info-value"><?php echo htmlspecialchars($hospital_info['address']); ?></span>
                                    </span>
                                </a>

                                <a class="contact-info-link" href="tel:<?php echo htmlspecialchars($hospital_info['phone_number']); ?>">
                                    <i class="fas fa-clock text-primary"></i>
                                    <span class="contact-info-text">
                                        <span class="contact-info-label">Hospital Timing</span>
                                        <span class="contact-info-value"><?php echo htmlspecialchars($hospital_info['hospital_timings']); ?></span>
                                    </span>
                                </a>

                                <a class="contact-info-link" href="tel:<?php echo htmlspecialchars($hospital_info['phone_number']); ?>">
                                    <i class="fas fa-calendar-day text-success"></i>
                                    <span class="contact-info-text">
                                        <span class="contact-info-label">OPD Timing</span>
                                        <span class="contact-info-value"><?php echo htmlspecialchars($hospital_info['opd_timings']); ?></span>
                                    </span>
                                </a>
                            </div>
                            <div class="contact-note">Tap any item to call, open maps, or view hospital timings.</div>
                        </div>
                    </li>

                    <li class="nav-item">
                        <div id="google_translate_element"></div>
                    </li>
                    
                    <!-- Search Bar -->
                    <li class="nav-item">
                        <form class="d-flex" action="search_results.php" method="GET" style="margin-right: 10px;">
                            <input class="form-control me-2" type="search" placeholder="Search..." aria-label="Search" name="query" id="searchInput">
                            <button class="btn btn-outline-success" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </li>
                    <?php
                    // Check if user is logged in
                    if (isset($_SESSION['email'])) {
                        // If logged in, show 'Hello, username' and 'Log Out'
                        echo '
                                <li class="nav-item">
                                    <a class="btn btn-danger me-2" href="' . $localhost . 'logout.php">Log Out</a>
                                </li>';
                    } else {
                        // If not logged in, show Log In button
                        echo '<li class="nav-item">
                                    <a class="btn btn-danger me-2" href="' . $localhost . 'login.php">Log In</a>
                                </li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>

</body>

</html>
