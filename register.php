<?php
session_start();
require_once 'config.php';
require_once 'emailConfig.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    
    // Verify with Google
    $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($verifyURL, false, $context);
    $responseData = json_decode($result);
    
    if (!$responseData->success) {
        $error = "reCAPTCHA verification failed. Please try again.";
    } else {
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $email = $_POST['email'];
        $pnumber = $_POST['pnumber'];
        $security_question = $_POST['security_question'];
        $security_answer = $_POST['security_answer'];
        $pword = $_POST['pword'];
        $pwordcheck = $_POST['pwordcheck'];

        // Validate all fields are filled
        if (empty($fname) || empty($lname) || empty($email) || empty($pnumber) || empty($security_question) || empty($security_answer) || empty($pword) || empty($pwordcheck)) {
            $error = "All fields are required. Please fill in all information.";
        }
        // Trim whitespace
        elseif (empty($error)) {
            $fname = trim($fname);
            $lname = trim($lname);
            $email = trim($email);
            $pnumber = trim($pnumber);
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email address format. Please enter a valid email.";
            }
            // Additional email validation - check for common patterns
            elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
                $error = "Invalid email address format. Please enter a valid email.";
            }
            // Check if email domain has MX record (optional but recommended)
            elseif (!checkEmailDomain($email)) {
                $error = "Invalid email domain. Please use a valid email address.";
            }
            // Check passwords match
            elseif ($pword !== $pwordcheck) {
                $error = "Passwords do not match!";
            }
            // Check password meets strength guidance
            elseif (!preg_match('/[a-z]/', $pword) || 
                !preg_match('/[A-Z]/', $pword) || 
                !preg_match('/[0-9]/', $pword) || 
                !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $pword) || 
                strlen($pword) < 8) {
                $error = "Password not strong enough, please try again using the password strength guidance";
            }
            // Validate phone number format (basic validation)
            elseif (!preg_match('/^[0-9\-\+\(\)\s]{10,20}$/', $pnumber)) {
                $error = "Invalid phone number format. Please enter a valid phone number.";
            } else {
                // Generate verification token for email validation
                $verification_token = bin2hex(random_bytes(32));
                
                // Hash the password and security question answer
                $hashed_password = password_hash($pword, PASSWORD_DEFAULT);
                $hashed_answer = password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT);
                
                // Database connection using function in config.php
                $conn = getDBConnection();

                // Check if email already registered
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $error = "Email already registered. Please use different credentials or login.";
                } else {
                    // Check if phone number already registered
                    $phone_check = $conn->prepare("SELECT user_id FROM user_profiles WHERE phone_number = ?");
                    $phone_check->bind_param("s", $pnumber);
                    $phone_check->execute();
                    $phone_result = $phone_check->get_result();

                    if ($phone_result->num_rows > 0) {
                        $error = "Phone number already registered. Please use a different phone number.";
                    } else {
                        // Insert user into users table (email_verified = 0)
                        $stmt = $conn->prepare("INSERT INTO users (email, password, email_verified) VALUES (?, ?, 0)");
                        $stmt->bind_param("ss", $email, $hashed_password);

                        if ($stmt->execute()) {
                            $new_user_id = $stmt->insert_id;
                            
                            // Insert into user_profiles
                            $profile_stmt = $conn->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone_number) VALUES (?, ?, ?, ?)");
                            $profile_stmt->bind_param("isss", $new_user_id, $fname, $lname, $pnumber);
                            $profile_stmt->execute();
                            
                            // Insert into user_security with verification token
                            $security_stmt = $conn->prepare("INSERT INTO user_security (user_id, security_question, security_answer_hash, verification_token) VALUES (?, ?, ?, ?)");
                            $security_stmt->bind_param("isss", $new_user_id, $security_question, $hashed_answer, $verification_token);
                            $security_stmt->execute();
                            
                            // Send verification email
                            if (sendVerificationEmail($email, $fname, $verification_token)) {
                                $success = "Registration successful! Please check your email to verify your account.";
                            } else {
                                $verification_link = "http://localhost/lovejoy/verifyEmail.php?token=" . $verification_token;
                                $success = "Registration successful! Your verification link is: <a href='$verification_link'>$verification_link</a><br>(Email sending failed - use this link to verify)";
                            }
                            
                            $stmt->close();
                        } else {
                            $error = "Registration failed: " . $stmt->error;
                        }
                    }
                    $phone_check->close();
                }
                $check_stmt->close();
                $conn->close();
            }
        }
    }
}

