<?php
// Include the database connection
require_once("includes/db_connection.php");

// Include the mailer.php file
require_once("mailer.php");

// Check if email is submitted
if (!isset($_POST["email"]) || empty($_POST["email"])) {
    echo "Email address is required.";
    exit;
}

$email = $_POST["email"];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email format.";
    exit;
}

// Check if the email exists in our database
$sql = "SELECT user_id, email_address FROM tbl_users WHERE email_address = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No account found with this email address.";
    exit;
}

$user = $result->fetch_assoc();
$user_id = $user["user_id"];

// Generate token
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);
$expires = date("Y-m-d H:i:s", time() + 60 * 30); // Token expires in 30 minutes

// Update the user record with the reset token
$sql = "UPDATE tbl_users SET reset_token = ?, token_expires = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $token_hash, $expires, $user_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    echo "Failed to generate reset token. Please try again later.";
    exit;
}

// Send email with reset link
try {
    $mail = getMailer();
    $mail->setFrom("visayasmemorialpark21@gmail.com", "Visayas Memorial Park");
    $mail->addAddress($email);
    $mail->Subject = "Password Reset";

    // Create HTML message with reset link
    $reset_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;

    $mail->Body = <<<HTML
    <html>
    <body style="font-family: Arial, sans-serif; line-height: 1.6;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
            <h2 style="color: #333;">Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password. Click the button below to create a new password:</p>
            <p style="text-align: center;">
                <a href="{$reset_url}" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Reset Password</a>
            </p>
            <p>If you didn't request a password reset, you can ignore this email.</p>
            <p>This link will expire in 30 minutes.</p>
            <p>Thank you,<br>Visayas Memorial Park Team</p>
        </div>
    </body>
    </html>
    HTML;

    // Set plain text alternative
    $mail->AltBody = "Reset your password by clicking this link: {$reset_url}";

    $mail->send();
    echo "Password reset link has been sent to your email.";
} catch (Exception $e) {
    echo "Failed to send email. Error: {$mail->ErrorInfo}";
}
?>