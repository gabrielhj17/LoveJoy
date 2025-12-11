<?php
session_start();
require_once 'config.php';

// Check if user is logged in, if not redirect them to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $method = $_POST['method'];
    // Redirect to appropriate page upon response
    if ($method === 'google') {
        header("Location: 2faSetup.php");
        exit();
    } elseif ($method === 'email') {
        header("Location: 2faEmailSetup.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>Choose Two-Factor Method</title>
</head>
<body>
    <h1>Two-Factor Authentication</h1>
    
    <div class="container">
        <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <p style="color: #856404; margin: 0;"><strong>⚠ Two-Factor Authentication Required</strong></p>
            <p style="color: #856404; margin: 5px 0 0 0;">Please choose your preferred authentication method.</p>
        </div>
        
        <h3>Choose Authentication Method</h3>
        
        <form method="post">
            <div style="margin: 20px 0; padding: 15px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer;" onclick="document.getElementById('google').checked = true;">
                <input type="radio" id="google" name="method" value="google" required>
                <label for="google" style="cursor: pointer;">
                    <strong>Google Authenticator</strong><br>
                    <small>Use an authenticator app on your phone to generate codes</small>
                </label>
            </div>
            
            <div style="margin: 20px 0; padding: 15px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer;" onclick="document.getElementById('email').checked = true;">
                <input type="radio" id="email" name="method" value="email" required>
                <label for="email" style="cursor: pointer;">
                    <strong>Email Verification</strong><br>
                    <small>Receive verification codes via email</small>
                </label>
            </div>
            
            <input type="submit" value="Continue">
        </form>
    </div>
</body>
</html>