<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * EmailSender - Handles sending HTML emails using ImpressCMS framework
 *
 * This class uses the ImpressCMS icms_messaging_Handler to send emails,
 * which respects the admin-configured mail settings (mail(), SMTP, sendmail).
 */
class EmailSender {

    /**
     * Send an HTML email using ImpressCMS framework
     *
     * @param string $toEmail Recipient email address
     * @param string $subject Email subject
     * @param string $htmlContent HTML email content
     * @param string $fromEmail Optional sender email (defaults to site admin email)
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function sendHtmlEmail($toEmail, $subject, $htmlContent, $fromEmail = null) {
        try {
            global $icmsConfig;

            icms_core_Debug::message('Starting email send process');
            icms_core_Debug::message('Recipient: ' . $toEmail);
            icms_core_Debug::message('Subject: ' . $subject);

            // Validate email address
            if (!self::isValidEmail($toEmail)) {
                icms_core_Debug::message('Invalid recipient email: ' . $toEmail, 'error');
                return false;
            }
            icms_core_Debug::message('Email validation passed');

            // Get sender email if not provided
            if (empty($fromEmail)) {
                $fromEmail = $icmsConfig['adminmail'];
                icms_core_Debug::message('Using admin email as sender: ' . $fromEmail);
            } else {
                icms_core_Debug::message('Using provided sender email: ' . $fromEmail);
            }

            icms_core_Debug::message('Site name: ' . $icmsConfig['sitename']);
            icms_core_Debug::message('HTML content length: ' . strlen($htmlContent) . ' bytes');

            // Create ImpressCMS messaging handler
            icms_core_Debug::message('Creating icms_messaging_Handler instance');
            $handler = new icms_messaging_Handler();
            icms_core_Debug::message('Handler created successfully');

            icms_core_Debug::message('Calling useMail() to use configured mail method');
            $handler->useMail();
            icms_core_Debug::message('useMail() completed');

            // Set email properties
            icms_core_Debug::message('Setting email properties');
            $handler->setFromEmail($fromEmail);
            icms_core_Debug::message('From email set: ' . $fromEmail);

            $handler->setFromName($icmsConfig['sitename']);
            icms_core_Debug::message('From name set: ' . $icmsConfig['sitename']);

            $handler->setSubject($subject);
            icms_core_Debug::message('Subject set: ' . $subject);

            $handler->setBody($htmlContent);
            icms_core_Debug::message('Body set (' . strlen($htmlContent) . ' bytes)');

            $handler->setToEmails($toEmail);
            icms_core_Debug::message('To email set: ' . $toEmail);

            // Add HTML content type header
            icms_core_Debug::message('Adding HTML content type header');
            $handler->addHeaders('Content-Type: text/html; charset=UTF-8');
            icms_core_Debug::message('Headers added');

            // Send email (false = no debug output)
            icms_core_Debug::message('Calling handler->send(false)');
            $result = $handler->send(false);
            icms_core_Debug::message('handler->send() returned: ' . ($result ? 'true' : 'false'));

            if ($result) {
                icms_core_Debug::message('Confirmation email sent successfully to ' . $toEmail, 'success');
                return true;
            } else {
                // Get error messages from handler
                icms_core_Debug::message('Retrieving error messages from handler');
                $errors = $handler->getErrors(false);
                icms_core_Debug::message('Handler errors: ' . print_r($errors, true), 'error');
                $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
                icms_core_Debug::message('Failed to send confirmation email to ' . $toEmail . ': ' . $errorMsg, 'error');
                return false;
            }
        } catch (Exception $e) {
            icms_core_Debug::message('Exception caught: ' . $e->getMessage(), 'error');
            icms_core_Debug::message('Exception code: ' . $e->getCode(), 'error');
            icms_core_Debug::message('Stack trace: ' . $e->getTraceAsString(), 'error');
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

