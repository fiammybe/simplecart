<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * OrderConfirmationEmail - Generates HTML confirmation emails for orders
 */

class OrderConfirmationEmail {
    private $order;
    private $orderItems;
    private $customerEmail;
    private $customerName;
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
        $customerInfo = (string)$this->order->getVar('customer_info');
        icms_core_Debug::message('OrderConfirmationEmail: customer_info raw content: ' . $customerInfo);

        // Decode HTML entities (setVar() HTML-encodes the data)
        $customerInfoDecoded = html_entity_decode($customerInfo, ENT_QUOTES, 'UTF-8');
        icms_core_Debug::message('OrderConfirmationEmail: customer_info decoded: ' . $customerInfoDecoded);

        // Parse customer_info as JSON
        $customerData = json_decode($customerInfoDecoded, true);

        if (is_array($customerData)) {
            // Extract email and name from JSON
            $this->customerEmail = isset($customerData['email']) ? trim($customerData['email']) : '';
            $this->customerName = isset($customerData['name']) ? trim($customerData['name']) : '';
            icms_core_Debug::message('OrderConfirmationEmail: extracted email: ' . ($this->customerEmail ?: 'EMPTY'));
            icms_core_Debug::message('OrderConfirmationEmail: extracted name: ' . ($this->customerName ?: 'EMPTY'));
        } else {
            // JSON parsing failed
            icms_core_Debug::message('OrderConfirmationEmail: ERROR - Failed to parse customer_info as JSON', 'error');
            $this->customerEmail = '';
            $this->customerName = '';
        }

        icms_core_Debug::message('OrderConfirmationEmail: final customerEmail: ' . ($this->customerEmail ?: 'EMPTY'));
        icms_core_Debug::message('OrderConfirmationEmail: final customerName: ' . ($this->customerName ?: 'EMPTY'));
    }

    public function getCustomerEmail() {
        return $this->customerEmail;
    }

    public function getSubject() {
        $orderId = (int)$this->order->getVar('order_id');
        return sprintf(_MD_SIMPLECART_EMAIL_SUBJECT, $orderId);
    }

    public function getHtmlContent() {
        $orderId = (int)$this->order->getVar('order_id');
        $totalAmount = (float)$this->order->getVar('total_amount');
        $timestamp = (int)$this->order->getVar('timestamp');
        $orderDate = date('Y-m-d H:i:s', $timestamp);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body { font-family: Arial, sans-serif; color: #333; }';
        $html .= '.container { max-width: 600px; margin: 0 auto; padding: 20px; }';
        $html .= '.header { background-color: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }';
        $html .= '.section { margin-bottom: 20px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin: 15px 0; }';
        $html .= 'th { background-color: #f0f0f0; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }';
        $html .= 'td { padding: 10px; border-bottom: 1px solid #eee; }';
        $html .= '.total-row { font-weight: bold; background-color: #f9f9f9; }';
        $html .= '.footer { color: #666; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }';
        $html .= '</style></head><body>';

        $html .= '<div class="container">';

        // Header
        $html .= '<div class="header">';
        $html .= '<h1>' . htmlspecialchars(_MD_SIMPLECART_EMAIL_THANK_YOU) . '</h1>';
        $html .= '<p>' . htmlspecialchars(_MD_SIMPLECART_EMAIL_GREETING) . ' ' . htmlspecialchars($this->customerName) . '</p>';
        $html .= '</div>';

        // Order Details Section
        $html .= '<div class="section">';
        $html .= '<h2>' . htmlspecialchars(_MD_SIMPLECART_EMAIL_ORDER_DETAILS) . '</h2>';
        $html .= '<p><strong>' . htmlspecialchars(_MD_SIMPLECART_ORDER_ID) . ':</strong> #' . $orderId . '</p>';
        $html .= '<p><strong>' . htmlspecialchars(_MD_SIMPLECART_EMAIL_ORDER_DATE) . ':</strong> ' . $orderDate . '</p>';
        $html .= '</div>';

        // Items Table
        $html .= '<div class="section">';
        $html .= '<h2>' . htmlspecialchars(_MD_SIMPLECART_EMAIL_ITEMS) . '</h2>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>' . htmlspecialchars(_MD_SIMPLECART_NAME) . '</th>';
        $html .= '<th style="text-align: right;">' . htmlspecialchars(_MD_SIMPLECART_EMAIL_UNIT_PRICE) . '</th>';
        $html .= '<th style="text-align: center;">' . htmlspecialchars(_MD_SIMPLECART_EMAIL_QUANTITY) . '</th>';
        $html .= '<th style="text-align: right;">' . htmlspecialchars(_MD_SIMPLECART_EMAIL_SUBTOTAL) . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($this->orderItems as $item) {
            $name = htmlspecialchars((string)$item->getVar('product_name'));
            $price = (float)$item->getVar('product_price');
            $qty = (int)$item->getVar('quantity');
            $subtotal = $qty * $price;

            $html .= '<tr>';
            $html .= '<td>' . $name . '</td>';
            $html .= '<td style="text-align: right;">' . $this->formatCurrency($price) . '</td>';
            $html .= '<td style="text-align: center;">' . $qty . '</td>';
            $html .= '<td style="text-align: right;">' . $this->formatCurrency($subtotal) . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr class="total-row">';
        $html .= '<td colspan="3" style="text-align: right;">' . htmlspecialchars(_MD_SIMPLECART_TOTAL) . ':</td>';
        $html .= '<td style="text-align: right;">' . $this->formatCurrency($totalAmount) . '</td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        $html .= '</div>';

        // Payment Information Section
        if (!empty($this->sepaConfig['beneficiary_iban'])) {
            $html .= '<div class="section">';
            $html .= '<h2>' . htmlspecialchars(_MD_SIMPLECART_PAYMENT_INFO) . '</h2>';
            $html .= '<table>';
            $html .= '<tr><td><strong>' . htmlspecialchars(_MD_SIMPLECART_BENEFICIARY) . ':</strong></td>';
            $html .= '<td>' . htmlspecialchars($this->sepaConfig['beneficiary_name']) . '</td></tr>';
            $html .= '<tr><td><strong>' . htmlspecialchars(_MD_SIMPLECART_IBAN) . ':</strong></td>';
            $html .= '<td style="font-family: monospace;">' . htmlspecialchars($this->sepaConfig['beneficiary_iban']) . '</td></tr>';
            $html .= '<tr><td><strong>' . htmlspecialchars(_MD_SIMPLECART_AMOUNT) . ':</strong></td>';
            $html .= '<td>' . $this->formatCurrency($totalAmount) . '</td></tr>';
            $html .= '</table>';
            $html .= '</div>';
        }

        // Footer
        $html .= '<div class="footer">';
        $html .= '<p>' . htmlspecialchars(_MD_SIMPLECART_EMAIL_FOOTER) . '</p>';
        $html .= '</div>';

        $html .= '</div></body></html>';

        return $html;
    }

    private function formatCurrency($amount) {
        return number_format((float)$amount, 2, '.', ',') . ' ' . htmlspecialchars($this->currency);
    }
}
?>

