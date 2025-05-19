<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

$conn = get_database_connection();

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$sql = "SELECT emp_rec_id, emp_fname, emp_minit, emp_lname, emp_suffix FROM employees";
$result = $conn->query($sql);

$employees = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
    $fullNameStandard = trim(trim($row['emp_fname']) . ' ' . (isset($row['emp_minit']) && $row['emp_minit'] ? trim($row['emp_minit']) . ' ' : '') . trim($row['emp_lname']) . (isset($row['emp_suffix']) && $row['emp_suffix'] ? ' ' . trim($row['emp_suffix']) : ''));
    $fullNameReversed = trim(trim($row['emp_lname']) . ', ' . trim($row['emp_fname']) . (isset($row['emp_minit']) && $row['emp_minit'] ? ' ' . trim($row['emp_minit']) : ''));
    $fullNameFirstLastOnly = trim(trim($row['emp_fname']) . ' ' . trim($row['emp_lname'])); // Add this line
    $employees[] = [
        'emp_rec_id' => $row['emp_rec_id'],
        'fullNameStandard' => $fullNameStandard,
        'fullNameReversed' => $fullNameReversed,
        'fullNameFirstLastOnly' => $fullNameFirstLastOnly, // Add this to the array
    ];
}
}

$conn->close();

echo json_encode($employees);
?>