<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
try {
    $conn = new mysqli("localhost", "root", "", "nexus_ims_db_dummy");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

$sql = "(SELECT
    gd.dist_id,
    i.box_no,
    CASE
        WHEN gd.borrower_type = 'employee' THEN TRIM(CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), '')))
        WHEN gd.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
    END AS borrower_name,
    d_mrep.department_name AS borrower_department,
    TRIM(CONCAT(m.emp_fname, ' ', COALESCE(m.emp_minit, ''), ' ', m.emp_lname, IF(m.emp_suffix IS NOT NULL AND m.emp_suffix != '', CONCAT(' ', m.emp_suffix), ''))) AS accountable_name,
    d_mrep.department_name AS accountable_department,
    s.student_id,
    s.section,
    id.item_name,
    id.category_id,
    i.serial_no,
    gd.received_date,
    gd.borrower_type,
    gd.stud_rec_id,
    s.gender,
    s.contact_number,
    s.email,
    s.stud_address,
    gd.photo_path,
    'gadget' as item_type
FROM gadget_distribution gd
LEFT JOIN items i ON gd.item_id = i.item_id
LEFT JOIN item_desc id ON i.item_desc_id = id.item_desc_id
LEFT JOIN students s ON gd.stud_rec_id = s.stud_rec_id AND gd.borrower_type = 'student'
LEFT JOIN employees e ON gd.receiver_id = e.emp_rec_id AND gd.borrower_type = 'employee'
LEFT JOIN employees m ON gd.mrep_id = m.emp_rec_id
LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id
LEFT JOIN departments d_emp ON e.department_id = d_emp.department_id
LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id
WHERE gd.status_id = 3)

UNION ALL

(SELECT
    bd.bag_dist_id as dist_id,
    bi.box_no,
    CASE
        WHEN bd.borrower_type = 'employee' THEN TRIM(CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), '')))
        WHEN bd.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
    END AS borrower_name,
    d_mrep.department_name AS borrower_department,
    TRIM(CONCAT(m.emp_fname, ' ', COALESCE(m.emp_minit, ''), ' ', m.emp_lname, IF(m.emp_suffix IS NOT NULL AND m.emp_suffix != '', CONCAT(' ', m.emp_suffix), ''))) AS accountable_name,
    d_mrep.department_name AS accountable_department,
    s.student_id,
    s.section,
    id.item_name,
    id.category_id,
    bi.serial_no,
    bd.received_date,
    bd.borrower_type,
    bd.stud_rec_id,
    s.gender,
    s.contact_number,
    s.email,
    s.stud_address,
    bd.photo_path,
    'bag' as item_type
FROM bag_distribution bd
LEFT JOIN bag_items bi ON bd.bag_item_id = bi.bag_item_id
LEFT JOIN item_desc id ON bi.item_desc_id = id.item_desc_id
LEFT JOIN students s ON bd.stud_rec_id = s.stud_rec_id AND bd.borrower_type = 'student'
LEFT JOIN employees e ON bd.receiver_id = e.emp_rec_id AND bd.borrower_type = 'employee'
LEFT JOIN employees m ON bd.mrep_id = m.emp_rec_id
LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id
LEFT JOIN departments d_emp ON e.department_id = d_emp.department_id
LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id
WHERE bd.status_id = 3)

ORDER BY received_date DESC";

$result = $conn->query($sql);
$records = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

$conn->close();

echo json_encode($records);
?>
