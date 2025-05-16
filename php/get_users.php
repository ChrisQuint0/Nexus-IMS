<?php
// Database credentials (replace with your actual credentials)
include 'db_connection_header.php';

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Prepare the SQL query with ordering
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

$result = $conn->query($sql);

if ($result) {
    $users = array();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'users' => $users]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error executing query: ' . $conn->error]);
}

$conn->close();
?>