<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Path to Composer autoloader (or manually require files if not using Composer)
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sends a password reset email using PHPMailer.
 * 
 * @param string $toEmail Recipient's email address
 * @param string $toName Recipient's full name
 * @param string $resetLink The generated reset URL
 * @return bool True on success, false on failure
 */
function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // --- Server Settings ---
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host       = 'smtp.gmail.com';                // SMTP server (e.g., smtp.gmail.com or mail.yourdomain.com)
        $mail->SMTPAuth   = true;                             // Enable SMTP authentication
        $mail->Username   = 'ayubafrax@gmail.com';           // SMTP username
        $mail->Password   = '617608747a';              // SMTP password or App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Encryption: STARTTLS or ENCRYPTION_SMTPS (465)
        $mail->Port       = 587;                              // TCP port (587 for STARTTLS, 465 for SMTPS)

        // --- Recipients ---
        $mail->setFrom('your-email@gmail.com', 'Canuuni Support');
        $mail->addAddress($toEmail, $toName);

        // --- Content ---
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Password Reset Request - Canuuni';

        // HTML Body
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                <h2 style='color: #0d6efd;'>Password Reset Request</h2>
                <p>Hello <strong>" . htmlspecialchars($toName) . "</strong>,</p>
                <p>We received a request to reset your password for your Canuuni account.</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='" . htmlspecialchars($resetLink) . "' style='background-color: #0d6efd; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 20px; font-weight: bold;'>Reset Password</a>
                </p>
                <p style='color: #6c757d; font-size: 13px;'>This link will expire in 1 hour. If you did not request a password reset, you can safely ignore this email.</p>
            </div>
        ";

        // Plain text fallback for non-HTML email clients
        $mail->AltBody = "Hello {$toName},\n\nWe received a request to reset your password. Click or copy the following link into your browser to reset it:\n{$resetLink}\n\nThis link will expire in 1 hour.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error in production instead of displaying to end users
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}