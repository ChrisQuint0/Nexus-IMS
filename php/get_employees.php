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

$sql = "SELECT emp_rec_id, emp_fname, emp_minit, emp_lname, emp_suffix FROM employees ORDER BY emp_lname ASC";
$result = $conn->query($sql);

$employees = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
  }
}

$conn->close();
echo json_encode($employees);
?>