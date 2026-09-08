<?php
session_start();
include("../../connection/config.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

if (strtolower(trim((string) ($_SESSION['user_type'] ?? ''))) !== 'patient') {
    header('Location: ../../include/dashboard_link.php');
    exit();
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$email = $_SESSION['email'] ?? '';
$localhost = "http://" . $_SERVER['SERVER_NAME'] . "/MedC/MedC/";

$patientData = [
    'firstname' => 'Patient',
    'lastname' => '',
    'email' => $email,
    'contact' => ''
];

if (!empty($email)) {
    $stmt = $conn->prepare("SELECT firstname, lastname, email, contact FROM patient WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $patientData = array_merge($patientData, $row);
        }
        $stmt->close();
    }
}

if (empty($patientData['email']) && $user_id > 0) {
    $stmt = $conn->prepare("SELECT firstname, lastname, email, contact FROM patient WHERE pid = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $patientData = array_merge($patientData, $row);
        }
        $stmt->close();
    }
}

$fullName = trim(($patientData['firstname'] ?? '') . ' ' . ($patientData['lastname'] ?? ''));
$fullName = $fullName !== '' ? $fullName : 'Patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings & Preferences</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --brand-blue: #0b3ea8;
            --brand-blue-dark: #072e7c;
            --brand-accent: #19b3e6;
            --brand-bg: #f5f7fb;
            --brand-text: #1f2a44;
            --brand-muted: #6b7a99;
            --brand-card: #ffffff;
            --brand-shadow: 0 16px 30px rgba(10, 36, 91, 0.08);
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--brand-bg);
            color: var(--brand-text);
            min-height: 100vh;
        }

        body.theme-dark {
            background: #0f172a;
            color: #e2e8f0;
        }

        body.theme-dark .page-card,
        body.theme-dark .option-card,
        body.theme-dark .center-modal,
        body.theme-dark .toast {
            background: #111827;
            color: #e2e8f0;
            border-color: rgba(148, 163, 184, 0.2);
        }

        body.theme-dark .option-card {
            box-shadow: 0 12px 22px rgba(15, 23, 42, 0.55);
        }

        body.theme-dark .muted {
            color: #94a3b8;
        }

        .page-wrap {
            padding: 28px 0 60px;
        }

        .page-card {
            background: var(--brand-card);
            border-radius: 18px;
            padding: 24px;
            box-shadow: var(--brand-shadow);
            border: 1px solid #e4ecfb;
        }

        .page-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .page-header h1 {
            font-size: 1.6rem;
            margin: 0;
            color: var(--brand-blue-dark);
        }

        .page-header a {
            text-decoration: none;
        }

        .option-grid {
            display: grid;
            gap: 16px;
        }

        @media (min-width: 992px) {
            .option-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .option-card {
            background: var(--brand-card);
            border-radius: 16px;
            border: 1px solid #e4ecfb;
            padding: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .option-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
            border-color: rgba(25, 179, 230, 0.3);
        }

        .option-card.active {
            border-color: var(--brand-accent);
            box-shadow: 0 16px 26px rgba(25, 179, 230, 0.2);
        }

        .option-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .option-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #eaf4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand-blue);
            font-size: 1.1rem;
        }

        .option-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .muted {
            color: var(--brand-muted);
            font-size: 0.92rem;
        }

        .switch {
            position: relative;
            width: 48px;
            height: 26px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #d0d8ea;
            transition: 0.2s;
            border-radius: 999px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.2s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--brand-accent);
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .modal-backdrop-blur {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(6px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 1040;
        }

        .modal-backdrop-blur.show {
            opacity: 1;
            pointer-events: auto;
        }

        .center-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.98);
            opacity: 0;
            pointer-events: none;
            width: min(92vw, 520px);
            max-height: 86vh;
            overflow-y: auto;
            background: var(--brand-card);
            border-radius: 18px;
            border: 1px solid #e4ecfb;
            box-shadow: var(--brand-shadow);
            padding: 22px;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 1050;
        }

        .center-modal.modal-lg {
            width: min(94vw, 760px);
        }

        .center-modal.show {
            opacity: 1;
            pointer-events: auto;
            transform: translate(-50%, -50%) scale(1);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .form-label {
            font-weight: 600;
        }

        .form-control:read-only {
            background-color: #f1f5ff;
        }

        .accordion-item {
            border-radius: 12px;
            border: 1px solid #e4ecfb;
            overflow: hidden;
        }

        .accordion-button {
            background: #f5f8ff;
            font-weight: 600;
        }

        .lang-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .lang-btn {
            border: 1px solid #d8e4ff;
            background: #f5f8ff;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 600;
        }

        .lang-btn.active {
            background: var(--brand-accent);
            color: #fff;
            border-color: var(--brand-accent);
        }

        .toast-container {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1080;
        }

        .toast {
            background: var(--brand-card);
            border-radius: 12px;
            border: 1px solid #e4ecfb;
            box-shadow: var(--brand-shadow);
            padding: 12px 16px;
            min-width: 220px;
            display: none;
        }

        .toast.show {
            display: block;
            animation: fadeIn 0.25s ease;
        }

        .fade-scale {
            animation: fadeScale 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeScale {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }

        .password-field {
            position: relative;
        }

        .toggle-visibility {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--brand-muted);
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .error-text {
            color: #dc2626;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container page-wrap">
        <div class="page-card fade-scale">
            <div class="page-header">
                <div>
                    <h1 id="pageTitle">Settings & Preferences</h1>
                    <div class="muted" id="pageSubtitle">Manage your profile, security, and app preferences.</div>
                </div>
                <a class="btn btn-outline-primary" href="<?php echo $localhost; ?>views/Patient/patient_dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>

            <div class="option-grid" id="settingsGrid">
                <div class="option-card" data-action="profile" id="edit-profile">
                    <div class="option-left">
                        <div class="option-icon"><i class="fas fa-user-edit"></i></div>
                        <div>
                            <div class="option-title" data-i18n="editProfileTitle">Edit Profile</div>
                            <div class="muted" data-i18n="editProfileDesc">Update your name, phone, and photo.</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right muted"></i>
                </div>

                <div class="option-card" data-action="password" id="change-password">
                    <div class="option-left">
                        <div class="option-icon"><i class="fas fa-key"></i></div>
                        <div>
                            <div class="option-title" data-i18n="passwordTitle">Change Password</div>
                            <div class="muted" data-i18n="passwordDesc">Keep your account secure with a new password.</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right muted"></i>
                </div>

                <div class="option-card" data-action="notifications" id="notifications">
                    <div class="option-left">
                        <div class="option-icon"><i class="fas fa-bell"></i></div>
                        <div>
                            <div class="option-title" data-i18n="notificationsTitle">Notification Preferences</div>
                            <div class="muted" data-i18n="notificationsDesc">Choose how you receive updates.</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down muted"></i>
                </div>

                <div class="option-card" data-action="language" id="language">
                    <div class="option-left">
                        <div class="option-icon"><i class="fas fa-language"></i></div>
                        <div>
                            <div class="option-title" data-i18n="languageTitle">Language Selection</div>
                            <div class="muted" data-i18n="languageDesc">Switch between English, Hindi, Gujarati.</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right muted"></i>
                </div>

                <div class="option-card" data-action="privacy" id="privacy">
                    <div class="option-left">
                        <div class="option-icon"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <div class="option-title" data-i18n="privacyTitle">Privacy Settings</div>
                            <div class="muted" data-i18n="privacyDesc">Control data sharing and account security.</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right muted"></i>
                </div>

                <div class="option-card" data-action="theme" id="theme">
                    <div class="option-left">
                        <div class="option-icon"><i class="fas fa-moon"></i></div>
                        <div>
                            <div class="option-title" data-i18n="themeTitle">Light/Dark Mode</div>
                            <div class="muted" data-i18n="themeDesc">Instantly adjust the app appearance.</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right muted"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-backdrop-blur" id="modalBackdrop"></div>

    <div class="center-modal" id="profileModal" aria-hidden="true">
        <div class="modal-header">
            <div>
                <h5 class="mb-0">Edit Profile</h5>
                <div class="muted">Update your personal details.</div>
            </div>
            <button class="btn btn-light" type="button" data-close="modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="profileForm">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($fullName); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($patientData['email'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($patientData['contact'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Profile Photo</label>
                <input type="file" class="form-control" name="photo" accept="image/*">
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save Changes</button>
                <button class="btn btn-outline-secondary" type="button" data-close="modal">Cancel</button>
            </div>
        </form>
    </div>

    <div class="center-modal" id="passwordModal" aria-hidden="true">
        <div class="modal-header">
            <h5 class="mb-0">Change Password</h5>
            <button class="btn btn-light" type="button" data-close="modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="passwordForm">
            <div class="mb-3 password-field">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-control" name="currentPassword" required>
                <button class="toggle-visibility" type="button" data-target="currentPassword"><i class="fas fa-eye"></i></button>
            </div>
            <div class="mb-3 password-field">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="newPassword" required>
                <button class="toggle-visibility" type="button" data-target="newPassword"><i class="fas fa-eye"></i></button>
            </div>
            <div class="mb-3 password-field">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirmPassword" required>
                <button class="toggle-visibility" type="button" data-target="confirmPassword"><i class="fas fa-eye"></i></button>
                <div class="error-text" id="passwordError"></div>
            </div>
            <button class="btn btn-primary w-100" type="submit" id="passwordSave">Update Password</button>
        </form>
    </div>

    <div class="center-modal" id="notificationsModal" aria-hidden="true">
        <div class="modal-header">
            <div>
                <h5 class="mb-0">Notification Preferences</h5>
                <div class="muted">Choose how you receive updates.</div>
            </div>
            <button class="btn btn-light" type="button" data-close="modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">App Notifications</div>
                <div class="muted">Receive alerts in the app.</div>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">Email Notifications</div>
                <div class="muted">Get updates by email.</div>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">SMS Alerts</div>
                <div class="muted">Text message reminders.</div>
            </div>
            <label class="switch">
                <input type="checkbox">
                <span class="slider"></span>
            </label>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">Assignment Alerts</div>
                <div class="muted">Assignments and tasks.</div>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
        <div class="d-flex align-items-center justify-content-between py-2">
            <div>
                <div class="fw-semibold">Attendance Alerts</div>
                <div class="muted">Appointment attendance reminders.</div>
            </div>
            <label class="switch">
                <input type="checkbox">
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <div class="center-modal" id="languageModal" aria-hidden="true">
        <div class="modal-header">
            <h5 class="mb-0">Language Selection</h5>
            <button class="btn btn-light" type="button" data-close="modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="lang-options">
            <button class="lang-btn" type="button" data-lang="en">English</button>
            <button class="lang-btn" type="button" data-lang="hi">Hindi</button>
            <button class="lang-btn" type="button" data-lang="gu">Gujarati</button>
        </div>
    </div>

    <div class="center-modal modal-lg" id="privacyModal" aria-hidden="true">
        <div class="modal-header">
            <div>
                <h4 class="mb-0">Privacy Settings</h4>
                <div class="muted">Control visibility and security options.</div>
            </div>
            <button class="btn btn-light" type="button" data-close="modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="page-card">
                    <h6 class="mb-3">Profile Visibility</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">Show profile to others</div>
                            <div class="muted">Toggle your public visibility.</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card">
                    <h6 class="mb-3">Data Sharing</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">Share anonymized data</div>
                            <div class="muted">Allow research insights.</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card">
                    <h6 class="mb-3">Security Options</h6>
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-outline-primary" type="button">Enable 2FA</button>
                        <button class="btn btn-outline-secondary" type="button">Manage Devices</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card">
                    <h6 class="mb-3">Logout Everywhere</h6>
                    <div class="muted mb-3">This will sign you out of all active sessions.</div>
                    <button class="btn btn-danger" type="button" id="logoutAllBtn">Logout from all devices</button>
                </div>
            </div>
        </div>
    </div>

    <div class="center-modal" id="themeModal" aria-hidden="true">
        <div class="modal-header">
            <div>
                <h5 class="mb-0">Light/Dark Mode</h5>
                <div class="muted">Switch the app appearance instantly.</div>
            </div>
            <button class="btn btn-light" type="button" data-close="modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <div class="fw-semibold">Enable Dark Mode</div>
                <div class="muted">Applies without reloading the page.</div>
            </div>
            <label class="switch">
                <input type="checkbox" id="themeToggle">
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <div class="toast-container">
        <div class="toast" id="statusToast">
            <div class="fw-semibold" id="toastTitle">Saved</div>
            <div class="muted" id="toastMessage">Your changes have been saved.</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const optionCards = document.querySelectorAll('.option-card');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const modals = document.querySelectorAll('.center-modal');
        const themeToggle = document.getElementById('themeToggle');
        const toast = document.getElementById('statusToast');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');

        const i18n = {
            en: {
                pageTitle: 'Settings & Preferences',
                pageSubtitle: 'Manage your profile, security, and app preferences.',
                editProfileTitle: 'Edit Profile',
                editProfileDesc: 'Update your name, phone, and photo.',
                passwordTitle: 'Change Password',
                passwordDesc: 'Keep your account secure with a new password.',
                notificationsTitle: 'Notification Preferences',
                notificationsDesc: 'Choose how you receive updates.',
                languageTitle: 'Language Selection',
                languageDesc: 'Switch between English, Hindi, Gujarati.',
                privacyTitle: 'Privacy Settings',
                privacyDesc: 'Control data sharing and account security.',
                themeTitle: 'Light/Dark Mode',
                themeDesc: 'Instantly adjust the app appearance.'
            },
            hi: {
                pageTitle: 'Settings & Preferences',
                pageSubtitle: 'Apna profile, security aur preferences manage karein.',
                editProfileTitle: 'Edit Profile',
                editProfileDesc: 'Naam, phone aur photo update karein.',
                passwordTitle: 'Change Password',
                passwordDesc: 'Account ko secure banayein.',
                notificationsTitle: 'Notification Preferences',
                notificationsDesc: 'Updates kaise mile, chunen.',
                languageTitle: 'Language Selection',
                languageDesc: 'English, Hindi, Gujarati mein switch karein.',
                privacyTitle: 'Privacy Settings',
                privacyDesc: 'Data sharing aur security control karein.',
                themeTitle: 'Light/Dark Mode',
                themeDesc: 'Theme turant badlein.'
            },
            gu: {
                pageTitle: 'Settings & Preferences',
                pageSubtitle: 'Profile, security ane preferences manage karo.',
                editProfileTitle: 'Edit Profile',
                editProfileDesc: 'Naam, phone ane photo update karo.',
                passwordTitle: 'Change Password',
                passwordDesc: 'Account secure rakho.',
                notificationsTitle: 'Notification Preferences',
                notificationsDesc: 'Updates kem male te pasand karo.',
                languageTitle: 'Language Selection',
                languageDesc: 'English, Hindi, Gujarati ma switch karo.',
                privacyTitle: 'Privacy Settings',
                privacyDesc: 'Data sharing ane security control karo.',
                themeTitle: 'Light/Dark Mode',
                themeDesc: 'Theme turant badlo.'
            }
        };

        function showToast(title, message) {
            toastTitle.textContent = title;
            toastMessage.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2200);
        }

        function setActive(card) {
            optionCards.forEach(item => item.classList.remove('active'));
            if (card) {
                card.classList.add('active');
            }
        }

        function closeAllModals() {
            modals.forEach(modal => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            });
            modalBackdrop.classList.remove('show');
        }

        function openModal(modalId) {
            closeAllModals();
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            modalBackdrop.classList.add('show');
        }

        function applyLanguage(lang) {
            const strings = i18n[lang] || i18n.en;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (strings[key]) {
                    el.textContent = strings[key];
                }
            });
            document.getElementById('pageTitle').textContent = strings.pageTitle;
            document.getElementById('pageSubtitle').textContent = strings.pageSubtitle;
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.lang === lang);
            });
            localStorage.setItem('medc-lang', lang);
            showToast('Language Updated', 'Your preference is saved.');
        }

        function applyTheme(enabled) {
            document.body.classList.toggle('theme-dark', enabled);
            localStorage.setItem('medc-theme', enabled ? 'dark' : 'light');
        }

        optionCards.forEach(card => {
            card.addEventListener('click', () => {
                const action = card.dataset.action;
                setActive(card);
                if (action === 'profile') {
                    openModal('profileModal');
                } else if (action === 'password') {
                    openModal('passwordModal');
                } else if (action === 'notifications') {
                    openModal('notificationsModal');
                } else if (action === 'language') {
                    openModal('languageModal');
                } else if (action === 'privacy') {
                    openModal('privacyModal');
                } else if (action === 'theme') {
                    openModal('themeModal');
                }
            });
        });

        document.querySelectorAll('[data-close="modal"]').forEach(button => {
            button.addEventListener('click', closeAllModals);
        });

        modalBackdrop.addEventListener('click', closeAllModals);

        document.getElementById('profileForm').addEventListener('submit', event => {
            event.preventDefault();
            const form = event.currentTarget;
            form.classList.add('loading');
            setTimeout(() => {
                form.classList.remove('loading');
                closeAllModals();
                showToast('Profile Updated', 'Your profile changes are saved.');
            }, 800);
        });

        document.getElementById('logoutAllBtn').addEventListener('click', () => {
            showToast('Logged out', 'All devices have been signed out.');
        });

        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.addEventListener('click', () => applyLanguage(btn.dataset.lang));
        });

        document.querySelectorAll('.toggle-visibility').forEach(button => {
            button.addEventListener('click', () => {
                const target = button.dataset.target;
                const input = document.querySelector(`[name="${target}"]`);
                if (!input) return;
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                button.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        });

        document.getElementById('passwordForm').addEventListener('submit', event => {
            event.preventDefault();
            const form = event.currentTarget;
            const newPassword = form.newPassword.value.trim();
            const confirmPassword = form.confirmPassword.value.trim();
            const errorEl = document.getElementById('passwordError');
            errorEl.textContent = '';

            if (newPassword.length < 6) {
                errorEl.textContent = 'Password must be at least 6 characters.';
                return;
            }
            if (newPassword !== confirmPassword) {
                errorEl.textContent = 'Passwords do not match.';
                return;
            }

            const button = document.getElementById('passwordSave');
            button.classList.add('loading');
            setTimeout(() => {
                button.classList.remove('loading');
                closeAllModals();
                showToast('Password Updated', 'Your password has been changed.');
                form.reset();
            }, 900);
        });

        themeToggle.addEventListener('change', event => {
            applyTheme(event.target.checked);
            showToast('Theme Updated', event.target.checked ? 'Dark mode enabled.' : 'Light mode enabled.');
        });

        const savedTheme = localStorage.getItem('medc-theme');
        if (savedTheme === 'dark') {
            themeToggle.checked = true;
            applyTheme(true);
        }

        const savedLang = localStorage.getItem('medc-lang') || 'en';
        applyLanguage(savedLang);

        const hash = window.location.hash.replace('#', '');
        if (hash) {
            const target = document.getElementById(hash);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setActive(target);
                const action = target.dataset.action;
                if (action === 'profile') {
                    openModal('profileModal');
                } else if (action === 'password') {
                    openModal('passwordModal');
                } else if (action === 'notifications') {
                    openModal('notificationsModal');
                } else if (action === 'language') {
                    openModal('languageModal');
                } else if (action === 'privacy') {
                    openModal('privacyModal');
                } else if (action === 'theme') {
                    openModal('themeModal');
                }
            }
        }

        window.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeAllModals();
            }
        });
    </script>
</body>
</html>
