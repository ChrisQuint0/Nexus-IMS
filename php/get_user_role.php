<?php

// Database credentials
include 'db_connection_header.php';

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit();
}

// Get the current user's ID from the session
$currentUserId = $_SESSION['user_id'];

// Prepare the SQL query to fetch the current user's role
$sql = "
    SELECT
        u.user_type AS role
    FROM users u
    WHERE u.user_id = ?
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error preparing statement: ' . $conn->error
    ]);
    $conn->close();
    exit();
}

$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode([
        'role' => $row['role']
    ]);
} else {
    http_response_code(404); // Not Found
    echo json_encode([
        'success' => false,
        'error' => 'User not found or role not defined'
    ]);
}

// Cleanup
if ($stmt) {
    $stmt->close();
}
$conn->close();

?>
