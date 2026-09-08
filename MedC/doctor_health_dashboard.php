<?php
session_start();
include('connection/config.php');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'doctor') {
    die('Error: Access denied. Only doctors can access this page.');
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

$doctorId = resolveDoctorId($conn);
$doctorName = $_SESSION['f_name'] ?? 'Doctor';
$localhost = "http://" . $_SERVER['SERVER_NAME'] . "/MedC/MedC/";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Health Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --bg-main: #f4f7fb;
            --panel: #ffffff;
            --line: #dbe3f0;
            --ink: #14213d;
            --muted: #5e6472;
            --ok: #1f7a4f;
            --warn: #b7791f;
            --danger: #b42318;
            --brand: #0b3d91;
            --brand-soft: #d9e8ff;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top right, #e7f1ff 0%, var(--bg-main) 35%, #eef4ff 100%);
            color: var(--ink);
            min-height: 100vh;
        }

        .layout-wrap {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        .side-nav {
            background: linear-gradient(170deg, #ffffff 0%, #edf3ff 100%);
            border-right: 1px solid var(--line);
            padding: 22px 16px;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .brand {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand);
            margin-bottom: 28px;
        }

        .nav-btn {
            border: none;
            width: 100%;
            text-align: left;
            padding: 11px 12px;
            margin-bottom: 8px;
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            font-weight: 600;
        }

        .nav-btn:hover,
        .nav-btn.active {
            background: var(--brand-soft);
            color: var(--brand);
        }

        .main-area {
            padding: 22px;
        }

        .topbar {
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 14px 18px;
            backdrop-filter: blur(4px);
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .status-chip {
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
            display: inline-block;
        }

        .patient-row {
            cursor: pointer;
        }

        .patient-row.selected-patient-row {
            background: #eaf3ff;
        }

        .critical {
            background: #fde8e8;
            color: var(--danger);
            border-color: #f5c2c2;
        }

        .moderate {
            background: #fff4d6;
            color: var(--warn);
            border-color: #f2de9d;
        }

        .stable {
            background: #dcfce7;
            color: var(--ok);
            border-color: #9ae6b4;
        }

        .dash-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 14px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(26, 58, 109, 0.05);
            padding: 14px;
        }

        .panel.is-hidden {
            display: none;
        }

        .metric-card {
            grid-column: span 3;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }

        .priority-panel {
            grid-column: span 5;
        }

        .alert-panel {
            grid-column: span 7;
        }

        .patient-panel,
        .detail-panel,
        .notes-panel,
        .appoint-panel,
        .rx-panel,
        .files-panel,
        .message-panel,
        .report-panel {
            grid-column: span 12;
        }

        .mini-table {
            width: 100%;
            font-size: 13px;
            border-collapse: collapse;
        }

        .mini-table th,
        .mini-table td {
            padding: 8px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }

        .patient-row {
            cursor: pointer;
        }

        .patient-row:hover {
            background: #f6f9ff;
        }

        .form-block {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 10px;
        }

        .full {
            grid-column: span 12;
        }

        .half {
            grid-column: span 6;
        }

        .third {
            grid-column: span 4;
        }

        .notification {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1200;
            min-width: 260px;
            display: none;
        }

        .timeline-item {
            border-left: 3px solid var(--brand);
            padding-left: 10px;
            margin-bottom: 10px;
        }

        .vital-bad {
            background: #fee2e2;
            color: #991b1b;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
        }

        .loading-layer {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.6);
            z-index: 1100;
            justify-content: center;
            align-items: center;
        }

        @media (max-width: 992px) {
            .layout-wrap {
                grid-template-columns: 1fr;
            }

            .side-nav {
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 1px solid var(--line);
            }

            .metric-card {
                grid-column: span 6;
            }

            .priority-panel,
            .alert-panel {
                grid-column: span 12;
            }
        }

        @media (max-width: 640px) {
            .metric-card,
            .half,
            .third {
                grid-column: span 12;
            }
        }
    </style>
</head>

<body>
    <div class="layout-wrap">
        <aside class="side-nav">
            <div class="brand">MedC</div>
            <button class="nav-btn active">
                <i class="fa-solid fa-heart-pulse me-2"></i>Patient Health Hub
            </button>
            <button class="nav-btn" onclick="showAppointmentOnly()">
                <i class="fa-solid fa-calendar-check me-2"></i>Appointments
            </button>
            <button class="nav-btn" onclick="window.location.href='logout.php'">
                <i class="fa-solid fa-right-from-bracket me-2"></i>Log Out
            </button>
        </aside>

        <main class="main-area">
            <div class="topbar">
                <div>
                    <h4 class="mb-1">Doctor Health Management Dashboard</h4>
                    <div class="text-muted small">Fast clinical decisions with real-time patient signals</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="fw-semibold">Welcome, Dr. <?php echo htmlspecialchars($doctorName); ?></div>
                    <a class="btn btn-outline-primary btn-sm" href="<?php echo $localhost; ?>homepage.php">
                        <i class="fa-solid fa-arrow-left me-1"></i>Back to Home
                    </a>
                </div>
            </div>

            <div class="dash-grid">
                <div class="panel metric-card">
                    <div class="text-muted">Assigned Patients</div>
                    <div id="metricAssigned" class="metric-value">0</div>
                </div>
                <div class="panel metric-card">
                    <div class="text-muted">Critical Patients</div>
                    <div id="metricCritical" class="metric-value" style="color: var(--danger);">0</div>
                </div>
                <div class="panel metric-card">
                    <div class="text-muted">Stable Patients</div>
                    <div id="metricStable" class="metric-value" style="color: var(--ok);">0</div>
                </div>
                <div class="panel metric-card">
                    <div class="text-muted">Recent Updates (7d)</div>
                    <div id="metricRecent" class="metric-value">0</div>
                </div>

                <section class="panel priority-panel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Today's Priority Patients</h6>
                        <button class="btn btn-sm btn-outline-primary" onclick="loadOverview()">Refresh</button>
                    </div>
                    <div id="priorityList" class="small text-muted">No priority patients found.</div>
                </section>

                <section class="panel alert-panel">
                    <h6 class="mb-2">Alerts & Monitoring</h6>
                    <div id="alertFeed" class="small text-muted">Select a patient to see alerts.</div>
                </section>

                <section class="panel patient-panel">
                    <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                        <div>
                            <label class="form-label mb-1 small">Search</label>
                            <input id="searchInput" class="form-control" placeholder="Name, ID, condition">
                        </div>
                        <div>
                            <label class="form-label mb-1 small">Search By</label>
                            <select id="searchField" class="form-select">
                                <option value="all">All</option>
                                <option value="name">Name</option>
                                <option value="id">ID</option>
                                <option value="condition">Condition</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label mb-1 small">Condition</label>
                            <select id="conditionFilter" class="form-select">
                                <option value="all">All</option>
                                <option value="critical">Critical</option>
                                <option value="moderate">Moderate</option>
                                <option value="stable">Stable</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" onclick="loadPatients()"><i class="fa-solid fa-filter me-1"></i>Apply</button>
                    </div>
                    <div class="table-responsive">
                        <table class="mini-table" id="patientTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Condition</th>
                                <th>Pending Appointments</th>
                                <th>Latest Slot</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="patientTbody"></tbody>
                    </table>
                </div>
                </section>

                <section class="panel detail-panel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Patient Health Details</h6>
                        <span id="activePatientTag" class="text-muted small">No patient selected</span>
                    </div>
                    <div id="profileGrid" class="row g-2 mb-3"></div>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="border rounded p-2 h-100">
                                <h6 class="small text-uppercase text-muted">Weight Trend</h6>
                                <canvas id="weightChart" height="180"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border rounded p-2 h-100">
                                <h6 class="small text-uppercase text-muted">Vitals Trend (Heart Rate)</h6>
                                <canvas id="vitalsChart" height="180"></canvas>
                            </div>
                        </div>
                    </div>
                    <div id="vitalsTableWrap" class="table-responsive mt-3"></div>
                </section>

                <section class="panel notes-panel">
                    <h6 class="mb-3">Doctor Notes & Diagnosis</h6>
                    <form id="noteForm" class="form-block mb-3">
                        <input type="hidden" id="noteId" value="">
                        <input type="hidden" id="notePid" value="">
                        <div class="half">
                            <label class="form-label">Diagnosis</label>
                            <input id="diagnosisInput" class="form-control" placeholder="Diagnosis">
                        </div>
                        <div class="half">
                            <label class="form-label">Follow-up Date</label>
                            <input id="followupInput" type="date" class="form-control">
                        </div>
                        <div class="full">
                            <label class="form-label">Treatment Plan</label>
                            <textarea id="treatmentInput" class="form-control" rows="2" placeholder="Treatment and follow-up instructions"></textarea>
                        </div>
                        <div class="full">
                            <label class="form-label">Clinical Notes</label>
                            <textarea id="noteTextInput" class="form-control" rows="3" placeholder="Observation and notes"></textarea>
                        </div>
                        <div class="full d-flex gap-2">
                            <button class="btn btn-success" type="submit">Save Note</button>
                            <button class="btn btn-outline-secondary" type="button" onclick="resetNoteForm()">Reset</button>
                        </div>
                    </form>
                    <div id="notesList" class="small text-muted">Select patient to load notes.</div>
                </section>

                <section class="panel appoint-panel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Appointment Management</h6>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted mb-0">View</label>
                            <select id="appointmentViewMode" class="form-select form-select-sm" style="width: 140px;" onchange="loadAppointments()">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="all">All Upcoming</option>
                            </select>
                        </div>
                    </div>
                    <div id="appointmentList" class="table-responsive"></div>
                </section>

                <section class="panel rx-panel">
                    <h6 class="mb-3">Prescription System</h6>
                    <form id="rxForm" class="form-block mb-3">
                        <input type="hidden" id="rxPid" value="">
                        <div class="third">
                            <label class="form-label">Medicine</label>
                            <select id="rxMedicine" class="form-select" required>
                                <option value="" selected disabled>Select medicine</option>
                            </select>
                        </div>
                        <div class="third">
                            <label class="form-label">Dosage</label>
                            <select id="rxDosage" class="form-select" required>
                                <option value="" selected disabled>Select dosage</option>
                            </select>
                        </div>
                        <div class="third">
                            <label class="form-label">Duration</label>
                            <select id="rxDuration" class="form-select" required>
                                <option value="" selected disabled>Select duration</option>
                            </select>
                        </div>
                        <div class="full">
                            <label class="form-label">Instructions</label>
                            <textarea id="rxInstructions" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="full">
                            <button class="btn btn-primary" type="submit">Create Prescription</button>
                        </div>
                    </form>
                    <div id="rxList" class="small text-muted">No prescriptions yet.</div>
                </section>

                <section class="panel files-panel">
                    <h6 class="mb-3">Patient Records & File Management</h6>
                    <form id="fileForm" class="form-block mb-3" enctype="multipart/form-data">
                        <input type="hidden" id="filePid" value="">
                        <div class="half">
                            <label class="form-label">File Type</label>
                            <select id="fileType" class="form-select">
                                <option value="report">Lab Report</option>
                                <option value="xray">X-ray</option>
                                <option value="scan">Scan</option>
                                <option value="prescription">Prescription</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="half">
                            <label class="form-label">Upload File</label>
                            <input id="healthFile" type="file" class="form-control">
                        </div>
                        <div class="full">
                            <button class="btn btn-outline-primary" type="submit">Upload</button>
                        </div>
                    </form>
                    <div id="fileList" class="small text-muted">Select patient to view files.</div>
                </section>

                <section class="panel message-panel">
                    <h6 class="mb-3">Communication Tools</h6>
                    <form id="messageForm" class="form-block mb-3">
                        <input type="hidden" id="msgPid" value="">
                        <div class="full">
                            <label class="form-label">Message to patient</label>
                            <textarea id="msgText" class="form-control" rows="2" placeholder="Follow-up or treatment instruction"></textarea>
                        </div>
                        <div class="full">
                            <button class="btn btn-outline-success" type="submit">Send Message</button>
                        </div>
                    </form>
                    <div id="messageList" class="small text-muted">No messages yet.</div>
                </section>

                <section class="panel report-panel">
                    <h6 class="mb-3">Reports & Export</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-dark" onclick="exportCurrentPatientCsv()">
                            <i class="fa-solid fa-file-csv me-1"></i>Export CSV
                        </button>
                        <button class="btn btn-outline-dark" onclick="window.print()">
                            <i class="fa-solid fa-file-pdf me-1"></i>Print / Save PDF
                        </button>
                    </div>
                    <div class="small text-muted mt-2">Use print dialog to save patient summary as PDF.</div>
                </section>
            </div>
        </main>
    </div>

    <div class="loading-layer" id="loadingLayer">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div class="notification alert" id="notificationBox"></div>

    <script>
        const API_URL = 'doctor_health_api.php';
        let currentPatientId = null;
        let weightChart = null;
        let vitalsChart = null;

        function showAppointmentOnly() {
            document.querySelectorAll('.panel').forEach(panel => {
                if (!panel.classList.contains('appoint-panel')) {
                    panel.classList.add('is-hidden');
                }
            });
            const appointmentSection = document.querySelector('.appoint-panel');
            if (appointmentSection) {
                appointmentSection.classList.remove('is-hidden');
                appointmentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            loadAppointments();
        }

        function showFullDashboard() {
            document.querySelectorAll('.panel').forEach(panel => {
                panel.classList.remove('is-hidden');
            });
        }

        function showLoading(show) {
            document.getElementById('loadingLayer').style.display = show ? 'flex' : 'none';
        }

        function notify(message, type = 'success') {
            const box = document.getElementById('notificationBox');
            box.className = 'notification alert alert-' + (type === 'error' ? 'danger' : 'success');
            box.textContent = message;
            box.style.display = 'block';
            setTimeout(() => {
                box.style.display = 'none';
            }, 2800);
        }

        async function apiPost(action, body = null, isFormData = false) {
            let options = { method: 'POST' };
            if (isFormData) {
                body.append('action', action);
                options.body = body;
            } else {
                const params = new URLSearchParams();
                params.append('action', action);
                Object.keys(body || {}).forEach((key) => params.append(key, body[key]));
                options.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
                options.body = params.toString();
            }

            const res = await fetch(API_URL, options);
            const text = await res.text();
            let json = null;
            try {
                json = JSON.parse(text);
            } catch (err) {
                throw new Error('Invalid server response');
            }
            if (!json.success) {
                throw new Error(json.message || 'Request failed');
            }
            return json;
        }

        function conditionClass(condition) {
            const c = (condition || '').toLowerCase();
            if (c === 'critical') return 'critical';
            if (c === 'stable') return 'stable';
            return 'moderate';
        }

        async function loadOverview() {
            const data = (await apiPost('overview')).data;
            document.getElementById('metricAssigned').textContent = data.assigned;
            document.getElementById('metricCritical').textContent = data.critical;
            document.getElementById('metricStable').textContent = data.stable;
            document.getElementById('metricRecent').textContent = data.recent_updates;

            const priorityList = data.priority_patients || [];
            if (!priorityList.length) {
                document.getElementById('priorityList').innerHTML = '<div class="text-muted">No priority patients found.</div>';
            } else {
                document.getElementById('priorityList').innerHTML = priorityList.map((p) => {
                    const name = ((p.firstname || '') + ' ' + (p.lastname || '')).trim();
                    return `<div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div><strong>#${p.pid}</strong> ${name}</div>
                        <span class="status-chip ${conditionClass(p.patient_condition)}">${p.patient_condition}</span>
                    </div>`;
                }).join('');
            }
        }

        async function loadPatients() {
            const search = document.getElementById('searchInput').value.trim();
            const searchField = document.getElementById('searchField').value;
            const condition = document.getElementById('conditionFilter').value;
            const rows = (await apiPost('list_patients', { search, search_field: searchField, condition })).data;

            const tbody = document.getElementById('patientTbody');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center py-3">No patients found.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map((row) => {
                const fullName = ((row.firstname || '') + ' ' + (row.lastname || '')).trim();
                const isSelected = currentPatientId && Number(currentPatientId) === Number(row.pid);
                return `<tr class="patient-row ${isSelected ? 'selected-patient-row' : ''}" onclick="selectPatient(${row.pid})">
                    <td>#${row.pid}</td>
                    <td>${fullName}</td>
                    <td><span class="status-chip ${conditionClass(row.patient_condition)}">${row.patient_condition}</span></td>
                    <td>${row.pending_count || 0}</td>
                    <td>${row.next_slot || '-'}</td>
                    <td>
                        <button type="button" class="btn btn-sm ${isSelected ? 'btn-success' : 'btn-outline-primary'}" onclick="event.stopPropagation(); selectPatient(${row.pid});">
                            ${isSelected ? 'Selected' : 'Select'}
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        function renderVitalsTable(vitals = []) {
            if (!vitals.length) {
                document.getElementById('vitalsTableWrap').innerHTML = '<div class="text-muted small">No vitals records available.</div>';
                return;
            }
            document.getElementById('vitalsTableWrap').innerHTML = `<table class="mini-table">
                <thead>
                    <tr>
                        <th>Recorded</th>
                        <th>BP</th>
                        <th>Heart Rate</th>
                        <th>Temp</th>
                        <th>Oxygen</th>
                    </tr>
                </thead>
                <tbody>
                    ${vitals.map((v) => {
                        const hr = Number(v.heart_rate || 0);
                        const temp = Number(v.temperature || 0);
                        const oxy = Number(v.oxygen_level || 0);
                        const abnormal = hr > 110 || hr < 50 || temp > 100.4 || oxy < 92;
                        return `<tr>
                            <td>${v.recorded_at || '-'}</td>
                            <td>${v.blood_pressure || '-'}</td>
                            <td>${hr || '-'} ${abnormal ? '<span class="vital-bad">Alert</span>' : ''}</td>
                            <td>${v.temperature || '-'}</td>
                            <td>${v.oxygen_level || '-'}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>`;
        }

        function renderCharts(weights = [], vitals = []) {
            const weightCtx = document.getElementById('weightChart');
            const vitalsCtx = document.getElementById('vitalsChart');

            if (weightChart) weightChart.destroy();
            if (vitalsChart) vitalsChart.destroy();

            const weightLabels = [...weights].reverse().map(w => (w.recorded_on || '').toString().split(' ')[0]);
            const weightData = [...weights].reverse().map(w => Number(w.weight || 0));

            weightChart = new Chart(weightCtx, {
                type: 'line',
                data: {
                    labels: weightLabels,
                    datasets: [{ label: 'Weight', data: weightData, borderColor: '#0b3d91', backgroundColor: 'rgba(11,61,145,0.15)', tension: 0.3, fill: true }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });

            const vitalsLabels = [...vitals].reverse().map(v => (v.recorded_at || '').toString().split(' ')[0]);
            const hrData = [...vitals].reverse().map(v => Number(v.heart_rate || 0));
            vitalsChart = new Chart(vitalsCtx, {
                type: 'bar',
                data: {
                    labels: vitalsLabels,
                    datasets: [{ label: 'Heart Rate', data: hrData, backgroundColor: '#1d4ed8' }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
        }

        function renderNotes(notes = []) {
            const wrap = document.getElementById('notesList');
            if (!notes.length) {
                wrap.innerHTML = '<div class="text-muted">No notes yet.</div>';
                return;
            }
            wrap.innerHTML = notes.map((n) => `
                <div class="timeline-item">
                    <div class="d-flex justify-content-between">
                        <strong>${n.diagnosis || 'General Note'}</strong>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick='editNote(${JSON.stringify(n.note_id)}, ${JSON.stringify(n.note_text || '')}, ${JSON.stringify(n.diagnosis || '')}, ${JSON.stringify(n.treatment_plan || '')}, ${JSON.stringify(n.follow_up_date || '')})'>Edit</button>
                            <button class="btn btn-sm btn-outline-danger" onclick='deleteNote(${n.note_id})'>Delete</button>
                        </div>
                    </div>
                    <div class="small text-muted mb-1">${n.created_at || ''}</div>
                    <div><strong>Treatment:</strong> ${n.treatment_plan || '-'}</div>
                    <div><strong>Note:</strong> ${n.note_text || '-'}</div>
                </div>
            `).join('');
        }

        function renderAlerts(alerts = []) {
            const feed = document.getElementById('alertFeed');
            if (!alerts.length) {
                feed.innerHTML = '<div class="text-success">No active alerts.</div>';
                return;
            }
            feed.innerHTML = alerts.map((a) => `<div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <span>${a.message}</span>
                <span class="status-chip ${conditionClass(a.severity)}">${a.severity}</span>
            </div>`).join('');
        }

        async function loadAppointments() {
            let rows = (await apiPost('appointments')).data;
            const mode = document.getElementById('appointmentViewMode').value;
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            rows = rows.filter((a) => {
                const d = new Date((a.appointment_date || '').toString().substring(0, 10));
                d.setHours(0, 0, 0, 0);
                const diffDays = Math.round((d - today) / 86400000);
                if (mode === 'daily') return diffDays === 0;
                if (mode === 'weekly') return diffDays >= 0 && diffDays <= 7;
                return diffDays >= 0;
            });

            const wrap = document.getElementById('appointmentList');
            if (!rows.length) {
                wrap.innerHTML = '<div class="text-muted">No upcoming appointments.</div>';
                return;
            }
            wrap.innerHTML = `<table class="mini-table">
                <thead><tr><th>ID</th><th>Patient</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    ${rows.map((a) => `<tr>
                        <td>${a.appointment_id}</td>
                        <td>
                            <button type="button" class="btn btn-link p-0 text-decoration-none" onclick="selectPatient(${a.pid})">
                                #${a.pid} <span class="small text-muted ms-1">(Select)</span>
                            </button>
                        </td>
                        <td><input type="date" class="form-control form-control-sm" id="adate_${a.appointment_id}" value="${(a.appointment_date || '').split(' ')[0] || ''}"></td>
                        <td><input type="time" class="form-control form-control-sm" id="atime_${a.appointment_id}" value="${a.appointment_time || ''}"></td>
                        <td>
                            <select class="form-select form-select-sm" id="astatus_${a.appointment_id}">
                                ${['Pending','Confirmed','Completed','Rescheduled','Cancelled'].map(s => `<option ${a.status === s ? 'selected' : ''}>${s}</option>`).join('')}
                            </select>
                        </td>
                        <td><button class="btn btn-sm btn-outline-primary" onclick="saveAppointment(${a.appointment_id})">Save</button></td>
                    </tr>`).join('')}
                </tbody>
            </table>`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash === '#appointments') {
                showAppointmentOnly();
            }
        });

        async function saveAppointment(id) {
            try {
                showLoading(true);
                const status = document.getElementById('astatus_' + id).value;
                const appointment_date = document.getElementById('adate_' + id).value;
                const appointment_time = document.getElementById('atime_' + id).value;
                await apiPost('update_appointment', { appointment_id: id, status, appointment_date, appointment_time });
                notify('Appointment updated');
                await loadAppointments();
            } catch (e) {
                notify(e.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        async function selectPatient(pid) {
            currentPatientId = pid;
            document.getElementById('notePid').value = pid;
            document.getElementById('rxPid').value = pid;
            document.getElementById('filePid').value = pid;
            document.getElementById('msgPid').value = pid;

            try {
                showLoading(true);
                const data = (await apiPost('patient_details', { pid })).data;
                const p = data.profile;
                const name = ((p.firstname || '') + ' ' + (p.lastname || '')).trim();
                document.getElementById('activePatientTag').innerHTML = `Patient #${p.pid} ${name} <span class="status-chip ${conditionClass(p.patient_condition)}">${p.patient_condition}</span>`;

                document.getElementById('profileGrid').innerHTML = `
                    <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Email</div><div>${p.email || '-'}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Phone</div><div>${p.mobile || '-'}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Gender</div><div>${p.gender || '-'}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Age</div><div>${p.age || '-'}</div></div></div>
                `;

                renderCharts(data.weights || [], data.vitals || []);
                renderVitalsTable(data.vitals || []);
                renderNotes(data.notes || []);
                renderAlerts(data.alerts || []);

                await Promise.all([loadPrescriptions(pid), loadFiles(pid), loadMessages(pid)]);
                await loadPatients();
            } catch (e) {
                notify(e.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        function resetNoteForm() {
            document.getElementById('noteId').value = '';
            document.getElementById('diagnosisInput').value = '';
            document.getElementById('followupInput').value = '';
            document.getElementById('treatmentInput').value = '';
            document.getElementById('noteTextInput').value = '';
        }

        function editNote(noteId, noteText, diagnosis, treatmentPlan, followUp) {
            document.getElementById('noteId').value = noteId;
            document.getElementById('noteTextInput').value = noteText;
            document.getElementById('diagnosisInput').value = diagnosis;
            document.getElementById('treatmentInput').value = treatmentPlan;
            document.getElementById('followupInput').value = followUp;
            window.scrollTo({ top: document.getElementById('noteForm').offsetTop - 60, behavior: 'smooth' });
        }

        async function deleteNote(noteId) {
            if (!confirm('Delete this note?')) return;
            try {
                showLoading(true);
                await apiPost('delete_note', { note_id: noteId });
                notify('Note deleted');
                if (currentPatientId) await selectPatient(currentPatientId);
            } catch (e) {
                notify(e.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        async function loadPrescriptions(pid) {
            const rows = (await apiPost('list_prescriptions', { pid })).data;
            const wrap = document.getElementById('rxList');
            if (!rows.length) {
                wrap.innerHTML = '<div class="text-muted">No prescriptions yet.</div>';
                return;
            }
            wrap.innerHTML = rows.map((r) => `<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center">
                <div><strong>${r.medicine_name}</strong> | ${r.dosage} | ${r.duration}<br><span class="small text-muted">${r.instructions || ''}</span></div>
                <button class="btn btn-sm btn-outline-secondary" onclick="printPrescription(${r.prescription_id})">PDF</button>
            </div>`).join('');
        }

        function printPrescription(id) {
            window.open(`${API_URL}?action=prescription_print_html&prescription_id=${id}`, '_blank');
        }

        async function loadFiles(pid) {
            const rows = (await apiPost('list_files', { pid })).data;
            const wrap = document.getElementById('fileList');
            if (!rows.length) {
                wrap.innerHTML = '<div class="text-muted">No files available.</div>';
                return;
            }
            wrap.innerHTML = rows.map((f) => `<div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                <div><strong>${f.file_name}</strong> <span class="small text-muted">(${f.file_type})</span><br><span class="small text-muted">${f.uploaded_at || ''}</span></div>
                <a class="btn btn-sm btn-outline-primary" href="${f.file_path}" target="_blank">View</a>
            </div>`).join('');
        }

        async function loadMessages(pid) {
            const rows = (await apiPost('list_messages', { pid })).data;
            const wrap = document.getElementById('messageList');
            if (!rows.length) {
                wrap.innerHTML = '<div class="text-muted">No messages sent yet.</div>';
                return;
            }
            wrap.innerHTML = rows.map((m) => `<div class="border rounded p-2 mb-2"><div>${m.message_text}</div><div class="small text-muted">${m.created_at} | ${m.status}</div></div>`).join('');
        }

        async function exportCurrentPatientCsv() {
            if (!currentPatientId) {
                notify('Select a patient first', 'error');
                return;
            }
            try {
                showLoading(true);
                const res = await apiPost('export_csv', { pid: currentPatientId });
                const data = atob(res.content_base64 || '');
                const blob = new Blob([data], { type: 'text/csv;charset=utf-8;' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = res.file_name || `patient_${currentPatientId}_summary.csv`;
                document.body.appendChild(a);
                a.click();
                a.remove();
            } catch (e) {
                notify(e.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        const medicineOptions = [
            "Paracetamol 500mg",
            "Azithromycin 500mg",
            "Amoxicillin 500mg",
            "Cetrizine 10mg",
            "Pantoprazole 40mg",
            "Omeprazole 20mg",
            "Ibuprofen 400mg",
            "Dolo 650",
            "Crocin 500",
            "Metformin 500mg",
            "Amlodipine 5mg",
            "Losartan 50mg",
            "Vitamin D3",
            "Calcium Tablet",
            "ORS",
            "Cough Syrup",
            "Antacid Syrup",
            "Insulin",
            "Inhaler",
            "Other"
        ];

        const dosageOptions = [
            "1 tablet once daily",
            "1 tablet twice daily",
            "1 tablet thrice daily",
            "1 tablet after food",
            "1 tablet before food",
            "1 capsule once daily",
            "1 capsule twice daily",
            "5 ml once daily",
            "5 ml twice daily",
            "10 ml twice daily",
            "Apply once daily",
            "Apply twice daily",
            "2 drops once daily",
            "2 drops twice daily",
            "1 injection as directed",
            "Use as needed"
        ];

        const durationOptions = [
            "1 day",
            "3 days",
            "5 days",
            "7 days",
            "10 days",
            "14 days",
            "15 days",
            "1 month",
            "2 months",
            "3 months",
            "Continue until next visit"
        ];

        function populateSelect(selectId, options, placeholderText) {
            const select = document.getElementById(selectId);
            if (!select) {
                return;
            }

            select.innerHTML = '<option value="" selected disabled>' + placeholderText + '</option>'
                + options.map(option => '<option value="' + option + '">' + option + '</option>').join('');
        }

        populateSelect('rxMedicine', medicineOptions, 'Select medicine');
        populateSelect('rxDosage', dosageOptions, 'Select dosage');
        populateSelect('rxDuration', durationOptions, 'Select duration');

        document.getElementById('noteForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentPatientId) {
                notify('Select a patient first', 'error');
                return;
            }

            const payload = {
                pid: currentPatientId,
                note_text: document.getElementById('noteTextInput').value,
                diagnosis: document.getElementById('diagnosisInput').value,
                treatment_plan: document.getElementById('treatmentInput').value,
                follow_up_date: document.getElementById('followupInput').value
            };

            try {
                showLoading(true);
                const noteId = document.getElementById('noteId').value;
                if (noteId) {
                    payload.note_id = noteId;
                    await apiPost('update_note', payload);
                    notify('Note updated');
                } else {
                    await apiPost('add_note', payload);
                    notify('Note added');
                }
                resetNoteForm();
                await selectPatient(currentPatientId);
            } catch (err) {
                notify(err.message, 'error');
            } finally {
                showLoading(false);
            }
        });

        document.getElementById('rxForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentPatientId) {
                notify('Select a patient first', 'error');
                return;
            }
            try {
                showLoading(true);
                await apiPost('add_prescription', {
                    pid: currentPatientId,
                    medicine_name: document.getElementById('rxMedicine').value,
                    dosage: document.getElementById('rxDosage').value,
                    duration: document.getElementById('rxDuration').value,
                    instructions: document.getElementById('rxInstructions').value
                });
                notify('Prescription created');
                document.getElementById('rxForm').reset();
                document.getElementById('rxPid').value = currentPatientId;
                await loadPrescriptions(currentPatientId);
            } catch (err) {
                notify(err.message, 'error');
            } finally {
                showLoading(false);
            }
        });

        document.getElementById('fileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentPatientId) {
                notify('Select a patient first', 'error');
                return;
            }
            const fileInput = document.getElementById('healthFile');
            if (!fileInput.files.length) {
                notify('Please select a file', 'error');
                return;
            }
            try {
                showLoading(true);
                const fd = new FormData();
                fd.append('pid', currentPatientId);
                fd.append('file_type', document.getElementById('fileType').value);
                fd.append('health_file', fileInput.files[0]);
                await apiPost('upload_file', fd, true);
                notify('File uploaded');
                document.getElementById('fileForm').reset();
                document.getElementById('filePid').value = currentPatientId;
                await loadFiles(currentPatientId);
            } catch (err) {
                notify(err.message, 'error');
            } finally {
                showLoading(false);
            }
        });

        document.getElementById('messageForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentPatientId) {
                notify('Select a patient first', 'error');
                return;
            }
            const message = document.getElementById('msgText').value.trim();
            if (!message) {
                notify('Message cannot be empty', 'error');
                return;
            }
            try {
                showLoading(true);
                await apiPost('send_message', { pid: currentPatientId, message_text: message });
                notify('Message sent');
                document.getElementById('msgText').value = '';
                await loadMessages(currentPatientId);
            } catch (err) {
                notify(err.message, 'error');
            } finally {
                showLoading(false);
            }
        });

        (async function boot() {
            try {
                showLoading(true);
                await loadOverview();
                await loadPatients();
                await loadAppointments();

                const params = new URLSearchParams(window.location.search);
                const forwardedPatientId = parseInt(params.get('patient_id') || '', 10);
                if (forwardedPatientId > 0) {
                    await selectPatient(forwardedPatientId);
                }
            } catch (err) {
                notify(err.message, 'error');
            } finally {
                showLoading(false);
            }
        })();
    </script>
</body>

</html>
