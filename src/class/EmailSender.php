<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * EmailSender - Handles sending plain text emails using ImpressCMS framework
 *
 * This class uses the ImpressCMS icms_messaging_Handler to send emails,
 * which respects the admin-configured mail settings (mail(), SMTP, sendmail).
 */
class EmailSender {

    /**
     * Send a plain text email using ImpressCMS framework
     *
     * @param string $toEmail Recipient email address
     * @param string $subject Email subject
     * @param string $textContent Plain text email content
     * @param string $fromEmail Optional sender email (defaults to site admin email)
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function sendTextEmail($toEmail, $subject, $textContent, $fromEmail = null) {
        try {
            global $icmsConfig;

            // Validate email address
            if (!self::isValidEmail($toEmail)) {
                return false;
            }

            // Get sender email if not provided
            if (empty($fromEmail)) {
                $fromEmail = $icmsConfig['adminmail'];
            }

            // Create ImpressCMS messaging handler
            $handler = new icms_messaging_Handler();
            $handler->useMail();

            // Set email properties
            $handler->setFromEmail($fromEmail);
            $handler->setFromName($icmsConfig['sitename']);
            $handler->setSubject($subject);
            $handler->setBody($textContent);
            $handler->setToEmails($toEmail);

            // Send email (false = no debug output)
            $result = $handler->send(false);

            if ($result) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate email address format
     *
     * @param string $email Email address to validate
     * @return bool True if valid, false otherwise
     */
    private static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
?>

