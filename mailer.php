<?php
/**
 * Mailer Configuration
 * This file contains functions for sending emails using PHPMailer
 */

// Include the PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

/**
 * Get a configured mailer instance
 * @return PHPMailer Returns a configured PHPMailer instance
 */
function getMailer()
{
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    // Configure SMTP settings
    $mail->isSMTP();                                     // Use SMTP
    $mail->SMTPAuth = true;                              // Enable SMTP authentication
    $mail->Host = 'smtp.gmail.com';                       // SMTP server
    $mail->Username = 'visayasmemorialpark21@gmail.com'; // SMTP username (the Gmail address)
    $mail->Password = 'umjftingueqozvht';                // App password generated for Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
    $mail->Port = 587;                                   // TCP port to connect to

    // Set email format to HTML
    $mail->isHTML(true);

    // Additional debug settings to help identify issues (you can set to 2 for detailed debugging)
    $mail->SMTPDebug = 0;                                // Set to 2 for detailed debugging
    $mail->Debugoutput = 'html';                         // Format of debug output

    // No preset sender or recipient - these should be set by the calling code

    return $mail;
}
