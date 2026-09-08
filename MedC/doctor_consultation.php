<?php
session_start();
include 'connection/config.php';
include 'include/prescription_helpers.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'doctor') {
    die('Error: Access denied.');
}

function resolveDoctorId(mysqli $conn): int
{
    if (!empty($_SESSION['doctor_id'])) {
        return (int) $_SESSION['doctor_id'];
    }

    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email === '') {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    $stmt = $conn->prepare('SELECT d_id FROM doctor WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return (int) ($_SESSION['user_id'] ?? 0);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($doctorId);
    $resolved = $stmt->fetch() ? (int) $doctorId : (int) ($_SESSION['user_id'] ?? 0);
    $stmt->close();

    return $resolved;
}

$doctor_id = resolveDoctorId($conn);
$appointment_id = (int) ($_GET['appointment_id'] ?? 0);

if ($appointment_id <= 0) {
    die('Invalid appointment.');
}

$stmt = $conn->prepare(
    "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason, a.status,
            p.pid, p.firstname, p.lastname, p.gender, p.dob, p.bloodgroup, p.contact_no, p.email,
            d.f_name, d.l_name
     FROM appointment a
     JOIN patient p ON a.pid = p.pid
     JOIN doctor d ON a.did = d.d_id
     WHERE a.appointment_id = ? AND a.did = ?"
);
$stmt->bind_param('ii', $appointment_id, $doctor_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die('Appointment not found.');
}

$patientName = trim($appointment['firstname'] . ' ' . $appointment['lastname']);
$doctorName = trim($appointment['f_name'] . ' ' . $appointment['l_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation - <?php echo escape($patientName); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Manrope', sans-serif;
            background: #f4f8ff;
            color: #15355a;
        }
        .page-header {
            background: #fff;
            padding: 20px 24px;
            border-radius: 16px;
            box-shadow: 0 16px 26px rgba(21, 53, 90, 0.08);
            margin-bottom: 20px;
        }
        .card-panel {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 14px 24px rgba(21, 53, 90, 0.08);
            margin-bottom: 20px;
        }
        .badge-soft {
            background: #e9f2ff;
            color: #1f5fbf;
        }
        .btn-primary {
            background: #1f5fbf;
            border: none;
        }
        .btn-primary:hover {
            background: #1a4f9a;
        }
        .medicine-row,
        .test-row {
            border-radius: 12px;
            padding: 12px;
            background: #f7faff;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1">Consultation</h3>
            <div class="text-muted">Appointment #<?php echo (int) $appointment['appointment_id']; ?> | <?php echo escape(date('d M Y', strtotime($appointment['appointment_date']))); ?> at <?php echo escape(date('h:i A', strtotime($appointment['appointment_time']))); ?></div>
        </div>
        <a class="btn btn-outline-primary" href="doctor_prescriptions.php"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
    </div>

    <div class="card-panel">
        <div class="row">
            <div class="col-md-7">
                <h5 class="mb-3">Patient Details</h5>
                <div class="row g-3">
                    <div class="col-sm-6"><strong>Name:</strong> <?php echo escape($patientName); ?></div>
                    <div class="col-sm-6"><strong>Gender:</strong> <?php echo escape($appointment['gender'] ?? 'N/A'); ?></div>
                    <div class="col-sm-6"><strong>DOB:</strong> <?php echo escape($appointment['dob'] ?? 'N/A'); ?></div>
                    <div class="col-sm-6"><strong>Blood Group:</strong> <?php echo escape($appointment['bloodgroup'] ?? 'N/A'); ?></div>
                    <div class="col-sm-6"><strong>Contact:</strong> <?php echo escape($appointment['contact_no'] ?? 'N/A'); ?></div>
                    <div class="col-sm-6"><strong>Email:</strong> <?php echo escape($appointment['email'] ?? 'N/A'); ?></div>
                </div>
            </div>
            <div class="col-md-5">
                <h5 class="mb-3">Consultation Info</h5>
                <p class="mb-2"><strong>Doctor:</strong> <?php echo escape($doctorName); ?></p>
                <p class="mb-2"><strong>Reason:</strong> <?php echo escape($appointment['reason'] ?? 'General consultation'); ?></p>
                <p class="mb-0"><strong>Status:</strong> <?php echo status_badge($appointment['status']); ?></p>
            </div>
        </div>
    </div>

    <form class="card-panel" method="POST" action="save_prescription.php">
        <input type="hidden" name="appointment_id" value="<?php echo (int) $appointment['appointment_id']; ?>">
        <h5 class="mb-3">Clinical Notes</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Symptoms</label>
                <textarea name="symptoms" class="form-control" rows="4" placeholder="Describe symptoms"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Diagnosis</label>
                <textarea name="diagnosis" class="form-control" rows="4" placeholder="Clinical diagnosis"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="4" placeholder="Additional notes"></textarea>
            </div>
        </div>

        <hr class="my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Prescription Form</h5>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addMedicineRow()"><i class="fa-solid fa-plus me-1"></i>Add Medicine</button>
        </div>
        <div id="medicineRows"></div>

        <hr class="my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Recommended Tests</h5>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTestRow()"><i class="fa-solid fa-plus me-1"></i>Add Test</button>
        </div>
        <div id="testRows"></div>

        <div class="row g-3 mt-3">
            <div class="col-md-4">
                <label class="form-label">Follow-up Date</label>
                <input type="date" name="follow_up_date" class="form-control">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="submit" name="action" value="save" class="btn btn-outline-primary">Save Prescription</button>
            <button type="submit" name="action" value="complete" class="btn btn-primary">Complete Consultation</button>
        </div>
    </form>
</div>

<script>
    const medicineRows = document.getElementById('medicineRows');
    const testRows = document.getElementById('testRows');

    function addMedicineRow() {
        const wrapper = document.createElement('div');
        wrapper.className = 'medicine-row';
        wrapper.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Medicine Name</label>
                    <input type="text" name="medicine_name[]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Generic Name</label>
                    <input type="text" name="generic_name[]" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dosage</label>
                    <input type="text" name="dosage[]" class="form-control" placeholder="500mg">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Frequency</label>
                    <select name="frequency[]" class="form-select">
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Night">Night</option>
                        <option value="Morning, Afternoon">Morning + Afternoon</option>
                        <option value="Morning, Night">Morning + Night</option>
                        <option value="Morning, Afternoon, Night">Morning + Afternoon + Night</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Before/After</label>
                    <select name="food_timing[]" class="form-select">
                        <option value="Before Food">Before Food</option>
                        <option value="After Food" selected>After Food</option>
                        <option value="With Food">With Food</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Days</label>
                    <input type="number" name="duration_days[]" class="form-control" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity[]" class="form-control" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Instructions</label>
                    <input type="text" name="instructions[]" class="form-control" placeholder="Notes">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.medicine-row').remove()"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;
        medicineRows.appendChild(wrapper);
    }

    function addTestRow() {
        const wrapper = document.createElement('div');
        wrapper.className = 'test-row';
        wrapper.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Test Name</label>
                    <input type="text" name="test_name[]" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" name="test_notes[]" class="form-control" placeholder="Preparation notes">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.test-row').remove()"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;
        testRows.appendChild(wrapper);
    }

    addMedicineRow();
    addTestRow();
</script>
</body>
</html>
