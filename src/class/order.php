<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartLanguage.php';
require_once __DIR__ . '/SimplecartAnswerValidator.php';

class SimplecartOrder extends icms_ipf_Object {
    public function __construct(&$handler) {
        parent::__construct($handler);
        $this->initVar('order_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('timestamp', XOBJ_DTYPE_LTIME, 0, false, null, '', false, _MI_SIMPLECART_ORDER_TIMESTAMP, '', false, true, false);
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
        $this->initVar('customer_language', XOBJ_DTYPE_TXTBOX, '', false, 50, '', false, _MI_SIMPLECART_ORDER_CUSTOMER_LANGUAGE);

        $this->setControl('status', array('name' => 'select', 'itemHandler' => 'order', 'method' => 'getStatusArray', 'module' => 'simplecart'));

        $this->hideFieldFromForm('order_id');
        $this->hideFieldFromForm('timestamp');
        $this->hideFieldFromForm('total_amount');
        $this->hideFieldFromForm('scan_token');
        $this->hideFieldFromForm('scanned_at');
        $this->hideFieldFromForm('scanned_by');
        $this->hideFieldFromForm('customer_language');
        $this->hideFieldFromForm('helpende_hand');
        $this->hideFieldFromSingleView('helpende_hand');
        $this->hideFieldFromForm('customer_phone');
        $this->hideFieldFromForm('customer_address');
        $this->hideFieldFromSingleView('customer_phone');
        $this->hideFieldFromSingleView('customer_address');
        $this->hideFieldFromSingleView('scan_token');
        $this->hideFieldFromSingleView('scanned_at');
        $this->hideFieldFromSingleView('scanned_by');

        $this->handler->identifierName = 'order_id';
        // The IPF view link adds the admin/ folder itself
        $this->handler->_page = 'order.php';
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

    /** What the customer puts on the bank transfer; orders from before it was stored get the same text */
    public function getPaymentReference(): string
    {
        $reference = trim((string)$this->getVar('payment_ref', 'n'));

        if ($reference !== '') {
            return $reference;
        }

        $orderId = (int)$this->getVar('order_id');

        if ($orderId <= 0) {
            return '';
        }

        return "Bestelling {$orderId}";
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

    /** The language of the customer when the order was placed; an order from before that was recorded follows the active language */
    public function getLanguage(): string
    {
        return SimplecartLanguage::normalize((string)$this->getVar('customer_language', 'n'));
    }

    /** @return array<int, SimplecartOrderanswer> */
    public function getAnswers(): array
    {
        $orderId = (int)$this->getVar('order_id');

        if ($orderId <= 0) {
            return [];
        }

        return simplecart_getHandler('orderanswer')->getByOrder($orderId);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getAnswerRows(string $language = ''): array
    {
        return array_map(fn (SimplecartOrderanswer $answer): array => $answer->toRow($language), $this->getAnswers());
    }

    /**
     * The rows escaped for an HTML page
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function getAnswerHtmlRows(string $language = ''): array
    {
        return array_map(fn (SimplecartOrderanswer $answer): array => $answer->toHtmlRow($language), $this->getAnswers());
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
            $qrData = $generator->generateQrData($orderId, $amount, $this->getPaymentReference());
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

    /** @var array<int, string|null> status as stored before an update, to detect a cancellation */
    private $previousStatus = array();

    /** @var bool|null */
    private $transactional = null;

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
        $orderId = (int)$obj->getVar('order_id');
        if ($orderId > 0 && SimplecartExtension::hasListeners(SimplecartExtension::EVENT_CANCELLED)) {
            $this->previousStatus[$orderId] = $this->getStoredStatus($orderId);
        }
        return true;
    }

    public function afterUpdate(&$obj) {
        $orderId = (int)$obj->getVar('order_id');
        $previous = $this->previousStatus[$orderId] ?? null;
        unset($this->previousStatus[$orderId]);

        if ($previous !== null && $previous !== 'cancelled' && (string)$obj->getVar('status', 'n') === 'cancelled') {
            SimplecartExtension::dispatch(SimplecartExtension::EVENT_CANCELLED, array('order' => $obj, 'reason' => 'status'));
        }

        return true;
    }

    public function beforeDelete(&$obj) {
        // Let add-ons release what they hold for this order while it still exists
        if ((int)$obj->getVar('order_id') > 0 && (string)$obj->getVar('status', 'n') !== 'cancelled') {
            SimplecartExtension::dispatch(SimplecartExtension::EVENT_CANCELLED, array('order' => $obj, 'reason' => 'deleted'));
        }

        // Delete all order items and answers associated with this order
        $orderId = (int)$obj->getVar('order_id');
        if ($orderId > 0) {
            simplecart_getHandler('orderitem')->deleteByOrder($orderId);
            simplecart_getHandler('orderanswer')->deleteByOrder($orderId);
        }
        return true;
    }

    private function getStoredStatus(int $orderId): ?string
    {
        $table = $this->db->prefix('simplecart_order');
        $result = $this->db->queryF("SELECT `status` FROM `{$table}` WHERE `order_id` = {$orderId}");

        if (! $result) {
            return null;
        }

        $row = $this->db->fetchArray($result);

        return $row ? (string)$row['status'] : null;
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<int|string, array<string, mixed>> $items
     * @return array{order_id: int, total: float}
     */
    public function placeOrder(array $customer, array $items): array
    {
        $transactionStarted = false;

        try {
            $extensionLines = $this->collectExtensionLines($customer, $items);

            if (empty($items) && empty($extensionLines)) {
                throw new Exception(_MD_SIMPLECART_EMPTY_CART);
            }

            $this->validateCustomer($customer);

            $language = SimplecartLanguage::normalize((string)($customer['language'] ?? ''));
            $answers = $this->validateAnswers($customer, $items, $language);

            SimplecartExtension::dispatch(SimplecartExtension::EVENT_BEFORE_PLACE, [
                'customer' => $customer,
                'items' => $items,
                'lines' => $extensionLines,
            ]);

            // Add-ons and answers need the order and their own changes to succeed or fail together
            $answersNeedTransaction = ! empty($answers) && $this->supportsTransactions();

            if (! empty($extensionLines) || $answersNeedTransaction || SimplecartExtension::hasListeners(SimplecartExtension::EVENT_AFTER_PLACE)) {
                $this->beginTransaction();
                $transactionStarted = true;
            }

            $productHandler = simplecart_getHandler('product');
            $orderItemHandler = simplecart_getHandler('orderitem');

            $order = $this->create();
            $order->setVar('status', 'pending');
            $order->setVar('timestamp', time());
            $order->setVar('customer_language', $language);

            foreach (['name', 'email'] as $field) {
                $value = (string)($customer[$field] ?? '');
                $order->setVar("customer_{$field}", trim(preg_replace('/\s*[\r\n]+\s*/', ' ', $value)));
            }

            $order->setVar('total_amount', 0.0);

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

            $placedLines = [];

            foreach ($extensionLines as $line) {
                $orderItem = $orderItemHandler->create();
                $orderItem->setVar('order_id', $orderId);
                $orderItem->setVar('product_name', $line['name']);
                $orderItem->setVar('product_price', $line['price']);
                $orderItem->setVar('quantity', $line['quantity']);

                if (! $orderItemHandler->insert($orderItem, true)) {
                    throw new Exception(_MD_SIMPLECART_ORDERITEM_CREATE_FAIL);
                }

                $total += $line['quantity'] * $line['price'];
                $validItemCount++;

                $placedLines[] = [
                    'orderitem_id' => (int)$orderItem->getVar('orderitem_id'),
                    'name' => $line['name'],
                    'price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'meta' => $line['meta'],
                    'source' => $line['source'],
                ];
            }

            if ($validItemCount === 0) {
                throw new Exception('No valid items in cart. Order cannot be placed.');
            }

            if ($total <= 0) {
                throw new Exception('Order total must be greater than zero');
            }

            $order->setVar('total_amount', $total);
            $order->setVar('payment_ref', "Bestelling {$orderId}");

            $this->storeAnswers($orderId, $answers, $transactionStarted);

            if (! $this->insert($order, true) && $transactionStarted) {
                throw new Exception(_MD_SIMPLECART_ORDER_CREATE_FAIL);
            }

            if ($transactionStarted) {
                SimplecartExtension::dispatch(SimplecartExtension::EVENT_AFTER_PLACE, [
                    'order' => $order,
                    'lines' => $placedLines,
                    'customer' => $customer,
                ]);

                $this->commitTransaction();
                $transactionStarted = false;
            }

            $emailResult = $order->sendConfirmationEmail();
            simplecart_debugLog("Checkout submission email result for order {$orderId}: " . ($emailResult ? 'TRUE' : 'FALSE'));

            return [
                'order_id' => $orderId,
                'total' => $total,
            ];
        } catch (Exception $e) {
            if ($transactionStarted) {
                $this->rollbackTransaction();
            }

            throw $e;
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->rollbackTransaction();
            }

            throw new RuntimeException("Failed to place order: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * What the customer answered to the questions that apply to the cart
     *
     * @param array<string, mixed> $customer
     * @param array<int|string, array<string, mixed>> $items
     * @return array<int, array{question_id: int, question_label: string, question_format: string, weight: int, answer_value: string}>
     * @throws SimplecartAnswersException when an answer is missing or not valid
     */
    private function validateAnswers(array $customer, array $items, string $language): array
    {
        $questions = simplecart_getHandler('question')->getForCart($items);

        if (empty($questions)) {
            return [];
        }

        $submitted = is_array($customer['answers'] ?? null) ? $customer['answers'] : [];
        $result = (new SimplecartAnswerValidator($language))->validate($questions, $submitted);

        if (! $result->isValid()) {
            throw new SimplecartAnswersException($result);
        }

        return array_values($result->answers);
    }

    /**
     * Inside a transaction a failure is rolled back by the caller; without one the new order is removed again
     *
     * @param array<int, array{question_id: int, question_label: string, question_format: string, weight: int, answer_value: string}> $answers
     */
    private function storeAnswers(int $orderId, array $answers, bool $inTransaction): void
    {
        if (empty($answers)) {
            return;
        }

        $answerHandler = simplecart_getHandler('orderanswer');

        foreach ($answers as $answer) {
            if ($answerHandler->store($orderId, $answer)) {
                continue;
            }

            if (! $inTransaction) {
                $this->discardOrder($orderId);
            }

            throw new Exception(_MD_SIMPLECART_ANSWER_SAVE_FAIL);
        }
    }

    /** Removes an order that was just created, without telling add-ons about a cancellation */
    private function discardOrder(int $orderId): void
    {
        foreach (['simplecart_orderanswer', 'simplecart_orderitem', 'simplecart_order'] as $name) {
            $table = $this->db->prefix($name);
            $this->db->queryF("DELETE FROM `{$table}` WHERE `order_id` = {$orderId}");
        }
    }

    /**
     * Asks add-ons for order lines; refuses invalid lines and add-on lines without transaction support
     *
     * @param array<string, mixed> $customer
     * @param array<int|string, array<string, mixed>> $items
     * @return array<int, array{name: string, price: float, quantity: int, meta: mixed, source: string, html: string}>
     */
    private function collectExtensionLines(array $customer, array $items): array
    {
        $event = SimplecartExtension::dispatch(SimplecartExtension::EVENT_COLLECT_LINES, [
            'customer' => $customer,
            'items' => $items,
        ]);

        $lines = [];

        foreach ($event->getResults() as $result) {
            $line = SimplecartExtension::normalizeLine($result);

            if ($line === null) {
                throw new Exception(_MD_SIMPLECART_EXTENSION_LINE_INVALID);
            }

            $lines[] = $line;
        }

        if (! empty($lines) && ! $this->supportsTransactions()) {
            throw new Exception(_MD_SIMPLECART_EXTENSION_NO_TRANSACTIONS);
        }

        return $lines;
    }

    private function supportsTransactions(): bool
    {
        if ($this->transactional !== null) {
            return $this->transactional;
        }

        $this->transactional = true;

        foreach (['simplecart_order', 'simplecart_orderitem'] as $name) {
            $table = $this->db->prefix($name);
            $result = $this->db->queryF("SHOW TABLE STATUS LIKE '{$table}'");
            $row = $result ? $this->db->fetchArray($result) : false;
            $engine = $row ? (string)($row['Engine'] ?? $row['engine'] ?? '') : '';

            if (strcasecmp($engine, 'InnoDB') !== 0) {
                $this->transactional = false;
            }
        }

        return $this->transactional;
    }

    private function beginTransaction(): void
    {
        if (! $this->db->queryF('START TRANSACTION')) {
            throw new RuntimeException('Could not start a database transaction');
        }
    }

    private function commitTransaction(): void
    {
        if (! $this->db->queryF('COMMIT')) {
            throw new RuntimeException('Could not commit the database transaction');
        }
    }

    private function rollbackTransaction(): void
    {
        $this->db->queryF('ROLLBACK');
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
        ];

        foreach ($maxLengths as $field => $maxLen) {
            if (isset($customer[$field]) && strlen((string)$customer[$field]) > $maxLen) {
                throw new Exception("Field '{$field}' exceeds maximum length of {$maxLen} characters");
            }
        }
    }

    /**
     * Closes a paid order from the scan page; the WHERE clause keeps concurrent scans from both succeeding
     */
    public function closeScannedOrder(int $orderId, int $uid): bool
    {
        $table = $this->db->prefix('simplecart_order');
        $scannedAt = time();

        $result = $this->db->queryF(
            "UPDATE `{$table}` SET `status` = 'closed', `scanned_at` = {$scannedAt}, `scanned_by` = {$uid} WHERE `order_id` = {$orderId} AND `status` = 'paid'"
        );

        if (!$result) {
            return false;
        }

        return (int)$this->db->getAffectedRows() === 1;
    }
}
?>
