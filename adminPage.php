<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<br>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>LoveJoy Admin Page</title>
</head>
<br>
</body>
</html>