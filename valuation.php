<?php
session_start();
require_once 'config.php';

$error = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $error = "You must be logged in to submit a valuation request.";
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        $error = "File upload error. Error code: " . $file_error;
    } else {
        // Validate file type using mime type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file_tmp);

        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime_type, $allowed_mime_types)) {
            $error = "Invalid file type. Only JPG, PNG, and GIF images allowed.";
        } else {
            // Validate file extension (double check)
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $allowed_extensions)) {
                $error = "Invalid file extension.";
            } else {
                // Validate file size (5MB max)
                $max_size = 5 * 1024 * 1024;
                if ($file_size > $max_size) {
                    $error = "File too large. Maximum size is 5MB.";
                } else {
                    // Generate secure unique filename
                    $new_filename = uniqid('img_', true) . '_' . time() . '.' . $file_ext;
                    $photo_path = $upload_dir . $new_filename;

                    // Move file with error checking
                    if (move_uploaded_file($file_tmp, $photo_path)) {
                        // Set proper permissions
                        chmod($photo_path, 0644);
                        
                        // Database connection
                        $conn = getDBConnection();

                        // Update user's preferred contact method in user_profiles
                        $update_pref = $conn->prepare("UPDATE user_profiles SET preferred_contact_method = ? WHERE user_id = ?");
                        $update_pref->bind_param("si", $contact_method, $user_id);
                        $update_pref->execute();

                        // Insert into evaluation_requests table
                        $stmt = $conn->prepare("INSERT INTO evaluation_requests (user_id, item_name, description, photo_path) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", $user_id, $item_name, $details, $photo_path);

                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "Valuation request submitted successfully!";
                            header("Location: home.php");
                            exit();
                        } else {
                            $error = "Database error: " . $stmt->error;
                        }
                        
                        $stmt->close();
                        $conn->close();
                    } else {
                        $error = "Error uploading file. Please try again.";
                    }
                }
            }
        }
    }
}

// If there's an error, redirect back with error message
if ($error) {
    $_SESSION['error_message'] = $error;
    header("Location: requestEval.php");
    exit();
}
?>