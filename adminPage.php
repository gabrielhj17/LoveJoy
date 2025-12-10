<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("Access denied. Admin only.");
}

// Database connection
$conn = getDBConnection();

// Get all evaluation requests with user details
$query = "
    SELECT 
        er.*, 
        up.first_name, 
        up.last_name, 
        u.email, 
        up.phone_number,
        up.preferred_contact_method
    FROM evaluation_requests er
    JOIN users u ON er.user_id = u.user_id
    JOIN user_profiles up ON u.user_id = up.user_id
    ORDER BY er.upload_date DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>LoveJoy Admin Page</title>
</head>
<body>
    <h1>LoveJoy Admin Page</h1>
    <div class="container">
        <a href="home.php" class="button">Back to Home</a>
    </div>
    <div class="adminContainer">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="request-card">
                    <img src="<?php echo htmlspecialchars($row['photo_path']); ?>" class="request-image">
                    <div class="request-details">
                        <h3><?php echo htmlspecialchars($row['item_name']); ?></h3>
                        <p><strong>From:</strong> <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($row['upload_date'])); ?></p>
                        <p><strong>Description: </strong><?php echo htmlspecialchars($row['description']); ?></p>
                        <p><strong>Contact via:</strong> <?php echo htmlspecialchars($row['preferred_contact_method']); ?> - 
                        <?php 
                        if ($row['preferred_contact_method'] == 'email') {
                            echo htmlspecialchars($row['email']);
                        } else {
                            echo htmlspecialchars($row['phone_number']);
                        }
                        ?></p>
                        <p><strong>Status:</strong> <span class="status-<?php echo $row['status']; ?>">
                            <?php echo htmlspecialchars($row['status']); ?></span></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No valuation requests yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>