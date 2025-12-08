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
    <title>LoveJoy Home</title>
</head>
<br>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
    <a href="requestEval.php" class="button">Request Evaluation</a>
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
        <a href="admin.php" class="button">Admin Panel - View Requests</a>
    <?php endif; ?>
    <a href="logout.php" class="button">Logout</a>
</body>
</html>