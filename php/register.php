<?php

include 'db_connection_header.php';

session_start();

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    error_log("[".date("Y-m-d H:i:s")."] Database connection failed: " . $conn->connect_error . "\n", 3, "error.log");
    exit;
}

// Get and decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
error_log("[".date("Y-m-d H:i:s")."] Received data: " . json_encode($data) . "\n", 3, "request.log");

// Validate input fields
if (
    !isset($data["username"]) ||
    !isset($data["email"]) ||
    !isset($data["password"]) ||
    !isset($data["userType"]) ||
    !isset($data["departmentId"])
) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    error_log("[".date("Y-m-d H:i:s")."] Missing required fields in request.\n", 3, "error.log");
    exit;
}

$username = trim($data["username"]);
$email = trim($data["email"]);
$password = $data["password"]; // Get the plain password for validation
$userType = trim($data["userType"]);
$departmentId = trim($data["departmentId"]);
$accountStatus = 'activated'; // Set default account status to 'activated'

// Password Requirements
$minLength = 8;
$requireUppercase = true;
$requireLowercase = true;
$requireNumber = true;
$requireSpecialChar = true;

$errors = [];

if (strlen($password) < $minLength) {
    $errors[] = "Password must be at least {$minLength} characters long.";
}

if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
    $errors[] = "Password must contain at least one uppercase letter.";
}

if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
    $errors[] = "Password must contain at least one lowercase letter.";
}

if ($requireNumber && !preg_match('/[0-9]/', $password)) {
    $errors[] = "Password must contain at least one number.";
}

if ($requireSpecialChar && !preg_match('/[^a-zA-Z0-9\s]/', $password)) {
    $errors[] = "Password must contain at least one special character.";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password does not meet the following requirements:", "errors" => $errors]);
    error_log("[".date("Y-m-d H:i:s")."] Password validation failed for user: " . $username . ". Errors: " . json_encode($errors) . "\n", 3, "error.log");
    exit;
}

// Hash the password only after it passes validation
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Validate userType against the ENUM values
$allowedUserTypes = ['admin', 'dept_head', 'student'];
if (!in_array($userType, $allowedUserTypes)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid user type."]);
    error_log("[".date("Y-m-d H:i:s")."] Invalid user type provided: " . $userType . " for user: " . $username . "\n", 3, "error.log");
    exit;
}

// 🔍 Check for duplicate username or email
$check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username or Email already exists."]);
    error_log("[".date("Y-m-d H:i:s")."] Duplicate username or email found for username: " . $username . " or email: " . $email . "\n", 3, "warning.log");
    $check->close();
    exit;
}
$check->close();

// ✅ Insert new user with default 'activated' status
$stmt = $conn->prepare("INSERT INTO users (username, email, password, department_id, user_type, account_status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssiss", $username, $email, $hashedPassword, $departmentId, $userType, $accountStatus); // Corrected bind_param

if ($stmt->execute()) {
    $newUserId = $conn->insert_id; // Get the ID of the newly inserted user
    $userId = $_SESSION['user_id'];

    // Fetch the newly inserted user's data to return in the response
    $selectStmt = $conn->prepare("SELECT u.user_id, u.username, u.email, u.user_type AS role, d.department_name AS department, u.account_status 
                                  FROM users u
                                  LEFT JOIN departments d ON u.department_id = d.department_id
                                  WHERE u.user_id = ?");
    $selectStmt->bind_param("i", $newUserId);
    $selectStmt->execute();
    $newUserResult = $selectStmt->get_result();
    $newUser = $newUserResult->fetch_assoc();
    $selectStmt->close();

    echo json_encode(["success" => true, "message" => "User registered successfully.", "user" => $newUser]);
    error_log("[".date("Y-m-d H:i:s")."] User registered successfully. User ID: " . $newUserId . ", Username: " . $username . ", Email: " . $email . "\n", 3, "success.log");

    // Log the action in the audit log
    $auditStmt = $conn->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, ?)"); // User ID is NULL for registration
    $auditAction = "User registered: Username - " . $username . ", Email - " . $email . ", User Type - " . $userType . ", Department ID - " . $departmentId . ", Account Status - " . $accountStatus;
    $auditStmt->bind_param("is", $userId, $auditAction);
    $auditStmt->execute();
    $auditStmt->close();

} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to register user."]);
    error_log("[".date("Y-m-d H:i:s")."] Failed to register user: " . $stmt->error . " for username: " . $username . "\n", 3, "error.log");
}

$stmt->close();
$conn->close();
?>