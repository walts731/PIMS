<?php
// Email Configuration for PIMS - PHPMailer Setup
// Install PHPMailer: composer require phpmailer/phpmailer

// Gmail SMTP Settings (Recommended)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'pims.system@gmail.com');     // Your Gmail address
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');         // Gmail app password (replace with actual app password)
define('SMTP_ENCRYPTION', 'tls');

// System Email Settings
define('SYSTEM_EMAIL', 'pims.system@gmail.com');      // Your system email
define('SYSTEM_EMAIL_NAME', 'PIMS System');          // System email name

// Site Settings
define('SITE_URL', 'http://localhost/PIMS');         // Your site URL
define('SITE_NAME', 'PIMS');                          // Your site name

/**
 * Send email using PHPMailer
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $altBody Alternative plain text body
 * @return bool Success status
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    try {
        // Import PHPMailer from manual installation
        require_once __DIR__ . '/../SYSTEM_ADMIN/PHPMailer/PHPMailer-7.0.0/src/PHPMailer.php';
        require_once __DIR__ . '/../SYSTEM_ADMIN/PHPMailer/PHPMailer-7.0.0/src/SMTP.php';
        require_once __DIR__ . '/../SYSTEM_ADMIN/PHPMailer/PHPMailer-7.0.0/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SYSTEM_EMAIL, SYSTEM_EMAIL_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send password reset email
 * 
 * @param string $to Recipient email
 * @param string $username User's username
 * @param string $resetLink Password reset link
 * @return bool Success status
 */
function sendPasswordResetEmail($to, $username, $resetLink) {
    $subject = "Password Reset Request - " . SITE_NAME;
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Password Reset</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #191BA9; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            .security-note { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset Request</h1>
                <p>" . SITE_NAME . " - Property Inventory Management System</p>
            </div>
            <div class='content'>
                <p>Hello <strong>$username</strong>,</p>
                <p>We received a request to reset your password for your " . SITE_NAME . " account.</p>
                
                <div class='security-note'>
                    <strong>🔒 Security Notice:</strong> This password reset link will expire in 1 hour for your security. If you didn't request this reset, please ignore this email.
                </div>
                
                <p style='text-align: center;'>
                    <a href='$resetLink' class='button'>Reset My Password</a>
                </p>
                
                <p>Or copy and paste this link into your browser:</p>
                <p style='background: #f0f0f0; padding: 10px; border-radius: 5px; word-break: break-all;'>
                    $resetLink
                </p>
                
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This link expires after 1 hour</li>
                    <li>If you didn't request this reset, please contact your administrator</li>
                    <li>Never share this link with anyone</li>
                </ul>
            </div>
            <div class='footer'>
                <p>This is an automated message from " . SITE_NAME . ". Please do not reply to this email.</p>
                <p>© " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    $altBody = "
    Password Reset Request - " . SITE_NAME . "
    
    Hello $username,
    
    We received a request to reset your password for your " . SITE_NAME . " account.
    
    Click this link to reset your password: $resetLink
    
    Important:
    - This link expires after 1 hour
    - If you didn't request this reset, please contact your administrator
    - Never share this link with anyone
    
    This is an automated message. Please do not reply.
    ";
    
    return sendEmail($to, $subject, $body, $altBody);
}
?>
