<?php
session_start();
// Include the database connection and hospital info
include("connection/config.php");
include("include/hospital_info.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_type = $_SESSION['user_type'] ?? '';
$hospital_info = getHospitalInfo();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Video Consultation - <?php echo htmlspecialchars($hospital_info['hospital_name']); ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .consultation-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            background: white;
        }
        
        .consultation-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
        }
        
        .consultation-body {
            padding: 20px;
        }
        
        .consultation-option {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .consultation-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .option-icon {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .option-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .option-description {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .join-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .join-btn:hover {
            background-color: #218838;
            color: white;
            text-decoration: none;
        }
        
        .recent-consultations {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .video-container {
            display: none;
            height: 70vh;
            background-color: #000;
            margin-bottom: 20px;
        }
        
        .back-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .back-btn:hover {
            background-color: #5a6268;
            color: white;
            text-decoration: none;
        }
        
        .appointment-card {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        
        .appointment-status-pending {
            border-left-color: #ffc107;
        }
        
        .appointment-status-confirmed {
            border-left-color: #28a745;
        }
        
        .appointment-status-cancelled {
            border-left-color: #dc3545;
        }

        .doctor-grid {
            row-gap: 20px;
        }

        .doctor-card {
            border: 1px solid #e1e5ea;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px 18px 16px;
            height: 100%;
            box-shadow: 0 6px 16px rgba(17, 24, 39, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .doctor-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px rgba(17, 24, 39, 0.12);
        }

        .doctor-card h5 {
            margin-bottom: 10px;
        }

        .doctor-meta {
            color: #495057;
            margin-bottom: 6px;
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="container mt-4">
        <h1 class="section-title text-center mb-4">Video Consultation</h1>
        
        <div id="consultationOptions">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="consultation-option">
                        <div class="option-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <h4 class="option-title">Quick Consultation</h4>
                        <p class="option-description">Connect with a doctor instantly for immediate consultation</p>
                        <button class="join-btn" onclick="startConsultation('quick')">
                            <i class="fas fa-play-circle me-2"></i>Start Consultation
                        </button>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="consultation-option">
                        <div class="option-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="option-title">Scheduled Appointment</h4>
                        <p class="option-description">Join a scheduled video appointment</p>
                        <button class="join-btn" onclick="showScheduledAppointments()">
                            <i class="fas fa-list me-2"></i>View Appointments
                        </button>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="consultation-option">
                        <div class="option-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h4 class="option-title">Doctor-Specific</h4>
                        <p class="option-description">Consult with a specific doctor</p>
                        <button class="join-btn" onclick="showDoctorList()">
                            <i class="fas fa-search me-2"></i>Find Doctor
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="scheduledAppointments" style="display: none;">
            <h3><i class="fas fa-calendar-alt me-2"></i>Your Scheduled Appointments</h3>
            <div class="row">
                <div class="col-12">
                    <?php
                    // Display upcoming appointments
                    $patient_id = $_SESSION['user_id'] ?? null;
                    if ($patient_id && $user_type === 'patient') {
                        $sql = "SELECT a.*, d.f_name, d.l_name, d.specialization 
                                FROM appointment a 
                                JOIN doctor d ON a.did = d.d_id 
                                WHERE a.pid = ? AND a.appointment_date >= CURDATE() 
                                ORDER BY a.appointment_date, a.appointment_time";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $patient_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status_class = '';
                                switch ($row['status']) {
                                    case 'Pending': $status_class = 'appointment-status-pending'; break;
                                    case 'Confirmed': $status_class = 'appointment-status-confirmed'; break;
                                    case 'Cancelled': $status_class = 'appointment-status-cancelled'; break;
                                    default: $status_class = '';
                                }
                                
                                echo '<div class="appointment-card ' . $status_class . '">';
                                echo '<h5>Dr. ' . htmlspecialchars($row['f_name'] . ' ' . $row['l_name']) . '</h5>';
                                echo '<p><strong>Specialization:</strong> ' . htmlspecialchars($row['specialization']) . '</p>';
                                echo '<p><strong>Date:</strong> ' . date('F j, Y', strtotime($row['appointment_date'])) . '</p>';
                                echo '<p><strong>Time:</strong> ' . date('g:i A', strtotime($row['appointment_time'])) . '</p>';
                                echo '<p><strong>Status:</strong> <span class="badge bg-' . 
                                     ($row['status'] === 'Confirmed' ? 'success' : ($row['status'] === 'Pending' ? 'warning' : 'danger')) . '">' . 
                                     $row['status'] . '</span></p>';
                                
                                if ($row['status'] === 'Confirmed' && !empty($row['jitsi_meeting_link'])) {
                                    echo '<a href="' . htmlspecialchars($row['jitsi_meeting_link']) . '" target="_blank" class="join-btn mt-2">';
                                    echo '<i class="fas fa-video me-2"></i>Join Meeting';
                                    echo '</a>';
                                } elseif ($row['status'] === 'Confirmed') {
                                    // Generate meeting link if not exists
                                    $meeting_id = 'medc_' . $row['appointment_id'] . '_' . time();
                                    $meeting_url = 'https://meet.jit.si/' . $meeting_id;
                                    
                                    // Update appointment with meeting link
                                    $update_sql = "UPDATE appointment SET jitsi_meeting_link = ? WHERE appointment_id = ?";
                                    $update_stmt = $conn->prepare($update_sql);
                                    $update_stmt->bind_param("si", $meeting_url, $row['appointment_id']);
                                    $update_stmt->execute();
                                    $update_stmt->close();
                                    
                                    echo '<a href="' . $meeting_url . '" target="_blank" class="join-btn mt-2">';
                                    echo '<i class="fas fa-video me-2"></i>Join Meeting';
                                    echo '</a>';
                                }
                                
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-info">No upcoming appointments found.</div>';
                        }
                        $stmt->close();
                    } else {
                        echo '<div class="alert alert-info">Please log in as a patient to view your appointments.</div>';
                    }
                    ?>
                </div>
            </div>
            <button class="back-btn mt-3" onclick="showConsultationOptions()">
                <i class="fas fa-arrow-left me-2"></i>Back to Options
            </button>
        </div>
        
        <div id="doctorList" style="display: none;">
            <h3><i class="fas fa-user-md me-2"></i>Available Doctors</h3>
            <div class="row">
                <div class="col-12">
                    <div class="input-group mb-3">
                        <input type="text" id="doctorSearch" class="form-control" placeholder="Search doctors by name or specialization...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div id="doctorsResults" class="row doctor-grid">
                        <?php
                        // Display available doctors
                        $sql = "SELECT d_id, f_name, l_name, specialization, experience FROM doctor WHERE approval = 'approved' LIMIT 10";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<div class="col-lg-4 col-md-6">';
                                echo '<div class="doctor-card">';
                                echo '<h5>Dr. ' . htmlspecialchars($row['f_name'] . ' ' . $row['l_name']) . '</h5>';
                                echo '<p class="doctor-meta"><strong>Specialization:</strong> ' . htmlspecialchars($row['specialization']) . '</p>';
                                echo '<p class="doctor-meta"><strong>Experience:</strong> ' . $row['experience'] . ' years</p>';
                                echo '<button class="join-btn mt-2" onclick="startConsultationWithDoctor(' . $row['d_id'] . ')">';
                                echo '<i class="fas fa-video me-2"></i>Start Consultation';
                                echo '</button>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-info">No doctors available at the moment.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <button class="back-btn mt-3" onclick="showConsultationOptions()">
                <i class="fas fa-arrow-left me-2"></i>Back to Options
            </button>
        </div>
        
        <div id="videoContainer" class="video-container">
            <div id="jitsi-container" style="width: 100%; height: 100%;"></div>
            <button class="back-btn mt-3" onclick="endConsultation()" style="position: absolute; top: 20px; right: 20px;">
                <i class="fas fa-times me-2"></i>End Consultation
            </button>
        </div>
    </div>

    <!-- Include Jitsi Meet External API -->
    <script src="https://meet.jit.si/external_api.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let api = null;
        
        function startConsultation(type) {
            // Generate a unique room name
            const roomName = 'medc_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            const domain = 'meet.jit.si';
            
            const options = {
                roomName: roomName,
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#jitsi-container'),
                userInfo: {
                    displayName: '<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Patient'; ?>'
                },
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: false
                }
            };
            
            api = new JitsiMeetExternalAPI(domain, options);
            
            // Show video container and hide options
            document.getElementById('consultationOptions').style.display = 'none';
            document.getElementById('scheduledAppointments').style.display = 'none';
            document.getElementById('doctorList').style.display = 'none';
            document.getElementById('videoContainer').style.display = 'block';
        }
        
        function startConsultationWithDoctor(doctorId) {
            // Generate a unique room name with doctor ID
            const roomName = 'medc_doctor_' + doctorId + '_' + Date.now();
            const domain = 'meet.jit.si';
            
            const options = {
                roomName: roomName,
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#jitsi-container'),
                userInfo: {
                    displayName: '<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Patient'; ?>'
                },
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: false
                }
            };
            
            api = new JitsiMeetExternalAPI(domain, options);
            
            // Show video container and hide options
            document.getElementById('consultationOptions').style.display = 'none';
            document.getElementById('scheduledAppointments').style.display = 'none';
            document.getElementById('doctorList').style.display = 'none';
            document.getElementById('videoContainer').style.display = 'block';
        }
        
        function showScheduledAppointments() {
            document.getElementById('consultationOptions').style.display = 'none';
            document.getElementById('scheduledAppointments').style.display = 'block';
            document.getElementById('doctorList').style.display = 'none';
            document.getElementById('videoContainer').style.display = 'none';
        }
        
        function showDoctorList() {
            document.getElementById('consultationOptions').style.display = 'none';
            document.getElementById('scheduledAppointments').style.display = 'none';
            document.getElementById('doctorList').style.display = 'block';
            document.getElementById('videoContainer').style.display = 'none';
        }
        
        function showConsultationOptions() {
            document.getElementById('consultationOptions').style.display = 'block';
            document.getElementById('scheduledAppointments').style.display = 'none';
            document.getElementById('doctorList').style.display = 'none';
            document.getElementById('videoContainer').style.display = 'none';
        }
        
        function endConsultation() {
            if (api) {
                api.dispose();
                api = null;
            }
            
            document.getElementById('consultationOptions').style.display = 'block';
            document.getElementById('scheduledAppointments').style.display = 'none';
            document.getElementById('doctorList').style.display = 'none';
            document.getElementById('videoContainer').style.display = 'none';
        }
        
        // Handle browser back button
        window.addEventListener('beforeunload', function(event) {
            if (api) {
                api.dispose();
            }
        });
        
        // Doctor search functionality
        $(document).ready(function() {
            $('#doctorSearch').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                $('#doctorsResults .doctor-card').each(function() {
                    var doctorName = $(this).find('h5').text().toLowerCase();
                    var doctorSpec = $(this).find('p').first().text().toLowerCase();
                    
                    if (doctorName.includes(searchTerm) || doctorSpec.includes(searchTerm)) {
                        $(this).closest('.col-lg-4').show();
                    } else {
                        $(this).closest('.col-lg-4').hide();
                    }
                });
            });
        });
    </script>
    
    <?php include("include/footer.php"); ?>
</body>

</html>