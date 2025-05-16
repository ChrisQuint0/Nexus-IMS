<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$conn = new mysqli("localhost", "root", "", "nexus_ims_db_dummy");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection error']);
    exit();
}

$sql = "SELECT
    rl.log_id,
    rl.dist_id,
    ad.record_id,
    ad.photo_path,
    i.box_no,

    CASE
        WHEN ad.borrower_type = 'employee' THEN TRIM(CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), '')))
        WHEN ad.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
    END AS borrower_name,

    CASE
        WHEN ad.borrower_type = 'employee' THEN d_emp.department_name
        WHEN ad.borrower_type = 'student' THEN d_stud.department_name
    END AS borrower_department,

    TRIM(CONCAT(m.emp_fname, ' ', COALESCE(m.emp_minit, ''), ' ', m.emp_lname, IF(m.emp_suffix IS NOT NULL AND m.emp_suffix != '', CONCAT(' ', m.emp_suffix), ''))) AS accountable_name,

    d_mrep.department_name AS accountable_department,

    s.student_id,
    s.section,
    id.item_name,
    i.serial_no,
    ad.received_date,
    ad.return_date,
    rl.returned_at,
    rl.item_condition,
    rl.remarks,
    rl.staff,
    ad.borrower_type,
    s.gender,
    s.contact_number,
    s.email,
    s.stud_address

FROM return_logs rl
-- Corrected JOIN condition: joining on ad.record_id
JOIN archive_distribution ad ON ad.record_id = rl.dist_id
LEFT JOIN items i ON ad.item_id = i.item_id
LEFT JOIN item_desc id ON i.item_desc_id = id.item_desc_id
LEFT JOIN students s ON ad.stud_rec_id = s.stud_rec_id AND ad.borrower_type = 'student'
LEFT JOIN employees e ON ad.receiver_id = e.emp_rec_id AND ad.borrower_type = 'employee'
LEFT JOIN employees m ON ad.mrep_id = m.emp_rec_id
LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id AND ad.borrower_type = 'student'
LEFT JOIN departments d_emp ON e.department_id = d_emp.department_id AND ad.borrower_type = 'employee'
LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id

ORDER BY rl.returned_at DESC;

";

$result = $conn->query($sql);
$logs = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

$conn->close();

echo json_encode($logs);
?>