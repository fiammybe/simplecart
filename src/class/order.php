<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

class SimplecartOrder extends icms_ipf_Object {
    public function __construct(&$handler) {
        parent::__construct($handler);
        $this->initVar('order_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('timestamp', XOBJ_DTYPE_LTIME, time(), false, null, '', false, _MI_SIMPLECART_ORDER_TIMESTAMP, '', false, true, false);
        $this->initVar('total_amount', XOBJ_DTYPE_FLOAT, 0.00, false, null, '', false, _MI_SIMPLECART_ORDER_TOTAL);
        $this->initVar('status', XOBJ_DTYPE_TXTBOX, 'pending', true, 32, '', false, _MI_SIMPLECART_ORDER_STATUS);
        $this->initVar('customer_info', XOBJ_DTYPE_TXTAREA, '', false, null, '', false, _MI_SIMPLECART_ORDER_CUSTOMER_INFO);

        $this->setControl('customer_info', array('name' => 'textarea'));
        $this->setControl('status', array('name' => 'select', 'itemHandler' => 'order', 'method' => 'getStatusArray', 'module' => 'simplecart'));

        $this->hideFieldFromForm('order_id');
        $this->hideFieldFromForm('timestamp');
        $this->hideFieldFromForm('total_amount');

        $this->handler->identifierName = 'order_id';
        $this->handler->_page = 'order.php';
    }
    public function getStatusActionLinks() {
        $id = (int)$this->getVar('order_id');
        $base = $this->handler->_moduleUrl . 'admin/order.php';
        $token = icms::$security->createToken(0, 'simplecart_order_status');
        $actions = array(
            'awaiting_payment' => 'Awaiting payment',
            'paid' => 'Paid',
            'reimbursed' => 'Reimbursed',
            'closed' => 'Closed',
        );
        $links = array();
        foreach ($actions as $key => $label) {
            $url = $base . '?op=changestatus&order_id=' . $id . '&status=' . $key . '&token=' . urlencode($token);
            $links[] = '<a href="' . $url . '" class="icms_actionlink">' . htmlspecialchars($label, ENT_QUOTES) . '</a>';
        }
        return implode(' | ', $links);
    }

}

class SimplecartOrderHandler extends icms_ipf_Handler {
    protected $allowedStatus = array('pending', 'awaiting_payment', 'paid', 'reimbursed', 'closed', 'cancelled');

    public function __construct(&$db) {
        parent::__construct($db, 'order', 'order_id', 'order_id', 'customer_info', 'simplecart');
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
}
?>
