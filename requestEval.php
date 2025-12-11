<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="styles.css">
  <title>LoveJoy Request Evaluation</title>
</head>

<body>
    <h1>Request Evaluation of an Item</h1>

    <div class="container">
        <?php if (isset($_SESSION['error_message'])): ?>
            <p style="color: red; background-color: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
                ?>
            </p>
        <?php endif; ?>
        
        <form action="valuation.php" method="post" enctype="multipart/form-data" id="valuation-form">
            <label for="item_name">Item Name:</label>
            <input type="text" id="item_name" name="item_name" placeholder="Enter item name" required><br>
            
            <label for="details">Item Details & Request:</label>
            <textarea id="details" name="details" rows="6" placeholder="Please describe your item and what you'd like to know about it..." required></textarea><br>
            
            <label for="photo">Upload Photo:</label>
            <input type="file" id="photo" name="photo" accept="image/*" required><br>
            
            <label for="contact_method">Preferred Contact Method:</label>
            <select id="contact_method" name="contact_method" required>
                <option value="">-- Select Method --</option>
                <option value="email">Email</option>
                <option value="phone">Phone</option>
            </select><br>
            
            <input type="submit" value="Submit Request"><br>
            
            <input type="reset" value="Reset">
        </form>
        
        <a href="home.php" class="button">Back to Home</a>
    </div>
</body>
</html>