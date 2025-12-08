<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to submit a valuation request.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $user_id = $_SESSION['user_id'];
    $item_name = $_POST['item_name'];
    $details = $_POST['details'];
    $contact_method = $_POST['contact_method'];
    
    // Handle file upload
    $upload_dir = 'uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_name = $_FILES['photo']['name'];
    $file_tmp = $_FILES['photo']['tmp_name'];
    $file_size = $_FILES['photo']['size'];
    $file_error = $_FILES['photo']['error'];

    // Check for upload errors
    if ($file_error !== UPLOAD_ERR_OK) {
        die("File upload error. Error code: " . $file_error);
    }
    
    // Validate file type using mime type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_tmp);

    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime_type, $allowed_mime_types)) {
        die("Invalid file type. Only JPG, PNG, and GIF images allowed.");
    }

    // Validate file extension (double check)
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_ext, $allowed_extensions)) {
        die("Invalid file extension.");
    }

    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024;
    if ($file_size > $max_size) {
        die("File too large. Maximum size is 5MB.");
    }
    
    // Generate secure unique filename
    $new_filename = uniqid('img_', true) . '_' . time() . '.' . $file_ext;
    $photo_path = $upload_dir . $new_filename;

    // Move file with error checking
    if (move_uploaded_file($file_tmp, $photo_path)) {
        // Set proper permissions
        chmod($photo_path, 0644);
        
        // Database connection
        $conn = new mysqli("localhost", "root", "", "users", null, "/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock");
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO items (user_id, item_name, description, photo_path, contact_method) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $item_name, $details, $photo_path, $contact_method);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Valuation request submitted successfully!";
            header("Location: home.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        
        $stmt->close();
        $conn->close();
    } else {
        die("Error uploading file. Please try again.");
    }
}
?>