<?php
session_start();

include("connection/config.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location:login.php');
    exit();
}
// Check if the logged-in user is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    die("Error: Access denied. Only doctors can access this page.");
}

function resolveDoctorId(mysqli $conn): int
{
    if (!empty($_SESSION['doctor_id'])) {
        return (int) $_SESSION['doctor_id'];
    }

    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email === '') {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    $stmt = $conn->prepare('SELECT d_id FROM doctor WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return (int) ($_SESSION['user_id'] ?? 0);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($doctorId);
    $resolved = $stmt->fetch() ? (int) $doctorId : (int) ($_SESSION['user_id'] ?? 0);
    $stmt->close();

    return $resolved;
}

$doctor_id = resolveDoctorId($conn); // Doctor ID from session or lookup
$doctor_name = $_SESSION['f_name'] ?? 'Doctor'; // Fallback in case it's not set

$sql_total_app = "SELECT * FROM appointment WHERE did = '$doctor_id'";
$result_total_app = mysqli_query($conn, $sql_total_app);
$count_total_app = mysqli_num_rows(mysqli_query($conn, $sql_total_app));

$sql_pen_app = "SELECT * FROM appointment WHERE status = 'Pending' AND did = '$doctor_id'";
$result_pen_app = mysqli_query($conn, $sql_pen_app);
$count_pen_app = mysqli_num_rows(mysqli_query($conn, $sql_pen_app));

$sql_approved_doc = "SELECT * FROM appointment WHERE status = 'Confirmed' AND did = '$doctor_id'";
$result_approved_doc = mysqli_query($conn, $sql_approved_doc);
$count_approved_doc = mysqli_num_rows(mysqli_query($conn, $sql_approved_doc));

$sql_today_app = "SELECT * FROM appointment WHERE (DATE(appointment_date) = CURDATE() AND did = '$doctor_id') AND status = 'Confirmed';";
$result_today_app = mysqli_query($conn, $sql_today_app);
$count_today_app = mysqli_num_rows(mysqli_query($conn, $sql_today_app));

$sql_tommorow_app = "SELECT * FROM appointment WHERE (DATE(appointment_date) = (CURDATE() + 1) AND did = '$doctor_id') AND status = 'Confirmed';";
$result_tommorow_app = mysqli_query($conn, $sql_tommorow_app);
$count_tommorow_app = mysqli_num_rows(mysqli_query($conn, $sql_tommorow_app));




// Fetch doctor's appointments
$stmt = $conn->prepare("SELECT a.appointment_id, p.firstname, p.lastname, a.user_id, a.appointment_date, 
                                a.appointment_time, a.status, a.jitsi_meeting_link 
                        FROM appointment a 
                        JOIN patient p ON a.pid = p.pid 
                        WHERE a.did = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }

        .cname {
            color: darkblue;
            font-size: 2.5rem;
            font-weight: bolder;
        }

        .sidebar {
            width: 280px;
            height: 100vh;
            /* Default height */
            background-color: white;
            color: black;
            box-shadow: 1px 1px 1px rgba(0, 0, 0, 0.1);
        }

        .sidebar li {
            cursor: pointer;
            padding: 2px 10px;
        }

        .nav-link {
            color: #6c757d;
            transition: #6c757d 0.3s ease;
        }


        .sidebar .nav-link:hover {
            color: #1d3557;
        }

        header {
            background-color: darkblue;
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: 80px;
            /* Navbar height */
        }

        header h2 {
            margin: 0;
        }

        .sidebar-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80px;
            /* Adjust height of the logo area */
        }

        .nav_icon {
            font-size: 20px;
            margin-right: 10px;
        }

        .selected-item {
            color: darkblue;
            font-weight: bold;
        }

        .custom-box {
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            color: maroon;
            font-size: 35px;
            font-weight: bold;
            border: 2px solid black;
        }

        .action {
            width: 150px;
        }

        #patient-details {
            font-size: 16px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        #patient-details p {
            margin-bottom: 5px;
            color: #333;
        }

        #patient-details strong {
            color: darkblue;
        }

        .modal-body {
            max-height: 400px;
            overflow-y: auto;
            /* Scroll if content exceeds height */
        }

        .patient-details-container {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;

        }

        .section-title {
            color: darkblue;
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .medications-list,
        .reports-list,
        .biomarkers-list {
            list-style: none;
            padding-left: 0;
        }

        .medications-list li,
        .reports-list li,
        .biomarkers-list li {
            padding: 8px;
            background-color: white;
            border-radius: 5px;
            margin-bottom: 5px;
            /* border-left: 4px solid darkblue; */
        }

        .report-link {
            color: darkblue;
            text-decoration: none;
            font-weight: bold;
        }

        .report-link:hover {
            text-decoration: underline;
        }

        .no-data {
            color: red;
            font-style: italic;
        }

        .error-message {
            color: red;
            font-weight: bold;
        }

        /* Style Google Translate Dropdown */
        #google_translate_element {
            display: flex;
            align-items: center;
            height: 50px;
        }

        /* Style the Google Translate frame */
        .goog-te-gadget {
            color: white !important;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        /* Hide the "powered by Google" text */
        .goog-te-gadget span {
            display: none;
        }

        /* Style the dropdown button */
        .goog-te-combo {
            background-color: transparent;
            color: white !important;
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
            background-color: white !important;
        }

        .goog-te-gadget span,
        .goog-te-gadget img {
            display: none !important;
        }

        /* Ensure the select box remains visible */
        .goog-te-combo {
            color: white !important;
            font-size: 14px !important;
            border: 2px solid white;
            border-radius: 5px;
            padding: 5px;
        }

        /* Hide the footer */
        .goog-te-footer {
            display: none !important;
        }

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


    <div class="d-flex">
        <!-- Sidebar -->
        <div class="d-flex flex-column sidebar">
            <div class="sidebar-logo">
                <a href="<?php echo $localhost ?>homepage.php" class="d-flex align-items-center text-decoration-none">
                    <h1 class="cname mt-2">MedC</h1>
                </a>
            </div>
            <ul class="nav nav-pills flex-column mb-auto mt-4">
                <li class="nav-item">
                    <div class="nav-link home" onclick="window.location.href = '<?php echo $localhost ?>homepage.php'">
                        <i class="fa-solid fa-home fa-lg nav_icon"></i> Home
                    </div>
                </li>
                <li class="nav-item">
                    <div class="nav-link selected-item dashboard">
                        <i class="fa-solid fa-chart-simple fa-lg nav_icon"></i> Dashboard
                    </div>
                </li>
                <li class="nav-item">
                    <div class="nav-link mng_appointments">
                        <i class="fa-solid fa-receipt fa-lg nav_icon"></i> Manage Appointments
                    </div>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $localhost ?>doctor_health_dashboard.php" class="d-flex align-items-center text-decoration-none">
                        <div class="nav-link text-secondary">
                            <i class="fa-solid fa-heart-pulse fa-lg nav_icon"></i> Patient Health Hub
                        </div>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo $localhost ?>logout.php" class="d-flex align-items-center text-decoration-none">
                        <div class="nav-link text-secondary">
                            <i class="fa fa-sign-out nav_icon" aria-hidden="true"></i> Log Out
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="flex-grow-1">
            <header class="d-flex justify-content-between align-items-center py-3 px-4" style="background-color: darkblue; color: white;">
                <h2><?php echo $doctor_name; ?>'s Dashboard</h2>
                <div class="d-flex align-items-center">
                    <div id="google_translate_element" style="margin-right: 20px;"></div>
                    <a class="btn btn-outline-light btn-sm" href="<?php echo $localhost ?>homepage.php">
                        <i class="fa-solid fa-arrow-left me-1"></i>Back to Home
                    </a>
                </div>
            </header>
            <div class="main_content">
                <div class="container mt-4" style="height:100vh;">
                    <!-- First Row with 3 Divs -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="custom-box box1 bg-light"><span class="text-dark">Total appointments</span><br><?php echo $count_total_app ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-box box2 bg-light"><span class="text-dark">Total pending appointments</span><br><?php echo $count_pen_app ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-box box3 bg-light"><span class="text-dark">Total approved appointments</span><br><?php echo $count_approved_doc ?></div>
                        </div>
                    </div>

                    <!-- Second Row with 2 Divs -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-box box4 bg-light"><span class="text-dark">Today's appointments</span><br><?php echo $count_today_app ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-box box5 bg-light"><span class="text-dark">Tommorow's appointments</span><br><?php echo $count_tommorow_app ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        const initialHash = window.location.hash || '';
        if (initialHash === '#appointments') {
            loadAppointments();
            $(".nav-link").removeClass("selected-item");
            $(".mng_appointments").addClass("selected-item");
        }

        $(".dashboard").click(function() {
            loadDashboard();
            // Remove 'selected-item' class from all nav links and add to the clicked link
            $(".nav-link").removeClass("selected-item");
            $(this).addClass("selected-item");
        });

        // Function to Load Dashboard
        function loadDashboard() {
            $.ajax({
                url: "loadDoctorDashboard.php",
                type: "POST",
                success: function(response) {
                    $(".main_content").html(response);
                },
                error: function() {
                    $(".main_content").html("<p class='text-danger'>Error fetching data.</p>");
                }
            });
        }

        $(".mng_appointments").click(function() {
            loadAppointments();
            // Remove 'selected-item' class from all nav links and add to the clicked link
            $(".nav-link").removeClass("selected-item");
            $(this).addClass("selected-item");
        });

        // Function to Load Diseases
        function loadAppointments() {
            $.ajax({
                url: "loadAppointments.php",
                type: "POST",
                success: function(response) {
                    $(".main_content").html(response);
                },
                error: function() {
                    $(".main_content").html("<p class='text-danger'>Error fetching data.</p>");
                }
            });
        }

        $(document).on("click", ".view-more", function() {
            let pid = $(this).data("pid");

            // Send an AJAX request to fetch_patient_details.php
            $.ajax({
                url: "fetch_patient_details.php",
                type: "POST",
                data: {
                    pid: pid
                },
                success: function(response) {
                    $("#patient-details").html(response);
                },
                error: function() {
                    console.error("Error fetching details.");
                    $("#patient-details").html("<p>Error loading patient details.</p>");
                }
            });
        });
    });
