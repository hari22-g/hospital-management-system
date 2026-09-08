<?php
session_start();
// Include the database connection and hospital info
include("connection/config.php");
include("include/hospital_info.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
$hospital_info = getHospitalInfo();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>My Reports - <?php echo htmlspecialchars($hospital_info['hospital_name']); ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .report-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            background: white;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .report-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 20px;
        }
        
        .report-body {
            padding: 20px;
        }
        
        .report-type {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .report-meta {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .report-actions {
            margin-top: 15px;
        }
        
        .download-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .download-btn:hover {
            background-color: #218838;
            color: white;
            text-decoration: none;
        }
        
        .upload-section {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #e9ecef;
            margin-bottom: 20px;
        }
        
        .file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .file-upload:hover {
            border-color: #007bff;
        }
        
        .file-upload i {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="container mt-4">
        <h1 class="section-title text-center mb-4">My Medical Reports</h1>
        
        <div class="upload-section">
            <h3><i class="fas fa-cloud-upload-alt me-2"></i>Upload New Report</h3>
            <p>Upload your medical reports, prescriptions, and test results securely to your personal health record.</p>
            
            <form id="uploadReportForm" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4">
                        <label for="reportType" class="form-label">Report Type:</label>
                        <select class="form-select" id="reportType" name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="Blood Test">Blood Test</option>
                            <option value="X-Ray">X-Ray</option>
                            <option value="MRI">MRI</option>
                            <option value="CT Scan">CT Scan</option>
                            <option value="Ultrasound">Ultrasound</option>
                            <option value="ECG">ECG</option>
                            <option value="Prescription">Prescription</option>
                            <option value="Discharge Summary">Discharge Summary</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="referredBy" class="form-label">Referred By:</label>
                        <input type="text" class="form-control" id="referredBy" name="referred_by" placeholder="Doctor/Hospital Name" required>
                    </div>
                    <div class="col-md-4">
                        <label for="reportDate" class="form-label">Report Date:</label>
                        <input type="date" class="form-control" id="reportDate" name="report_date" required>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="file-upload" id="dropZone">
                        <i class="fas fa-file-upload"></i>
                        <p>Drag & drop your report here or click to browse</p>
                        <input type="file" class="form-control" id="reportFile" name="report_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display: none;" required>
                        <small class="text-muted">Supported formats: PDF, JPG, PNG, DOC, DOCX (Max size: 10MB)</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload Report
                    </button>
                </div>
            </form>
        </div>
        
        <div class="row">
            <div class="col-12">
                <h3><i class="fas fa-history me-2"></i>Uploaded Reports</h3>
                
                <div class="table-responsive">
                    <table class="table table-striped" id="reportsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Report Type</th>
                                <th>Referred By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reportsTableBody">
                            <!-- Reports will be loaded here dynamically -->
                        </tbody>
                    </table>
                </div>
                
                <div id="noReportsMessage" class="empty-state" style="display: none;">
                    <i class="fas fa-file-medical"></i>
                    <h4>No Reports Found</h4>
                    <p>You haven't uploaded any medical reports yet. Upload your first report using the form above.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load reports when page loads
            loadReports();
            
            // Handle file upload click
            $('#dropZone').click(function() {
                $('#reportFile').click();
            });
            
            // Handle file selection
            $('#reportFile').change(function() {
                if(this.files && this.files[0]) {
                    const fileName = this.files[0].name;
                    $('#dropZone p').text(fileName);
                }
            });
            
            // Handle drag and drop
            $('#dropZone').on('dragover', function(e) {
                e.preventDefault();
                $(this).css('border-color', '#007bff');
            });
            
            $('#dropZone').on('dragleave', function(e) {
                e.preventDefault();
                $(this).css('border-color', '#dee2e6');
            });
            
            $('#dropZone').on('drop', function(e) {
                e.preventDefault();
                $(this).css('border-color', '#dee2e6');
                
                const files = e.originalEvent.dataTransfer.files;
                if(files.length) {
                    $('#reportFile')[0].files = files;
                    $('#dropZone p').text(files[0].name);
                }
            });
            
            // Handle form submission
            $('#uploadReportForm').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                $.ajax({
                    url: 'upload_report.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const result = JSON.parse(response);
                        if(result.success) {
                            alert('Report uploaded successfully!');
                            $('#uploadReportForm')[0].reset();
                            $('#dropZone p').text('Drag & drop your report here or click to browse');
                            loadReports(); // Reload reports after upload
                        } else {
                            alert('Error: ' + result.message);
                        }
                    },
                    error: function() {
                        alert('Error uploading report. Please try again.');
                    }
                });
            });
        });
        
        function loadReports() {
            $.ajax({
                url: 'views/Dashboard/fetch_reports.php',
                method: 'GET',
                dataType: 'json',
                success: function(reports) {
                    const tbody = $('#reportsTableBody');
                    tbody.empty();
                    
                    if(reports.length > 0) {
                        $('#noReportsMessage').hide();
                        
                        reports.forEach(function(report) {
                            const row = `
                                <tr>
                                    <td>${report.report_type}</td>
                                    <td>${report.referred_by}</td>
                                    <td>${report.report_date}</td>
                                    <td>
                                        <a href="${report.file_path}" target="_blank" class="download-btn">
                                            <i class="fas fa-download me-1"></i>Download
                                        </a>
                                    </td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    } else {
                        $('#noReportsMessage').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading reports:', error);
                    $('#reportsTableBody').html(`<tr><td colspan="4">Error loading reports: ${error}</td></tr>`);
                }
            });
        }
    </script>
    
    <?php include("include/footer.php"); ?>
</body>

</html>