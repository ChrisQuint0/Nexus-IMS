<?php
session_start();

// Include database connection (create db.php if you haven't already)
include 'db.php';

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Get user type from session
$userType = $_SESSION['user_type'] ?? '';
$sessionDepartmentId = $_SESSION['department_id'] ?? null;

// Get selected columns from POST request
$selectedColumns = $_POST['columns'] ?? [];

// Get selected department from POST request
$selectedDepartment = isset($_POST['department']) ? sanitize($_POST['department']) : 'all';

// Define column mapping between ID (table header text) and database field
$columnMapping = [
    'Action' => null, // No direct database field
    'Box No.' => 'ad.box_no',
    'Accountable' => "CASE
                        WHEN ad.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), ''))
                        WHEN ad.borrower_type = 'employee' THEN CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), ''))
                        ELSE 'Unknown'
                    END",
    'Department' => 'd_borrower.department_name', // Department of the borrower
    'Name' => "CASE
                WHEN ad.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), ''))
                WHEN ad.borrower_type = 'employee' THEN CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), ''))
                ELSE 'Unknown'
            END",
    'Section' => 'ad.section',
    'Item Name' => 'idesc.item_name',
    'Serial No.' => 'i.serial_no',
    'Received Date' => 'DATE(ad.received_date)',
    'Return Date' => 'DATE(rl.returned_at)',
    'Condition' => 'rl.item_condition',
    'Remarks' => 'rl.remarks'
];

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="return_logs_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header row
$csvHeader = [];
$selectedSqlColumns = [];
foreach ($selectedColumns as $columnHeader) {
    if (isset($columnMapping[$columnHeader])) {
        $csvHeader[] = $columnHeader;
        if ($columnMapping[$columnHeader]) {
            $selectedSqlColumns[] = $columnMapping[$columnHeader] . ' AS `' . $columnHeader . '`';
        }
    }
}
fputcsv($output, $csvHeader);

// Build the SQL query
$sql = "SELECT " . implode(', ', $selectedSqlColumns) . "
        FROM return_logs rl
        JOIN archive_distribution ad ON rl.dist_id = ad.record_id
        LEFT JOIN students s ON ad.stud_rec_id = s.stud_rec_id AND ad.borrower_type = 'student'
        LEFT JOIN employees e ON ad.receiver_id = e.emp_rec_id AND ad.borrower_type = 'employee'
        LEFT JOIN items i ON ad.item_id = i.item_id
        LEFT JOIN item_desc idesc ON i.item_desc_id = idesc.item_desc_id
        LEFT JOIN employees emp_mrep ON ad.mrep_id = emp_mrep.emp_rec_id
        LEFT JOIN departments d_mrep ON emp_mrep.department_id = d_mrep.department_id
        LEFT JOIN departments d_borrower ON
            (ad.borrower_type = 'student' AND s.dept_id = d_borrower.department_id) OR
            (ad.borrower_type = 'employee' AND e.department_id = d_borrower.department_id)
        WHERE rl.returned_at IS NOT NULL";

// Apply department filter
if ($userType === 'department_head' && $sessionDepartmentId !== null) {
    $sql .= " AND (
        ad.borrower_type = 'student' AND EXISTS (SELECT 1 FROM students WHERE stud_rec_id = ad.stud_rec_id AND dept_id = ?) OR
        ad.borrower_type = 'employee' AND EXISTS (SELECT 1 FROM employees WHERE emp_rec_id = ad.receiver_id AND department_id = ?)
    )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $sessionDepartmentId, $sessionDepartmentId);
} elseif ($selectedDepartment !== 'all' && $userType === 'admin') {
    $sql .= " AND (
        ad.borrower_type = 'student' AND EXISTS (SELECT 1 FROM students WHERE stud_rec_id = ad.stud_rec_id AND dept_id = ?) OR
        ad.borrower_type = 'employee' AND EXISTS (SELECT 1 FROM employees WHERE emp_rec_id = ad.receiver_id AND department_id = ?)
    )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $selectedDepartment, $selectedDepartment);
} else {
    $stmt = $conn->prepare($sql);
}

if ($stmt === false) {
    error_log('Prepare failed: ' . $conn->error);
    die('Prepare failed: ' . $conn->error);
}

if (!$stmt->execute()) {
    error_log('Execute failed: ' . $stmt->error);
    die('Execute failed: ' . $stmt->error);
}

$result = $stmt->get_result();

// Write data rows to CSV
while ($row = $result->fetch_assoc()) {
    $csvRow = [];
    foreach ($csvHeader as $header) {
        $csvRow[] = $row[$header] ?? '';
    }
    fputcsv($output, $csvRow);
}

// Close database connection
$stmt->close();
$conn->close();

// Close the output stream
fclose($output);
exit;
?>