<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendVerificationEmail($to, $fname, $verification_token) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = EMAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_USERNAME;
        $mail->Password   = EMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = EMAIL_PORT;
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($to, $fname);
        
        // Content
        $verification_link = "http://localhost/lovejoy/verifyEmail.php?token=" . $verification_token;
        
        $mail->isHTML(true);
        $mail->Subject = 'LoveJoy - Verify Your Email';
        $mail->Body    = "
            <h2>Hello $fname,</h2>
            <p>Thank you for registering with LoveJoy!</p>
            <p>Please click the button below to verify your email address:</p>
            <p><a href='$verification_link' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
            <p>Or copy this link: $verification_link</p>
            <p>If you did not create this account, please ignore this email.</p>
            <p>Best regards,<br>LoveJoy Team</p>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>