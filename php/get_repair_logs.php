<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "nexus_ims_db_dummy";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// SQL query to fetch repair logs data
$sql = "SELECT
            rl.log_id,
            rl.box_number,
            rl.accountable,
            rl.department,
            rl.receiver,
            rl.section,
            rl.item_name,
            rl.serial_number,
            rl.repair_date,
            rl.reason,
            rl.staff
            
        FROM repair_logs rl
        ORDER BY rl.repair_date DESC";

$result = $conn->query($sql);

$repairLogs = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $repairLogs[] = $row;
    }
}

// Close the database connection
$conn->close();

// Return the repair logs as JSON
echo json_encode($repairLogs);
?>
