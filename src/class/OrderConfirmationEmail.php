<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/OrderEmailTrait.php';

/**
 * OrderConfirmationEmail - Generates plain text confirmation emails for orders
 */

class OrderConfirmationEmail {
    use OrderEmailTrait;

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
        $this->initLanguage($order);

        // Extract customer info from order
        $this->extractCustomerInfo();

        // Debug logging
        if (defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL) {
            require_once dirname(__DIR__) . '/include/common.php';
            simplecart_debugLog("OrderConfirmationEmail constructor: customer_email='" . $this->customerEmail . "', customer_name='" . $this->customerName . "'");
        }
    }

    private function extractCustomerInfo() {
        $this->customerEmail = trim((string)$this->order->getVar('customer_email', 'n'));
        $this->customerName = trim((string)$this->order->getVar('customer_name', 'n'));
    }

    public function getCustomerEmail() {
        return $this->customerEmail;
    }

    public function getSubject() {
        $orderId = (int)$this->order->getVar('order_id');
        $campaign = simplecart_getHandler('campaign')->getHeadlineCampaign();

        if (! $campaign) {
            return sprintf($this->t('EMAIL_SUBJECT'), $orderId);
        }

        return sprintf($this->t('EMAIL_SUBJECT_CAMPAIGN'), $orderId, (string)$campaign->getVar('name', 'n'));
    }

    public function getTextContent() {
        $orderId = (int)$this->order->getVar('order_id');
        $totalAmount = (float)$this->order->getVar('total_amount');
        $orderDate = $this->formatDate();

        $text = '';
        $text .= str_repeat('=', 70) . "\n";
        $text .= $this->t('EMAIL_GREETING') . " " . $this->customerName . "\n\n";
        $text .= $this->t('EMAIL_THANK_YOU') . "\n";
        $text .= str_repeat('=', 70) . "\n\n";

        // Order Details Section
        $text .= str_repeat('-', 70) . "\n";
        $text .= $this->t('EMAIL_ORDER_DETAILS') . "\n";
        $text .= str_repeat('-', 70) . "\n";
        $text .= $this->t('ORDER_ID') . ": #" . $orderId . "\n";
        $text .= $this->t('EMAIL_ORDER_DATE') . ": " . $orderDate . "\n\n";

        // Customer Information Section
        $text .= str_repeat('-', 70) . "\n";
        $text .= $this->t('EMAIL_CUSTOMER_INFO') . "\n";
        $text .= str_repeat('-', 70) . "\n";
        if (!empty($this->customerName)) {
            $text .= $this->t('NAME') . ": " . $this->customerName . "\n";
        }
        if (!empty($this->customerEmail)) {
            $text .= $this->t('EMAIL') . ": " . $this->customerEmail . "\n";
        }
        $text .= "\n";

        $text .= $this->getAnswersText();

        // Items Section
        $text .= str_repeat('-', 70) . "\n";
        $text .= $this->t('EMAIL_ITEMS') . "\n";
        $text .= str_repeat('-', 70) . "\n";

        // Column headers
        $text .= sprintf("%-35s %12s %8s %12s\n",
            $this->t('NAME'),
            $this->t('EMAIL_UNIT_PRICE'),
            $this->t('EMAIL_QUANTITY'),
            $this->t('EMAIL_SUBTOTAL')
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
            $this->t('TOTAL') . ":",
            "",
            "",
            $this->formatCurrency($totalAmount)
        );
        $text .= str_repeat('=', 70) . "\n\n";

        // Extra sections supplied by add-ons
        foreach ($this->getExtensionTexts() as $extensionText) {
            $text .= $extensionText . "\n\n";
        }

        // Payment Information Section
        if (!empty($this->sepaConfig['beneficiary_iban'])) {
            $text .= str_repeat('-', 70) . "\n";
            $text .= $this->t('PAYMENT_INFO') . "\n";
            $text .= str_repeat('-', 70) . "\n";
            $text .= $this->t('BENEFICIARY') . ": " . $this->sepaConfig['beneficiary_name'] . "\n";
            $text .= $this->t('IBAN') . ": " . $this->sepaConfig['beneficiary_iban'] . "\n";
            if (!empty($this->sepaConfig['beneficiary_bic'])) {
                $text .= "BIC: " . $this->sepaConfig['beneficiary_bic'] . "\n";
            }
            $text .= $this->t('AMOUNT') . ": " . $this->formatCurrency($totalAmount) . "\n\n";
            $text .= $this->t('MAIL_PAYMENTINFO') . "\n\n";
            $text .= str_repeat('-', 70) . "\n\n";
        }

        // Footer
        $text .= $this->t('EMAIL_FOOTER') . "\n\n";
        $text .= str_repeat('=', 70) . "\n";

        return $text;
    }

    /**
     * Plain text blocks that add-ons want in the mail
     *
     * @return array<int, string>
     */
    private function getExtensionTexts() {
        $event = SimplecartExtension::dispatch(SimplecartExtension::EVENT_RENDER, array(
            'order' => $this->order,
            'context' => 'mail',
            'language' => $this->language,
        ));

        $texts = array();
        foreach ($event->getResults() as $result) {
            $text = trim((string)($result['text'] ?? ''));
            if ($text !== '') {
                $texts[] = $text;
            }
        }

        return $texts;
    }

    private function formatCurrency($amount) {
        return number_format((float)$amount, 2, '.', ',') . ' ' . $this->currency;
    }
}
?>

