<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

class SimplecartOrderitem extends icms_ipf_Object {
    public function __construct(&$handler) {
        parent::__construct($handler);
        $this->initVar('orderitem_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('order_id', XOBJ_DTYPE_INT, 0, true, null, '', false, _MI_SIMPLECART_ORDERITEM_ORDER_ID);
        $this->initVar('product_name', XOBJ_DTYPE_TXTBOX, '', true, 255, '', false, _MI_SIMPLECART_ORDERITEM_PRODUCT_NAME);
        $this->initVar('product_price', XOBJ_DTYPE_FLOAT, 0.00, true, null, '', false, _MI_SIMPLECART_ORDERITEM_PRODUCT_PRICE);
        $this->initVar('quantity', XOBJ_DTYPE_INT, 1, true, null, '', false, _MI_SIMPLECART_ORDERITEM_QUANTITY);
        $this->initVar('subtotal', XOBJ_DTYPE_FLOAT, 0.00, false, null, '', false, _MI_SIMPLECART_ORDERITEM_SUBTOTAL);

        $this->hideFieldFromForm('orderitem_id');
        $this->setControl('quantity', 'text');
    }
}

class SimplecartOrderitemHandler extends icms_ipf_Handler {
    public function __construct(&$db) {
        parent::__construct($db, 'orderitem', 'orderitem_id', 'product_name', 'product_name', 'simplecart');
    }

    public function beforeInsert(&$obj) {
        $qty = max(1, (int)$obj->getVar('quantity'));
        $price = (float)$obj->getVar('product_price');
        if ($price < 0) { $price = 0.0; }
        $obj->setVar('quantity', $qty);
        $obj->setVar('product_price', $price);
        $obj->setVar('subtotal', $qty * $price);
        return true;
    }

    public function beforeUpdate(&$obj) {
        return $this->beforeInsert($obj);
    }
}
?>
