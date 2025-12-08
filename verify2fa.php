<?php
session_start();
require_once 'config.php';

use PragmaRX\Google2FA\Google2FA;
require 'vendor/autoload.php';

if (!isset($_SESSION['2fa_user_id'])) {
    header("Location: login.html");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['code'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT two_factor_secret FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['2fa_user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $google2fa = new Google2FA();
    
    if ($google2fa->verifyKey($user['two_factor_secret'], $code)) {
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
        
        header("Location: home.php");
        exit();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>Two-Factor Verification</title>
</head>
<body>
    <h1>Two-Factor Authentication</h1>
    
    <div class="container">
        <?php if ($error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <p>Enter the 6-digit code from your authenticator app:</p>
        
        <form method="post">
            <label for="code">Verification Code:</label>
            <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus>
            <input type="submit" value="Verify">
        </form>
        
        <a href="login.html">Cancel</a>
    </div>
</body>
</html>