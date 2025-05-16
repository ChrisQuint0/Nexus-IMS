<?php
require_once "db_connect.php";
require_once "mail_config.php";
require_once "functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../pages/request_email.html?error=invalid_email");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Check if user exists
        $user = getUserByEmail($pdo, $email);

        if ($user) {
            $token = bin2hex(random_bytes(32));

            storeResetToken($pdo, $user['user_id'], $token, null);

            $stmt = $pdo->prepare("SELECT reset_id, created_at FROM password_resets WHERE user_id = ? AND reset_token = ?");
            $stmt->EXECUTE([$user['user_id'], $token]);
            $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resetRecord && $resetRecord['created_at'] && $resetRecord['reset_id']) {
                $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes", strtotime($resetRecord['created_at'])));

                $stmt = $pdo->prepare("UPDATE password_resets SET reset_token_expiry = ? WHERE reset_id = ?");
                $stmt->execute([$expiry, $resetRecord['reset_id']]);

                $resetLink = generateResetLink($token);

                error_log("Generated token: " . $token);
                error_log("Generated reset link: " . $resetLink);

                $pdo->commit();

                if (sendResetEmail($email, $resetLink)) {
                    header("Location: ../pages/request_email.html?success=email_sent");
                } else {
                    header("Location: ../pages/request_email.html?error=email_failed");
                }
                exit();
            } else {
                $pdo->rollBack();
                error_log("Error retrieving created_at timestamp.");
                header("Location: ../pages/request_email.html?error=database_error");
                exit();
            }
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in password reset request: " . $e->getMessage());
        header("Location: ../pages/request_email.html?");
        exit();
    }
}
?>