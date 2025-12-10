<?php
session_start();
require_once 'config.php';
require_once 'emailConfig.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$conn = getDBConnection();

// Get user info
$stmt = $conn->prepare("
    SELECT u.email, us.two_factor_enabled, up.first_name 
    FROM users u 
    JOIN user_security us ON u.user_id = us.user_id
    JOIN user_profiles up ON u.user_id = up.user_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Send verification code
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_code'])) {
    // Generate 6-digit code
    $_SESSION['setup_email_code'] = sprintf("%06d", mt_rand(0, 999999));
    $_SESSION['setup_email_code_expiry'] = time() + 600; // 10 minutes
    
    // Send email with code
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = EMAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_USERNAME;
        $mail->Password   = EMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = EMAIL_PORT;
        
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($user['email'], $user['first_name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'LoveJoy - Setup Verification Code';
        $mail->Body    = "
            <h2>Hello {$user['first_name']},</h2>
            <p>Your setup verification code is:</p>
            <h1 style='color: #4CAF50; font-size: 32px; letter-spacing: 5px;'>{$_SESSION['setup_email_code']}</h1>
            <p>This code will expire in 10 minutes.</p>
            <p>Enter this code to complete your two-factor authentication setup.</p>
            <p>Best regards,<br>LoveJoy Team</p>
        ";
        
        $mail->send();
        $code_sent = true;
    } catch (Exception $e) {
        $error = "Could not send verification email. Please try again.";
    }
}

// Verify code and enable 2FA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_code'])) {
    $code = $_POST['code'];
    
    // Check if code expired
    if (!isset($_SESSION['setup_email_code'])) {
        $error = "No verification code found. Please request a new code.";
    } elseif (time() > $_SESSION['setup_email_code_expiry']) {
        $error = "Verification code expired. Please request a new code.";
        unset($_SESSION['setup_email_code']);
        unset($_SESSION['setup_email_code_expiry']);
    } elseif ($code === $_SESSION['setup_email_code']) {
        // Code is valid - enable email-based 2FA
        $update_stmt = $conn->prepare("UPDATE user_security SET two_factor_enabled = 1, two_factor_method = 'email' WHERE user_id = ?");
        $update_stmt->bind_param("i", $_SESSION['user_id']);
        $update_stmt->execute();
        
        unset($_SESSION['must_setup_2fa']);
        unset($_SESSION['setup_email_code']);
        unset($_SESSION['setup_email_code_expiry']);
        
        $success = "Email-based two-factor authentication enabled successfully!";
        
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>Email 2FA Setup</title>
</head>
<body>
    <h1>Email Two-Factor Authentication</h1>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
            <a href="home.php" class="button">Continue to Home</a>
        <?php else: ?>
            <?php if ($must_setup): ?>
                <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <p style="color: #856404; margin: 0;"><strong>⚠ Two-Factor Authentication Required</strong></p>
                </div>
            <?php endif; ?>
            
            <h3>Setup Email-Based 2FA</h3>
            <p>When you log in, we'll send a verification code to:</p>
            <p><strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
            
            <?php if (!isset($code_sent)): ?>
                <p>Click below to send a test verification code to your email:</p>
                <form method="post">
                    <input type="submit" name="send_code" value="Send Verification Code">
                </form>
            <?php else: ?>
                <p style="color: green;">✓ Verification code sent to your email</p>
                <p>Enter the 6-digit code to complete setup:</p>
                <form method="post">
                    <label for="code">Verification Code:</label>
                    <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus>
                    <input type="submit" name="verify_code" value="Verify and Enable 2FA">
                </form>
                
                <form method="post" style="margin-top: 10px;">
                    <input type="submit" name="send_code" value="Resend Code" style="background-color: #6c757d;">
                </form>
            <?php endif; ?>
            <a href="choose2faMethod.php" class="button">Back</a>
        <?php endif; ?>
    </div>
</body>
</html>