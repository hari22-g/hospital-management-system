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
    <title>FAQs - <?php echo htmlspecialchars($hospital_info['hospital_name']); ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .faq-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
            background: white;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .faq-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .faq-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-header:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
        }
        
        .faq-body {
            padding: 20px;
            display: none;
        }
        
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .faq-category {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .search-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .contact-card {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .contact-card a {
            color: white;
            text-decoration: underline;
        }
        
        .category-tab {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px 15px;
            margin-right: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .category-tab.active {
            background-color: #007bff;
            color: white;
        }
        
        .category-tab:hover {
            background-color: #e9ecef;
        }
        
        .category-tab.active:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="container mt-4">
        <h1 class="section-title text-center mb-4">Frequently Asked Questions</h1>
        
        <div class="search-container">
            <h3><i class="fas fa-search me-2"></i>Search for Answers</h3>
            <div class="input-group mb-3">
                <input type="text" id="faqSearch" class="form-control" placeholder="Type your question or keyword...">
                <button class="btn btn-primary" type="button" id="searchBtn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <p class="text-muted">Can't find your answer? Contact us directly.</p>
        </div>
        
        <div class="row mb-4">
            <div class="col-12">
                <h4>Filter by Category:</h4>
                <div class="mb-3">
                    <span class="category-tab active" data-category="all">All</span>
                    <span class="category-tab" data-category="admission">Admission</span>
                    <span class="category-tab" data-category="appointment">Appointment</span>
                    <span class="category-tab" data-category="insurance">Insurance</span>
                    <span class="category-tab" data-category="services">Services</span>
                    <span class="category-tab" data-category="general">General</span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="faq-category">General Information</div>
                
                <div class="faq-card" data-category="general">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>What are the hospital visiting hours?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p><strong>General Ward:</strong> 10:00 AM - 11:00 AM and 5:00 PM - 7:00 PM</p>
                        <p><strong>ICU Visiting Hours:</strong> 11:00 AM - 12:00 PM and 5:00 PM - 6:00 PM (Limited visitors)</p>
                        <p><strong>Emergency:</strong> 24/7</p>
                    </div>
                </div>
                
                <div class="faq-card" data-category="general">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>How can I find a doctor?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>You can find our doctors through:</p>
                        <ul>
                            <li>Our <a href="doctors_directory.php">Doctors Directory</a> page</li>
                            <li>Searching by specialization on our website</li>
                            <li>Calling our helpline at <?php echo htmlspecialchars($hospital_info['phone_number']); ?></li>
                            <li>Visiting our OPD directly during working hours</li>
                        </ul>
                    </div>
                </div>
                
                <div class="faq-card" data-category="general">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>What facilities are available at the hospital?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>Our hospital offers:</p>
                        <ul>
                            <li>24/7 Emergency Services</li>
                            <li>Advanced Diagnostic Imaging (MRI, CT Scan, X-Ray, Ultrasound)</li>
                            <li>Well-equipped Operation Theaters</li>
                            <li>Intensive Care Units (ICU, CCU, NICU)</li>
                            <li>Pharmacy Services</li>
                            <li>Pathology Laboratory</li>
                            <li>Cardiology Services</li>
                            <li>Orthopedics, Neurology, Gynecology, and other specialized departments</li>
                        </ul>
                    </div>
                </div>
                
                <div class="faq-category">Admission Process</div>
                
                <div class="faq-card" data-category="admission">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>What documents are required for admission?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>Please bring the following documents for admission:</p>
                        <ul>
                            <li>Valid Government-issued Photo ID (Aadhaar, PAN, Driving License, Passport)</li>
                            <li>Doctor's referral letter or prescription</li>
                            <li>Insurance documents (if applicable)</li>
                            <li>Previous medical records and test reports</li>
                            <li>Medicare card (if enrolled)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="faq-card" data-category="admission">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>How do I get admitted to the hospital?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>The admission process includes:</p>
                        <ol>
                            <li>Visit the admission desk at the front lobby</li>
                            <li>Fill out the registration form with personal details</li>
                            <li>Submit required documents</li>
                            <li>Complete payment or insurance formalities</li>
                            <li>Room allocation based on availability and preference</li>
                            <li>Nursing staff will escort you to your room</li>
                        </ol>
                    </div>
                </div>
                
                <div class="faq-card" data-category="admission">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>What are the room categories available?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>We offer the following room categories:</p>
                        <ul>
                            <li><strong>General Ward:</strong> Shared accommodation with basic amenities</li>
                            <li><strong>Semi-Private Room:</strong> Accommodates two patients with separate beds</li>
                            <li><strong>Private Room:</strong> Individual room with premium amenities</li>
                            <li><strong>Deluxe Suite:</strong> Luxury accommodation with enhanced comfort and services</li>
                            <li><strong>ICU Rooms:</strong> Intensive care units with advanced monitoring</li>
                        </ul>
                    </div>
                </div>
                
                <div class="faq-category">Appointment & Consultation</div>
                
                <div class="faq-card" data-category="appointment">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>How can I book an appointment?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>You can book an appointment through:</p>
                        <ul>
                            <li>Our <a href="doctors_directory.php">Online Appointment System</a></li>
                            <li>Calling our appointment desk at <?php echo htmlspecialchars($hospital_info['phone_number']); ?></li>
                            <li>Visiting the hospital directly</li>
                            <li>Using our mobile app (coming soon)</li>
                        </ul>
                        <p>Appointments can be booked up to 30 days in advance.</p>
                    </div>
                </div>
                
                <div class="faq-card" data-category="appointment">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>Can I reschedule or cancel my appointment?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>Yes, you can reschedule or cancel your appointment:</p>
                        <ul>
                            <li>Minimum 24 hours' notice required for changes</li>
                            <li>Log in to your patient portal</li>
                            <li>Call our appointment desk</li>
                            <li>Visit the hospital's appointment counter</li>
                        </ul>
                        <p>Same-day cancellations may incur a fee depending on the doctor's policy.</p>
                    </div>
                </div>
                
                <div class="faq-category">Insurance & Payments</div>
                
                <div class="faq-card" data-category="insurance">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>Does the hospital accept insurance?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>Yes, we accept:</p>
                        <ul>
                            <li>All major health insurance policies</li>
                            <li>Corporate health insurance plans</li>
                            <li>Government health schemes (Ayushman Bharat, State Health Schemes)</li>
                            <li>Third Party Administrator (TPA) cards</li>
                        </ul>
                        <p>For cashless facility, please visit our insurance desk with your insurance card before consultation.</p>
                    </div>
                </div>
                
                <div class="faq-card" data-category="insurance">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>How does the cashless claim process work?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>For cashless claims:</p>
                        <ol>
                            <li>Present your insurance card at the TPA desk</li>
                            <li>Submit required documents (ID proof, insurance card, referral)</li>
                            <li>Our team initiates pre-authorization with your insurance company</li>
                            <li>Upon approval, receive treatment without paying upfront</li>
                            <li>We handle the claim settlement directly with the insurer</li>
                        </ol>
                    </div>
                </div>
                
                <div class="faq-category">Hospital Services</div>
                
                <div class="faq-card" data-category="services">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>What diagnostic services are available?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>Our diagnostic services include:</p>
                        <ul>
                            <li>MRI (Magnetic Resonance Imaging)</li>
                            <li>CT Scan (Computed Tomography)</li>
                            <li>X-Ray Services</li>
                            <li>Ultrasound and Color Doppler</li>
                            <li>Pathology Laboratory Tests</li>
                            <li>Cardiac Diagnostics (ECG, Echo, TMT)</li>
                            <li>Endoscopy Services</li>
                            <li>Mammography</li>
                        </ul>
                    </div>
                </div>
                
                <div class="faq-card" data-category="services">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>Is there parking available?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>Yes, we have:</p>
                        <ul>
                            <li>Spacious parking area for patients and visitors</li>
                            <li>Reserved parking for differently-abled patients</li>
                            <li>24/7 security for vehicles</li>
                            <li>Free parking for emergency cases</li>
                        </ul>
                        <p>Parking charges apply for regular visits (Rs. 20 per entry).</p>
                    </div>
                </div>
                
                <div class="faq-card" data-category="services">
                    <div class="faq-header" onclick="toggleFAQ(this)">
                        <span>Are there food and dining options?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-body">
                        <p>Yes, we offer:</p>
                        <ul>
                            <li>Cafeteria serving hygienic meals</li>
                            <li>Room service for admitted patients</li>
                            <li>Special dietary options as per medical requirements</li>
                            <li>Pharmacy for nutritional supplements</li>
                        </ul>
                        <p>Visitors can purchase meals from the cafeteria on the ground floor.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="contact-card">
            <h4><i class="fas fa-comments me-2"></i>Still Have Questions?</h4>
            <p>Our friendly staff is ready to help you with any additional questions you may have.</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <p><i class="fas fa-phone me-2"></i>Helpline: <a href="tel:<?php echo htmlspecialchars($hospital_info['phone_number']); ?>"><?php echo htmlspecialchars($hospital_info['phone_number']); ?></a></p>
                </div>
                <div class="col-md-4 mb-3">
                    <p><i class="fas fa-envelope me-2"></i>Email: info@medchospital.com</p>
                </div>
                <div class="col-md-4 mb-3">
                    <p><i class="fas fa-map-marker-alt me-2"></i>Address: <?php echo htmlspecialchars($hospital_info['address']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleFAQ(element) {
            const faqBody = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            // Close all other FAQ bodies in the same category
            const parent = element.parentElement.parentElement;
            const allBodies = parent.querySelectorAll('.faq-body');
            const allIcons = parent.querySelectorAll('.faq-header i');
            
            allBodies.forEach(body => {
                if (body !== faqBody) {
                    body.style.display = 'none';
                }
            });
            
            allIcons.forEach(iconItem => {
                iconItem.className = 'fas fa-chevron-down';
            });
            
            if (faqBody.style.display === 'block') {
                faqBody.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
            } else {
                faqBody.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
            }
        }
        
        // Filter FAQs by category
        $(document).ready(function() {
            $('.category-tab').click(function() {
                const category = $(this).data('category');
                
                // Update active tab
                $('.category-tab').removeClass('active');
                $(this).addClass('active');
                
                // Show/hide FAQs based on category
                if (category === 'all') {
                    $('.faq-card').show();
                } else {
                    $('.faq-card').hide();
                    $(`.faq-card[data-category="${category}"]`).show();
                }
            });
            
            // Search functionality
            $('#faqSearch').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                if (searchTerm.length === 0) {
                    // If search is cleared, show all FAQs in current category
                    const activeCategory = $('.category-tab.active').data('category');
                    if (activeCategory === 'all') {
                        $('.faq-card').show();
                    } else {
                        $('.faq-card').hide();
                        $(`.faq-card[data-category="${activeCategory}"]`).show();
                    }
                    return;
                }
                
                $('.faq-card').each(function() {
                    const question = $(this).find('.faq-header span').text().toLowerCase();
                    const answer = $(this).find('.faq-body').text().toLowerCase();
                    
                    if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Search button click event
            $('#searchBtn').click(function() {
                const searchTerm = $('#faqSearch').val().toLowerCase();
                
                if (searchTerm.length === 0) {
                    // If search is cleared, show all FAQs in current category
                    const activeCategory = $('.category-tab.active').data('category');
                    if (activeCategory === 'all') {
                        $('.faq-card').show();
                    } else {
                        $('.faq-card').hide();
                        $(`.faq-card[data-category="${activeCategory}"]`).show();
                    }
                    return;
                }
                
                $('.faq-card').each(function() {
                    const question = $(this).find('.faq-header span').text().toLowerCase();
                    const answer = $(this).find('.faq-body').text().toLowerCase();
                    
                    if (question.includes(searchTerm) || answer.includes(searchTerm)) {
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