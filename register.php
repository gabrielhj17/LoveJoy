<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $pnumber = $_POST['pnumber'];
    $pword = $_POST['pword'];
    $pwordcheck = $_POST['pwordcheck'];
    
    // Check passwords match
    if ($pword !== $pwordcheck) {
        die("Passwords do not match!");
    }
    
    // Hash the password
    $hashed_password = password_hash($pword, PASSWORD_DEFAULT);
    
    // Database connection
    $conn = new mysqli("localhost", "root", "", "users", null, "/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock");
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fname, $lname, $email, $pnumber, $hashed_password);
    
    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>