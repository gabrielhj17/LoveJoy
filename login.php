<?php
session_start();
require_once 'config.php';

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
        die("reCAPTCHA verification failed. Please try again.");
    }

    $email = $_POST['email'];
    $pword = $_POST['pword'];
    
    // Database connection using function in config file
    $conn = getDBConnection();
    
    // Get user by email
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, is_admin, locked_counter, email_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if account is locked
        if ($user['locked_counter'] >= 3) {
            die("Account is locked due to multiple failed login attempts. Please reset your password using the 'Forgot Password' feature.");
        }
        
        // Verify password
        if (password_verify($pword, $user['password'])) {
            // Check email verification
            if ($user['email_verified'] == 0) {
                die("Please verify your email before logging in. Check your inbox for the verification link.");
            }

            // Successful login - reset counter
            $reset_stmt = $conn->prepare("UPDATE users SET locked_counter = 0 WHERE user_id = ?");
            $reset_stmt->bind_param("i", $user['user_id']);
            $reset_stmt->execute();
            
            // Create session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            header("Location: home.php");
            exit();
        } else {
            // Failed login - increment counter
            $new_counter = $user['locked_counter'] + 1;
            
            $update_stmt = $conn->prepare("UPDATE users SET locked_counter = ? WHERE user_id = ?");
            $update_stmt->bind_param("ii", $new_counter, $user['user_id']);
            $update_stmt->execute();
            
            if ($new_counter >= 3) {
                die("Account locked due to 3 failed login attempts. Please reset your password using the 'Forgot Password' feature.");
            } else {
                $remaining = 3 - $new_counter;
                echo "Invalid password! You have $remaining attempt(s) remaining.";
            }
        }
    } else {
        echo "Invalid email or password!";
    }
    
    $stmt->close();
    $conn->close();
}
?>