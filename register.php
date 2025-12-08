<?php
session_start();
require_once 'config.php';
require_once 'emailConfig.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $pnumber = $_POST['pnumber'];
    $security_question = $_POST['security_question'];
    $security_answer = $_POST['security_answer'];
    $pword = $_POST['pword'];
    $pwordcheck = $_POST['pwordcheck'];

    // Validate all fields are filled
    if (empty($fname) || empty($lname) || empty($email) || empty($pnumber) || empty($security_question) || empty($security_answer) || empty($pword) || empty($pwordcheck)) {
        die("All fields are required. Please fill in all information.");
    }

    // Trim whitespace
    $fname = trim($fname);
    $lname = trim($lname);
    $email = trim($email);
    $pnumber = trim($pnumber);
    
    // Check passwords match
    if ($pword !== $pwordcheck) {
        die("Passwords do not match!");
    }

    // Generate verification token for email validation
    $verification_token = bin2hex(random_bytes(32));
    
    // Hash the password and security question answer
    $hashed_password = password_hash($pword, PASSWORD_DEFAULT);
    $hashed_answer = password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT);
    
    // Database connection
    $conn = getDBConnection();

    // Check if email or phone number already registered
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone_number = ?");
    $check_stmt->bind_param("ss", $email, $pnumber);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        die("Email or phone number already registered. Please use different credentials or login.");
    }
    $check_stmt->close();

    // Insert user with verification token (email_verified = 0)
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, security_question, security_answer_hash, password, email_verified, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
    $stmt->bind_param("ssssssss", $fname, $lname, $email, $pnumber, $security_question, $hashed_answer, $hashed_password, $verification_token);
    
    if ($stmt->execute()) {
        // Use PHPMailer function instead of mail()
        if (sendVerificationEmail($email, $fname, $verification_token)) {
            echo "Registration successful! Please check your email to verify your account before logging in.";
        } else {
            $verification_link = "http://localhost/lovejoy/verifyEmail.php?token=" . $verification_token;
            echo "Registration successful! Your verification link is: <a href='$verification_link'>$verification_link</a><br>(Email sending failed - use this link to verify)";
        }
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>