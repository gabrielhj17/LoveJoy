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

    // Check password meets strength guidance
    if (!preg_match('/[a-z]/', $pword) || 
        !preg_match('/[A-Z]/', $pword) || 
        !preg_match('/[0-9]/', $pword) || 
        !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $pword) || 
        strlen($pword) < 8) {
        die("Password not strong enough, please try again using the password strength guidance");
    }

    // Generate verification token for email validation
    $verification_token = bin2hex(random_bytes(32));
    
    // Hash the password and security question answer
    $hashed_password = password_hash($pword, PASSWORD_DEFAULT);
    $hashed_answer = password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT);
    
    // Database connection using function in config.php
    $conn = getDBConnection();

    // Check if email already registered
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        die("Email already registered. Please use different credentials or login.");
    }

    // Check if phone number already registered
    $phone_check = $conn->prepare("SELECT user_id FROM user_profiles WHERE phone_number = ?");
    $phone_check->bind_param("s", $pnumber);
    $phone_check->execute();
    $phone_result = $phone_check->get_result();

    if ($phone_result->num_rows > 0) {
        die("Phone number already registered. Please use a different phone number.");
    }

    $check_stmt->close();
    $phone_check->close();

    // Insert user into users table (email_verified = 0)
    $stmt = $conn->prepare("INSERT INTO users (email, password, email_verified) VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $email, $hashed_password);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        
        // Insert into user_profiles
        $profile_stmt = $conn->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone_number) VALUES (?, ?, ?, ?)");
        $profile_stmt->bind_param("isss", $new_user_id, $fname, $lname, $pnumber);
        $profile_stmt->execute();
        
        // Insert into user_security with verification token
        $security_stmt = $conn->prepare("INSERT INTO user_security (user_id, security_question, security_answer_hash, verification_token) VALUES (?, ?, ?, ?)");
        $security_stmt->bind_param("isss", $new_user_id, $security_question, $hashed_answer, $verification_token);
        $security_stmt->execute();
        
        // Send verification email
        if (sendVerificationEmail($email, $fname, $verification_token)) {
            echo "Registration successful! Please check your email to verify your account.";
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