<?php
// Get parameters from POST request
$onlyDepartment = isset($_POST['onlyDepartment']) ? $_POST['onlyDepartment'] : '0';
$today = date('Y-m-d'); // Format: YYYY-MM-DD
$startDate = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : $today;
$endDate = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : $today;

// Debug: Log the received parameters
error_log("onlyDepartment: " . $onlyDepartment);
error_log("startDate: " . $startDate);
error_log("endDate: " . $endDate);

// Get selected columns, defaulting to all columns if none selected
$selectedColumns = isset($_POST['columns']) ? json_decode($_POST['columns'], true) : [];
error_log("Selected columns: " . print_r($selectedColumns, true));

// Default to these columns if none selected
if (empty($selectedColumns)) {
    $selectedColumns = ['borrower_name', 'serial_no'];
}

// Include database connection
include 'db.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="claimed_logs_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Define column mapping between ID and display name
$columnMapping = [
    'log_id' => 'Log ID',
    'claimed_at' => 'Claimed At',
    'staff' => 'Staff',
    'dist_id' => 'Distribution ID',
    'borrower_type' => 'Borrower Type',
    'borrower_name' => 'Borrower Name',
    'serial_no' => 'Serial No',
    'item_name' => 'Item Name',
    'received_date' => 'Received Date',
    'return_date' => 'Return Date',
    'department' => 'Department'
];

// Define SQL column mapping (database field names)
$sqlColumns = [
    'log_id' => 'cl.log_id',
    'claimed_at' => 'cl.claimed_at',
    'staff' => 'cl.staff',
    'dist_id' => 'gd.dist_id',
    'borrower_type' => 'gd.borrower_type',
    'borrower_name' => "CASE 
                WHEN gd.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                WHEN gd.borrower_type IS NOT NULL THEN CONCAT(e.emp_fname, ' ', e.emp_lname)
                ELSE 'Unknown'
            END",
    'serial_no' => 'i.serial_no',
    'item_name' => 'idesc.item_name',
    'received_date' => 'gd.received_date',
    'return_date' => 'gd.return_date',
    'department' => 'd.department_name'
];

// Make sure claimed_at is always included in the output
if (!in_array('claimed_at', $selectedColumns)) {
    // Add claimed_at to the beginning of the array
    array_unshift($selectedColumns, 'claimed_at');
}

// Build CSV header row with only selected columns
$csvHeader = [];
foreach ($selectedColumns as $columnId) {
    if (isset($columnMapping[$columnId])) {
        $csvHeader[] = $columnMapping[$columnId];
    }
}
fputcsv($output, $csvHeader);

// Build the SQL SELECT part with selected columns
$selectColumns = [];
foreach ($selectedColumns as $columnId) {
    if (isset($sqlColumns[$columnId])) {
        $selectColumns[] = $sqlColumns[$columnId] . " AS " . $columnId;
    }
}
$selectClause = implode(", ", $selectColumns);

// Base query with joins - handle datetime comparison properly
// Make sure the dates are in proper format for datetime comparison
$startDateFormatted = $startDate . ' 00:00:00';
$endDateFormatted = $endDate . ' 23:59:59';

error_log("Formatted dates for query - Start: $startDateFormatted, End: $endDateFormatted");

// Modify the SQL to handle "All departments" (value = 0)
if ($onlyDepartment == '0') {
    $sql = "SELECT 
                $selectClause
            FROM 
                claim_logs cl
            JOIN 
                gadget_distribution gd ON cl.dist_id = gd.dist_id
            LEFT JOIN 
                students s ON gd.stud_rec_id = s.stud_rec_id
            LEFT JOIN 
                employees e ON gd.receiver_id = e.emp_rec_id
            LEFT JOIN 
                items i ON gd.item_id = i.item_id
            LEFT JOIN 
                item_desc idesc ON i.item_desc_id = idesc.item_desc_id
            LEFT JOIN 
                employees emp ON gd.mrep_id = emp.emp_rec_id
            LEFT JOIN 
                departments d ON emp.department_id = d.department_id
            WHERE 
                cl.claimed_at BETWEEN ? AND ?
            ORDER BY 
                cl.claimed_at DESC";

    // Prepare statement with only date parameters
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log('Prepare failed: ' . $conn->error);
        die('Prepare failed: ' . $conn->error);
    }

    // Bind only date parameters for "All departments"
    $stmt->bind_param("ss", $startDateFormatted, $endDateFormatted);
} else {
    $sql = "SELECT 
                $selectClause
            FROM 
                claim_logs cl
            JOIN 
                gadget_distribution gd ON cl.dist_id = gd.dist_id
            LEFT JOIN 
                students s ON gd.stud_rec_id = s.stud_rec_id
            LEFT JOIN 
                employees e ON gd.receiver_id = e.emp_rec_id
            LEFT JOIN 
                items i ON gd.item_id = i.item_id
            LEFT JOIN 
                item_desc idesc ON i.item_desc_id = idesc.item_desc_id
            LEFT JOIN 
                employees emp ON gd.mrep_id = emp.emp_rec_id
            LEFT JOIN 
                departments d ON emp.department_id = d.department_id
            WHERE 
                d.department_id = ?
                AND cl.claimed_at BETWEEN ? AND ?
            ORDER BY 
                cl.claimed_at DESC";

    // Prepare statement with department and date parameters
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log('Prepare failed: ' . $conn->error);
        die('Prepare failed: ' . $conn->error);
    }

    // Bind parameters: department_id (int), startDate (string), endDate (string)
    $stmt->bind_param("iss", $onlyDepartment, $startDateFormatted, $endDateFormatted);
}

error_log("SQL Query: " . $sql);

if (!$stmt->execute()) {
    error_log('Execute failed: ' . $stmt->error);
    die('Execute failed: ' . $stmt->error);
}

$result = $stmt->get_result();
error_log("Number of rows returned: " . $result->num_rows);

// Write data rows to CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $csvRow = [];
        foreach ($selectedColumns as $columnId) {
            // Handle special cases for formatting or default values
            if ($columnId == 'return_date' && empty($row[$columnId])) {
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
