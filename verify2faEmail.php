<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';
require_once 'emailConfig.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Check user is logged in, if not redirect to login
if (!isset($_SESSION['2fa_user_id'])) {
    header("Location: login.html");
    exit();
}

$error = '';
$conn = getDBConnection();

// Load user data from session
$user = [
    'email' => $_SESSION['2fa_email'],
    'first_name' => $_SESSION['2fa_first_name']
];

// Generate and send code if not already sent
if (!isset($_SESSION['2fa_email_code'])) {

    $_SESSION['2fa_email_code'] = sprintf("%06d", mt_rand(0, 999999));
    $_SESSION['2fa_email_code_expiry'] = time() + 600;

    $mail = new PHPMailer(true);
    try {

        // PHP mailer login from config file
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
        $mail->Body = "
            <h2>Hello {$user['first_name']},</h2>
            <p>Your login verification code is:</p>
            <h1 style='color: #4CAF50; font-size: 32px; letter-spacing: 5px;'>{$_SESSION['2fa_email_code']}</h1>
            <p>This code will expire in 10 minutes.</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        $error = "Could not send verification email. Error: " . $mail->ErrorInfo;
    }
}

// Verify submitted code
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['code'];

    if (time() > $_SESSION['2fa_email_code_expiry']) {
        $error = "Verification code expired. Please log in again.";
        unset($_SESSION['2fa_email_code'], $_SESSION['2fa_email_code_expiry']);
    } elseif ($code === $_SESSION['2fa_email_code']) {

        // Login success
        $_SESSION['user_id']    = $_SESSION['2fa_user_id'];
        $_SESSION['email']      = $_SESSION['2fa_email'];
        $_SESSION['first_name'] = $_SESSION['2fa_first_name'];
        $_SESSION['is_admin']   = $_SESSION['2fa_is_admin'];

        unset(
            $_SESSION['2fa_user_id'],
            $_SESSION['2fa_email'],
            $_SESSION['2fa_first_name'],
            $_SESSION['2fa_is_admin'],
            $_SESSION['2fa_email_code'],
            $_SESSION['2fa_email_code_expiry']
        );

        header("Location: home.php");
        exit();
    } else {
        $error = "Invalid verification code.";
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
    <h1>Email Two-Factor Authentication</h1>
    
    <div class="container">
        <?php if ($error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <p>A verification code has been sent to:</p>
        <p><strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
        
        <p>Enter the 6-digit code from your email:</p>
        
        <form method="post">
            <label for="code">Verification Code:</label>
            <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus>
            <input type="submit" value="Verify">
        </form>
        
        <a href="login.html">Cancel</a>
    </div>
</body>
</html>