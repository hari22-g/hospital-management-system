<?php
session_start();
// Include the database connection and hospital info
include("connection/config.php");
include("include/hospital_info.php");

// Fetch all doctors
$doctors = getAllDoctors();
$uniqueDoctors = [];

foreach ($doctors as $doctor) {
    $doctorId = (int) ($doctor['d_id'] ?? 0);
    if ($doctorId > 0 && !isset($uniqueDoctors[$doctorId])) {
        $uniqueDoctors[$doctorId] = $doctor;
    }
}

$doctors = array_values($uniqueDoctors);

function medc_encode_web_path(string $path): string {
    $segments = array_map('rawurlencode', array_filter(explode('/', str_replace('\\', '/', $path)), 'strlen'));
    return implode('/', $segments);
}

function medc_find_doctor_photo_fallback(array $doctor): ?string {
    static $photoFiles = null;

    if ($photoFiles === null) {
        $photoFiles = [];
        $photoDirectories = [
            __DIR__ . DIRECTORY_SEPARATOR . 'doctor photos',
            __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'Doctor_photos'
        ];

        foreach ($photoDirectories as $photoDirectory) {
            if (!is_dir($photoDirectory)) {
                continue;
            }

            foreach (scandir($photoDirectory) as $fileName) {
                if ($fileName === '.' || $fileName === '..') {
                    continue;
                }

                $filePath = $photoDirectory . DIRECTORY_SEPARATOR . $fileName;
                if (!is_file($filePath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'avif'], true)) {
                    $photoFiles[] = [
                        'file' => $fileName,
                        'dir' => basename($photoDirectory)
                    ];
                }
            }
        }
    }

    $firstName = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) ($doctor['f_name'] ?? '')));
    $lastName = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) ($doctor['l_name'] ?? '')));
    $fullName = $firstName . $lastName;

    $exactMatches = [];
    $partialMatches = [];
    foreach ($photoFiles as $photoFile) {
        $normalizedFileName = strtolower(preg_replace('/[^a-z0-9]+/', '', pathinfo($photoFile['file'], PATHINFO_FILENAME)));
        if ($fullName !== '' && $normalizedFileName === $fullName) {
            $exactMatches[] = $photoFile;
            continue;
        }

        if (
            ($fullName !== '' && str_contains($normalizedFileName, $fullName)) ||
            ($firstName !== '' && str_contains($normalizedFileName, $firstName)) ||
            ($lastName !== '' && str_contains($normalizedFileName, $lastName))
        ) {
            $partialMatches[] = $photoFile;
        }
    }

    $matchedFile = $exactMatches[0] ?? $partialMatches[0] ?? null;
    if ($matchedFile !== null) {
        return ($matchedFile['dir'] === 'Doctor_photos')
            ? 'uploads/Doctor_photos/' . $matchedFile['file']
            : 'doctor photos/' . $matchedFile['file'];
    }

    return null;
}

