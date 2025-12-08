<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("Access denied. Admin only.");
}
?>
<!DOCTYPE html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>LoveJoy Admin Page</title>
</head>
<body>
    <h1>LoveJoy Admin Page</h1>
</body>
</html>