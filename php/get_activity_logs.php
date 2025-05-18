<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Session invalid"]);
    exit();
}

include 'db.php';

// Base query
$sql = "SELECT al.id, al.user_id, al.action, al.timestamp, u.username, u.user_type, d.department_name
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.user_id
        LEFT JOIN departments d ON u.department_id = d.department_id";

// Add filter conditions
$whereClauses = [];
if (isset($_GET['action_filter']) && !empty($_GET['action_filter'])) {
    $actionFilter = $conn->real_escape_string($_GET['action_filter']);
    $whereClauses[] = "al.action LIKE '%$actionFilter%'";
}

// Add department filter for non-admin users
if ($_SESSION['user_type'] !== 'admin' && isset($_SESSION['department_id'])) {
    $whereClauses[] = "u.department_id = " . (int)$_SESSION['department_id'];
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

// Add ordering
$sql .= " ORDER BY al.timestamp DESC";

// Execute the query
$result = $conn->query($sql);

if ($result === false) {
    echo json_encode([
        "success" => false,
        "message" => "Error executing query: " . $conn->error
    ]);
    exit();
}

$logs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format timestamp
        $row['timestamp'] = date('Y-m-d H:i:s', strtotime($row['timestamp']));
        $logs[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "logs" => $logs,
    "message" => empty($logs) ? "No activity logs found." : null
]);

$conn->close();
?>