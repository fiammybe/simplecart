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
        $this->initVar('image', XOBJ_DTYPE_TXTBOX, '', false, 255, '', false, _MI_SIMPLECART_PRODUCT_IMAGE);
        $this->initVar('active', XOBJ_DTYPE_INT, 1, false, null, '', false, _MI_SIMPLECART_PRODUCT_ACTIVE);

        $this->setControl('description', array('name' => 'textarea'));
        $this->setControl('image', [
            'name' => 'select',
            'itemHandler' => 'product',
            'method' => 'getImageManagerOptions',
            'module' => 'simplecart',
        ]);
        $this->setControl('active', 'yesno');

        $this->hideFieldFromForm('product_id');
        $this->setControl('price', 'text');

        $this->handler->identifierName = 'name';
        $this->handler->summaryName = 'description';
    }

    public function getImageUrl(): string
    {
        $image = (string)$this->getVar('image', 'e');

        if ($image === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        return ICMS_URL . $image;
    }

    public function getImageThumbnail(): string
    {
        $url = $this->getImageUrl();

        if ($url === '') {
            return '';
        }

        $src = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars((string)$this->getVar('name', 'e'), ENT_QUOTES, 'UTF-8');

        return "<img src=\"{$src}\" alt=\"{$alt}\" style=\"max-width: 60px; max-height: 60px; object-fit: cover;\">";
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

    /** @return array<string, string> */
    public function getImageManagerOptions(): array
    {
        $options = ['' => '---'];
        $imageList = (new icms_form_elements_select_Image('', 'image'))->getImageList();

        foreach ($imageList as $category => $images) {
            if (!is_array($images)) {
                continue;
            }

            foreach ($images as $path => $name) {
                $options[$path] = "{$category} – {$name}";
            }
        }

        return $options;
    }
}
?>
