<?php
// Database credentials
include 'db_connection_header.php';

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Get the current user's ID from the session
$currentUserId = $_SESSION['user_id'];

// Prepare the SQL query to fetch all users' data with ordering and the current user's role
$sql = "
    SELECT
        u.user_id,
        u.username,
        u.email,
        u.password,
        u.user_type AS role,
        d.department_name AS department,
        u.account_status
    FROM users u
    INNER JOIN departments d ON u.department_id = d.department_id
    ORDER BY
        CASE u.user_type
            WHEN 'admin' THEN 1
            WHEN 'dept_head' THEN 2
            ELSE 3
        END,
        CASE u.account_status
            WHEN 'deactivated' THEN 2
            ELSE 1
        END,
        u.username;
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Prepare a separate SQL query to fetch the current user's role
$roleSql = "
    SELECT
        u.user_type AS role
    FROM users u
    WHERE u.user_id = ?
";

$roleStmt = $conn->prepare($roleSql);
if ($roleStmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error preparing role statement: ' . $conn->error]);
    $conn->close();
    exit();
}

$roleStmt->bind_param("i", $currentUserId);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();

$currentUserRole = null;
if ($roleResult && $roleResult->num_rows > 0) {
    $roleRow = $roleResult->fetch_assoc();
    $currentUserRole = $roleRow['role'];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'users' => $users, 'currentUserRole' => $currentUserRole]);

if ($stmt) {
    $stmt->close();
}
if ($roleStmt) {
    $roleStmt->close();
}
$conn->close();
?>