</script>
<script>
    // document.addEventListener("DOMContentLoaded", function () {
    //     document.querySelectorAll(".view-more").forEach(button => {
    //         button.addEventListener("click", function () {
    //             let pid = this.getAttribute("data-pid");

    //             // Send an AJAX request to fetch_patient_details.php
    //             fetch("fetch_patient_details.php", {
    //                 method: "POST",
    //                 headers: { "Content-Type": "application/x-www-form-urlencoded" },
    //                 body: "pid=" + encodeURIComponent(pid)

    //             })
    //                 .then(response => response.text())
    //                 .then(data => {
    //                     document.getElementById("patient-details").innerHTML = data;
    //                 })
    //                 .catch(error => {
    //                     console.error("Error fetching details:", error);
    //                     document.getElementById("patient-details").innerHTML = "<p>Error loading patient details.</p>";
    //                 });
    //         });
    //     });
    // });



    // $(document).ready(function () {
    //     $(document).on("click", ".patient_list", function (e) {
    //         e.preventDefault();
    //         $.ajax({
    //             url: "load_patient_data.php",
    //             type: "POST",
    //             success: function (data) {
    //                 $(".main_content").html(data);
    //             }
    //         });
    //     });

    // })
    // document.addEventListener("DOMContentLoaded", function () {
    //     setInterval(() => {
    //         let currentTimestamp = Math.floor(Date.now() / 1000);
    //         document.querySelectorAll(".meeting-container").forEach(container => {
    //             let appointmentTimestamp = parseInt(container.getAttribute("data-appointment"));
    //             if (currentTimestamp >= appointmentTimestamp) {
    //                 container.innerHTML = <a href="${container.getAttribute("data-link")}" target="_blank" class="btn btn-primary">Join Meeting</a>;
    //             }
    //         });
    //     }, 60000); // Check every minute
    // });
</script>

</html>
