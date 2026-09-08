<?php

if (!function_exists('medc_admin_dashboard_table_has_column')) {
    function medc_admin_dashboard_table_has_column(mysqli $conn, string $table, string $column): bool
    {
        $tableEscaped = mysqli_real_escape_string($conn, $table);
        $columnEscaped = mysqli_real_escape_string($conn, $column);
        $query = "
            SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tableEscaped}'
              AND COLUMN_NAME = '{$columnEscaped}'
            LIMIT 1
        ";

        $result = mysqli_query($conn, $query);
        return $result instanceof mysqli_result && mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('medc_admin_dashboard_scalar')) {
    function medc_admin_dashboard_scalar(mysqli $conn, string $query, float $default = 0.0): float
    {
        $result = mysqli_query($conn, $query);
        if (!($result instanceof mysqli_result)) {
            return $default;
        }

        $row = mysqli_fetch_assoc($result);
        if (!$row) {
            return $default;
        }

        $value = reset($row);
        if ($value === null || $value === false || $value === '') {
            return $default;
        }

        return (float) $value;
    }
}

if (!function_exists('renderAdminDashboardOverview')) {
    function renderAdminDashboardOverview(mysqli $conn, string $localhost): void
    {
        $today = date('Y-m-d');
        $appointmentsToday = (int) medc_admin_dashboard_scalar(
            $conn,
            "SELECT COUNT(*) FROM appointment WHERE appointment_date = '{$today}'"
        );

        $patientsTodayLabel = 'New Patients Today';
        if (medc_admin_dashboard_table_has_column($conn, 'patient', 'created_at')) {
            $patientsToday = (int) medc_admin_dashboard_scalar(
                $conn,
                "SELECT COUNT(*) FROM patient WHERE DATE(created_at) = '{$today}'"
            );
        } else {
            $patientsToday = (int) medc_admin_dashboard_scalar($conn, "SELECT COUNT(*) FROM patient");
            $patientsTodayLabel = 'Registered Patients';
        }

        $activeDoctors = (int) medc_admin_dashboard_scalar(
            $conn,
            "SELECT COUNT(*) FROM doctor WHERE LOWER(COALESCE(approval, '')) = 'approved'"
        );

        $revenueToday = medc_admin_dashboard_scalar(
            $conn,
            "SELECT COALESCE(SUM(d.consultation_fees), 0)
             FROM appointment a
             INNER JOIN doctor d ON a.did = d.d_id
             WHERE a.appointment_date = '{$today}' AND a.status = 'Confirmed'"
        );

        $pendingAppointments = (int) medc_admin_dashboard_scalar(
            $conn,
            "SELECT COUNT(*) FROM appointment WHERE status = 'Pending'"
        );
        $pendingDoctors = (int) medc_admin_dashboard_scalar(
            $conn,
            "SELECT COUNT(*) FROM doctor WHERE LOWER(COALESCE(approval, '')) = 'pending'"
        );
        $totalPatients = (int) medc_admin_dashboard_scalar($conn, "SELECT COUNT(*) FROM patient");
        $totalUsers = (int) medc_admin_dashboard_scalar($conn, "SELECT COUNT(*) FROM users");
        $totalDiseases = (int) medc_admin_dashboard_scalar($conn, "SELECT COUNT(*) FROM disease_information");
        $totalCommunities = (int) medc_admin_dashboard_scalar($conn, "SELECT COUNT(*) FROM communities");

        $weekStats = [];
        for ($offset = 6; $offset >= 0; $offset--) {
            $date = date('Y-m-d', strtotime("-{$offset} days"));
            $label = date('D', strtotime($date));
            $count = (int) medc_admin_dashboard_scalar(
                $conn,
                "SELECT COUNT(*) FROM appointment WHERE appointment_date = '{$date}'"
            );
            $weekStats[] = [
                'label' => $label,
                'value' => $count,
            ];
        }

        $maxValue = 1;
        foreach ($weekStats as $dayStat) {
            if ($dayStat['value'] > $maxValue) {
                $maxValue = $dayStat['value'];
            }
        }

        $chartWidth = 640;
        $chartHeight = 250;
        $paddingLeft = 42;
        $paddingRight = 24;
        $paddingTop = 28;
        $paddingBottom = 44;
        $usableWidth = $chartWidth - $paddingLeft - $paddingRight;
        $usableHeight = $chartHeight - $paddingTop - $paddingBottom;
        $pointStep = count($weekStats) > 1 ? ($usableWidth / (count($weekStats) - 1)) : 0;

        $polylinePoints = [];
        $pointMarkup = [];
        $labelMarkup = [];

        foreach ($weekStats as $index => $dayStat) {
            $x = $paddingLeft + ($pointStep * $index);
            $ratio = $dayStat['value'] / $maxValue;
            $y = $paddingTop + (($usableHeight) * (1 - $ratio));
            $polylinePoints[] = round($x, 2) . ',' . round($y, 2);
            $pointMarkup[] = sprintf(
                '<circle cx="%s" cy="%s" r="%s" class="chart-point%s"></circle>',
                round($x, 2),
                round($y, 2),
                $index === count($weekStats) - 1 ? '5.5' : '4.5',
                $index === count($weekStats) - 1 ? ' is-current' : ''
            );
            $labelMarkup[] = sprintf(
                '<div class="chart-label" style="left:%s%%">%s</div>',
                count($weekStats) > 1 ? round(($index / (count($weekStats) - 1)) * 100, 2) : 0,
                htmlspecialchars($dayStat['label'], ENT_QUOTES, 'UTF-8')
            );
        }

        $notifications = [];
        if ($pendingAppointments > 0) {
            $notifications[] = [
                'icon' => 'fa-bell',
                'tone' => 'warning',
                'text' => $pendingAppointments . ' appointment request(s) are waiting for confirmation.'
            ];
        }
        if ($pendingDoctors > 0) {
            $notifications[] = [
                'icon' => 'fa-user-clock',
                'tone' => 'info',
                'text' => $pendingDoctors . ' doctor application(s) are pending review.'
            ];
        }
        if ($patientsToday > 0) {
            $notifications[] = [
                'icon' => 'fa-user-plus',
                'tone' => 'success',
                'text' => $patientsToday . ' patient registration(s) have been recorded today.'
            ];
        }
        if ($revenueToday > 0) {
            $notifications[] = [
                'icon' => 'fa-wallet',
                'tone' => 'success',
                'text' => 'Confirmed consultations generated Rs. ' . number_format($revenueToday, 0) . ' today.'
            ];
        }
        if (empty($notifications)) {
            $notifications[] = [
                'icon' => 'fa-circle-check',
                'tone' => 'success',
                'text' => 'All core admin queues look clear right now.'
            ];
        }

        ?>
        <div class="overview-grid">
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-regular fa-calendar-check"></i></div>
                <div class="metric-value"><?php echo number_format($appointmentsToday); ?></div>
                <div class="metric-label">Today's Appointments</div>
            </div>
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-user-plus"></i></div>
                <div class="metric-value"><?php echo number_format($patientsToday); ?></div>
                <div class="metric-label"><?php echo htmlspecialchars($patientsTodayLabel, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="metric-value"><?php echo number_format($activeDoctors); ?></div>
                <div class="metric-label">Active Doctors</div>
            </div>
            <div class="metric-card metric-card-revenue">
                <div class="metric-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div class="metric-value">Rs. <?php echo number_format($revenueToday, 0); ?></div>
                <div class="metric-label">Today's Revenue</div>
            </div>
        </div>

        <div class="overview-panels">
            <section class="chart-panel">
                <div class="panel-header">
                    <div>
                        <h3><i class="fa-regular fa-chart-bar me-2"></i>Weekly Performance</h3>
                        <p>Appointment flow for the last 7 days.</p>
                    </div>
                    <div class="panel-pills">
                        <span class="panel-pill is-active">Appointments</span>
                        <span class="panel-pill">Revenue</span>
                        <span class="panel-pill">Avg. Wait</span>
                    </div>
                </div>

                <div class="chart-shell">
                    <div class="chart-grid-lines">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <svg class="chart-svg" viewBox="0 0 <?php echo $chartWidth; ?> <?php echo $chartHeight; ?>" preserveAspectRatio="none" aria-hidden="true">
                        <polyline class="chart-line-shadow" points="<?php echo htmlspecialchars(implode(' ', $polylinePoints), ENT_QUOTES, 'UTF-8'); ?>"></polyline>
                        <polyline class="chart-line" points="<?php echo htmlspecialchars(implode(' ', $polylinePoints), ENT_QUOTES, 'UTF-8'); ?>"></polyline>
                        <?php echo implode('', $pointMarkup); ?>
                    </svg>
                    <div class="chart-labels"><?php echo implode('', $labelMarkup); ?></div>
                </div>

                <div class="insight-pills">
                    <span class="insight-pill">Pending Appointments: <?php echo number_format($pendingAppointments); ?></span>
                    <span class="insight-pill">Total Patients: <?php echo number_format($totalPatients); ?></span>
                    <span class="insight-pill">Diseases Listed: <?php echo number_format($totalDiseases); ?></span>
                    <span class="insight-pill">Communities: <?php echo number_format($totalCommunities); ?></span>
                </div>
            </section>

            <aside class="notification-panel">
                <div class="panel-header">
                    <div>
                        <h3><i class="fa-solid fa-bell me-2"></i>Recent Notifications</h3>
                        <p>Fast actions you may want to review.</p>
                    </div>
                </div>

                <div class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item notification-<?php echo htmlspecialchars($notification['tone'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="notification-icon">
                                <i class="fa-solid <?php echo htmlspecialchars($notification['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                            </span>
                            <span class="notification-text"><?php echo htmlspecialchars($notification['text'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="snapshot-card">
                    <h4>System Snapshot</h4>
                    <div class="snapshot-row">
                        <span>Total Users</span>
                        <strong><?php echo number_format($totalUsers); ?></strong>
                    </div>
                    <div class="snapshot-row">
                        <span>Pending Doctors</span>
                        <strong><?php echo number_format($pendingDoctors); ?></strong>
                    </div>
                    <div class="snapshot-row">
                        <span>Approved Doctors</span>
                        <strong><?php echo number_format($activeDoctors); ?></strong>
                    </div>
                </div>
            </aside>
        </div>
        <?php
    }
}
