<?php
session_start();
// Include the database connection and hospital info
include("connection/config.php");
include("include/hospital_info.php");

$hospital_info = getHospitalInfo();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Hospital Facilities - <?php echo htmlspecialchars($hospital_info['hospital_name']); ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .facility-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            background: white;
        }
        
        .facility-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .facility-media {
            width: 100%;
            height: 200px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .facility-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }

        .facility-card:hover .facility-media img {
            transform: scale(1.04);
        }
        
        .facility-info {
            padding: 20px;
        }
        
        .facility-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #212529;
        }
        
        .facility-description {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .accreditation-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .specialty-tag {
            background-color: #17a2b8;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        .insurance-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .gallery-item {
            height: 200px;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            background: #e9ecef;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .gallery-caption {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 12px 14px;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.02) 0%, rgba(0, 0, 0, 0.72) 100%);
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="container mt-4">
        <h1 class="section-title text-center mb-4">Hospital Facilities & Infrastructure</h1>
        
        <!-- Hospital Overview -->
        <div class="row mb-5">
            <div class="col-md-6">
                <h3><i class="fas fa-hospital me-2"></i>About Our Hospital</h3>
                <p class="lead"><?php echo htmlspecialchars($hospital_info['hospital_name']); ?> is committed to providing world-class healthcare services with state-of-the-art facilities and compassionate care.</p>
                
                <div class="mt-4">
                    <h4><i class="fas fa-award me-2"></i>Accreditations</h4>
                    <?php if(!empty($hospital_info['accreditations'])): ?>
                        <?php $accreditations = explode(',', $hospital_info['accreditations']); ?>
                        <?php foreach($accreditations as $acc): ?>
                            <span class="accreditation-badge me-2 mb-2"><?php echo trim(htmlspecialchars($acc)); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>NABH, NABL Certified</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="image-gallery">
                    <div class="gallery-item">
                        <img src="include/homepage_slider/img3.jpg" alt="Exterior view of the hospital building" loading="lazy" onerror="this.onerror=null;this.src='include/homepage_slider/img5.jpg';">
                        <div class="gallery-caption">Hospital Building</div>
                    </div>
                    <div class="gallery-item">
                        <img src="include/homepage_slider/emergency_care_banner.svg" alt="Emergency care and ambulance services" loading="lazy" onerror="this.onerror=null;this.src='include/homepage_slider/img3.jpg';">
                        <div class="gallery-caption">Emergency Care</div>
                    </div>
                    <div class="gallery-item">
                        <img src="include/homepage_slider/Picsart_25-02-15_01-17-09-154.jpg" alt="Hospital support and facility area" loading="lazy" onerror="this.onerror=null;this.src='include/homepage_slider/img3.jpg';">
                        <div class="gallery-caption">Modern Facilities</div>
                    </div>
                    <div class="gallery-item">
                        <img src="include/homepage_slider/We.jpeg" alt="Compassionate care environment in the hospital" loading="lazy" onerror="this.onerror=null;this.src='include/homepage_slider/img5.jpg';">
                        <div class="gallery-caption">Patient Care</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Medical Specialties -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="section-title"><i class="fas fa-stethoscope me-2"></i>Medical Specialties</h3>
                
                <?php if(!empty($hospital_info['specialties'])): ?>
                    <?php $specialties = explode(',', $hospital_info['specialties']); ?>
                    <?php foreach($specialties as $spec): ?>
                        <span class="specialty-tag"><?php echo trim(htmlspecialchars($spec)); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <span class="specialty-tag">Cardiology</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <span class="specialty-tag">Orthopedics</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <span class="specialty-tag">Pediatrics</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <span class="specialty-tag">Dermatology</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <span class="specialty-tag">Ophthalmology</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <span class="specialty-tag">Neurology</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <span class="specialty-tag">Gynecology</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <span class="specialty-tag">ENT</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Hospital Facilities -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="section-title"><i class="fas fa-building me-2"></i>Facilities & Services</h3>
                
                <div class="row">
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="facility-card">
                            <div class="facility-media">
                                <img src="include/facility_gallery/emergency-care.svg" alt="Emergency care facility illustration" loading="lazy">
                            </div>
                            <div class="facility-info">
                                <h4 class="facility-title">Emergency Care</h4>
                                <p class="facility-description">24/7 emergency services with state-of-the-art equipment and trained staff to handle critical situations.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="facility-card">
                            <div class="facility-media">
                                <img src="include/facility_gallery/icu-ccu.svg" alt="ICU and CCU facility illustration" loading="lazy">
                            </div>
                            <div class="facility-info">
                                <h4 class="facility-title">ICU & CCU</h4>
                                <p class="facility-description">Intensive Care Units equipped with advanced monitoring systems and life support equipment.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="facility-card">
                            <div class="facility-media">
                                <img src="include/facility_gallery/operation-theater.svg" alt="Operation theater illustration" loading="lazy">
                            </div>
                            <div class="facility-info">
                                <h4 class="facility-title">Operation Theater</h4>
                                <p class="facility-description">Modern operation theaters with laminar airflow systems and advanced surgical equipment.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="facility-card">
                            <div class="facility-media">
                                <img src="include/facility_gallery/laboratory.svg" alt="Laboratory illustration" loading="lazy">
                            </div>
                            <div class="facility-info">
                                <h4 class="facility-title">Laboratory</h4>
                                <p class="facility-description">Fully automated laboratory with advanced diagnostic equipment and quick turnaround times.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="facility-card">
                            <div class="facility-media">
                                <img src="include/facility_gallery/imaging-services.svg" alt="Imaging services illustration" loading="lazy">
                            </div>
                            <div class="facility-info">
                                <h4 class="facility-title">Imaging Services</h4>
                                <p class="facility-description">MRI, CT Scan, X-Ray, Ultrasound, and other advanced imaging technologies.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="facility-card">
                            <div class="facility-media">
                                <img src="include/facility_gallery/pharmacy.svg" alt="Pharmacy illustration" loading="lazy">
                            </div>
                            <div class="facility-info">
                                <h4 class="facility-title">Pharmacy</h4>
                                <p class="facility-description">Round-the-clock pharmacy service with genuine medicines and competitive pricing.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Insurance Information -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="section-title"><i class="fas fa-file-invoice-dollar me-2"></i>Insurance & TPA</h3>
                
                <div class="insurance-card">
                    <?php if(!empty($hospital_info['insurance_accepted'])): ?>
                        <h5><?php echo htmlspecialchars($hospital_info['insurance_accepted']); ?></h5>
                    <?php else: ?>
                        <h5>All Major Insurances Accepted</h5>
                    <?php endif; ?>
                    
                    <p>We accept all major insurance providers and TPA (Third Party Administrator) schemes. Our team assists with insurance claims and documentation.</p>
                    
                    <div class="row mt-3">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-shield-alt fa-2x text-primary mb-2"></i>
                                <p>Cashless Treatment</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-clipboard-check fa-2x text-success mb-2"></i>
                                <p>Claims Assistance</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-file-medical fa-2x text-warning mb-2"></i>
                                <p>Documentation Support</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-headset fa-2x text-info mb-2"></i>
                                <p>24/7 Helpline</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("include/footer.php"); ?>
</body>

</html>
