<?php
session_start();
require_once 'config.php';

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
    
    // Hash the password and security quesiton answer
    $hashed_password = password_hash($pword, PASSWORD_DEFAULT);
    $hashed_answer = password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT); // Lowercase for consistency
    
    // Database connection
    $conn = new mysqli("localhost", "root", "", "users", null, "/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock");
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if email or phone number already registered
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone_number = ?");
    $check_stmt->bind_param("ss", $email, $pnumber);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        die("Email or phone number already registered. Please use different credentials or login.");
    }
    $check_stmt->close();
    
    // Prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, security_question, security_answer_hash, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $fname, $lname, $email, $pnumber, $security_question, $hashed_answer, $hashed_password);
    
    if ($stmt->execute()) {
    // Get the newly created user's ID
    $new_user_id = $stmt->insert_id;
    
    // Start session and log them in
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $fname;
    $_SESSION['is_admin'] = 0;
    
    // Redirect to home page
    header("Location: home.php");
    exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>