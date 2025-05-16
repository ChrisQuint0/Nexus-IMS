<?php
function authenticateUser($pdo, $email, $password) {
    error_log("Attempting to authenticate: $email");
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        error_log("User not found for email: $email");
        return false;
    }

    error_log("Found user: " . print_r($user, true));
    
    if (!password_verify($password, $user['password'])) {
        error_log("Password verification failed for user: $email");
        return false;
    }

    return $user;
}

function getUserByEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function storeResetToken($pdo, $userId, $token, $expiry) {
    // Invalidate any existing tokens
    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Store new token
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, reset_token, reset_token_expiry) VALUES (?, ?, ?)");
    return $stmt->execute([$userId, $token, $expiry]);
}

function generateResetLink($token) {
    // Validate token exists
    if (empty($token)) {
        throw new Exception("Empty token provided for reset link");
    }
    
    // Determine protocol
    $protocol = 'http';
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
        ($_SERVER['SERVER_PORT'] == 443)) {
        $protocol = 'https';
    }
    
    // Get host with fallback
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = rtrim($host, '/');
    
    // URL encode token
    $encodedToken = urlencode($token);
    
    // Validate components
    if (empty($host) || empty($encodedToken)) {
        throw new Exception("Invalid components for reset link generation");
    }
    
    return "$protocol://$host/MIS-Nexus-Inventory-Management-NIMS-/php/reset_pass.php?token=$encodedToken";
}

function sendResetEmail($email, $resetLink) {
    try {
        // Validate inputs
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }
        if (!filter_var($resetLink, FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid reset link URL");
        }

        $mail = configureMailer();
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Nexus IMS Password Reset Request';
        
        $mail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    .reset-button {
                        display: inline-block;
                        padding: 12px 24px;
                        background-color: #4CAF50;
                        color: white !important;
                        text-decoration: none;
                        border-radius: 4px;
                        font-weight: bold;
                        margin: 15px 0;
                    }
                    .reset-link {
                        word-break: break-all;
                        color: #0066cc;
                        text-decoration: underline;
                    }
                </style>
            </head>
            <body>
                <p>Hello,</p>
                <p>You requested a password reset for your Nexus IMS account.</p>
                <p>Please click the button below to reset your password:</p>
                <p>
                    <a href="'.htmlspecialchars($resetLink).'" class="reset-button">
                        Reset My Password
                    </a>
                </p>
                <p>If the button doesn\'t work, copy and paste this URL into your browser:</p>
                <p class="reset-link">'.htmlspecialchars($resetLink).'</p>
                <p>This link will expire in 10 minutes.</p>
                <p>If you didn\'t request this, please ignore this email.</p>
            </body>
            </html>
        ';
        
        $mail->AltBody = "Nexus IMS Password Reset\n\n" .
                       "Please visit the following link to reset your password:\n\n" .
                       $resetLink . "\n\n" .
                       "This link expires in 10 minutes.\n\n" .
                       "If you didn't request this, please ignore this email.";
        
                       error_log("PHPMailer object before send: " . print_r($mail, true));
            if (!$mail->send()) {
            throw new Exception($mail->ErrorInfo);
        }
        return true;
    } catch (Exception $e) {
        error_log("Failed to send reset email to $email: " . $e->getMessage());
        return false;
    }
}

function validateResetToken($pdo, $token) {
    error_log("Validating token: $token");
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE reset_token = ? AND used = 0 AND reset_token_expiry > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function updateUserPassword($pdo, $userId, $newPassword) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    $affected = $stmt->rowCount();
    error_log("Password update attempt. Affected rows: " . $affected);
    return $affected > 0;
}

function markTokenAsUsed($pdo, $resetId) {
    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE reset_id = ?");
    return $stmt->execute([$resetId]);
}
?>