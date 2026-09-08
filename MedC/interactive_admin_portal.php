<?php
session_start();
require_once 'connection/config.php';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get hospital info
$hospital_info = [
    'hospital_name' => 'AROGYA',
    'phone_number' => '+91-9876543210',
    'address' => '123 Healthcare Avenue, Medical City, India'
];

// Get today's statistics
$today = date('Y-m-d');
$appointments_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointment WHERE appointment_date = '$today'"))['count'];
$patients_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM patient WHERE DATE(created_at) = '$today'"))['count'];
$doctors_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM doctor WHERE approval = 'approved'"))['count'];
$revenue_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(consultation_fees) as total FROM appointment a JOIN doctor d ON a.did = d.d_id WHERE a.appointment_date = '$today' AND a.status = 'Confirmed'"))['total'] ?? 0;

// Get emergency alerts (pending appointments)
$emergency_alerts = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointment WHERE status = 'Pending' AND appointment_date <= '$today'") or die(mysqli_error($conn));
$pending_count = mysqli_fetch_assoc($emergency_alerts)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AROGYA Admin Portal - Interactive Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="include/interactive_styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #007bff, #0056b3);
            color: white;
            height: 100vh;
            position: fixed;
            width: 280px;
            padding-top: 20px;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .logo-section {
            text-align: center;
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 30px;
            position: relative;
        }
        
        .logo-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20%;
            width: 60%;
            height: 2px;
            background: linear-gradient(90deg, transparent, white, transparent);
        }
        
        .logo-section img {
            width: 190px;
            max-width: 100%;
            height: auto;
            background: white;
            padding: 12px 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 3px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            border-radius: 24px;
        }
        
        .logo-section img:hover {
            transform: scale(1.04) translateY(-4px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        
        .logo-section .portal-label {
            margin: 16px 0 0;
            color: rgba(255,255,255,0.82);
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.24em;
            text-transform: uppercase;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 15px 25px;
            border-radius: 12px;
            margin: 8px 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #ff9a9e, #fad0c4);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .nav-link:hover::before, .nav-link.active::before {
            transform: scaleY(1);
        }
        
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #ff9a9e, #fad0c4, #a1c4fd, #c2e9fb);
            animation: gradientMove 3s infinite;
        }
        
        .stat-card .icon {
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card .number {
            font-size: 3.5rem;
            font-weight: 700;
            margin: 15px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card .label {
            font-size: 1.2rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .emergency-alert {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(255, 65, 108, 0.3);
            position: relative;
            overflow: hidden;
            animation: pulse 2s infinite;
        }
        
        .emergency-alert::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 4s linear infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .revenue-card {
            background: linear-gradient(135deg, #00b09b, #96c93d);
            animation: revenueGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes revenueGlow {
            from { box-shadow: 0 15px 35px rgba(0, 176, 155, 0.3); }
            to { box-shadow: 0 20px 45px rgba(0, 176, 155, 0.5); }
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.6s forwards;
        }
        
        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2);
            background: white;
            transform: translateY(-2px);
        }
        
        .table-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .table th {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px;
        }
        
        .table tr {
            transition: all 0.3s ease;
        }
        
        .table tr:hover {
            background: rgba(0, 123, 255, 0.05);
            transform: scale(1.01);
        }
        
        .quick-action-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.4);
        }
        
        .quick-action-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .quick-action-btn:active::after {
            width: 300px;
            height: 300px;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff416c;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-section">
            <img src="include/homepage_slider/aarogya_logo.svg" alt="AAROGYA Logo" onerror="this.src='include/homepage_slider/logo.png'">
            <p class="portal-label">Admin Portal</p>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item position-relative">
                <a class="nav-link active" href="#" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt me-3"></i>Dashboard
                    <?php if ($pending_count > 0): ?>
                    <span class="notification-badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showSection('patients')">
                    <i class="fas fa-user-injured me-3"></i>Patient Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showSection('appointments')">
                    <i class="fas fa-calendar-check me-3"></i>Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showSection('doctors')">
                    <i class="fas fa-user-md me-3"></i>Doctor Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showSection('billing')">
                    <i class="fas fa-file-invoice-dollar me-3"></i>Billing & Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showSection('cms')">
                    <i class="fas fa-edit me-3"></i>Content Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showSection('reports')">
                    <i class="fas fa-chart-bar me-3"></i>Reports & Analytics
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-3"></i>Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <!-- Dashboard Section -->
        <div id="dashboard-section">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2"><i class="fas fa-tachometer-alt me-3"></i>Dashboard Overview</h1>
                        <p class="text-muted mb-0">Welcome back, Admin! Here's your hospital's performance today.</p>
                    </div>
                    <div class="text-end">
                        <div class="fs-5 fw-bold"><?php echo date('F j, Y'); ?></div>
                        <div class="text-muted"><?php echo date('l'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon text-primary">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="number"><?php echo $appointments_today; ?></div>
                        <div class="label">Today's Appointments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon text-success">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="number"><?php echo $patients_today; ?></div>
                        <div class="label">New Patients Today</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon text-info">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="number"><?php echo $doctors_count; ?></div>
                        <div class="label">Active Doctors</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center revenue-card">
                        <div class="icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="number">₹<?php echo number_format($revenue_today); ?></div>
                        <div class="label">Today's Revenue</div>
                    </div>
                </div>
            </div>

            <?php if ($pending_count > 0): ?>
            <div class="emergency-alert">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-exclamation-triangle me-3" style="font-size: 2rem;"></i>
                        <strong>Emergency Alert:</strong> <?php echo $pending_count; ?> pending appointments require immediate attention!
                    </div>
                    <button class="btn btn-light" onclick="showSection('appointments')">
                        <i class="fas fa-eye me-2"></i>View Appointments
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="content-section">
                        <h3><i class="fas fa-chart-line me-2"></i>Weekly Performance</h3>
                        <canvas id="weeklyChart" height="120"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="content-section">
                        <h3><i class="fas fa-bell me-2"></i>Recent Notifications</h3>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-user-plus text-success me-2"></i>
                                    New patient registered
                                </div>
                                <span class="badge bg-success rounded-pill">2 min ago</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-calendar-check text-primary me-2"></i>
                                    Appointment confirmed
                                </div>
                                <span class="badge bg-primary rounded-pill">15 min ago</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-pills text-warning me-2"></i>
                                    Medicine stock low
                                </div>
                                <span class="badge bg-warning rounded-pill">1 hour ago</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-medical text-info me-2"></i>
                                    Lab report uploaded
                                </div>
                                <span class="badge bg-info rounded-pill">3 hours ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="content-section">
                        <h3><i class="fas fa-bolt me-2"></i>Quick Actions</h3>
                        <div class="d-flex flex-wrap gap-3">
                            <button class="quick-action-btn" onclick="showSection('patients')">
                                <i class="fas fa-user-injured me-2"></i>Add New Patient
                            </button>
                            <button class="quick-action-btn" onclick="showSection('appointments')">
                                <i class="fas fa-calendar-plus me-2"></i>Schedule Appointment
                            </button>
                            <button class="quick-action-btn" onclick="showSection('doctors')">
                                <i class="fas fa-user-md me-2"></i>Add Doctor
                            </button>
                            <button class="quick-action-btn" onclick="showSection('billing')">
                                <i class="fas fa-file-invoice me-2"></i>Generate Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Other sections will be loaded dynamically -->
        <div id="patients-section" style="display: none;">
            <div class="dashboard-header">
                <h1><i class="fas fa-user-injured me-3"></i>Patient Management</h1>
                <p class="text-muted">Manage patient records, search, and digital health information</p>
            </div>
            
            <div class="content-section">
                <div class="row">
                    <div class="col-md-6">
                        <h4><i class="fas fa-search me-2"></i>Patient Records (EMR)</h4>
                        <div class="input-group mb-4">
                            <input type="text" class="form-control" id="patientSearch" placeholder="Search by mobile number or Patient ID">
                            <button class="btn btn-primary" type="button" onclick="searchPatient()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" onclick="addNewPatient()">
                                <i class="fas fa-user-plus me-2"></i>Add New Patient
                            </button>
                            <button class="btn btn-info" onclick="exportPatients()">
                                <i class="fas fa-file-export me-2"></i>Export Data
                            </button>
                        </div>
                    </div>
                </div>
                <div id="patientResults"></div>
            </div>
        </div>

        <!-- Additional sections would follow the same pattern -->
        <div id="appointments-section" style="display: none;">
            <div class="dashboard-header">
                <h1><i class="fas fa-calendar-check me-3"></i>Appointment Management</h1>
                <p class="text-muted">Schedule, manage, and track all appointments</p>
            </div>
            <div class="content-section">
                <h3><i class="fas fa-calendar-alt me-2"></i>Calendar View</h3>
                <div id="calendar" class="bg-light p-4 rounded">
                    Calendar functionality would be implemented here
                </div>
            </div>
        </div>

        <div id="doctors-section" style="display: none;">
            <div class="dashboard-header">
                <h1><i class="fas fa-user-md me-3"></i>Doctor Management</h1>
                <p class="text-muted">Add, edit, and manage doctor profiles and schedules</p>
            </div>
            <div class="content-section">
                <h3><i class="fas fa-user-plus me-2"></i>Add New Doctor</h3>
                <form id="doctorForm" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="f_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="l_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Specialization</label>
                        <input type="text" class="form-control" name="specialization" required>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>Add Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('[id$="-section"]').forEach(section => {
                section.style.display = 'none';
                section.style.animation = 'none';
            });
            
            // Show selected section
            const targetSection = document.getElementById(sectionId + '-section');
            targetSection.style.display = 'block';
            
            // Add animation
            targetSection.style.animation = 'slideUp 0.6s forwards';
            
            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Find and activate the clicked link
            event.target.closest('.nav-link').classList.add('active');
        }
        
        function searchPatient() {
            const searchTerm = document.getElementById('patientSearch').value;
            // Implement patient search logic
            alert('Searching for: ' + searchTerm);
        }
        
        function addNewPatient() {
            // Implement add patient logic
            alert('Add new patient form will open');
        }
        
        function exportPatients() {
            // Implement export logic
            alert('Exporting patient data');
        }
        
        // Form submission for doctor
        document.getElementById('doctorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Implement doctor creation logic
            alert('Doctor added successfully!');
            this.reset();
        });
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            showSection('dashboard');
            
            // Initialize chart if canvas exists
            const chartCanvas = document.getElementById('weeklyChart');
            if (chartCanvas) {
                const ctx = chartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Appointments',
                            data: [12, 19, 15, 17, 22, 25, 18],
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Patients',
                            data: [8, 12, 10, 14, 18, 20, 15],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
