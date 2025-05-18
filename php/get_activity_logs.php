<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$host = "localhost";
$username = "root";
$password = "";
$database = "nexus_ims_db_dummy";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

$sql = "SELECT al.action, al.timestamp, u.username
FROM audit_log al
LEFT JOIN users u ON al.user_id = u.user_id";

$whereClauses = [];
if (isset($_GET['action_filter']) && !empty($_GET['action_filter'])) {
    $actionFilter = $conn->real_escape_string($_GET['action_filter']);
    $whereClauses[] = "al.action LIKE '%$actionFilter%'";
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY al.timestamp DESC";

$result = $conn->query($sql);

$logs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    echo json_encode(["success" => true, "logs" => $logs]);
} else {
    echo json_encode(["success" => true, "logs" => [], "message" => "No activity logs found."]);
}

$conn->close();
?>