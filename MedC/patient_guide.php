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
    <title>Patient Guide - <?php echo htmlspecialchars($hospital_info['hospital_name']); ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .guide-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            background: white;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .guide-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .guide-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 20px;
        }
        
        .guide-body {
            padding: 20px;
        }
        
        .guide-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .guide-description {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .process-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
            padding: 15px;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
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
            font-weight: bold;
        }
        
        .step-content h5 {
            margin-bottom: 8px;
            color: #007bff;
        }
        
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .important-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .checklist-item {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
        }
        
        .checklist-item i {
            color: #28a745;
            margin-right: 10px;
            margin-top: 5px;
        }
        
        .contact-info {
            background-color: #e7f3ff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .icon-large {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #007bff;
            left: 20px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 50px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #007bff;
            left: 11px;
            top: 5px;
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="container mt-4">
        <h1 class="section-title text-center mb-4">Patient Guide</h1>
        
        <div class="row mb-5">
            <div class="col-md-8">
                <div class="guide-card">
                    <div class="guide-header">
                        <h3><i class="fas fa-info-circle me-2"></i>Welcome to <?php echo htmlspecialchars($hospital_info['hospital_name']); ?></h3>
                    </div>
                    <div class="guide-body">
                        <p class="guide-description">
                            This guide provides comprehensive information about our hospital processes, helping you navigate your healthcare journey smoothly and comfortably.
                        </p>
                        
                        <div class="important-note">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> Please carry a valid government-issued photo ID (Aadhaar, PAN, Driving License, or Passport) during your visit to the hospital.
                        </div>
                        
                        <h5><i class="fas fa-user-plus me-2"></i>Before Your Visit</h5>
                        <div class="checklist">
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Carry your appointment confirmation (if any)</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Bring your insurance card (if applicable)</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Carry previous medical records and prescriptions</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>List of current medications</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="guide-card text-center">
                    <div class="icon-large">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="guide-body">
                        <h4 class="guide-title">Hospital Hours</h4>
                        <p><i class="fas fa-clock me-2"></i>24/7 Emergency Services</p>
                        <p><i class="fas fa-calendar-alt me-2"></i>OPD: Mon-Sat 8:00 AM - 8:00 PM</p>
                        <p><i class="fas fa-phone me-2"></i>Helpline: <?php echo htmlspecialchars($hospital_info['phone_number']); ?></p>
                    </div>
                </div>
                
                <div class="guide-card mt-4">
                    <div class="guide-header">
                        <h4><i class="fas fa-map-marker-alt me-2"></i>Directions</h4>
                    </div>
                    <div class="guide-body">
                        <p><i class="fas fa-map-marked-alt me-2"></i><?php echo htmlspecialchars($hospital_info['address']); ?></p>
                        <?php if(!empty($hospital_info['google_maps_link'])): ?>
                            <a href="<?php echo htmlspecialchars($hospital_info['google_maps_link']); ?>" target="_blank" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-directions me-1"></i>Get Directions
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Admission Process -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="guide-card">
                    <div class="guide-header">
                        <h3><i class="fas fa-procedures me-2"></i>Admission Process</h3>
                    </div>
                    <div class="guide-body">
                        <p class="guide-description">
                            Follow these steps for a smooth admission process:
                        </p>
                        
                        <div class="timeline">
                            <div class="timeline-item">
                                <h5>Registration</h5>
                                <p>Visit the admission desk at the front lobby with your valid ID proof. Fill out the registration form and provide necessary details.</p>
                            </div>
                            
                            <div class="timeline-item">
                                <h5>Payment & Insurance</h5>
                                <p>Complete payment formalities or initiate insurance claim process. Our staff will assist with pre-authorization if you have insurance.</p>
                            </div>
                            
                            <div class="timeline-item">
                                <h5>Room Allocation</h5>
                                <p>Based on availability and your preference (subject to doctor's recommendation), you will be allocated a room.</p>
                            </div>
                            
                            <div class="timeline-item">
                                <h5>Nursing Care</h5>
                                <p>Nursing staff will escort you to your room and provide orientation about hospital facilities and services.</p>
                            </div>
                            
                            <div class="timeline-item">
                                <h5>Doctor Consultation</h5>
                                <p>Your attending physician will visit to conduct examination and discuss treatment plan.</p>
                            </div>
                        </div>
                        
                        <div class="important-note">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Emergency Admissions:</strong> For emergency admissions, proceed directly to the Emergency Department where our staff will guide you through the process.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Discharge Process -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="guide-card">
                    <div class="guide-header">
                        <h3><i class="fas fa-clipboard-check me-2"></i>Discharge Process</h3>
                    </div>
                    <div class="guide-body">
                        <p class="guide-description">
                            Steps to follow for discharge:
                        </p>
                        
                        <div class="process-steps">
                            <div class="process-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h5>Discharge Summary</h5>
                                    <p>Your doctor will prepare a discharge summary including diagnosis, treatment received, medications prescribed, and follow-up instructions.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h5>Final Billing</h5>
                                    <p>Visit the billing department to settle any outstanding dues. Request itemized bills if needed.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h5>Medication Collection</h5>
                                    <p>Collect prescribed medications from our hospital pharmacy.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h5>Feedback</h5>
                                    <p>Share your feedback about our services to help us improve.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h5>Departure</h5>
                                    <p>Collect all personal belongings and exit through the main lobby. Our staff can assist with transportation arrangements if needed.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="important-note">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> All discharge procedures must be completed by authorized personnel. Do not leave the hospital premises without proper discharge formalities.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Patient Rights & Responsibilities -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="guide-card">
                    <div class="guide-header">
                        <h4><i class="fas fa-balance-scale me-2"></i>Patient Rights</h4>
                    </div>
                    <div class="guide-body">
                        <div class="checklist">
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Right to receive quality healthcare</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Right to informed consent</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Right to privacy and confidentiality</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Right to review medical records</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Right to second opinion</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Right to respectful treatment</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="guide-card">
                    <div class="guide-header">
                        <h4><i class="fas fa-users me-2"></i>Patient Responsibilities</h4>
                    </div>
                    <div class="guide-body">
                        <div class="checklist">
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Provide accurate medical history</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Follow prescribed treatment plans</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Respect hospital property and staff</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Pay for services rendered</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Inform about insurance details</div>
                            </div>
                            <div class="checklist-item">
                                <i class="fas fa-check-circle"></i>
                                <div>Comply with hospital policies</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="row">
            <div class="col-12">
                <div class="contact-info">
                    <h4><i class="fas fa-phone-alt me-2"></i>Need Assistance?</h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <p><i class="fas fa-phone me-2"></i>General Helpline: <?php echo htmlspecialchars($hospital_info['phone_number']); ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <p><i class="fas fa-ambulance me-2"></i>Emergency: <?php echo htmlspecialchars($hospital_info['emergency_helpline']); ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <p><i class="fas fa-clock me-2"></i>OPD Timings: <?php echo htmlspecialchars($hospital_info['opd_timings']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <p><i class="fas fa-user-md me-2"></i>Admission Desk: Ext. 101</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <p><i class="fas fa-credit-card me-2"></i>Billing: Ext. 102</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <p><i class="fas fa-shield-alt me-2"></i>Insurance: Ext. 103</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("include/footer.php"); ?>
</body>

</html>