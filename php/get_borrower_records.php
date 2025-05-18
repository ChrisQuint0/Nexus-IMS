<?php
// Set response headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8"); // ✅ Enforce UTF-8

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
try {
    $conn = new mysqli("localhost", "root", "", "nexus_ims_db_dummy");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // ✅ Ensure UTF-8 character set is used
    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

session_start();
$userType = $_SESSION['user_type'] ?? null;
$userDepartment = null;

if ($userType === 'dept_head' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $stmt_user = $conn->prepare("SELECT department_id FROM users WHERE user_id = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("i", $userId);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if ($result_user->num_rows > 0) {
            $row_user = $result_user->fetch_assoc();
            $departmentId = $row_user['department_id'];

            $stmt_dept_name = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            if ($stmt_dept_name) {
                $stmt_dept_name->bind_param("i", $departmentId);
                $stmt_dept_name->execute();
                $result_dept_name = $stmt_dept_name->get_result();
                if ($result_dept_name->num_rows > 0) {
                    $row_dept_name = $result_dept_name->fetch_assoc();
                    $userDepartment = $row_dept_name['department_name'];
                    error_log("Logged in dept_head's department: " . $userDepartment);
                }
                $stmt_dept_name->close();
            }
        }
        $stmt_user->close();
    }
} else {
    error_log("User is not a department head or user ID is not set.");
}

$whereClauseGadget = "WHERE gd.status_id = 3";
$whereClauseBag = "WHERE bd.status_id = 3";
$filterApplied = false;

if ($userType === 'dept_head' && $userDepartment) {
    $whereClauseGadget .= " AND d_mrep.department_name = ?";
    $whereClauseBag .= " AND d_mrep.department_name = ?";
    $filterApplied = true;
    error_log("Department filter will be applied: " . $userDepartment);
} else {
    error_log("Department filter will NOT be applied.");
}

$sql = "(SELECT
    gd.dist_id,
    i.box_no,
    CASE
        WHEN gd.borrower_type = 'employee' THEN TRIM(CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), '')))
        WHEN gd.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
    END AS borrower_name,
    d_mrep.department_name AS department,
    CASE
        WHEN gd.borrower_type = 'student' THEN d_stud.department_name
        WHEN gd.borrower_type = 'employee' THEN d_emp.department_name
    END AS receiver_department,
    TRIM(CONCAT(m.emp_fname, ' ', COALESCE(m.emp_minit, ''), ' ', m.emp_lname, IF(m.emp_suffix IS NOT NULL AND m.emp_suffix != '', CONCAT(' ', m.emp_suffix), ''))) AS accountable_name,
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
$whereClauseGadget)

UNION ALL

(SELECT
    bd.bag_dist_id as dist_id,
    bi.box_no,
    CASE
        WHEN bd.borrower_type = 'employee' THEN TRIM(CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), '')))
        WHEN bd.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
    END AS borrower_name,
    d_mrep.department_name AS department,
    CASE
        WHEN bd.borrower_type = 'student' THEN d_stud.department_name
        WHEN bd.borrower_type = 'employee' THEN d_emp.department_name
    END AS receiver_department,
    TRIM(CONCAT(m.emp_fname, ' ', COALESCE(m.emp_minit, ''), ' ', m.emp_lname, IF(m.emp_suffix IS NOT NULL AND m.emp_suffix != '', CONCAT(' ', m.emp_suffix), ''))) AS accountable_name,
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
$whereClauseBag)

ORDER BY received_date DESC";

$stmt = $conn->prepare($sql);
$records = [];

if ($stmt) {
    if ($filterApplied) {
        $stmt->bind_param("ss", $userDepartment, $userDepartment);
        error_log("Bound department parameters: " . $userDepartment);
    }

    $result = $stmt->execute();

    if ($result) {
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $records[] = $row;
            }
            $res->free();
        } else if ($stmt->errno) {
            error_log("Error getting result: " . $stmt->error);
            echo json_encode(['error' => 'Error getting result: ' . $stmt->error]);
            exit;
        }
    } else if ($stmt->errno) {
        error_log("Error executing query: " . $stmt->error);
        echo json_encode(['error' => 'Error executing query: ' . $stmt->error]);
        exit;
    }

    $stmt->close();
} else if ($conn->errno) {
    error_log("Error preparing statement: " . $conn->error);
    echo json_encode(['error' => 'Error preparing statement: ' . $conn->error]);
    exit;
}

$conn->close();

// ✅ Output JSON safely
echo json_encode($records, JSON_UNESCAPED_UNICODE);
?>
