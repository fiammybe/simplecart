<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

class SimplecartProduct extends icms_ipf_Object {
    public function __construct(&$handler) {
        parent::__construct($handler);
        $this->quickInit();
    }

    protected function quickInit() {
        $this->initVar('product_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('name', XOBJ_DTYPE_TXTBOX, '', true, 255, '', false, _MI_SIMPLECART_PRODUCT_NAME);
        $this->initVar('price', XOBJ_DTYPE_FLOAT, 0.00, true, null, '', false, _MI_SIMPLECART_PRODUCT_PRICE);
        $this->initVar('description', XOBJ_DTYPE_TXTAREA, '', false, null, '', false, _MI_SIMPLECART_PRODUCT_DESC);
        $this->initVar('active', XOBJ_DTYPE_INT, 1, false, null, '', false, _MI_SIMPLECART_PRODUCT_ACTIVE);

        $this->setControl('description', array('name' => 'textarea'));
        $this->setControl('active', 'yesno');

        $this->hideFieldFromForm('product_id');
        $this->setControl('price', 'text');

        $this->handler->identifierName = 'name';
        $this->handler->summaryName = 'description';
    }
}

class SimplecartProductHandler extends icms_ipf_Handler {
    public function __construct(&$db) {
        parent::__construct($db, 'product', 'product_id', 'name', 'description', 'simplecart');
    }

    public function beforeInsert(&$obj) {
        // Normalize price and basic validation
        $price = (float)$obj->getVar('price');
        if ($price < 0) {
            $price = 0.0;
        }
        $obj->setVar('price', $price);
        $active = (int)$obj->getVar('active');
        $obj->setVar('active', $active ? 1 : 0);
        return true;
    }

    public function beforeUpdate(&$obj) {
        return $this->beforeInsert($obj);
    }
}
?>
