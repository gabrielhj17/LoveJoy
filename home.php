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
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
        <a href="requestEval.php" class="button">Request Evaluation</a><br><br>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
            <a href="adminPage.php" class="button">View Evaluation Requests</a><br><br>
        <?php endif; ?>
        <a href="logout.php" class="button">Logout</a>
        <?php if (isset($_SESSION['success_message'])): ?>
            <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    </div>
</body>
</html>