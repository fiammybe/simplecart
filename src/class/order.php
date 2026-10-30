<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

class SimplecartOrder extends icms_ipf_Object {
    public function __construct(&$handler) {
        parent::__construct($handler);
        $this->initVar('order_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('timestamp', XOBJ_DTYPE_LTIME, time(), false, null, '', false, _MI_SIMPLECART_ORDER_TIMESTAMP, '', false, true, false);
        $this->initVar('total_amount', XOBJ_DTYPE_FLOAT, 0.00, false, null, '', false, _MI_SIMPLECART_ORDER_TOTAL);
        $this->initVar('status', XOBJ_DTYPE_TXTBOX, 'pending', true, 32, '', false, _MI_SIMPLECART_ORDER_STATUS);
        $this->initVar('customer_name', XOBJ_DTYPE_TXTBOX, '', false, 100, '', false, _MI_SIMPLECART_ORDER_CUSTOMER_NAME);
        $this->initVar('customer_email', XOBJ_DTYPE_TXTBOX, '', false, 255, '', false, _MI_SIMPLECART_ORDER_CUSTOMER_EMAIL);
        $this->initVar('customer_phone', XOBJ_DTYPE_TXTBOX, '', false, 50, '', false, _MI_SIMPLECART_ORDER_CUSTOMER_PHONE);
        $this->initVar('customer_address', XOBJ_DTYPE_TXTAREA, '', false, null, '', false, _MI_SIMPLECART_ORDER_CUSTOMER_ADDRESS);
        $this->initVar('payment_ref', XOBJ_DTYPE_TXTAREA, '', false, null, '', false, _MI_SIMPLECART_ORDER_PAYMENT_REF);
        $this->initVar('helpende_hand', XOBJ_DTYPE_TXTBOX, '', false, 50, '', false, _MI_SIMPLECART_ORDER_HELPENDE_HAND);
        $this->initVar('scan_token', XOBJ_DTYPE_TXTBOX, '', false, 64);
        $this->initVar('scanned_at', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('scanned_by', XOBJ_DTYPE_INT, 0, false);

        $this->setControl('status', array('name' => 'select', 'itemHandler' => 'order', 'method' => 'getStatusArray', 'module' => 'simplecart'));

        $this->hideFieldFromForm('order_id');
        $this->hideFieldFromForm('timestamp');
        $this->hideFieldFromForm('total_amount');
        $this->hideFieldFromForm('scan_token');
        $this->hideFieldFromForm('scanned_at');
        $this->hideFieldFromForm('scanned_by');
        $this->hideFieldFromSingleView('scan_token');
        $this->hideFieldFromSingleView('scanned_at');
        $this->hideFieldFromSingleView('scanned_by');

        $this->handler->identifierName = 'order_id';
        $this->handler->_page = 'admin/order.php';
    }
    public function getStatusActionLinks() {
        $id = (int)$this->getVar('order_id');
        $base = $this->handler->_moduleUrl . 'admin/order.php';
        // Security: Create time-limited token (1 hour) for status changes
        $token = icms::$security->createToken(3600, 'simplecart_order_status');
        $actions = array(
            'paid' => 'Betaald',
//            'reimbursed' => 'Terugbetaald',
//            'closed' => 'Afgesloten',
        );
        $links = array();
        foreach ($actions as $key => $label) {
            // Security: Use POST form instead of GET link to prevent CSRF
            $formId = 'status_form_' . $id . '_' . $key;
            $links[] = '<form id="' . $formId . '" method="POST" action="' . $base . '" style="display:inline;">' .
                       '<input type="hidden" name="op" value="changestatus">' .
                       '<input type="hidden" name="order_id" value="' . $id . '">' .
                       '<input type="hidden" name="status" value="' . $key . '">' .
                       '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES) . '">' .
                       '<a href="#" onclick="document.getElementById(\'' . $formId . '\').submit(); return false;" class="icms_actionlink">' . 
                       htmlspecialchars($label, ENT_QUOTES) . '</a>' .
                       '</form>';
        }
        return implode(' | ', $links);
    }

    public function getDeleteLink() {
        $id = (int)$this->getVar('order_id');
        $base = $this->handler->_moduleUrl . 'admin/order.php';
        // Security: Create time-limited token (1 hour) for delete action
        $token = icms::$security->createToken(3600, 'simplecart_order_delete');
        $formId = 'delete_form_' . $id;
        return '<form id="' . $formId . '" method="POST" action="' . $base . '" style="display:inline;">' .
               '<input type="hidden" name="op" value="delete">' .
               '<input type="hidden" name="order_id" value="' . $id . '">' .
               '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES) . '">' .
               '<a href="#" onclick="if(confirm(\'' . addslashes(_AM_SIMPLECART_ORDER_DELETE_CONFIRM) . '\')) { document.getElementById(\'' . $formId . '\').submit(); } return false;" class="icms_actionlink">Delete</a>' .
               '</form>';
    }

    public function getItemsSummary() {
        $orderId = (int)$this->getVar('order_id');
        if ($orderId <= 0) {
            return '';
        }

        $items = $this->getItems();

        if (empty($items)) {
            return 'No items';
        }

        $summary = array();
        foreach ($items as $item) {
            $name = (string)$item->getVar('product_name');
            $qty = (int)$item->getVar('quantity');
            $summary[] = htmlspecialchars($name, ENT_QUOTES) . ' (x' . $qty . ')';
        }

        return implode(', ', $summary);
    }

    /** @return array<int, SimplecartOrderitem> */
    public function getItems(): array
    {
        return simplecart_getHandler('orderitem')->getByOrder((int)$this->getVar('order_id'));
    }

    /**
     * @return array{
     *     items: array<int, array{product_name: string, product_price_fmt: string, quantity: int, subtotal_fmt: string}>,
     *     grand_total_fmt: string
     * }
     */
    public function getItemRows(): array
    {
        $rows = [];
        $grandTotal = 0.0;

        foreach ($this->getItems() as $item) {
            $quantity = (int)$item->getVar('quantity');
            $price = (float)$item->getVar('product_price');
            $subtotal = $quantity * $price;
            $grandTotal += $subtotal;

            $rows[] = [
                'product_name' => (string)$item->getVar('product_name'),
                'product_price_fmt' => number_format($price, 2),
                'quantity' => $quantity,
                'subtotal_fmt' => number_format($subtotal, 2),
            ];
        }

        return [
            'items' => $rows,
            'grand_total_fmt' => number_format($grandTotal, 2),
        ];
    }

    /**
     * @return array{
     *     qr_data: string,
     *     qr_image: string,
     *     beneficiary_name: string,
     *     beneficiary_iban: string,
     *     amount: float,
     *     currency: string
     * }
     */
    public function getPaymentData(): array
    {
        $orderId = (int)$this->getVar('order_id');

        try {
            $config = simplecart_getSepaConfig();
            if (empty($config['beneficiary_iban'])) {
                throw new RuntimeException('SEPA beneficiary IBAN is not configured');
            }

            if (! class_exists('SepaQrCodeGenerator')) {
                require_once SIMPLECART_ROOT_PATH . 'class/SepaQrCodeGenerator.php';
            }

            $generator = new SepaQrCodeGenerator($config);
            $amount = (float)$this->getVar('total_amount');
            $qrData = $generator->generateQrData($orderId, $amount, "Bestelling {$orderId}");
            $qrImage = '';

            if (class_exists('Endroid\\QrCode\\QrCode') && class_exists('Endroid\\QrCode\\Writer\\PngWriter')) {
                $writer = new Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write(new Endroid\QrCode\QrCode($qrData));
                $qrImage = 'data:image/png;base64,' . base64_encode($result->getString());
            }

            return [
                'qr_data' => $qrData,
                'qr_image' => $qrImage,
                'beneficiary_name' => $config['beneficiary_name'],
                'beneficiary_iban' => $config['beneficiary_iban'],
                'amount' => $amount,
                'currency' => strtoupper($config['currency']),
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to generate payment data for order {$orderId}: {$e->getMessage()}", 0, $e);
        }
    }

    public function sendConfirmationEmail(): bool
    {
        $orderId = (int)$this->getVar('order_id');

        try {
            require_once SIMPLECART_ROOT_PATH . 'class/OrderConfirmationEmail.php';
            require_once SIMPLECART_ROOT_PATH . 'class/EmailSender.php';

            $orderItems = $this->getItems();
            if (empty($orderItems)) {
                simplecart_debugLog("ERROR: No order items found for order ID: {$orderId}");

                return false;
            }

            $sepaConfig = simplecart_getSepaConfig();
            $emailTemplate = new OrderConfirmationEmail($this, $orderItems, $sepaConfig, $sepaConfig['currency']);

            $customerEmail = $emailTemplate->getCustomerEmail();
            if (empty($customerEmail)) {
                simplecart_debugLog("ERROR: Customer email is empty for order ID: {$orderId}");

                return false;
            }

            $subject = $emailTemplate->getSubject();
            $textContent = $emailTemplate->getTextContent();
            $result = EmailSender::sendTextEmail($customerEmail, $subject, $textContent);

            $duplicateRecipients = array_filter(
                array_map('trim', simplecart_getDuplicateOrderEmailRecipients()),
                fn ($email) => $email !== '' && strtolower($email) !== strtolower($customerEmail)
            );

            foreach ($duplicateRecipients as $duplicateRecipient) {
                if (! filter_var($duplicateRecipient, FILTER_VALIDATE_EMAIL)) {
                    simplecart_debugLog("Skipping invalid duplicate email recipient for order {$orderId}: {$duplicateRecipient}");

                    continue;
                }

                $duplicateResult = EmailSender::sendTextEmail($duplicateRecipient, $subject, $textContent);
                simplecart_debugLog("Duplicate email result for order {$orderId} to {$duplicateRecipient}: " . ($duplicateResult ? 'TRUE' : 'FALSE'));
            }

            return (bool)$result;
        } catch (Exception $e) {
            simplecart_debugLog("EXCEPTION in sendConfirmationEmail() for order {$orderId}: {$e->getMessage()}");

            return false;
        }
    }

    public function sendPaymentReceivedEmail(): bool
    {
        $orderId = (int)$this->getVar('order_id');

        try {
            require_once SIMPLECART_ROOT_PATH . 'class/PaymentReceivedEmail.php';
            require_once SIMPLECART_ROOT_PATH . 'class/EmailSender.php';
            require_once SIMPLECART_ROOT_PATH . 'class/OrderScanQrCode.php';

            $orderItems = $this->getItems();
            if (empty($orderItems)) {
                return false;
            }

            $sepaConfig = simplecart_getSepaConfig();
            $emailTemplate = new PaymentReceivedEmail($this, $orderItems, $sepaConfig, $sepaConfig['currency']);

            $customerEmail = $emailTemplate->getCustomerEmail();
            if (empty($customerEmail)) {
                return false;
            }

            $subject = $emailTemplate->getSubject();
            $textContent = $emailTemplate->getTextContent();

            try {
                $qrPng = OrderScanQrCode::pngFor($this->getScanUrl());
            } catch (Throwable $e) {
                simplecart_debugLog("Order scan QR generation failed for order {$orderId}, falling back to text email: {$e->getMessage()}");

                return EmailSender::sendTextEmail($customerEmail, $subject, $textContent);
            }

            return EmailSender::sendHtmlEmail(
                $customerEmail,
                $subject,
                $emailTemplate->getHtmlContent(OrderScanQrCode::CONTENT_ID),
                $textContent,
                [OrderScanQrCode::CONTENT_ID => $qrPng]
            );
        } catch (Throwable $e) {
            simplecart_debugLog("EXCEPTION in sendPaymentReceivedEmail() for order {$orderId}: {$e->getMessage()}");

            return false;
        }
    }

    public function ensureScanToken(): string
    {
        $token = (string)$this->getVar('scan_token', 'n');

        if ($token !== '') {
            return $token;
        }

        $token = bin2hex(random_bytes(16));
        $this->setVar('scan_token', $token);

        return $token;
    }

    public function hasValidScanToken(string $token): bool
    {
        $storedToken = (string)$this->getVar('scan_token', 'n');

        if ($storedToken === '') {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    public function getScanUrl(): string
    {
        $orderId = (int)$this->getVar('order_id');
        $token = $this->ensureScanToken();

        return SIMPLECART_URL . "scan.php?order_id={$orderId}&token={$token}";
    }

    public function isScanned(): bool
    {
        return (int)$this->getVar('scanned_at', 'n') > 0;
    }

    public function getScannedAtFormatted(): string
    {
        if (!$this->isScanned()) {
            return '';
        }

        return formatTimestamp((int)$this->getVar('scanned_at', 'n'), 'm');
    }

    public function getScannedByName(): string
    {
        $uid = (int)$this->getVar('scanned_by', 'n');

        if ($uid <= 0) {
            return '';
        }

        return (string)icms_member_user_Object::getUnameFromId($uid);
    }
}

class SimplecartOrderHandler extends icms_ipf_Handler {
    protected $allowedStatus = array('pending', 'awaiting_payment', 'paid', 'reimbursed', 'closed', 'cancelled');

    public function __construct(&$db) {
        parent::__construct($db, 'order', 'order_id', 'order_id', 'customer_name', 'simplecart');
        // Avoid using reserved keyword "order" as SQL alias; use a safe alias instead
        $this->_itemname = 'sorder';
    }

    public function getStatusArray() {
        // Labels fallback to humanized text if constants are not defined
        $label = function($key, $fallback) {
            $const = '._DUMMY_'; // ensure undefined
            return defined($const = $key) ? constant($const) : $fallback;
        };
        return array(
            'pending' => 'Pending',
            'awaiting_payment' => 'Awaiting payment',
            'paid' => 'Paid',
            'reimbursed' => 'Reimbursed',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
        );
    }

    public function beforeInsert(&$obj) {
        $obj->setVar('timestamp', time());
        $status = $obj->getVar('status');
        if (!in_array($status, $this->allowedStatus, true)) {
            $obj->setVar('status', 'pending');
        }
        $total = (float)$obj->getVar('total_amount');
        if ($total < 0) { $obj->setVar('total_amount', 0.0); }
        return true;
    }

    public function beforeUpdate(&$obj) {
        // Prevent editing orders via admin (enforce read-only by ignoring admin saves)
        return true;
    }

    public function beforeDelete(&$obj) {
        // Delete all order items associated with this order
        $orderId = (int)$obj->getVar('order_id');
        if ($orderId > 0) {
            simplecart_getHandler('orderitem')->deleteByOrder($orderId);
        }
        return true;
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<int|string, array<string, mixed>> $items
     * @return array{order_id: int, total: float}
     */
    public function placeOrder(array $customer, array $items): array
    {
        try {
            if (empty($items)) {
                throw new Exception(_MD_SIMPLECART_EMPTY_CART);
            }

            $this->validateCustomer($customer);

            $productHandler = simplecart_getHandler('product');
            $orderItemHandler = simplecart_getHandler('orderitem');

            $order = $this->create();
            $order->setVar('status', 'pending');
            $order->setVar('timestamp', time());

            foreach (['name', 'email', 'phone', 'address'] as $field) {
                $order->setVar("customer_{$field}", trim((string)($customer[$field] ?? '')));
            }

            $order->setVar('total_amount', 0.0);

            if (! empty($customer['helpendehanden'])) {
                $order->setVar('helpende_hand', icms_core_DataFilter::htmlSpecialChars($customer['helpendehanden']));
            }

            if (! $this->insert($order, true)) {
                $errors = $order->getErrors();

                throw new Exception(! empty($errors) ? implode(', ', $errors) : _MD_SIMPLECART_ORDER_CREATE_FAIL);
            }

            $orderId = (int)$order->getVar('order_id');
            $total = 0.0;
            $validItemCount = 0;
            $maxQuantityPerItem = 1000;

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);

                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }

                if ($quantity > $maxQuantityPerItem) {
                    throw new Exception("Quantity for product ID {$productId} exceeds maximum allowed ({$maxQuantityPerItem})");
                }

                $product = $productHandler->getActiveProduct($productId);
                if (! $product) {
                    continue;
                }

                $price = (float)$product->getVar('price');

                $orderItem = $orderItemHandler->create();
                $orderItem->setVar('order_id', $orderId);
                $orderItem->setVar('product_name', (string)$product->getVar('name'));
                $orderItem->setVar('product_price', $price);
                $orderItem->setVar('quantity', $quantity);

                if (! $orderItemHandler->insert($orderItem, true)) {
                    throw new Exception(_MD_SIMPLECART_ORDERITEM_CREATE_FAIL);
                }

                $total += $quantity * $price;
                $validItemCount++;
            }

            if ($validItemCount === 0) {
                throw new Exception('No valid items in cart. Order cannot be placed.');
            }

            if ($total <= 0) {
                throw new Exception('Order total must be greater than zero');
            }

            $order->setVar('total_amount', $total);
            $this->insert($order, true);

            $emailResult = $order->sendConfirmationEmail();
            simplecart_debugLog("Checkout submission email result for order {$orderId}: " . ($emailResult ? 'TRUE' : 'FALSE'));

            return [
                'order_id' => $orderId,
                'total' => $total,
            ];
        } catch (Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to place order: {$e->getMessage()}", 0, $e);
        }
    }

    /** @param array<string, mixed> $customer */
    private function validateCustomer(array $customer): void
    {
        foreach (['name', 'email'] as $field) {
            if (empty($customer[$field])) {
                throw new Exception("Required field missing: {$field}");
            }
        }

        if (! filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }

        $maxLengths = [
            'name' => 100,
            'email' => 255,
            'phone' => 50,
            'address' => 500,
            'helpendehanden' => 50,
        ];

        foreach ($maxLengths as $field => $maxLen) {
            if (isset($customer[$field]) && strlen((string)$customer[$field]) > $maxLen) {
                throw new Exception("Field '{$field}' exceeds maximum length of {$maxLen} characters");
            }
        }
    }

    /**
     * Records the first scan of an order; the WHERE clause keeps concurrent scans from both succeeding
     */
    public function markScanned(int $orderId, int $uid): bool
    {
        $table = $this->db->prefix('simplecart_order');
        $scannedAt = time();

        $result = $this->db->queryF(
            "UPDATE `{$table}` SET `scanned_at` = {$scannedAt}, `scanned_by` = {$uid} WHERE `order_id` = {$orderId} AND `scanned_at` = 0"
        );

        if (!$result) {
            return false;
        }

        return (int)$this->db->getAffectedRows() === 1;
    }
}
?>
