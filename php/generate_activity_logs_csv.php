<?php
// Get parameters from POST request
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';
$departmentId = isset($_POST['departmentId']) ? intval($_POST['departmentId']) : 0;
$onlyDepartment = isset($_POST['onlyDepartment']) ? intval($_POST['onlyDepartment']) : 0;

// Get selected columns, defaulting to all columns if none selected
$selectedColumns = isset($_POST['columns']) ? json_decode($_POST['columns'], true) : [];

// Default to these columns if none selected
if (empty($selectedColumns)) {
    $selectedColumns = ['username', 'action', 'timestamp'];
}

// Include database connection
include 'db.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Define column mapping between ID and display name
$columnMapping = [
    'log_id' => 'Log ID',
    'user_id' => 'User ID',
    'username' => 'Username',
    'email' => 'Email',
    'department' => 'Department',
    'user_type' => 'User Type',
    'action' => 'Action',
    'timestamp' => 'Timestamp'
];

// Define SQL column mapping (database field names)
$sqlColumns = [
    'log_id' => 'al.id',
    'user_id' => 'al.user_id',
    'username' => 'u.username',
    'email' => 'u.email',
    'department' => 'd.department_name',
    'user_type' => 'u.user_type',
    'action' => 'al.action',
    'timestamp' => 'al.timestamp'
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
    audit_log al
LEFT JOIN users u ON al.user_id = u.user_id
LEFT JOIN departments d ON u.department_id = d.department_id
WHERE 1=1";

// Initialize parameters array for prepared statement
$params = [];
$paramTypes = "";

// Add date filters if provided
if (!empty($startDate)) {
    $baseQuery .= " AND al.timestamp >= ?";
    $params[] = $startDate . " 00:00:00";
    $paramTypes .= "s";
}

if (!empty($endDate)) {
    $baseQuery .= " AND al.timestamp <= ?";
    $params[] = $endDate . " 23:59:59";
    $paramTypes .= "s";
}


// Add department filter if specified
if ($onlyDepartment > 0) {
    $baseQuery .= " AND u.department_id = ?";
    $params[] = $onlyDepartment;
    $paramTypes .= "i";
}
// When onlyDepartment is 0, no filter is added, so all departments are included

// Order by timestamp descending
$baseQuery .= " ORDER BY al.timestamp DESC";

// Final SQL query
$sql = "SELECT " . $selectClause . " " . $baseQuery;

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Prepare failed: ' . $conn->error);
}

// Bind parameters if there are any
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

if (!$stmt->execute()) {
    die('Execute failed: ' . $stmt->error);
}

$result = $stmt->get_result();

// Write data rows to CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $csvRow = [];
        foreach ($selectedColumns as $columnId) {
            // Format timestamp if needed
            if ($columnId == 'timestamp' && !empty($row[$columnId])) {
                $csvRow[] = date('Y-m-d H:i:s', strtotime($row[$columnId]));
            } else {
                $csvRow[] = $row[$columnId] ?? '';
            }
        }
        fputcsv($output, $csvRow);
    }
} else {
    // Add a row indicating no data
    $emptyRow = array_fill(0, count($csvHeader), '');
    $emptyRow[0] = 'No audit logs found for the selected criteria';
    fputcsv($output, $emptyRow);
}

// Close database connection
$stmt->close();
$conn->close();

// Close the output stream
fclose($output);
exit;
