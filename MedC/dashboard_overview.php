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
    <title>AROGYA Admin Portal - Dashboard Overview</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="include/interactive_styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .sidebar-brand-logo {
            width: 180px;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 1rem;
            background: white;
            padding: 10px 14px;
            border-radius: 24px;
            border: 3px solid rgba(255, 255, 255, 0.75);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
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
        
        .revenue-card {
            background: linear-gradient(135deg, #00b09b, #96c93d);
            animation: revenueGlow 3s ease-in-out infinite alternate;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
        
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        @keyframes revenueGlow {
            from { box-shadow: 0 15px 35px rgba(0, 176, 155, 0.3); }
            to { box-shadow: 0 20px 45px rgba(0, 176, 155, 0.5); }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center p-4">
            <img src="include/homepage_slider/aarogya_logo.svg" alt="AAROGYA Logo" class="sidebar-brand-logo">
            <p class="text-white-50 mb-0 text-uppercase fw-semibold" style="letter-spacing: 0.22em;">Admin Portal</p>
        </div>
        
        <ul class="nav flex-column px-3">
            <li class="nav-item">
                <a class="nav-link active text-white" href="#">
                    <i class="fas fa-tachometer-alt me-3"></i>Dashboard Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50" href="#">
                    <i class="fas fa-user-injured me-3"></i>Patient Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50" href="#">
                    <i class="fas fa-calendar-check me-3"></i>Appointment Scheduling
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50" href="#">
                    <i class="fas fa-user-md me-3"></i>Doctor Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50" href="#">
                    <i class="fas fa-file-invoice-dollar me-3"></i>Billing & Finance
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-white-50" href="logout.php">
                    <i class="fas fa-sign-out-alt me-3"></i>Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="fas fa-tachometer-alt me-3"></i>Dashboard Overview</h1>
                    <p class="text-muted mb-0">Comprehensive hospital management system dashboard</p>
                </div>
                <div class="text-end">
                    <div class="fs-5 fw-bold"><?php echo date('F j, Y'); ?></div>
                    <div class="text-muted"><?php echo date('l'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Main Dashboard Statistics -->
        <div class="row">
            <!-- Appointment Management -->
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="icon text-primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="number"><?php echo $appointments_today; ?></div>
                    <div class="label">Appointment Management</div>
                </div>
            </div>
            
            <!-- Patient Registration -->
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="icon text-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="number"><?php echo $patients_today; ?></div>
                    <div class="label">Patient Registration</div>
                </div>
            </div>
            
            <!-- Medical Staff -->
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="icon text-info">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="number"><?php echo $doctors_count; ?></div>
                    <div class="label">Medical Staff</div>
                </div>
            </div>
            
            <!-- Financial Overview -->
            <div class="col-md-3">
                <div class="stat-card text-center revenue-card">
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="number">₹<?php echo number_format($revenue_today); ?></div>
                    <div class="label">Financial Overview</div>
                </div>
            </div>
        </div>

        <!-- Emergency Alerts -->
        <?php if ($pending_count > 0): ?>
        <div class="emergency-alert">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-exclamation-triangle me-3" style="font-size: 2rem;"></i>
                    <strong>Emergency Alert:</strong> <?php echo $pending_count; ?> pending appointments require immediate attention!
                </div>
                <button class="btn btn-light">
                    <i class="fas fa-eye me-2"></i>View Details
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hospital Operations Overview -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="content-section">
                    <h3><i class="fas fa-hospital me-2"></i>Hospital Operations</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 border rounded mb-3">
                                <i class="fas fa-procedures fa-2x text-primary me-3"></i>
                                <div>
                                    <h5 class="mb-0">ICU Status</h5>
                                    <p class="text-muted mb-0">8 beds available</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 border rounded mb-3">
                                <i class="fas fa-flask fa-2x text-success me-3"></i>
                                <div>
                                    <h5 class="mb-0">Lab Reports</h5>
                                    <p class="text-muted mb-0">24 pending results</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 border rounded mb-3">
                                <i class="fas fa-pills fa-2x text-warning me-3"></i>
                                <div>
                                    <h5 class="mb-0">Pharmacy</h5>
                                    <p class="text-muted mb-0">Low stock alerts: 5 items</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 border rounded mb-3">
                                <i class="fas fa-ambulance fa-2x text-danger me-3"></i>
                                <div>
                                    <h5 class="mb-0">Emergency Services</h5>
                                    <p class="text-muted mb-0">24/7 operational</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="content-section">
                    <h3><i class="fas fa-bell me-2"></i>Recent Activities</h3>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user-plus text-success me-2"></i>
                                New patient registered
                            </div>
                            <span class="badge bg-success rounded-pill">2 min</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-check text-primary me-2"></i>
                                Appointment confirmed
                            </div>
                            <span class="badge bg-primary rounded-pill">15 min</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-medical text-info me-2"></i>
                                Lab report uploaded
                            </div>
                            <span class="badge bg-info rounded-pill">1 hour</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-prescription-bottle text-warning me-2"></i>
                                Medicine prescription
                            </div>
                            <span class="badge bg-warning rounded-pill">2 hours</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="content-section">
                    <h3><i class="fas fa-bolt me-2"></i>Quick Actions</h3>
                    <div class="d-flex flex-wrap gap-3">
                        <button class="quick-action-btn">
                            <i class="fas fa-user-plus me-2"></i>Register Patient
                        </button>
                        <button class="quick-action-btn">
                            <i class="fas fa-calendar-plus me-2"></i>Schedule Appointment
                        </button>
                        <button class="quick-action-btn">
                            <i class="fas fa-file-medical me-2"></i>View Medical Records
                        </button>
                        <button class="quick-action-btn">
                            <i class="fas fa-file-invoice me-2"></i>Generate Invoice
                        </button>
                        <button class="quick-action-btn">
                            <i class="fas fa-chart-bar me-2"></i>View Reports
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
