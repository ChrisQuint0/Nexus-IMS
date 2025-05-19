<?php
require_once 'db_functions.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Get database connection
$conn = get_pdo_connection();

$borrowerType = isset($_GET['borrower_type']) ? filter_var($_GET['borrower_type'], FILTER_SANITIZE_STRING) : '';
$receiverId = isset($_GET['receiver_id']) ? filter_var($_GET['receiver_id'], FILTER_SANITIZE_STRING) : '';

if (empty($borrowerType) || empty($receiverId)) {
    echo json_encode(['success' => false, 'message' => 'Borrower type and receiver ID are required.']);
    exit();
}

$borrower = null;
$tableName = '';
$idColumn = '';

if ($borrowerType === 'student') {
    $tableName = 'students';
    $idColumn = 'student_id';
} else if ($borrowerType === 'employee') {
    $tableName = 'employees';
    $idColumn = 'employee_id';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid borrower type.']);
    exit();
}

$sql = "SELECT ";
if ($borrowerType === 'student') {
    $sql .= "first_name AS first_name, last_name AS last_name, middle_name AS middle_name, suffix AS suffix, gender AS gender, department_id, section AS section, contact_number AS contact_number, email AS email, stud_address AS stud_address FROM $tableName WHERE $idColumn = :receiver_id";
} else if ($borrowerType === 'employee') {
    $sql .= "emp_fname AS first_name, emp_lname AS last_name, emp_minit AS middle_name, emp_suffix AS suffix, emp_email AS email, emp_contact_number, emp_address, department_id FROM $tableName WHERE $idColumn = :receiver_id";
}

$stmt = $conn->prepare($sql);
$stmt->bindParam(':receiver_id', $receiverId);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $borrower = $result;
    if (isset($borrower['gender'])) {
        $borrower['gender'] = strtolower($borrower['gender']);
    }
    echo json_encode(['success' => true, 'borrower' => $borrower]);
} else {
    echo json_encode(['success' => true, 'borrower' => null]);
}

$conn = null;
?>