function medc_get_doctor_photo_url(array $doctor, string $localhost): ?string {
    $fallbackPhoto = medc_find_doctor_photo_fallback($doctor);
    if ($fallbackPhoto !== null) {
        return $localhost . medc_encode_web_path($fallbackPhoto);
    }

    $photoPath = trim((string) ($doctor['photo_path'] ?? ''));

    if ($photoPath !== '') {
        $absolutePhotoPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photoPath);
        if (is_file($absolutePhotoPath)) {
            return $localhost . medc_encode_web_path($photoPath);
        }
    }

    return null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Doctors Directory - <?php echo htmlspecialchars(getHospitalInfo()['hospital_name']); ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .doctor-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            background: white;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .doctor-photo {
            width: 100%;
            height: 230px;
            background: linear-gradient(180deg, #eef4ff 0%, #dfe9ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #6c757d;
        }

        .doctor-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .doctor-info {
            padding: 15px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .doctor-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #212529;
        }
        
        .doctor-specialization {
            color: #007bff;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .doctor-experience {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .doctor-contact {
            color: #28a745;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .doctor-email {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 12px;
            word-break: break-word;
        }

        .doctor-fees {
            color: #212529;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .specialties-badge {
            background-color: #17a2b8;
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        .availability {
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .booking-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .booking-btn:hover {
            background-color: #0056b3;
            color: white;
            text-decoration: none;
        }

        .doctor-grid-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .doctor-grid-summary {
            color: #6c757d;
            margin: 0;
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="container mt-4">
        <h1 class="section-title text-center mb-4">Our Medical Specialists</h1>
        
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5><i class="fas fa-search me-2"></i>Find Your Specialist</h5>
                    <p class="text-muted">Browse our experienced medical professionals by specialty</p>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" id="doctorSearch" class="form-control" placeholder="Search doctors...">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="doctor-grid-header">
            <h3 class="section-title mb-0">All Available Doctors</h3>
            <p class="doctor-grid-summary mb-0"><?php echo count($doctors); ?> doctors available</p>
        </div>

        <div class="row g-4" id="doctorsContainer">
            <?php if (!empty($doctors)): ?>
                <?php foreach ($doctors as $doctor): ?>
                    <?php
                    $doctorName = trim((string) ($doctor['f_name'] ?? '') . ' ' . (string) ($doctor['l_name'] ?? ''));
                    $doctorPhotoUrl = medc_get_doctor_photo_url($doctor, $localhost);
                    ?>
                    <div class="col-12 col-md-6 col-lg-4 doctor-item" data-specialization="<?php echo strtolower(htmlspecialchars((string) $doctor['specialization'], ENT_QUOTES, 'UTF-8')); ?>" data-name="<?php echo strtolower(htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8')); ?>">
                        <div class="doctor-card">
                            <div class="doctor-photo">
                                <?php if ($doctorPhotoUrl !== null): ?>
                                    <img src="<?php echo htmlspecialchars($doctorPhotoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars('Photo of Dr. ' . $doctorName, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php else: ?>
                                    <i class="fas fa-user-md"></i>
                                <?php endif; ?>
                            </div>
                            <div class="doctor-info">
                                <div class="doctor-name">
                                    Dr. <?php echo htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="doctor-specialization">
                                    <i class="fas fa-stethoscope me-1"></i>
                                    <?php echo htmlspecialchars((string) ($doctor['specialization'] ?? 'General Specialist'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="doctor-experience">
                                    <i class="fas fa-award me-1"></i>
                                    <?php echo htmlspecialchars((string) ($doctor['experience'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?> years experience
                                </div>
                                <div class="doctor-contact">
                                    <i class="fas fa-phone me-1"></i>
                                    <?php echo htmlspecialchars((string) ($doctor['contact_no'] ?? 'Not available'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="doctor-email">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?php echo htmlspecialchars((string) ($doctor['email'] ?? 'Email not available'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="doctor-fees">
                                    <i class="fas fa-indian-rupee-sign me-1"></i>
                                    Consultation Fee: <?php echo htmlspecialchars((string) ($doctor['consultation_fees'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>

                                <?php if (!empty($doctor['available_from']) && !empty($doctor['available_to'])): ?>
                                    <div class="availability mb-3">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        <?php echo htmlspecialchars((string) ($doctor['day_of_week'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>:
                                        <?php echo htmlspecialchars(date('g:i A', strtotime((string) $doctor['available_from'])), ENT_QUOTES, 'UTF-8'); ?> -
                                        <?php echo htmlspecialchars(date('g:i A', strtotime((string) $doctor['available_to'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-auto">
                                    <a href="homepage.php?doctor_id=<?php echo (int) $doctor['d_id']; ?>" class="booking-btn">
                                        <i class="fas fa-calendar-check me-1"></i>Book Appointment
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>No doctors available at the moment.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Doctor search functionality
        $(document).ready(function() {
            $('#doctorSearch').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                $('.doctor-item').each(function() {
                    var doctorName = $(this).data('name');
                    var doctorSpec = $(this).data('specialization');
                    
                    if (doctorName.includes(searchTerm) || doctorSpec.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
    
    <?php include("include/footer.php"); ?>
</body>

</html>
