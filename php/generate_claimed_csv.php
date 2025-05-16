<?php
// Get parameters from POST request
$userType = isset($_POST['userType']) ? $_POST['userType'] : '';
$department = isset($_POST['department']) ? $_POST['department'] : '';
$onlyDepartment = isset($_POST['onlyDepartment']) ? $_POST['onlyDepartment'] : '';

// Get selected columns, defaulting to all columns if none selected
$selectedColumns = isset($_POST['columns']) ? json_decode($_POST['columns'], true) : [];

// Default to these columns if none selected
if (empty($selectedColumns)) {
    $selectedColumns = ['borrower_name', 'serial_no'];
}

// Include database connection
include 'db.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="claims_inventory_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Define column mapping between ID and display name
$columnMapping = [
    'dist_id' => 'Distribution ID',
    'borrower_type' => 'Borrower Type',
    'item_id' => 'Item ID',
    'serial_no' => 'Serial No',
    'item_name' => 'Item Name',
    'received_date' => 'Received Date',
    'returned_date' => 'Return Date',
    'borrower_name' => 'Borrower Name',
    'section' => 'Section',
    'MR' => 'MR',
    'department' => 'Department'
];

// Define SQL column mapping (database field names)
$sqlColumns = [
    'dist_id' => 'gd.dist_id',
    'borrower_type' => 'gd.borrower_type',
    'item_id' => 'gd.item_id',
    'serial_no' => 'i.serial_no',
    'item_name' => 'idesc.item_name',
    'received_date' => 'gd.received_date',
    'returned_date' => 'gd.return_date',
    'borrower_name' => "CASE
                WHEN gd.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                WHEN gd.borrower_type IS NOT NULL THEN CONCAT(e.emp_fname, ' ', e.emp_lname)
                ELSE 'Unknown'
            END",
    'section' => 's.section',
    'MR' => "CONCAT(emp.emp_fname, ' ', emp.emp_lname)",
    'department' => 'd.department_name'
];

// Build CSV header row with only selected columns
$csvHeader = [];
foreach ($selectedColumns as $columnId) {
    if (isset($columnMapping[$columnId])) {
        $csvHeader[] = $columnMapping[$columnId];
    }
}
fputcsv($output, $csvHeader);

// Build the SQL SELECT part with only selected columns
$selectColumns = [];
foreach ($selectedColumns as $columnId) {
    if (isset($sqlColumns[$columnId])) {
        $selectColumns[] = $sqlColumns[$columnId] . " AS " . $columnId;
    }
}
$selectClause = implode(", ", $selectColumns);

// Base query with joins
$baseQuery = "FROM
    gadget_distribution gd
LEFT JOIN students s ON gd.stud_rec_id = s.stud_rec_id
LEFT JOIN employees e ON gd.receiver_id = e.emp_rec_id
LEFT JOIN items i ON gd.item_id = i.item_id
LEFT JOIN item_desc idesc ON i.item_desc_id = idesc.item_desc_id
LEFT JOIN employees emp ON gd.mrep_id = emp.emp_rec_id
LEFT JOIN departments d ON emp.department_id = d.department_id
WHERE
    gd.status_id = 3";

if ($userType != 'admin') {
    // Build query for department manager view
    $sql = "SELECT " . $selectClause . " " . $baseQuery . " AND emp.department_id = ?";
    $debug_department = intval($department);

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $debug_department);
    if (!$stmt->execute()) {
        die('Execute failed: ' . $stmt->error);
    }
} else if ($userType == 'admin') {
    if ($onlyDepartment == 0) {
        // Query for ALL departments when onlyDepartment is 0
        $sql = "SELECT " . $selectClause . " " . $baseQuery;

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Prepare failed: ' . $conn->error);
        }
        if (!$stmt->execute()) {
            die('Execute failed: ' . $stmt->error);
        }
    } else {
        // Build query for admin view with specific department
        $sql = "SELECT " . $selectClause . " " . $baseQuery . " AND emp.department_id = ?";
        $debug_department = intval($onlyDepartment);

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $debug_department);
        if (!$stmt->execute()) {
            die('Execute failed: ' . $stmt->error);
        }
    }
} else {
    // fallback for missing or invalid data
    fputcsv($output, ['Error: No valid department selected']);
    fclose($output);
    exit;
}

$result = $stmt->get_result();

// Write data rows to CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $csvRow = [];
        foreach ($selectedColumns as $columnId) {
            // Handle special cases for formatting or default values
            if ($columnId == 'returned_date' && empty($row[$columnId])) {
                $csvRow[] = 'Not Returned';
            } else if ($columnId == 'department' && empty($row[$columnId])) {
                $csvRow[] = 'Unknown';
            } else {
                $csvRow[] = $row[$columnId] ?? '';
            }
        }
        fputcsv($output, $csvRow);
    }
} else {
    // Add a row indicating no data
    $emptyRow = array_fill(0, count($csvHeader), '');
    $emptyRow[0] = 'No claimed items found';
    fputcsv($output, $emptyRow);
}

// Close database connection
$stmt->close();
$conn->close();

// Close the output stream
fclose($output);
exit;
