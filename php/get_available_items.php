<?php
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

$baseSqlGadget = "SELECT
    gd.dist_id,
    i.box_no,
    CASE
        WHEN gd.borrower_type = 'employee' THEN TRIM(CONCAT(e_borrower.emp_fname, ' ', COALESCE(e_borrower.emp_minit, ''), ' ', e_borrower.emp_lname, IF(e_borrower.emp_suffix IS NOT NULL AND e_borrower.emp_suffix != '', CONCAT(' ', e_borrower.emp_suffix), '')))
        WHEN gd.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
    END AS borrower_name,
    d_mrep.department_name AS borrower_department, /* Changed to accountable's department - but aliased as borrower_department for now */
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
LEFT JOIN employees e_borrower ON gd.receiver_id = e_borrower.emp_rec_id AND gd.borrower_type = 'employee'
LEFT JOIN employees m ON gd.mrep_id = m.emp_rec_id
LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id
LEFT JOIN departments d_emp ON e_borrower.department_id = d_emp.department_id
LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id
WHERE gd.status_id = 1";

$baseSqlBag = "SELECT
    bd.bag_dist_id as dist_id,
    bi.box_no,
    CASE
        WHEN bd.borrower_type = 'employee' THEN TRIM(CONCAT(e_borrower.emp_fname, ' ', COALESCE(e_borrower.emp_minit, ''), ' ', e_borrower.emp_lname, IF(e_borrower.emp_suffix IS NOT NULL AND e_borrower.emp_suffix != '', CONCAT(' ', e_borrower.emp_suffix), '')))
        WHEN bd.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
    END AS borrower_name,
    d_mrep.department_name AS borrower_department, /* Changed to accountable's department - but aliased as borrower_department for now */
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
LEFT JOIN employees e_borrower ON bd.receiver_id = e_borrower.emp_rec_id AND bd.borrower_type = 'employee'
LEFT JOIN employees m ON bd.mrep_id = m.emp_rec_id
LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id
LEFT JOIN departments d_emp ON e_borrower.department_id = d_emp.department_id
LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id
WHERE bd.status_id = 1";

$whereClause = "";
$params = [];
$paramTypes = "";

// Check if the user is not an admin
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'admin') {
    if (isset($_SESSION['department_id'])) {
        $whereClause = " AND m.department_id = ?";
        // Add department filter to both subqueries, so we need to bind twice
        $params[] = $_SESSION['department_id'];
        $params[] = $_SESSION['department_id'];
        $paramTypes .= "ii";
    } else {
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
        $records[] = [
            'dist_id' => $row['dist_id'],
            'box_no' => $row['box_no'],
            'accountable_name' => $row['accountable_name'],
            'department_name' => $row['accountable_department'], // Use accountable's department here
            'borrower_name' => $row['borrower_name'],
            'section' => $row['section'],
            'item_name' => $row['item_name'],
            'serial_no' => $row['serial_no'],
            'received_date' => $row['received_date'],
            'borrower_type' => $row['borrower_type'],
            'stud_rec_id' => $row['stud_rec_id'],
            'gender' => $row['gender'],
            'contact_number' => $row['contact_number'],
            'email' => $row['email'],
            'stud_address' => $row['stud_address'],
            'photo_path' => $row['photo_path'],
            'item_type' => $row['item_type'],
            'category_id' => $row['category_id'],
            'accountable_dept_id' => $row['accountable_dept_id']
        ];
    }
}

if ($stmt) {
    $stmt->close();
}
$conn->close();

echo json_encode($records);
?>