<?php
require_once 'db_functions.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get database connection
$conn = get_database_connection();

$baseSqlGadget = "SELECT
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
    'gadget' as item_type,
    m.department_id AS accountable_dept_id
FROM gadget_distribution gd
LEFT JOIN items i ON gd.item_id = i.item_id
LEFT JOIN item_desc id ON i.item_desc_id = id.item_desc_id
LEFT JOIN students s ON gd.stud_rec_id = s.stud_rec_id AND gd.borrower_type = 'student'
LEFT JOIN employees e ON gd.receiver_id = e.emp_rec_id AND gd.borrower_type = 'employee'
LEFT JOIN employees m ON gd.mrep_id = m.emp_rec_id
LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id
LEFT JOIN departments d_emp ON e.department_id = d_emp.department_id
LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id
WHERE gd.status_id = 2";

$baseSqlBag = "SELECT
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
    'bag' as item_type,
    m.department_id AS accountable_dept_id
FROM bag_distribution bd
LEFT JOIN bag_items bi ON bd.bag_item_id = bi.bag_item_id
LEFT JOIN item_desc id ON bi.item_desc_id = id.item_desc_id
LEFT JOIN students s ON bd.stud_rec_id = s.stud_rec_id AND bd.borrower_type = 'student'
LEFT JOIN employees e ON bd.receiver_id = e.emp_rec_id AND bd.borrower_type = 'employee'
LEFT JOIN employees m ON bd.mrep_id = m.emp_rec_id
LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id
LEFT JOIN departments d_emp ON e.department_id = d_emp.department_id
LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id
WHERE bd.status_id = 2";

$whereClause = "";
$params = [];
$paramTypes = "";

// Check user role and apply department filter if needed
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'dept_head') {
    if (isset($_SESSION['department_id'])) {
        $whereClause = " AND m.department_id = ?";
        $params[] = $_SESSION['department_id'];
        $params[] = $_SESSION['department_id'];
        $paramTypes .= "ii";
    } else {
        // If department_id is not set for a dept_head, something is wrong.
        // You might want to log this or handle it differently.
        echo json_encode([]);
        $conn->close();
        exit;
    }
}

// Append the WHERE clause to both queries
$sqlGadget = $baseSqlGadget . $whereClause;
$sqlBag = $baseSqlBag . $whereClause;

// Wrap each query and combine
$sql = "($sqlGadget) UNION ALL ($sqlBag) ORDER BY received_date DESC";

$stmt = $conn->prepare($sql);

// Bind parameters if needed
if (!empty($params) && $stmt) {
    $stmt->bind_param($paramTypes, ...$params);
}

$records = [];

if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

if ($stmt) {
    $stmt->close();
}
$conn->close();

echo json_encode($records);
?>