<?php
// Include necessary files
require_once 'connection/config.php';

// Get visitor count
$get_count = "SELECT counter FROM visitor_count";
$run_count = mysqli_query($conn, $get_count);
$row_count = mysqli_fetch_array($run_count);
$count = $row_count["counter"];

// Get hospital info
require_once 'include/hospital_info.php';
$hospital_info = getHospitalInfo();
$localhost = 'http://localhost/MedC/MedC/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AROGYA Hospital - Comprehensive Healthcare Solutions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="include/interactive_styles.css">
    
    <style>
        /* Custom homepage specific styles */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="40" r="1.5" fill="rgba(255,255,255,0.08)"/><circle cx="80" cy="30" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="30" cy="70" r="2.5" fill="rgba(255,255,255,0.12)"/><circle cx="70" cy="60" r="1.2" fill="rgba(255,255,255,0.07)"/></svg>');
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-20px) translateX(10px); }
            100% { transform: translateY(0) translateX(0); }
        }
        
        .service-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin: 20px 0;
            text-align: center;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            height: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.98);
        }
        
        .service-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #007bff, #0056b3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .feature-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 80px 0;
        }
        
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: all 0.3s ease;
        }
        
        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 5rem;
            color: rgba(0, 123, 255, 0.1);
            font-family: Georgia, serif;
        }
        
        .cta-section {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .stats-counter {
            font-size: 3rem;
            font-weight: 700;
            margin: 15px 0;
            background: linear-gradient(45deg, #fff, #f8f9fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .image-slider {
            position: relative;
            height: 500px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .slider-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 1s ease-in-out;
        }
        
        .slider-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
            color: white;
            padding: 30px;
        }
    </style>
</head>

<body>
    <!-- Interactive Navbar -->
    <?php include("include/navbar.php") ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4">Welcome to <span style="background: linear-gradient(45deg, #fff, #f0f8ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">AROGYA</span></h1>
                    <p class="lead fs-4 mb-4">Your trusted partner in comprehensive healthcare. Experience world-class medical services with cutting-edge technology and compassionate care.</p>
                    <div class="d-flex gap-3">
                        <button class="btn btn-light btn-lg px-4 py-3 rounded-pill">
                            <i class="fas fa-calendar-check me-2"></i>Book Appointment
                        </button>
                        <button class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill">
                            <i class="fas fa-phone-alt me-2"></i>Call Now
                        </button>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="image-slider">
                        <img src="include/homepage_slider/logo.png" alt="AROGYA Hospital" class="slider-image">
                        <div class="slider-overlay">
                            <h3>Advanced Healthcare Solutions</h3>
                            <p>24/7 Emergency Services • State-of-the-art Facilities • Expert Medical Team</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Statistics Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-user-md service-icon"></i>
                        <div class="stats-counter" id="doctorCount">150+</div>
                        <div class="label">Expert Doctors</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-heartbeat service-icon"></i>
                        <div class="stats-counter" id="patientCount">50000+</div>
                        <div class="label">Happy Patients</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-award service-icon"></i>
                        <div class="stats-counter" id="awardCount">25+</div>
                        <div class="label">Years Experience</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-flask service-icon"></i>
                        <div class="stats-counter" id="researchCount">100+</div>
                        <div class="label">Research Papers</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Services Section -->
    <section class="feature-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold mb-3">Our <span style="background: linear-gradient(45deg, #007bff, #0056b3); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Services</span></h2>
                <p class="lead">Comprehensive healthcare solutions tailored to your needs</p>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-stethoscope service-icon"></i>
                        <h3 class="mb-3">24/7 Emergency Care</h3>
                        <p>Round-the-clock emergency medical services with our dedicated team of specialists ready to respond immediately to critical situations.</p>
                        <button class="btn btn-primary mt-3">Learn More</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-user-md service-icon"></i>
                        <h3 class="mb-3">Expert Consultations</h3>
                        <p>Consult with our team of experienced specialists across various medical disciplines for personalized treatment plans.</p>
                        <button class="btn btn-primary mt-3">Book Consultation</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-laptop-medical service-icon"></i>
                        <h3 class="mb-3">Telemedicine</h3>
                        <p>Access quality healthcare from anywhere through our secure video consultation platform with certified doctors.</p>
                        <button class="btn btn-primary mt-3">Start Video Call</button>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-procedures service-icon"></i>
                        <h3 class="mb-3">Advanced Diagnostics</h3>
                        <p>State-of-the-art diagnostic facilities including MRI, CT Scan, and laboratory services for accurate health assessments.</p>
                        <button class="btn btn-primary mt-3">View Facilities</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-pills service-icon"></i>
                        <h3 class="mb-3">Pharmacy Services</h3>
                        <p>24/7 pharmacy services with a wide range of medications and professional pharmaceutical consultation.</p>
                        <button class="btn btn-primary mt-3">Order Medicines</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-file-medical service-icon"></i>
                        <h3 class="mb-3">Health Records</h3>
                        <p>Secure digital health records management with easy access to your medical history and test reports.</p>
                        <button class="btn btn-primary mt-3">Access Records</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Testimonials Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">What Our <span style="background: linear-gradient(45deg, #28a745, #20c997); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Patients Say</span></h2>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Rajesh Kumar</h5>
                                <small class="text-muted">Cardiac Patient</small>
                            </div>
                        </div>
                        <p>The care I received at AROGYA was exceptional. The doctors were knowledgeable and compassionate. My heart surgery was successful and recovery was smooth.</p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Priya Sharma</h5>
                                <small class="text-muted">Maternity Care</small>
                            </div>
                        </div>
                        <p>AROGYA's maternity services are outstanding. The entire team supported me throughout my pregnancy and delivery. I'm grateful for their professional care.</p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Amit Patel</h5>
                                <small class="text-muted">Orthopedic Patient</small>
                            </div>
                        </div>
                        <p>The orthopedic department at AROGYA is world-class. My knee replacement surgery was a success, and the rehabilitation program was excellent.</p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="display-3 fw-bold mb-4">Ready to Experience <br>World-Class Healthcare?</h2>
            <p class="lead fs-4 mb-5">Join thousands of satisfied patients who trust AROGYA for their healthcare needs</p>
            <div class="d-flex justify-content-center gap-4">
                <button class="btn btn-light btn-lg px-5 py-3 rounded-pill">
                    <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                </button>
                <button class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">
                    <i class="fas fa-headset me-2"></i>Contact Us
                </button>
            </div>
        </div>
    </section>
    
    <!-- Interactive Footer -->
    <?php include("include/footer.php") ?>
    
    <!-- Floating Action Button -->
    <button class="fab" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animated counters
        function animateCounter(elementId, target, duration = 2000) {
            const element = document.getElementById(elementId);
            let start = 0;
            const increment = target / (duration / 16);
            
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    element.textContent = target + (elementId.includes('Count') ? '+' : '');
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(start) + (elementId.includes('Count') ? '+' : '');
                }
            }, 16);
        }
        
        // Initialize counters when section is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter('doctorCount', 150);
                    animateCounter('patientCount', 50000);
                    animateCounter('awardCount', 25);
                    animateCounter('researchCount', 100);
                }
            });
        });
        
        observer.observe(document.querySelector('.py-5'));
        
        // Smooth scrolling for CTA buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.textContent.includes('Book Appointment')) {
                    window.location.href = '<?php echo $localhost; ?>doctors_directory.php';
                } else if (this.textContent.includes('Call Now') || this.textContent.includes('Contact Us')) {
                    window.location.href = 'tel:<?php echo $hospital_info['phone_number']; ?>';
                }
            });
        });
        
        // Floating action button
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Show/hide FAB on scroll
        window.addEventListener('scroll', function() {
            const fab = document.querySelector('.fab');
            if (window.scrollY > 300) {
                fab.style.opacity = '1';
                fab.style.transform = 'scale(1)';
            } else {
                fab.style.opacity = '0';
                fab.style.transform = 'scale(0.8)';
            }
        });
        
        // Image slider functionality
        let currentImage = 0;
        const images = [
            'include/homepage_slider/logo.png',
            'include/homepage_slider/Picsart_25-02-15_01-17-09-154.jpg',
            'include/homepage_slider/We.jpeg'
        ];
        
        function changeImage() {
            const imgElement = document.querySelector('.slider-image');
            imgElement.style.opacity = '0';
            
            setTimeout(() => {
                currentImage = (currentImage + 1) % images.length;
                imgElement.src = images[currentImage];
                imgElement.style.opacity = '1';
            }, 500);
        }
        
        setInterval(changeImage, 4000);
    </script>
</body>
</html>