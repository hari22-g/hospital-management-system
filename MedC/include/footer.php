<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer with Visitor Count</title>
    <style>
        footer {
            background-color: rgb(0,0,139);
            color: #fff;
            text-align: center;
            padding: 10px 0;
            bottom: 0;
            width: 100%;
            font-family: Arial, sans-serif;
            margin-left:-8px;
        }
        .visitor-count {
            margin-top: 5px;
            font-size: 1em;
            font-weight: bold;
            color: #ffcc00;
            animation: pop 1s ease-in-out;
        }
        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Content of the page -->

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center mb-3">
                    <a href="doctors_directory.php" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-calendar-check me-2"></i>Book Appointment
                    </a>
                    <a href="doctors_directory.php" class="btn btn-success btn-lg me-2">
                        <i class="fas fa-user-md me-2"></i>Find Doctors
                    </a>
                    <a href="video_consultation.php" class="btn btn-info btn-lg">
                        <i class="fas fa-video me-2"></i>Video Consultation
                    </a>
                </div>
            </div>
            <hr style="border-color: white;">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; 2025 AROGYA. All Rights Reserved.</p>
                    <div class="visitor-count">Visitor Count: <span id="visitorCount"><?php echo $count ?></span></div>
                </div>
            </div>
        </div>
    </footer>

    
</body>
</html>
