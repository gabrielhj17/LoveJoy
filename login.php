<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    
    // Verify with Google
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
        $error = "reCAPTCHA verification failed. Please try again.";
    } else {
        $email = $_POST['email'];
        $pword = $_POST['pword'];
        
        // Database connection using function in config file
        $conn = getDBConnection();
        
        // Join tables to get all user info
        $stmt = $conn->prepare("
            SELECT 
                u.user_id, 
                u.email, 
                u.password, 
                u.is_admin, 
                u.locked_counter, 
                u.email_verified,
                up.first_name,
                us.two_factor_enabled,
                us.two_factor_method
            FROM users u
            LEFT JOIN user_profiles up ON u.user_id = up.user_id
            LEFT JOIN user_security us ON u.user_id = us.user_id
            WHERE u.email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is locked
            if ($user['locked_counter'] >= 3) {
                $error = "Account is locked due to multiple failed login attempts. Please reset your password using the 'Forgot Password' feature.";
            }
            // Verify password
            elseif (password_verify($pword, $user['password'])) {
                // Check email verification
                if ($user['email_verified'] == 0) {
                    $error = "Please verify your email before logging in. Check your inbox for the verification link.";
                } else {
                    // Reset failed login counter
                    $reset_stmt = $conn->prepare("UPDATE users SET locked_counter = 0 WHERE user_id = ?");
                    $reset_stmt->bind_param("i", $user['user_id']);
                    $reset_stmt->execute();

                    // Check if 2FA is enabled
                    if ($user['two_factor_enabled'] == 1) {
                        // User has 2FA - redirect based on method
                        $_SESSION['2fa_user_id'] = $user['user_id'];
                        $_SESSION['2fa_email'] = $user['email'];
                        $_SESSION['2fa_first_name'] = $user['first_name'];
                        $_SESSION['2fa_is_admin'] = $user['is_admin'];
                        
                        if ($user['two_factor_method'] === 'email') {
                            header("Location: verify2faEmail.php");
                            exit();
                        } else {
                            header("Location: verify2fa.php");
                            exit();
                        }
                    } else {
                        // User doesn't have 2FA - show choice page
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        $_SESSION['must_setup_2fa'] = true;
                        
                        header("Location: choose2faMethod.php");
                        exit();
                    }
                }
            } else {
                // Failed login - increment counter
                $new_counter = $user['locked_counter'] + 1;
                
                $update_stmt = $conn->prepare("UPDATE users SET locked_counter = ? WHERE user_id = ?");
                $update_stmt->bind_param("ii", $new_counter, $user['user_id']);
                $update_stmt->execute();
                
                if ($new_counter >= 3) {
                    $error = "Account locked due to 3 failed login attempts. Please reset your password using the 'Forgot Password' feature.";
                } else {
                    $remaining = 3 - $new_counter;
                    $error = "Invalid password! You have $remaining attempt(s) remaining.";
                }
            }
        } else {
            $error = "Invalid email or password!";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="styles.css">
  <title>LoveJoy Login</title>
</head>
<body>

<script>
  function onSubmit(token) {
    document.getElementById("demo-form").submit();
  }
</script>

  <h1>Login</h1>

  <div class="container">
      <?php if ($error): ?>
          <p style="color: red; background-color: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>
      
      <form action="login.php" method="post" id="demo-form">
          <label for="email">Email Address:</label>
          <input type="email" id="email" name="email" placeholder="Enter Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required><br>

          <label for="pword">Password:</label>
          <input type="password" id="pword" name="pword" placeholder="Enter Password" required><br>

          <input type="checkbox" onclick="showPassword()">Show Password

          <button class="g-recaptcha" 
          data-sitekey="6LdYMyQsAAAAAGE6Nh8V_WfWpaPIEFSNin1zkub7" 
          data-callback='onSubmit' 
          data-action='submit'>Submit</button><br>

          <a href="forgotPassword.php" class="button">Forgot Password?</a><br>
          <a href="register.html" class="button">No account yet? - Register</a>
      </form>
  </div>
  <script src="showPassword.js"></script>
  <script src="https://www.google.com/recaptcha/api.js"></script>
</body>
</html>