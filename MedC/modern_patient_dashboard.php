<?php
session_start();
include("connection/config.php");

// Redirect if not logged in or not a patient
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (strtolower(trim((string) ($_SESSION['user_type'] ?? ''))) !== 'patient') {
    header('Location: include/dashboard_link.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

// Fetch patient data
$patientData = null;
if (!empty($email)) {
    $stmt = $conn->prepare("SELECT * FROM patient WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $patientData = $result->fetch_assoc();
    $stmt->close();
}

// Fetch upcoming appointments
$appointmentsQuery = $conn->prepare(
    "SELECT a.*, d.f_name, d.l_name, d.specialization 
     FROM appointment a 
     JOIN doctor d ON a.did = d.d_id 
     WHERE a.pid = ? AND a.appointment_date >= CURDATE() 
     ORDER BY a.appointment_date ASC LIMIT 5"
);
$appointmentsQuery->bind_param("i", $user_id);
$appointmentsQuery->execute();
$appointments = $appointmentsQuery->get_result();

// Fetch health stats
$biomarkerQuery = $conn->prepare(
    "SELECT * FROM biomarker WHERE user_id = ? ORDER BY id DESC LIMIT 1"
);
$biomarkerQuery->bind_param("i", $user_id);
$biomarkerQuery->execute();
$biomarkerResult = $biomarkerQuery->get_result();
$healthStats = $biomarkerResult->fetch_assoc();

// Fetch medical records
$recordsQuery = $conn->prepare(
    "SELECT * FROM patient WHERE pid = ? LIMIT 1"
);
$recordsQuery->bind_param("i", $user_id);
$recordsQuery->execute();
$medicalRecord = $recordsQuery->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - AROGYA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #2f74de;
            --secondary-blue: #1da8d6;
            --light-blue: #eff6ff;
            --accent-green: #10b981;
            --light-gray: #f3f4f6;
            --border-gray: #e5e7eb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
        }

        body {
            background-color: var(--light-gray);
            color: var(--text-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 20px;
        }

        .sidebar-logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin: 15px 0;
        }

        .sidebar-nav a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.2);
            padding-left: 20px;
        }

        .sidebar-nav i {
            margin-right: 12px;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
        }

        /* Header */
        .top-header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .patient-profile {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
        }

        .header-info h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .header-info p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .icon-btn {
            background: var(--light-gray);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .icon-btn:hover {
            background: var(--primary-blue);
            color: white;
        }

        /* Cards */
        .card-modern {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .card-modern:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary-blue);
            font-size: 22px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
            height: 50px;
            width: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }

        .stat-icon.bp {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }

        .stat-icon.glucose {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .stat-icon.heart {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* Appointments List */
        .appointment-item {
            background: var(--light-blue);
            border-left: 4px solid var(--primary-blue);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .appointment-item:hover {
            background: var(--primary-blue);
            color: white;
        }

        .appointment-item:hover .appointment-time {
            color: white;
        }

        .appointment-info h5 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 600;
        }

        .appointment-info p {
            margin: 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        .appointment-item:hover .appointment-info p {
            color: rgba(255, 255, 255, 0.8);
        }

        .appointment-time {
            background: white;
            padding: 10px 15px;
            border-radius: 6px;
            font-weight: 600;
            color: var(--primary-blue);
            white-space: nowrap;
        }

        /* Action Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary-action {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary-action:hover {
            background: #1e5cc9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(47, 116, 222, 0.3);
        }

        .btn-secondary-action {
            background: var(--accent-green);
            color: white;
        }

        .btn-secondary-action:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .action-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .action-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .action-icon {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--primary-blue);
        }

        .action-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Search Bar */
        .search-bar {
            position: relative;
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-gray);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(47, 116, 222, 0.1);
        }

        /* Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                padding: 10px;
            }

            .sidebar-nav a span {
                display: none;
            }

            .main-content {
                margin-left: 80px;
                padding: 15px;
            }

            .top-header {
                flex-direction: column;
                gap: 15px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .appointment-item {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        /* Dark Mode Toggle */
        .dark-mode {
            background-color: #1a202c;
            color: #e5e7eb;
        }

        .dark-mode .card-modern,
        .dark-mode .stat-card,
        .dark-mode .appointment-item,
        .dark-mode .action-box,
        .dark-mode .top-header {
            background: #2d3748;
            color: #e5e7eb;
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .prescription-item {
            background: #f0fdf4;
            border-left: 4px solid var(--accent-green);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .prescription-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .prescription-details {
            font-size: 13px;
            color: var(--text-muted);
        }

        .btn-download {
            background: var(--accent-green);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🏥 AROGYA</div>
            <small>Patient Portal</small>
        </div>
        <ul class="sidebar-nav">
            <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="#"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
            <li><a href="#"><i class="fas fa-file-medical"></i> <span>Medical Records</span></a></li>
            <li><a href="#"><i class="fas fa-pills"></i> <span>Prescriptions</span></a></li>
            <li><a href="#"><i class="fas fa-receipt"></i> <span>Billing</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            <li style="margin-top: 30px;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="header-left">
                <div class="patient-profile">
                    <?php echo strtoupper(substr($patientData['firstname'] ?? 'P', 0, 1)); ?>
                </div>
                <div class="header-info">
                    <h3>Welcome back, <?php echo htmlspecialchars($patientData['firstname'] ?? 'Patient'); ?></h3>
                    <p><?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
            <div class="header-actions">
                <button class="icon-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <button class="icon-btn" title="Emergency Contact" onclick="alert('Emergency: 108 or nearby hospital')">
                    <i class="fas fa-exclamation-circle"></i>
                </button>
                <button class="icon-btn" onclick="toggleDarkMode()" title="Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" class="search-input" placeholder="🔍 Search appointments, prescriptions, records...">
        </div>

        <!-- Health Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bp">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($healthStats['systolic_blood_pressure'] ?? '120') ?></div>
                <div class="stat-label">Blood Pressure (mmHg)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon glucose">
                    <i class="fas fa-droplet"></i>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($healthStats['non_fasting_glucose'] ?? '95') ?></div>
                <div class="stat-label">Glucose Level (mg/dL)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon heart">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-value">72</div>
                <div class="stat-label">Heart Rate (bpm)</div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div>
                <!-- Upcoming Appointments -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-calendar-alt"></i>
                        Upcoming Appointments
                    </div>
                    <div>
                        <?php if ($appointments->num_rows > 0): ?>
                            <?php while ($appt = $appointments->fetch_assoc()): ?>
                                <div class="appointment-item">
                                    <div class="appointment-info">
                                        <h5><?php echo htmlspecialchars($appt['f_name'] . ' ' . $appt['l_name']); ?></h5>
                                        <p><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($appt['specialization']); ?></p>
                                        <p><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></p>
                                    </div>
                                    <div class="appointment-time">
                                        <?php echo date('H:i', strtotime($appt['appointment_time'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No upcoming appointments</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-lightning-bolt"></i>
                        Quick Actions
                    </div>
                    <div class="quick-actions">
                        <div class="action-box" onclick="location.href='book_appointment.php'">
                            <div class="action-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="action-title">Book<br>Appointment</div>
                        </div>
                        <div class="action-box">
                            <div class="action-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="action-title">View<br>Reports</div>
                        </div>
                        <div class="action-box">
                            <div class="action-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <div class="action-title">Video<br>Consultation</div>
                        </div>
                        <div class="action-box">
                            <div class="action-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="action-title">Call<br>Doctor</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column Sidebar Info -->
            <div>
                <!-- Medical Records Summary -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-file-medical"></i>
                        Medical Info
                    </div>
                    <div style="font-size: 14px;">
                        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-gray);">
                            <strong>Blood Group:</strong><br>
                            <span style="color: var(--text-muted);"><?php echo htmlspecialchars($medicalRecord['bloodgroup'] ?? 'N/A'); ?></span>
                        </div>
                        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-gray);">
                            <strong>Height:</strong><br>
                            <span style="color: var(--text-muted);"><?php echo htmlspecialchars($medicalRecord['height'] ?? 'N/A'); ?> cm</span>
                        </div>
                        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-gray);">
                            <strong>Weight:</strong><br>
                            <span style="color: var(--text-muted);"><?php echo htmlspecialchars($medicalRecord['weight'] ?? 'N/A'); ?> kg</span>
                        </div>
                        <div>
                            <strong>Gender:</strong><br>
                            <span style="color: var(--text-muted);"><?php echo htmlspecialchars($medicalRecord['gender'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recent Prescriptions -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-pills"></i>
                        Recent Prescriptions
                    </div>
                    <div class="prescription-item">
                        <div>
                            <div class="prescription-name">Aspirin 500mg</div>
                            <div class="prescription-details">1 tablet x 2 times daily</div>
                        </div>
                        <button class="btn-download">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                    <div class="prescription-item">
                        <div>
                            <div class="prescription-name">Amoxicillin 250mg</div>
                            <div class="prescription-details">1 tablet x 3 times daily</div>
                        </div>
                        <button class="btn-download">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        // Sidebar active link
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                document.querySelectorAll('.sidebar-nav a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
