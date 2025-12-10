<?php
session_start();
require_once 'config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $conn = getDBConnection();
    
    // Find user with this token
    $stmt = $conn->prepare("SELECT u.user_id, u.email_verified FROM users u JOIN user_security us ON u.user_id = us.user_id WHERE us.verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['email_verified'] == 1) {
            echo "Email already verified! <a href='login.html'>Login here</a>";
        } else {
            // Verify the email
            $update_stmt = $conn->prepare("UPDATE users u JOIN user_security us ON u.user_id = us.user_id SET u.email_verified = 1, us.verification_token = NULL WHERE us.verification_token = ?");
            $update_stmt->bind_param("s", $token);
            
            if ($update_stmt->execute()) {
                echo "Email verified successfully! You can now <a href='login.html'>login</a>.";
            } else {
                echo "Error verifying email. Please try again.";
            }
        }
    } else {
        echo "Invalid verification token.";
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo "No verification token provided.";
}
?>