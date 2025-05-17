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

// Get the current user's ID from the session (you might not need this if you're selecting all users)
$currentUserId = $_SESSION['user_id'];

// Prepare the SQL query to fetch all users' data
$sql = "
    SELECT
        u.user_id,
        u.username,
        u.email,
        u.password,
        u.user_type,
        d.department_name AS department,
        u.account_status
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.department_id;
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

$allUsers = []; // Initialize an empty array to store all user data

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allUsers[] = $row; // Add each user's data (all columns) to the array
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'users' => $allUsers]); // Encode the array within a 'users' key
} else {
    http_response_code(404); // Not Found (No users found)
    echo json_encode(['success' => false, 'error' => 'No users found']);
}

if ($stmt) {
    $stmt->close();
}
$conn->close();
?>