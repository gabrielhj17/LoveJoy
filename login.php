<?php
session_start(); // Start session for logged-in users
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
    
    // Database connection
    $conn = new mysqli("127.0.0.1", "root", "", "users");
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Prepared statement to get user by email
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, is_admin FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify the password against the hashed password
        if (password_verify($pword, $user['password'])) {
            // Password is correct - create session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Redirect to dashboard or home page
            header("Location: home.php");
            exit();
        } else {
            echo "Invalid email or password!";
        }
    } else {
        echo "Invalid email or password!";
    }
    
    $stmt->close();
    $conn->close();
}
?>