<?php
// ... (error reporting and includes)

include 'db_connection_header.php';

$data = json_decode(file_get_contents('php://input'), true);

error_log("Received user_id for deletion: " . print_r($data['user_id'], true));

if (isset($data['user_id'])) {
    $userId = $data['user_id'];

    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        error_log("Error executing delete query: " . $stmt->error); // Log the MySQL error
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing user ID.']);
}

$conn->close();
?>