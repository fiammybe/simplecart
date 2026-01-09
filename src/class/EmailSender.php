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

            // Debug logging
            $debugEnabled = defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL;
            if ($debugEnabled) {
                require_once dirname(dirname(__FILE__)) . '/include/common.php';
                simplecart_debugLog("EmailSender::sendTextEmail() called");
                simplecart_debugLog("  - toEmail: {$toEmail}");
                simplecart_debugLog("  - subject: {$subject}");
                simplecart_debugLog("  - textContent length: " . strlen($textContent) . " characters");
                simplecart_debugLog("  - fromEmail: " . (empty($fromEmail) ? "not provided (will use admin email)" : $fromEmail));
            }

            // Validate email address
            if (!self::isValidEmail($toEmail)) {
                if ($debugEnabled) {
                    simplecart_debugLog("ERROR: Email validation failed for: {$toEmail}");
                }
                return false;
            }

            if ($debugEnabled) {
                simplecart_debugLog("Email validation passed for: {$toEmail}");
            }

            // Get sender email if not provided
            if (empty($fromEmail)) {
                $fromEmail = "info@colomaenpa.be";
                if ($debugEnabled) {
                    simplecart_debugLog("Using defined email as sender: {$fromEmail}");
                }
            }

            // Create ImpressCMS messaging handler
            if ($debugEnabled) {
                simplecart_debugLog("Creating icms_messaging_Handler...");
            }
            $handler = new icms_messaging_Handler();
            $handler->useMail();

            // Set email properties
            if ($debugEnabled) {
                simplecart_debugLog("Setting email handler properties...");
            }
            $handler->setFromEmail($fromEmail);
            $handler->setFromName($icmsConfig['sitename']);
            $handler->setSubject($subject);
            $handler->setBody($textContent);
            $handler->setToEmails($toEmail);

            // Send email (false = no debug output)
            if ($debugEnabled) {
                simplecart_debugLog("Calling handler->send(false)...");
            }
            $result = $handler->send(false);

            if ($debugEnabled) {
                simplecart_debugLog("handler->send() returned: " . ($result ? "TRUE" : "FALSE"));
            }

            if ($result) {
                if ($debugEnabled) {
                    simplecart_debugLog("Email sent successfully to: {$toEmail}");
                }
                return true;
            } else {
                if ($debugEnabled) {
                    simplecart_debugLog("Email sending failed for: {$toEmail}");
                }
                return false;
            }
        } catch (Exception $e) {
            if (defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL) {
                require_once dirname(dirname(__FILE__)) . '/include/common.php';
                simplecart_debugLog("EXCEPTION in EmailSender::sendTextEmail(): " . $e->getMessage());
                simplecart_debugLog("Exception trace: " . $e->getTraceAsString());
            }
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

