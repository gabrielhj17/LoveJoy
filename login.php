<?php
session_start(); // Start session for logged-in users

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pword = $_POST['pword'];
    
    // Database connection
    $conn = new mysqli("127.0.0.1", "root", "", "users");
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Prepared statement to get user by email
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password FROM users WHERE email = ?");
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