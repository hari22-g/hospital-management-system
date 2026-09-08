<?php
// Include the database connection
// Include the database connection
include_once(dirname(__DIR__) . "/connection/config.php");

/**
 * Fetch hospital information from the database
 * @return array Hospital information
 */
function getHospitalInfo() {
    global $conn;
    $sql = "SELECT * FROM hospital_info ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        // Return default values if no record exists
        return [
            'hospital_name' => 'MedC Hospital',
            'phone_number' => '+91-9876543210',
            'address' => '123 Healthcare Avenue, Medical City, India',
            'google_maps_link' => 'https://maps.google.com/?q=hospital',
            'emergency_helpline' => '+91-9876543211',
            'hospital_timings' => '24x7',
            'opd_timings' => 'Mon-Sat: 8AM-8PM',
            'specialties' => 'Cardiology,Orthopedics,Pediatrics,Dermatology,Ophthalmology',
            'facilities' => 'ICU,Operation Theater,Laboratory,Imaging,MRI,CT Scan',
            'accreditations' => 'NABH,NABL',
            'insurance_accepted' => 'All Major Insurances Accepted'
        ];
    }
}

/**
 * Fetch doctor schedule for a specific doctor
 * @param int $doctor_id Doctor ID
 * @return array Doctor schedule
 */
function getDoctorSchedule($doctor_id = null) {
    global $conn;
    
    if ($doctor_id) {
        $sql = "SELECT ds.*, d.f_name, d.l_name, d.specialization 
                FROM doctor_schedule ds 
                JOIN doctor d ON ds.doctor_id = d.d_id 
                WHERE ds.doctor_id = ? AND ds.is_available = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $doctor_id);
    } else {
        $sql = "SELECT ds.*, d.f_name, d.l_name, d.specialization 
                FROM doctor_schedule ds 
                JOIN doctor d ON ds.doctor_id = d.d_id 
                WHERE ds.is_available = 1";
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedule = [];
    while ($row = $result->fetch_assoc()) {
        $schedule[] = $row;
    }
    
    return $schedule;
}

/**
 * Fetch all doctors with their information
 * @return array Doctors information
 */
function getAllDoctors() {
    global $conn;
    $sql = "SELECT d.*, s.schedule_id, s.day_of_week, s.available_from, s.available_to
            FROM doctor d
            LEFT JOIN doctor_schedule s ON d.d_id = s.doctor_id
            WHERE d.approval = 'approved'
            ORDER BY d.specialization, d.f_name";
    
    $result = $conn->query($sql);
    
    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    
    return $doctors;
}
?>