<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * PaymentReceivedEmail - Generates payment received notification emails
 * Reuses the order overview template from OrderConfirmationEmail
 */

class PaymentReceivedEmail {
    private $order;
    private $orderItems;
    private $customerEmail;
    private $customerName;
    private $customerPhone;
    private $customerHelpendehanden;
    private $sepaConfig;
    private $currency;

    public function __construct($order, $orderItems, $sepaConfig = array(), $currency = 'EUR') {
        $this->order = $order;
        $this->orderItems = $orderItems;
        $this->sepaConfig = $sepaConfig;
        $this->currency = $currency;

        // Extract customer info from order
        $this->extractCustomerInfo();
    }

    private function extractCustomerInfo() {
        $this->customerEmail = trim((string)$this->order->getVar('customer_email', 'n'));
        $this->customerName = trim((string)$this->order->getVar('customer_name', 'n'));
        $this->customerPhone = trim((string)$this->order->getVar('customer_phone', 'n'));

        // Extract helpende_hand from order fields
        $this->customerHelpendehanden = (string)$this->order->getVar('helpende_hand');
    }

    public function getCustomerEmail() {
        return $this->customerEmail;
    }

    public function getSubject() {
        $orderId = (int)$this->order->getVar('order_id');
        return sprintf(_MD_SIMPLECART_PAYMENT_RECEIVED_SUBJECT, $orderId);
    }

    public function getTextContent() {
        $orderId = (int)$this->order->getVar('order_id');
        $totalAmount = (float)$this->order->getVar('total_amount');
        // Get the pre-formatted timestamp from ImpressCMS XOBJ_DTYPE_LTIME
        $orderDate = (string)$this->order->getVar('timestamp');

        $text = '';
        $text .= str_repeat('=', 70) . "\n";
        $text .= _MD_SIMPLECART_PAYMENT_RECEIVED_HEADING . "\n";
        $text .= str_repeat('=', 70) . "\n\n";

        $text .= _MD_SIMPLECART_EMAIL_GREETING . " " . $this->customerName . "\n\n";
        $text .= sprintf(_MD_SIMPLECART_PAYMENT_RECEIVED_MESSAGE, $orderId) . "\n\n";

        // Order Details Section (reused from confirmation email)
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_EMAIL_ORDER_DETAILS . "\n";
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_ORDER_ID . ": #" . $orderId . "\n";
        $text .= _MD_SIMPLECART_EMAIL_ORDER_DATE . ": " . $orderDate . "\n\n";

        // Customer Information Section
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_EMAIL_CUSTOMER_INFO . "\n";
        $text .= str_repeat('-', 70) . "\n";
        if (!empty($this->customerName)) {
            $text .= _MD_SIMPLECART_NAME . ": " . $this->customerName . "\n";
        }
        if (!empty($this->customerEmail)) {
            $text .= _MD_SIMPLECART_EMAIL . ": " . $this->customerEmail . "\n";
        }
        if (!empty($this->customerPhone)) {
            $text .= _MD_SIMPLECART_PHONE . ": " . $this->customerPhone . "\n";
        }
        if (!empty($this->customerHelpendehanden)) {
            $helpText = $this->getHelpLabel($this->customerHelpendehanden);
            $text .= _MD_SIMPLECART_HELP_MAIL . ": " . $helpText . "\n";
        }
        $text .= "\n";

        // Items Section (reused from confirmation email)
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_EMAIL_ITEMS . "\n";
        $text .= str_repeat('-', 70) . "\n";

        // Column headers
        $text .= sprintf("%-35s %12s %8s %12s\n",
            _MD_SIMPLECART_NAME,
            _MD_SIMPLECART_EMAIL_UNIT_PRICE,
            _MD_SIMPLECART_EMAIL_QUANTITY,
            _MD_SIMPLECART_EMAIL_SUBTOTAL
        );
        $text .= str_repeat('-', 70) . "\n";

        // Items
        foreach ($this->orderItems as $item) {
            $name = (string)$item->getVar('product_name');
            $price = (float)$item->getVar('product_price');
            $qty = (int)$item->getVar('quantity');
            $subtotal = $qty * $price;

            $text .= sprintf("%-35s %12s %8d %12s\n",
                substr($name, 0, 35),
                $this->formatCurrency($price),
                $qty,
                $this->formatCurrency($subtotal)
            );
        }

        // Total
        $text .= str_repeat('-', 70) . "\n";
        $text .= sprintf("%-35s %12s %8s %12s\n",
            _MD_SIMPLECART_TOTAL . ":",
            "",
            "",
            $this->formatCurrency($totalAmount)
        );
        $text .= str_repeat('=', 70) . "\n\n";

        $text .= _MD_SIMPLECART_SCAN_EMAIL_HEADING . "\n";
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_SCAN_EMAIL_TEXT . "\n";
        $text .= $this->order->getScanUrl() . "\n\n";

        // Footer
        $text .= _MD_SIMPLECART_EMAIL_FOOTER . "\n\n";
        $text .= str_repeat('=', 70) . "\n";

        return $text;
    }

