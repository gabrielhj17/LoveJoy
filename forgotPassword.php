<?php
session_start();
require_once 'config.php';

$step = 1;
$conn = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // reCAPTCHA verification
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($verifyURL, false, $context);
    $responseData = json_decode($result);
    
    if (!$responseData->success) {
        $error = "reCAPTCHA verification failed.";
    } else {
        $email = trim($_POST['email']);
        $answer = strtolower(trim($_POST['security_answer']));
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get user by email
        $stmt = $conn->prepare("SELECT user_id, security_question, security_answer_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Email not found.";
        } else {
            $user = $result->fetch_assoc();
            
            // Verify security answer
            if (!password_verify($answer, $user['security_answer_hash'])) {
                $error = "Incorrect security answer.";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                // Update password and reset counter
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ?, locked_counter = 0 WHERE user_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user['user_id']);
                
                if ($update_stmt->execute()) {
                    $success = "Password reset successful! <a href='login.html' class='button'>Login here</a>";
                } else {
                    $error = "Error resetting password.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <title>LoveJoy Forgot Password</title>
</head>

<script>
  function onSubmit(token) {
    document.getElementById("demo-form").submit();
  }
</script>

<body>
    <div class="container">
        <h1>Reset Password</h1>
        
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php else: ?>
        
        <form action="forgotPassword.php" method="post" id="demo-form">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" placeholder="Enter Email Address" required><br>

            <label for="security_answer">Answer to your security question:</label>
            <input type="text" id="security_answer" name="security_answer" placeholder="Enter Answer" required><br>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter New Password" required><br>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required><br>

            <button class="g-recaptcha" 
            data-sitekey="6LdYMyQsAAAAAGE6Nh8V_WfWpaPIEFSNin1zkub7" 
            data-callback='onSubmit' 
            data-action='submit'>Reset Password</button><br>

            <input type="checkbox" onclick="showPassword()">Show Password

            <p id="pwordMatchText">Passwords do not match</p>
        </form>
        
        <a href="login.html" class="button">Back to Login</a>
        <?php endif; ?>
  </div>

<script src="showPassword.js"></script>
<script src="pwordMatch.js"></script>
</body>
</html>