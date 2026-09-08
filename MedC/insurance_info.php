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
    <title>Insurance & TPA - <?php echo htmlspecialchars($hospital_info['hospital_name']); ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .info-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            background: white;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .info-header {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            padding: 15px 20px;
        }
        
        .info-body {
            padding: 20px;
        }
        
        .info-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .info-description {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .provider-logo {
            width: 100px;
            height: 60px;
            background-color: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px;
            font-size: 2rem;
            color: #6c757d;
        }
        
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .process-step {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
            .process-step:last-child {
            margin-bottom: 0;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .step-content {
            flex-grow: 1;
        }
        
        .faq-item {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .faq-question {
            background-color: #f8f9fa;
            padding: 15px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-answer {
            padding: 15px;
            display: none;
        }
        
        .accepted-insurance {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .contact-card {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .contact-card a {
            color: white;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="container mt-4">
        <h1 class="section-title text-center mb-4">Insurance & TPA Information</h1>
        
        <!-- Insurance Overview -->
        <div class="row mb-5">
            <div class="col-md-8">
                <div class="info-card">
                    <div class="info-header">
                        <h3><i class="fas fa-shield-alt me-2"></i>Insurance & Cashless Facility</h3>
                    </div>
                    <div class="info-body">
                        <p class="info-description">
                            At <?php echo htmlspecialchars($hospital_info['hospital_name']); ?>, we believe in providing quality healthcare without financial stress. 
                            We offer cashless treatment facilities through various insurance providers and TPA schemes.
                        </p>
                        
                        <h5><i class="fas fa-check-circle me-2"></i>Our Insurance Partners</h5>
                        <p>We accept all major insurance providers and Third Party Administrators (TPAs) offering cashless facilities.</p>
                        
                        <div class="accepted-insurance">
                            <div class="provider-logo">
                                <i class="fas fa-umbrella"></i>
                            </div>
                            <div class="provider-logo">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <div class="provider-logo">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="provider-logo">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="provider-logo">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="provider-logo">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                        </div>
                        
                        <h5 class="mt-4"><i class="fas fa-file-contract me-2"></i>Accepted Insurance Companies</h5>
                        <ul class="mt-2">
                            <li>Star Health and Allied Insurance Co. Ltd.</li>
                            <li>HDFC ERGO General Insurance Co. Ltd.</li>
                            <li>ICICI Lombard General Insurance Co. Ltd.</li>
                            <li>Max Bupa Health Insurance Co. Ltd.</li>
                            <li>Religare Health Insurance Co. Ltd.</li>
                            <li>United India Insurance Co. Ltd.</li>
                            <li>New India Assurance Co. Ltd.</li>
                            <li>Universal Sompo General Insurance Co. Ltd.</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="contact-card">
                    <h4><i class="fas fa-headset me-2"></i>Need Help?</h4>
                    <p>For insurance-related queries, please contact our dedicated team:</p>
                    <p><i class="fas fa-phone me-2"></i>Phone: <a href="tel:<?php echo htmlspecialchars($hospital_info['phone_number']); ?>"><?php echo htmlspecialchars($hospital_info['phone_number']); ?></a></p>
                    <p><i class="fas fa-envelope me-2"></i>Email: insurance@medchospital.com</p>
                    <p><i class="fas fa-clock me-2"></i>Timing: 24/7 Support</p>
                </div>
                
                <div class="info-card mt-4">
                    <div class="info-header">
                        <h4><i class="fas fa-percent me-2"></i>Benefits</h4>
                    </div>
                    <div class="info-body">
                        <ul>
                            <li>Cashless treatment facility</li>
                            <li>Direct settlement with insurance companies</li>
                            <li>Quick claim processing</li>
                            <li>Network discounts</li>
                            <li>Pre-authorization assistance</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cashless Process -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="info-card">
                    <div class="info-header">
                        <h3><i class="fas fa-wallet me-2"></i>Cashless Treatment Process</h3>
                    </div>
                    <div class="info-body">
                        <p class="info-description">
                            Follow these simple steps to avail cashless treatment at our hospital:
                        </p>
                        
                        <div class="process-steps">
                            <div class="process-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h5>Visit our TPA Desk</h5>
                                    <p>Reach our hospital and visit the TPA/Insurance desk with your insurance card and valid ID proof.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h5>Submit Documents</h5>
                                    <p>Submit required documents including insurance card, ID proof, and referral letter if applicable.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h5>Pre-Authorization</h5>
                                    <p>Our team initiates the pre-authorization process with your insurance company/TPA.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h5>Receive Approval</h5>
                                    <p>Get instant approval for planned treatments or within 30 minutes for emergency cases.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h5>Avail Treatment</h5>
                                    <p>Receive world-class medical treatment without paying anything upfront.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- TPA Information -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="info-card">
                    <div class="info-header">
                        <h3><i class="fas fa-handshake me-2"></i>Third Party Administrator (TPA) Network</h3>
                    </div>
                    <div class="info-body">
                        <p class="info-description">
                            We are associated with leading TPAs to facilitate smooth cashless treatment for our patients:
                        </p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Active TPA Partners:</h5>
                                <ul>
                                    <li>Medi Assist India TPA Services Pvt. Ltd.</li>
                                    <li>Universal Mediclaim TPA Pvt. Ltd.</li>
                                    <li>ESKONI TPA Services Pvt. Ltd.</li>
                                    <li>Paramount Health Services TPA Pvt. Ltd.</li>
                                    <li>Global Health Servicing TPA Pvt. Ltd.</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Services Provided:</h5>
                                <ul>
                                    <li>Pre-authorization processing</li>
                                    <li>Claim settlement</li>
                                    <li>Customer support</li>
                                    <li>Network management</li>
                                    <li>Fraud prevention</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="info-card">
                    <div class="info-header">
                        <h3><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h3>
                    </div>
                    <div class="info-body">
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                What documents are required for cashless treatment?
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>You need to provide:</p>
                                <ul>
                                    <li>Valid insurance card</li>
                                    <li>Government-issued photo ID (Aadhaar, PAN, Driving License, Passport)</li>
                                    <li>Doctor's prescription/referral letter (for planned treatments)</li>
                                    <li>Previous medical records (if any)</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                How long does the pre-authorization process take?
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>For planned treatments, pre-authorization typically takes 30-60 minutes during business hours. For emergency cases, we provide approval within 30 minutes. The timeline may vary based on the insurance company's policies and the complexity of the treatment.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                Can I upgrade my room category during cashless treatment?
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Yes, you can upgrade your room category, but the additional charges beyond the approved package will need to be paid by you. The insurance company covers the approved room category as per your policy terms.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                Is there a limit on cashless treatment?
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Yes, the cashless facility is subject to the sum insured as per your policy. Any expenses exceeding the policy limit will need to be settled by the patient. Pre-authorization is done within the policy limits and approved by the insurance company.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                What if my claim gets rejected?
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>If your claim is rejected, you will be notified immediately with the reason for rejection. You can then opt for self-payment for the treatment. Our TPA desk will assist you in understanding the rejection reason and help with any appeal process if applicable.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            if (answer.style.display === 'block') {
                answer.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                answer.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
    </script>
    
    <?php include("include/footer.php"); ?>
</body>

</html>