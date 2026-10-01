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
        $this->initVar('access_code', XOBJ_DTYPE_TXTBOX, '', false, 16, '', false, _MI_SIMPLECART_ORDER_ACCESS_CODE);

        $this->setControl('status', array('name' => 'select', 'itemHandler' => 'order', 'method' => 'getStatusArray', 'module' => 'simplecart'));

        $this->hideFieldFromForm('order_id');
        $this->hideFieldFromForm('timestamp');
        $this->hideFieldFromForm('total_amount');
        $this->hideFieldFromForm('scan_token');
        $this->hideFieldFromForm('scanned_at');
        $this->hideFieldFromForm('scanned_by');
        $this->hideFieldFromForm('access_code');
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
            'paid' => _MI_SIMPLECART_STATUS_PAID,
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

        $orderItemHandler = simplecart_getHandler('orderitem');
        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('order_id', $orderId));
        $criteria->setSort('orderitem_id');
        $criteria->setOrder('ASC');
        $items = $orderItemHandler->getObjects($criteria, false, true);

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

    public function ensureAccessCode(): string
    {
        $code = (string)$this->getVar('access_code', 'n');

        if ($code !== '') {
            return $code;
        }

        $code = SimplecartOrderHandler::generateAccessCode();
        $this->setVar('access_code', $code);

        return $code;
    }

    public function hasValidAccessCode(string $code): bool
    {
        $storedCode = (string)$this->getVar('access_code', 'n');

        if ($storedCode === '') {
            return false;
        }

        return hash_equals($storedCode, SimplecartOrderHandler::normalizeAccessCode($code));
    }

    public function getFormattedAccessCode(): string
    {
        return SimplecartOrderHandler::formatAccessCode($this->ensureAccessCode());
    }

    public function getAccessUrl(): string
    {
        return SIMPLECART_URL . 'orders.php';
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

    /** Unambiguous characters only, so codes survive being read aloud or retyped from a mail */
    private const ACCESS_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const ACCESS_CODE_LENGTH = 10;

    public function __construct(&$db) {
        parent::__construct($db, 'order', 'order_id', 'order_id', 'customer_name', 'simplecart');
        // Avoid using reserved keyword "order" as SQL alias; use a safe alias instead
        $this->_itemname = 'sorder';
    }

    /** @return array<string, string> */
    public function getStatusArray(): array
    {
        return [
            'pending' => _MI_SIMPLECART_STATUS_PENDING,
            'awaiting_payment' => _MI_SIMPLECART_STATUS_AWAITING_PAYMENT,
            'paid' => _MI_SIMPLECART_STATUS_PAID,
            'reimbursed' => _MI_SIMPLECART_STATUS_REIMBURSED,
            'closed' => _MI_SIMPLECART_STATUS_CLOSED,
            'cancelled' => _MI_SIMPLECART_STATUS_CANCELLED,
        ];
    }

    public function beforeInsert(&$obj) {
        $obj->setVar('timestamp', time());
        $status = $obj->getVar('status');
        if (!in_array($status, $this->allowedStatus, true)) {
            $obj->setVar('status', 'pending');
        }
        $total = (float)$obj->getVar('total_amount');
        if ($total < 0) { $obj->setVar('total_amount', 0.0); }
        $obj->ensureAccessCode();
        return true;
    }

    public function beforeUpdate(&$obj) {
        $obj->ensureAccessCode();
        // Prevent editing orders via admin (enforce read-only by ignoring admin saves)
        return true;
    }

    public function beforeDelete(&$obj) {
        // Delete all order items associated with this order
        $orderId = (int)$obj->getVar('order_id');
        if ($orderId > 0) {
            $orderItemHandler = simplecart_getHandler('orderitem');
            $criteria = new icms_db_criteria_Compo();
            $criteria->add(new icms_db_criteria_Item('order_id', $orderId));
            $orderItemHandler->deleteAll($criteria);
        }
        return true;
    }

    public static function generateAccessCode(): string
    {
        $alphabetLength = strlen(self::ACCESS_CODE_ALPHABET);
        $code = '';

        for ($i = 0; $i < self::ACCESS_CODE_LENGTH; $i++) {
            $code .= self::ACCESS_CODE_ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $code;
    }

    public static function normalizeAccessCode(string $code): string
    {
        return (string)preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
    }

    public static function formatAccessCode(string $code): string
    {
        return implode('-', str_split($code, 5));
    }

    /**
     * Orders placed with this e-mail address, newest first
     *
     * @return array<int, SimplecartOrder>
     */
    public function getOrdersByEmail(string $email): array
    {
        $email = trim($email);

        if ($email === '') {
            return [];
        }

        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('customer_email', $email));
        $criteria->setSort('timestamp');
        $criteria->setOrder('DESC');

        return $this->getObjects($criteria, false, true);
    }

    /**
     * True when one of the orders of this e-mail address carries the given access code
     */
    public function isValidAccessCodeForEmail(string $email, string $code): bool
    {
        if (self::normalizeAccessCode($code) === '') {
            return false;
        }

        foreach ($this->getOrdersByEmail($email) as $order) {
            if ($order->hasValidAccessCode($code)) {
                return true;
            }
        }

        return false;
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
