<?php
// Updated admin_portal_modules_section.php - Code snippets for large section updates
// This shows what to ADD to admin_portal.php in the appointments section onwards

// APPOINTMENT SCHEDULING SECTION (Replace old appointments-section div)
?>

<!-- APPOINTMENT SCHEDULING SECTION -->
<div id="appointments-section" style="display: none;">
    <h1 class="mb-4"><span class="text-icon me-2" aria-hidden="true">&#128197;</span>Appointment Management</h1>
    <div class="row">
        <div class="col-md-6">
            <div class="stat-card">
                <h4><span class="text-icon me-2" aria-hidden="true">&#10133;</span>Book Appointment</h4>
                <form id="appointmentForm">
                    <div id="appointmentMessage" class="form-message" aria-live="polite"></div>
                    
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <select class="form-select" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php
                            $patients = $conn->query("SELECT pid, firstname, lastname FROM patient ORDER BY firstname");
                            while ($p = $patients->fetch_assoc()) {
                                echo '<option value="' . $p['pid'] . '">' . htmlspecialchars($p['firstname'] . ' ' . $p['lastname']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Doctor</label>
                        <select class="form-select" name="doctor_id" required>
                            <option value="">Select Doctor</option>
                            <?php
                            $doctors = $conn->query("SELECT d_id, f_name, l_name, specialization FROM doctor WHERE approval = 'approved' ORDER BY f_name");
                            while ($d = $doctors->fetch_assoc()) {
                                echo '<option value="' . $d['d_id'] . '">Dr. ' . htmlspecialchars($d['f_name'] . ' ' . $d['l_name']) . ' (' . $d['specialization'] . ')</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="appointment_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Time</label>
                        <input type="time" class="form-control" name="appointment_time" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason for Visit</label>
                        <input type="text" class="form-control" name="reason" placeholder="Chief complaint">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-calendar-check me-1"></i>Book Appointment
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="stat-card">
                <h4><span class="text-icon me-2" aria-hidden="true">&#128467;</span>Upcoming Appointments</h4>
                <div style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentListBody">
                            <?php
                            $appointments = $conn->query(
                                "SELECT a.*, p.firstname, p.lastname, d.f_name, d.l_name 
                                 FROM appointment a 
                                 JOIN patient p ON a.pid = p.pid 
                                 JOIN doctor d ON a.did = d.d_id 
                                 WHERE a.appointment_date >= CURDATE() AND a.status != 'Cancelled'
                                 ORDER BY a.appointment_date, a.appointment_time LIMIT 20"
                            );
                            while ($apt = $appointments->fetch_assoc()) {
                                $statusBadge = ($apt['status'] === 'Pending') ? 'warning' : (($apt['status'] === 'Confirmed') ? 'success' : 'secondary');
                                echo '<tr data-appointment-id="' . $apt['appointment_id'] . '">';
                                echo '<td>' . $apt['appointment_date'] . '</td>';
                                echo '<td>' . $apt['appointment_time'] . '</td>';
                                echo '<td class="appt-patient">' . htmlspecialchars($apt['firstname'] . ' ' . $apt['lastname']) . '</td>';
                                echo '<td class="appt-doctor">Dr. ' . htmlspecialchars($apt['f_name'] . ' ' . $apt['l_name']) . '</td>';
                                echo '<td><span class="badge bg-' . $statusBadge . '">' . $apt['status'] . '</span></td>';
                                echo '<td>
                                        <button class="btn btn-outline-info btn-sm" onclick="rescheduleAppointment(' . $apt['appointment_id'] . ')">Reschedule</button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="cancelAppointment(' . $apt['appointment_id'] . ')">Cancel</button>
                                    </td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// This is sample HTML. Create a patch to insert it into admin_portal.php
// replacing the old appointments-section
?>
