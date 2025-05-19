<?php
require_once 'db_functions.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
header("Access-Control-Allow-Credentials: true"); // Crucial for session handling

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get database connection
$conn = get_database_connection();

// Start the session to access user information
session_start();

// Retrieve user information from the session
$userType = $_SESSION['user_type'] ?? '';
$departmentId = $_SESSION['department_id'] ?? '';

$sql = "SELECT gd.dist_id,
            gd.borrower_type,
            CASE
                WHEN gd.borrower_type = 'employee' THEN TRIM(CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), '')))
                WHEN gd.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
            END AS borrower_name,
            CASE
                WHEN gd.borrower_type = 'employee' THEN d_emp.department_name
                WHEN gd.borrower_type = 'student' THEN d_stud.department_name
            END AS borrower_department,
            TRIM(CONCAT(m.emp_fname, ' ', COALESCE(m.emp_minit, ''), ' ', m.emp_lname, IF(m.emp_suffix IS NOT NULL AND m.emp_suffix != '', CONCAT(' ', m.emp_suffix), ''))) AS accountable_name,
            i.box_no,
            id.item_name,
            i.serial_no,
            gd.received_date,
            gd.return_date,
            s.student_id,
            s.section,
            s.gender,
            s.contact_number,
            s.email,
            s.stud_address,
            gd.repair_reason,
            gd.status_id,
            st.status_name,
            gd.photo_path,
            m.department_id AS accountable_department_id
    FROM gadget_distribution gd
    LEFT JOIN items i ON gd.item_id = i.item_id
    LEFT JOIN item_desc id ON i.item_desc_id = id.item_desc_id
    LEFT JOIN students s ON gd.stud_rec_id = s.stud_rec_id AND gd.borrower_type = 'student'
    LEFT JOIN employees e ON gd.receiver_id = e.emp_rec_id AND gd.borrower_type = 'employee'
    LEFT JOIN employees m ON gd.mrep_id = m.emp_rec_id
    LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id
    LEFT JOIN departments d_emp ON e.department_id = d_emp.department_id
    LEFT JOIN statuses st ON gd.status_id = st.status_id
    WHERE gd.status_id IN (4, 6)";

if ($userType !== 'admin' && !empty($departmentId)) {
    $sql .= " AND m.department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$repairRecords = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['repair_reason'] = stripslashes($row['repair_reason']);
        $repairRecords[] = $row;
    }
}

$conn->close();

echo json_encode($repairRecords);
?>