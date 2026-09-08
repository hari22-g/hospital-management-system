-- Appointment Scheduling Tables
ALTER TABLE appointment ADD COLUMN IF NOT EXISTS reason VARCHAR(255);
ALTER TABLE appointment ADD COLUMN IF NOT EXISTS notes TEXT;
ALTER TABLE appointment ADD COLUMN IF NOT EXISTS reminder_sent TINYINT DEFAULT 0;
ALTER TABLE appointment ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Doctor profile enhancements
ALTER TABLE doctor ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `doctor_schedule` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) DEFAULT 30,
  `is_available` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`schedule_id`),
  KEY `doctor_id` (`doctor_id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`d_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Billing & Invoicing Tables
CREATE TABLE IF NOT EXISTS `invoices` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11),
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11),
  `invoice_date` date NOT NULL,
  `due_date` date,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0,
  `tax` decimal(10,2) NOT NULL DEFAULT 0,
  `discount` decimal(10,2) NOT NULL DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0,
  `status` enum('Draft','Sent','Paid','Partial','Overdue','Cancelled') DEFAULT 'Draft',
  `payment_method` varchar(50),
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`invoice_id`),
  KEY `patient_id` (`patient_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `doctor_id` (`doctor_id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patient` (`pid`) ON DELETE CASCADE,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointment` (`appointment_id`) ON DELETE SET NULL,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`d_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `invoice_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `rate` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `item_type` enum('consultation','test','procedure','medicine','other') DEFAULT 'consultation',
  PRIMARY KEY (`item_id`),
  KEY `invoice_id` (`invoice_id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100),
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `patient_id` (`patient_id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patient` (`pid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Staff Management Tables
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role` enum('admin','doctor','receptionist','nurse','pharmacist','lab_technician') DEFAULT 'receptionist';
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `department` varchar(100);
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `phone` varchar(15);
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `address` text;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `hire_date` date;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) DEFAULT 1;

CREATE TABLE IF NOT EXISTS `staff_profiles` (
  `staff_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `qualifications` text,
  `experience_years` int(11),
  `license_number` varchar(100),
  `license_expiry` date,
  `shift` enum('morning','afternoon','night','flexible') DEFAULT 'flexible',
  `salary` decimal(10,2),
  `emergency_contact` varchar(100),
  `emergency_phone` varchar(15),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `user_id_unique` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(50) NOT NULL,
  `resource` varchar(100) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `role_resource` (`role`, `resource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inventory & Pharmacy Tables
ALTER TABLE `medications` ADD COLUMN IF NOT EXISTS `medicine_name` varchar(255);
ALTER TABLE `medications` ADD COLUMN IF NOT EXISTS `unit` varchar(50);
ALTER TABLE `medications` ADD COLUMN IF NOT EXISTS `stock_level` int(11) DEFAULT 0;
ALTER TABLE `medications` ADD COLUMN IF NOT EXISTS `expiry_date` date;
ALTER TABLE `medications` ADD COLUMN IF NOT EXISTS `low_stock_threshold` int(11) DEFAULT 10;
ALTER TABLE `medications` ADD COLUMN IF NOT EXISTS `vendor_id` int(11);
ALTER TABLE `medications` ADD COLUMN IF NOT EXISTS `unit_price` decimal(10,2);
ALTER TABLE `medications` ADD COLUMN IF NOT EXISTS `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS `stock_movements` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_id` int(11) NOT NULL,
  `movement_type` enum('purchase','usage','adjustment','damaged','expired') DEFAULT 'usage',
  `quantity` int(11) NOT NULL,
  `notes` text,
  `created_by` int(11),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`medicine_id`) REFERENCES `medications` (`drug_id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `vendors` (
  `vendor_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(255) NOT NULL,
  `contact_person` varchar(100),
  `phone` varchar(15),
  `email` varchar(100),
  `address` text,
  `city` varchar(100),
  `payment_terms` varchar(100),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Security & Audit Tables
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `action` varchar(100) NOT NULL,
  `resource` varchar(100) NOT NULL,
  `resource_id` int(11),
  `changes` json,
  `ip_address` varchar(45),
  `user_agent` text,
  `status` varchar(20),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `action` (`action`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `backup_logs` (
  `backup_id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `backup_file` varchar(255),
  `backup_size` bigint,
  `backup_type` enum('full','incremental') DEFAULT 'full',
  `status` enum('success','failed','in_progress') DEFAULT 'in_progress',
  `location` varchar(255),
  `notes` text,
  PRIMARY KEY (`backup_id`),
  KEY `backup_date` (`backup_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default role permissions
INSERT IGNORE INTO `role_permissions` (`role`, `resource`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
-- Admin - full access
('admin', 'patients', 1, 1, 1, 1),
('admin', 'appointments', 1, 1, 1, 1),
('admin', 'invoices', 1, 1, 1, 1),
('admin', 'staff', 1, 1, 1, 1),
('admin', 'inventory', 1, 1, 1, 1),
('admin', 'analytics', 1, 0, 0, 0),
('admin', 'audit_logs', 1, 0, 0, 0),

-- Doctor - patient, appointment, own invoices
('doctor', 'patients', 1, 0, 0, 0),
('doctor', 'appointments', 1, 0, 1, 0),
('doctor', 'invoices', 1, 0, 0, 0),

-- Receptionist - appointments, patient basic, invoices view
('receptionist', 'patients', 1, 1, 0, 0),
('receptionist', 'appointments', 1, 1, 1, 0),
('receptionist', 'invoices', 1, 1, 0, 0),

-- Nurse - appointments, patient medical info
('nurse', 'patients', 1, 0, 0, 0),
('nurse', 'appointments', 1, 0, 1, 0),

-- Pharmacist - inventory view/edit, patient view
('pharmacist', 'patients', 1, 0, 0, 0),
('pharmacist', 'inventory', 1, 1, 1, 0),

-- Lab Technician - reports, inventory
('lab_technician', 'patients', 1, 0, 0, 0),
('lab_technician', 'inventory', 1, 0, 1, 0);

-- Prescription & Pharmacy Workflow Tables
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `prescription_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11),
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `symptoms` text,
  `diagnosis` text,
  `notes` text,
  `recommended_tests` text,
  `follow_up_date` date,
  `status` enum('Pending','Processing','Ready','Dispensed','Completed') DEFAULT 'Pending',
  `pharmacy_notes` text,
  `invoice_id` int(11),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`prescription_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `invoice_id` (`invoice_id`),
  FOREIGN KEY (`appointment_id`) REFERENCES `appointment` (`appointment_id`) ON DELETE SET NULL,
  FOREIGN KEY (`patient_id`) REFERENCES `patient` (`pid`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`d_id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `prescription_medicines` (
  `medicine_id` int(11) NOT NULL AUTO_INCREMENT,
  `prescription_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `generic_name` varchar(255),
  `dosage` varchar(100),
  `frequency` varchar(50),
  `food_timing` enum('Before Food','After Food','With Food') DEFAULT 'After Food',
  `duration_days` int(11),
  `quantity` int(11) DEFAULT 0,
  `dispensed_quantity` int(11) DEFAULT 0,
  `availability` enum('Available','Not Available','Partial') DEFAULT 'Available',
  `unit_price` decimal(10,2) DEFAULT 0,
  `instructions` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`medicine_id`),
  KEY `prescription_id` (`prescription_id`),
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `prescription_tests` (
  `test_id` int(11) NOT NULL AUTO_INCREMENT,
  `prescription_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `notes` varchar(255),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`test_id`),
  KEY `prescription_id` (`prescription_id`),
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
