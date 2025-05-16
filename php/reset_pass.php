<?php
require_once "db_connect.php";
require_once "functions.php";

// Database connection check
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit();
}

// Handle POST: password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false];

    try {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        error_log("POST Data Received - Token: $token, New Password: [hidden], Confirm Password: [hidden]");
        $pdo->beginTransaction();

        $resetRequest = validateResetToken($pdo, $token);
        if (!$resetRequest || $resetRequest['used'] || strtotime($resetRequest['reset_token_expiry']) < time()) {
            throw new Exception('Invalid or expired token');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }

        if (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            throw new Exception('Password must be at least 8 characters with at least one uppercase letter and one number');
        }

        if (!updateUserPassword($pdo, $resetRequest['user_id'], $newPassword)) {
            throw new Exception('Failed to update password');
        }

        markTokenAsUsed($pdo, $resetRequest['reset_id']);

        $pdo->commit();
        $response = ['success' => true, 'redirect' => 'login.html?success=password_updated'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle GET: token validation and redirect to HTML page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';
    $valid = validateResetToken($pdo, $token);

if (!$valid || $valid['used'] == 1 || strtotime($valid['reset_token_expiry']) < time()) {
        header("Location: ../pages/request_email.html?error=invalid_token");
        exit();
    }

    // ✅ Redirect to reset_pass.html (static HTML page) with token in URL
    header("Location: ../pages/reset_pass.html?token=" . urlencode($token));
    exit();
}
?>