// Helper function to validate email domain
function checkEmailDomain($email) {
    $domain = substr(strrchr($email, "@"), 1);
    
    // Check if domain exists and has MX records
    if (checkdnsrr($domain, "MX") || checkdnsrr($domain, "A")) {
        return true;
    }
    
    return false;
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>LoveJoy Register</title>
    <style>
        .valid { color: green !important; }
        .invalid { color: red !important; }
    </style>
</head>

<body>
    <div class="wrapper">
        <h1>Register</h1>
        <div class="container">
            <?php if ($error): ?>
                <p style="color: red; background-color: #ffebee; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <p style="color: green; background-color: #e8f5e9; padding: 10px; border-radius: 5px;"><?php echo $success; ?></p>
                <a href="login.html" class="button">Go to Login</a>
            <?php else: ?>
            
            <form action="register.php" method="post" id="register-form">
                <label for="fname">First Name:</label>
                <input type="text" id="fname" name="fname" placeholder="Enter First Name" value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''; ?>" required><br>

                <label for="lname">Last Name:</label>
                <input type="text" id="lname" name="lname" placeholder="Enter Last Name" value="<?php echo isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''; ?>" required><br>

                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" placeholder="Enter Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required 
                pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                title="Please enter a valid email address"><br>

                <label for="pnumber">Phone Number:</label>
                <input type="tel" id="pnumber" name="pnumber" placeholder="Enter Phone Number" value="<?php echo isset($_POST['pnumber']) ? htmlspecialchars($_POST['pnumber']) : ''; ?>" required
                pattern="[0-9\-\+\(\)\s]{10,20}"
                title="Please enter a valid phone number (10-20 digits)"><br>

                <label for="security_question">Security Question:</label>
                <select id="security_question" name="security_question" required>
                    <option value="">-- Select a Question --</option>
                    <option value="What was the name of your first pet?" <?php echo (isset($_POST['security_question']) && $_POST['security_question'] == "What was the name of your first pet?") ? 'selected' : ''; ?>>What was the name of your first pet?</option>
                    <option value="What city were you born in?" <?php echo (isset($_POST['security_question']) && $_POST['security_question'] == "What city were you born in?") ? 'selected' : ''; ?>>What city were you born in?</option>
                    <option value="What is your mother's maiden name?" <?php echo (isset($_POST['security_question']) && $_POST['security_question'] == "What is your mother's maiden name?") ? 'selected' : ''; ?>>What is your mother's maiden name?</option>
                    <option value="What was the name of your first school?" <?php echo (isset($_POST['security_question']) && $_POST['security_question'] == "What was the name of your first school?") ? 'selected' : ''; ?>>What was the name of your first school?</option>
                    <option value="What is your favorite book?" <?php echo (isset($_POST['security_question']) && $_POST['security_question'] == "What is your favorite book?") ? 'selected' : ''; ?>>What is your favorite book?</option>
                </select><br>

                <label for="security_answer">Security Answer:</label>
                <input type="text" id="security_answer" name="security_answer" placeholder="Enter Answer" required><br>

                <label for="pword">Password:</label>
                <input type="password" id="pword" name="pword" placeholder="Enter Password" 
                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?]).{8,}" required><br>

                <label for="pwordcheck">Re-Enter Password:</label>
                <input type="password" id="pwordcheck" name="pwordcheck" placeholder="Re-enter Password" required><br>

                <input type="checkbox" onclick="showPassword()">Show Password

                <p id="pwordMatchText">Passwords do not match</p>

                <div id="message">
                    <h3>Password must contain the following:</h3>
                    <p id="letter" class="invalid">A <b>lowercase</b> letter</p>
                    <p id="capital" class="invalid">A <b>capital (uppercase)</b> letter</p>
                    <p id="number" class="invalid">A <b>number</b></p>
                    <p id="special" class="invalid">A <b>special character</b> (!@#$%^&* etc.)</p>
                    <p id="length" class="invalid">Minimum <b>8 characters</b></p>
                </div>
                
                <button class="g-recaptcha" 
                data-sitekey="6LdYMyQsAAAAAGE6Nh8V_WfWpaPIEFSNin1zkub7" 
                data-callback='onSubmit' 
                data-action='submit'>Submit</button><br>

                <input type="reset" value="Reset">
                
                <a href="login.html" class="button">Already got an account? - Login Here</a>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts at bottom so DOM is ready -->
    <script src="showPassword.js"></script>
    <script src="pwordMatch.js"></script>
    <script src="pwordStrength.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script>
        function onSubmit(token) {
            document.getElementById("register-form").submit();
        }
    </script>
</body>
</html>