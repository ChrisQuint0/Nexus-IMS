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
    $selectedColumns = ['item_name', 'serial_number'];
}

// Include database connection
include 'db.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="repairs_logs_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Define column mapping between ID and display name
$columnMapping = [
    'log_id' => 'Log ID',
    'repair_date' => 'Repair Date',
    'box_number' => 'Box Number',
    'accountable' => 'Accountable',
    'department' => 'Department',
    'receiver' => 'Receiver',
    'section' => 'Section',
    'item_name' => 'Item Name',
    'serial_number' => 'Serial Number',
    'reason' => 'Reason',
    'staff' => 'Staff'
];

// Define SQL column mapping (database field names)
$sqlColumns = [
    'log_id' => 'rpl.log_id',
    'repair_date' => 'rpl.repair_date',
    'box_number' => 'rpl.box_number',
    'accountable' => 'rpl.accountable',
    'department' => 'rpl.department',
    'receiver' => 'rpl.receiver',
    'section' => 'rpl.section',
    'item_name' => 'rpl.item_name',
    'serial_number' => 'rpl.serial_number',
    'reason' => 'rpl.reason',
    'staff' => 'rpl.staff'
];

// Make sure repair_date is always included in the output
if (!in_array('repair_date', $selectedColumns)) {
    // Add repair_date to the beginning of the array
    array_unshift($selectedColumns, 'repair_date');
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
                repair_logs rpl
            WHERE 
                rpl.repair_date BETWEEN ? AND ?
            ORDER BY 
                rpl.repair_date DESC";

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
                repair_logs rpl
            WHERE 
                LOWER(rpl.department) = (SELECT LOWER(department_name) FROM departments WHERE department_id = ?)
                AND rpl.repair_date BETWEEN ? AND ?
            ORDER BY 
                rpl.repair_date DESC";

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
            if ($columnId == 'department' && empty($row[$columnId])) {
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
    $emptyRow[0] = 'No repair items found';
    fputcsv($output, $emptyRow);
}

// Close database connection
$stmt->close();
$conn->close();

// Close the output stream
fclose($output);
exit;