    public function getHtmlContent(string $qrContentId): string
    {
        $orderId = (int)$this->order->getVar('order_id');
        $totalAmount = (float)$this->order->getVar('total_amount');
        $orderDate = $this->escape((string)$this->order->getVar('timestamp'));
        $scanUrl = $this->escape($this->order->getScanUrl());

        $customerRows = '';
        $customerFields = [
            _MD_SIMPLECART_NAME => $this->customerName,
            _MD_SIMPLECART_EMAIL => $this->customerEmail,
            _MD_SIMPLECART_PHONE => $this->customerPhone,
            _MD_SIMPLECART_HELP_MAIL => $this->customerHelpendehanden === '' ? '' : $this->getHelpLabel($this->customerHelpendehanden),
        ];

        foreach ($customerFields as $label => $value) {
            if ($value === '') {
                continue;
            }

            $customerRows .= "<tr><td style=\"padding:2px 8px 2px 0;\"><strong>{$this->escape($label)}</strong></td><td>{$this->escape($value)}</td></tr>";
        }

        $itemRows = '';
        foreach ($this->orderItems as $item) {
            $price = (float)$item->getVar('product_price');
            $quantity = (int)$item->getVar('quantity');

            $itemRows .= '<tr>'
                . "<td style=\"padding:4px;border-bottom:1px solid #ddd;\">{$this->escape((string)$item->getVar('product_name', 'n'))}</td>"
                . "<td style=\"padding:4px;border-bottom:1px solid #ddd;text-align:right;\">{$this->formatCurrency($price)}</td>"
                . "<td style=\"padding:4px;border-bottom:1px solid #ddd;text-align:right;\">{$quantity}</td>"
                . "<td style=\"padding:4px;border-bottom:1px solid #ddd;text-align:right;\">{$this->formatCurrency($quantity * $price)}</td>"
                . '</tr>';
        }

        $heading = $this->escape(_MD_SIMPLECART_PAYMENT_RECEIVED_HEADING);
        $greeting = $this->escape(_MD_SIMPLECART_EMAIL_GREETING . ' ' . $this->customerName);
        $message = $this->escape(sprintf(_MD_SIMPLECART_PAYMENT_RECEIVED_MESSAGE, $orderId));
        $detailsHeading = $this->escape(_MD_SIMPLECART_EMAIL_ORDER_DETAILS);
        $orderIdLabel = $this->escape(_MD_SIMPLECART_ORDER_ID);
        $orderDateLabel = $this->escape(_MD_SIMPLECART_EMAIL_ORDER_DATE);
        $customerHeading = $this->escape(_MD_SIMPLECART_EMAIL_CUSTOMER_INFO);
        $itemsHeading = $this->escape(_MD_SIMPLECART_EMAIL_ITEMS);
        $nameLabel = $this->escape(_MD_SIMPLECART_NAME);
        $unitPriceLabel = $this->escape(_MD_SIMPLECART_EMAIL_UNIT_PRICE);
        $quantityLabel = $this->escape(_MD_SIMPLECART_EMAIL_QUANTITY);
        $subtotalLabel = $this->escape(_MD_SIMPLECART_EMAIL_SUBTOTAL);
        $totalLabel = $this->escape(_MD_SIMPLECART_TOTAL);
        $total = $this->formatCurrency($totalAmount);
        $scanHeading = $this->escape(_MD_SIMPLECART_SCAN_EMAIL_HEADING);
        $scanText = $this->escape(_MD_SIMPLECART_SCAN_EMAIL_TEXT);
        $footer = $this->escape(_MD_SIMPLECART_EMAIL_FOOTER);

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;max-width:640px;margin:0 auto;">
    <h1 style="font-size:22px;">{$heading}</h1>
    <p>{$greeting},</p>
    <p>{$message}</p>

    <div style="text-align:center;margin:24px 0;padding:16px;border:1px solid #ddd;border-radius:6px;">
        <h2 style="font-size:18px;margin-top:0;">{$scanHeading}</h2>
        <p>{$scanText}</p>
        <img src="cid:{$qrContentId}" width="300" height="300" alt="QR code order #{$orderId}">
        <p style="font-size:12px;color:#666;word-break:break-all;"><a href="{$scanUrl}">{$scanUrl}</a></p>
    </div>

    <h2 style="font-size:18px;">{$detailsHeading}</h2>
    <table>
        <tr><td style="padding:2px 8px 2px 0;"><strong>{$orderIdLabel}</strong></td><td>#{$orderId}</td></tr>
        <tr><td style="padding:2px 8px 2px 0;"><strong>{$orderDateLabel}</strong></td><td>{$orderDate}</td></tr>
    </table>

    <h2 style="font-size:18px;">{$customerHeading}</h2>
    <table>{$customerRows}</table>

    <h2 style="font-size:18px;">{$itemsHeading}</h2>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th style="padding:4px;text-align:left;border-bottom:2px solid #222;">{$nameLabel}</th>
                <th style="padding:4px;text-align:right;border-bottom:2px solid #222;">{$unitPriceLabel}</th>
                <th style="padding:4px;text-align:right;border-bottom:2px solid #222;">{$quantityLabel}</th>
                <th style="padding:4px;text-align:right;border-bottom:2px solid #222;">{$subtotalLabel}</th>
            </tr>
        </thead>
        <tbody>{$itemRows}</tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="padding:4px;text-align:right;"><strong>{$totalLabel}</strong></td>
                <td style="padding:4px;text-align:right;"><strong>{$total}</strong></td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top:24px;">{$footer}</p>
</body>
</html>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function formatCurrency($amount) {
        return number_format((float)$amount, 2, '.', ',') . ' ' . $this->currency;
    }

    /**
     * Map helpende handen value to translated label
     *
     * @param string $help The helpende handen value
     * @return string The translated help label
     */
    private function getHelpLabel($help) {
        $help = trim($help);

        // Map help values to language constants
        if (strpos($help, '1') !== false) {
            return defined('_MD_SIMPLECART_HELP_1') ? _MD_SIMPLECART_HELP_1 : $help;
        } elseif (strpos($help, '2') !== false) {
            return defined('_MD_SIMPLECART_HELP_2') ? _MD_SIMPLECART_HELP_2 : $help;
        } elseif (strpos($help, '3') !== false) {
            return defined('_MD_SIMPLECART_HELP_3') ? _MD_SIMPLECART_HELP_3 : $help;
        } elseif (strpos($help, '4') !== false) {
            return defined('_MD_SIMPLECART_HELP_4') ? _MD_SIMPLECART_HELP_4 : $help;
        }

        return $help;
    }
}
?>

