<?php
require_once 'db_functions.php';

// Determine the origin of the request
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_origins = [
    'http://127.0.0.1:5501',
];

// Check if the request origin is in the allowed list
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true"); // Allow sending cookies
}

header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Logging setup
$logFile = 'login_audit.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date("Y-m-d H:i:s");
    $logEntry = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

logMessage("Received login request from origin: " . $origin . ", IP: " . $_SERVER['REMOTE_ADDR']);

$session_id = session_id();
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$httponly = true;
$samesite = 'Lax';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => $secure,
    'httponly' => $httponly,
    'samesite' => $samesite,
]);

// Only start the session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    logMessage("Session started with ID: " . session_id());
} else {
    logMessage("Session already active with ID: " . session_id());
}

// Database connection details
$host = "localhost";

$user = "root";
$pass = "";

// Connect to MySQL
$conn = get_database_connection();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    logMessage("Database connection failed: " . $conn->connect_error);
    exit;
}

// Get input data from JSON body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
logMessage("Received JSON data: " . json_encode($data));

$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Please enter both email and password."]);
    logMessage("Login failed: Email or password missing.");
    $conn->close();
    exit;
}

// Query the database for the user with the given email and active status
$stmt = $conn->prepare("SELECT user_id, username, password, user_type, department_id, account_status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    logMessage("User found with email: " . $email . ", User ID: " . $user['user_id']);
    // Check if the account is activated
    if ($user['account_status'] === 'activated') {
        logMessage("Account is activated for user: " . $user['username']);
        // Verify the password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['department_id'] = $user['department_id'];

            $logAction = "User logged in: Username - " . $user['username'] . ", User ID - " . $user['user_id'];
            $logStmt = $conn->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
            $logStmt->bind_param("is", $user['user_id'], $logAction);
            $logStmt->execute();
            $logStmt->close();
            logMessage("Login successful for user: " . $user['username'] . ". Session variables set.");
            echo json_encode(["success" => true, "message" => "Login successful.", "userId" => $user['user_id'], "username" => $user['username']]);
        } else {
            $logAction = "Failed login attempt: Incorrect password for Email - " . $email;
            $logStmt = $conn->prepare("INSERT INTO audit_log (user_id, action) VALUES (NULL, ?)");
            $logStmt->bind_param("s", $logAction);
            $logStmt->execute();
            $logStmt->close();
            logMessage("Login failed: Incorrect password for email: " . $email);
            echo json_encode(["success" => false, "message" => "Incorrect password."]);
        }
    } else {
        logMessage("Login failed: Account deactivated for email: " . $email . ", Username: " . $user['username']);
        echo json_encode(["success" => false, "message" => "Your account is deactivated. Please contact an administrator."]);
    }
} else {
    $logAction = "Failed login attempt: Invalid email - " . $email;
    $logStmt = $conn->prepare("INSERT INTO audit_log (user_id, action) VALUES (NULL, ?)");
    $logStmt->bind_param("s", $logAction);
    $logStmt->execute();
    $logStmt->close();
    logMessage("Login failed: Invalid email: " . $email);
    echo json_encode(["success" => false, "message" => "Invalid email."]);
}

$stmt->close();
$conn->close();
?>