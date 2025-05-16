<?php
// Database credentials (replace with your actual credentials)
include 'db_connection_header.php';
header('Content-Type: application/json');

// Logging setup
$logFile = 'edit_user_audit.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date("Y-m-d H:i:s");
    $logEntry = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

session_start();

logMessage("Received edit user request from IP: " . $_SERVER['REMOTE_ADDR']);

$data = json_decode(file_get_contents('php://input'), true);
logMessage("Received JSON data: " . json_encode($data));

// Check if a user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in as an administrator.']);
    logMessage("Unauthorized access attempt to edit user.");
    exit;
}

if (isset($data['user_id']) && isset($data['username']) && isset($data['email']) && isset($data['role']) && isset($data['department_id']) && isset($data['account_status'])) {
    $userId = $data['user_id'];
    $username = $data['username'];
    $email = $data['email'];
    $role = $data['role'];
    $departmentId = $data['department_id'];
    $accountStatus = $data['account_status']; // Receive account_status

    $passwordToUpdate = null;
    $passwordBindType = "";
    $passwordBindValue = "";
    $passwordSqlPart = "";

if (isset($data['password']) && trim($data['password']) !== '' && strpos($data['password'], '$2y$') !== 0) {
    $passwordToUpdate = trim($data['password']);
    $hashedPassword = password_hash($passwordToUpdate, PASSWORD_DEFAULT);
    $passwordSqlPart = ", password = ?";
    $passwordBindType = "s";
    $passwordBindValue = $hashedPassword;
}


    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
        logMessage("Database connection failed: " . $conn->connect_error);
        exit;
    }

    $sql = "UPDATE users SET username = ?, email = ?, user_type = ?, department_id = ?, account_status = ? " . $passwordSqlPart . " WHERE user_id = ?";
    $stmt = $conn->prepare($sql);

    $bindValues = [$username, $email, $role, $departmentId, $accountStatus];
    $bindTypes = "sssis";

    if ($passwordToUpdate !== null) {
        $bindValues[] = $hashedPassword;
        $bindTypes .= "s";
    }

    $bindValues[] = $userId;
    $bindTypes .= "i";


    // Create an array of references for bind_param
    $refs = [];
    $refs[] = &$bindTypes;
    for ($i = 0; $i < count($bindValues); $i++) {
        $refs[] = &$bindValues[$i];
    }

    logMessage("Before bind_param - bindTypes: " . $bindTypes);
    logMessage("Before bind_param - bindValues count: " . count($bindValues));
    if ($passwordToUpdate !== null) {
        logMessage("Before bind_param - passwordBindValue: " . $hashedPassword);
    }

    // Use call_user_func_array to bind parameters by reference
    call_user_func_array([$stmt, 'bind_param'], $refs);

    logMessage("Attempting to update user ID: " . $userId . ", Username: " . $username . ", Email: " . $email . ", Role: " . $role . ", Department ID: " . $departmentId . ", Account Status: " . $accountStatus . ($passwordToUpdate !== null ? ", Password (hashed): " . $hashedPassword : "") . " by admin ID: " . $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
        logMessage("User ID: " . $userId . " updated successfully by admin ID: " . $_SESSION['user_id'] . ($passwordToUpdate !== null ? " with password change." : "."));

        // Log the action in the audit log
        $auditStmt = $conn->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
        $loggedInUserId = $_SESSION['user_id'];
        $auditAction = "User updated: User ID - " . $userId . ", Username - " . $username . ", Email - " . $email . ", Role - " . $role . ", Department ID - " . $departmentId . ", Account Status - " . $accountStatus . ($passwordToUpdate !== null ? ", Password changed" : "");
        $auditStmt->bind_param("is", $loggedInUserId, $auditAction);
        $auditStmt->execute();
        $auditStmt->close();

    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        logMessage("Error updating user ID: " . $userId . " by admin ID: " . $_SESSION['user_id'] . ". Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} else {
    logMessage("Missing required data in edit_user.php. Received: " . print_r($data, true));
    echo json_encode(['success' => false, 'error' => 'Missing required user data.']);
}
?>