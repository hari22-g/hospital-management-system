# Hospital Management System

A PHP and MySQL hospital management system for AROGYA Hospital. The application supports patient, doctor, appointment, prescription, pharmacy, reporting, community, and administration workflows.

## Features

- Patient and doctor management
- Appointment booking and status management
- Doctor dashboards and consultations
- Prescriptions and prescription printing
- Pharmacy billing and medicine status updates
- Patient reports and health information
- Hospital administration portal
- Community posts, comments, likes, and reports
- Hospital facilities, insurance, FAQ, and disease information pages

## Technology Stack

- PHP
- MySQL/MariaDB
- HTML, CSS, and JavaScript
- Bootstrap and PHPMailer components
- XAMPP for local development

## Requirements

- XAMPP with Apache and MySQL
- PHP with the `mysqli` extension enabled
- A web browser

## Installation With XAMPP

1. Clone or copy this repository into the XAMPP web directory:

   ```text
   C:\xampp\htdocs\MedC
   ```

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Open phpMyAdmin at [http://localhost/phpmyadmin](http://localhost/phpmyadmin).

4. Create a database named `medc`.

5. Select the `medc` database, open **Import**, and import:

   ```text
   MedC/sql/setup_complete_pms.sql
   ```

6. Open the application:

   [http://localhost/MedC/MedC/homepage.php](http://localhost/MedC/MedC/homepage.php)

## Important URLs

- Homepage: `http://localhost/MedC/MedC/homepage.php`
- Admin login: `http://localhost/MedC/MedC/admin_login.php`
- Standard login: `http://localhost/MedC/MedC/login.php`
- phpMyAdmin: `http://localhost/phpmyadmin`

The application uses the default local XAMPP database settings in `MedC/connection/config.php`:

```text
Host: localhost
User: root
Password: empty
Database: medc
```

Update that configuration if your MySQL installation uses a different username or password.

## Project Structure

```text
MedC/
├── MedC/              Application source code
├── MedC/connection/   Database connection configuration
├── MedC/sql/          Database setup scripts
├── MedC/include/      Shared PHP files and assets
└── START_PROJECT.bat  Windows startup helper
```

## Security Notice

Do not commit real passwords, API keys, private database credentials, or production configuration files. Local credential files are excluded through `.gitignore`.

## License

This project does not currently include a license. Add a license before distributing it for reuse.
