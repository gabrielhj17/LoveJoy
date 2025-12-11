<?php
session_start();
require_once 'config.php';

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

require 'vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$conn = getDBConnection();
$google2fa = new Google2FA();

// Get user info
$stmt = $conn->prepare("
    SELECT u.email, us.two_factor_enabled, us.two_factor_secret 
    FROM users u 
    JOIN user_security us ON u.user_id = us.user_id 
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user must setup 2FA (first login)
$must_setup = isset($_SESSION['must_setup_2fa']) && $_SESSION['must_setup_2fa'] === true;

// Handle form submission to enable 2FA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enable_2fa'])) {
    $code = $_POST['code'];
    $secret = $_SESSION['temp_2fa_secret'];
    
    // Verify the code
    if ($google2fa->verifyKey($secret, $code)) {
        // Save the secret to database
        $update_stmt = $conn->prepare("UPDATE user_security SET two_factor_enabled = 1, two_factor_secret = ?, two_factor_method = 'google' WHERE user_id = ?");
        $update_stmt->bind_param("si", $secret, $_SESSION['user_id']);
        $update_stmt->execute();
        
        unset($_SESSION['temp_2fa_secret']);
        unset($_SESSION['must_setup_2fa']); // Clear the mandatory flag once setup is sucessful
        
        $success = "Two-factor authentication enabled successfully! You can now access your account.";
        
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}

// Generate new secret if setting up
if (!$user['two_factor_enabled']) {
    if (!isset($_SESSION['temp_2fa_secret'])) {
        $_SESSION['temp_2fa_secret'] = $google2fa->generateSecretKey();
    }
    $secret = $_SESSION['temp_2fa_secret'];
    
    // Generate QR code
    $qrCodeUrl = $google2fa->getQRCodeUrl(
        'LoveJoy',
        $user['email'],
        $secret
    );
    
    $renderer = new ImageRenderer(
        new RendererStyle(200),
        new SvgImageBackEnd()
    );
    $writer = new Writer($renderer);
    $qrCode = $writer->writeString($qrCodeUrl);
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>Two-Factor Authentication Setup</title>
</head>
<body>
    <h1>Two-Factor Authentication</h1>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        
        <?php if ($user['two_factor_enabled']): ?>
            <p style="color: green;">✓ Two-factor authentication is enabled</p>
            
            <?php if (!$must_setup): ?>
                <form method="post">
                    <input type="submit" name="disable_2fa" value="Disable 2FA">
                </form>
            <?php endif; ?>
            
            <a href="home.php" class="button">Continue to Home</a>
            
        <?php else: ?>
            <?php if ($must_setup): ?>
                <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <p style="color: #856404; margin: 0;"><strong>⚠ Two-Factor Authentication Required</strong></p>
                    <p style="color: #856404; margin: 5px 0 0 0;">You must set up 2FA before accessing your account.</p>
                </div>
            <?php endif; ?>
            
            <h3>Setup Two-Factor Authentication</h3>
            <p>1. Install Google Authenticator on your phone:</p>
            <ul style="text-align: left;">
                <li><a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank">iOS</a></li>
                <li><a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</a></li>
            </ul>
            
            <p>2. Scan this QR code with the app:</p>
            <div><?php echo $qrCode; ?></div>
            
            <p>Or enter this secret key manually: <strong><?php echo htmlspecialchars($secret); ?></strong></p>
            
            <p>3. Enter the 6-digit code from the app to verify:</p>
            <form method="post">
                <label for="code">Verification Code:</label>
                <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required>
                <input type="submit" name="enable_2fa" value="Enable 2FA">
            </form>
            
            <?php if (!$must_setup): ?>
                <a href="home.php" class="button">Back to Home</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>