<?php
session_start();
require_once 'config.php';
require_once 'emailConfig.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

if (!isset($_SESSION['2fa_user_id'])) {
    header("Location: login.html");
    exit();
}

$error = '';
$conn = getDBConnection();

// Generate and send code if not already sent
if (!isset($_SESSION['2fa_email_code'])) {
    // Generate 6-digit code
    $_SESSION['2fa_email_code'] = sprintf("%06d", mt_rand(0, 999999));
    $_SESSION['2fa_email_code_expiry'] = time() + 600; // 10 minutes
    
    // Get user email
    $stmt = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['2fa_user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
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
        $mail->Subject = 'LoveJoy - Login Verification Code';
        $mail->Body    = "
            <h2>Hello {$user['first_name']},</h2>
            <p>Your login verification code is:</p>
            <h1 style='color: #4CAF50; font-size: 32px; letter-spacing: 5px;'>{$_SESSION['2fa_email_code']}</h1>
            <p>This code will expire in 10 minutes.</p>
            <p>If you did not attempt to log in, please ignore this email and consider changing your password.</p>
            <p>Best regards,<br>LoveJoy Team</p>
        ";
        
        $mail->send();
        $email_sent = true;
    } catch (Exception $e) {
        $error = "Could not send verification email. Please try again.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['code'];
    
    // Check if code expired
    if (time() > $_SESSION['2fa_email_code_expiry']) {
        $error = "Verification code expired. Please log in again.";
        unset($_SESSION['2fa_email_code']);
        unset($_SESSION['2fa_email_code_expiry']);
    } elseif ($code === $_SESSION['2fa_email_code']) {
        // Code is valid - complete login
        $_SESSION['user_id'] = $_SESSION['2fa_user_id'];
        $_SESSION['email'] = $_SESSION['2fa_email'];
        $_SESSION['first_name'] = $_SESSION['2fa_first_name'];
        $_SESSION['is_admin'] = $_SESSION['2fa_is_admin'];
        
        // Clear 2FA session variables
        unset($_SESSION['2fa_user_id']);
        unset($_SESSION['2fa_email']);
        unset($_SESSION['2fa_first_name']);
        unset($_SESSION['2fa_is_admin']);
        unset($_SESSION['2fa_email_code']);
        unset($_SESSION['2fa_email_code_expiry']);
        
        header("Location: home.php");
        exit();
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
    <title>Email Verification</title>
</head>
<body>
    <h1>Email Verification</h1>
    
    <div class="container">
        <?php if ($error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <?php if (isset($email_sent)): ?>
            <p style="color: green;">✓ Verification code sent to your email</p>
        <?php endif; ?>
        
        <p>Enter the 6-digit code sent to your email:</p>
        
        <form method="post">
            <label for="code">Verification Code:</label>
            <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus>
            <input type="submit" value="Verify">
        </form>
        
        <a href="login.html">Cancel</a>
    </div>
</body>
</html>