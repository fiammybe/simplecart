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
        $this->setControl('image', 'imagemanager');
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

    /**
     * @return array{
     *     product_id: int,
     *     name: string,
     *     price: float,
     *     description: string,
     *     image_url: string,
     *     price_formatted: string
     * }
     */
    public function toListRow(): array
    {
        $price = (float)$this->getVar('price');

        return [
            'product_id' => (int)$this->getVar('product_id'),
            'name' => (string)$this->getVar('name'),
            'price' => $price,
            'description' => (string)$this->getVar('description'),
            'image_url' => $this->getImageUrl(),
            'price_formatted' => simplecart_formatMoney($price),
        ];
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

    public function getActiveProduct(int $productId): ?SimplecartProduct
    {
        if ($productId <= 0) {
            return null;
        }

        $product = $this->get($productId);

        if (! $product || $product->isNew()) {
            return null;
        }

        if ((int)$product->getVar('active') !== 1) {
            return null;
        }

        return $product;
    }

    /** @return array<int, SimplecartProduct> */
    public function getProducts(bool $onlyActive = true): array
    {
        $criteria = new icms_db_criteria_Compo();

        if ($onlyActive) {
            $criteria->add(new icms_db_criteria_Item('active', 1));
        }

        $criteria->setSort('name');
        $criteria->setOrder('ASC');

        return $this->getObjects($criteria, false, true);
    }
}
?>
