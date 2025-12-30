<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';
$xoopsOption['template_main'] = 'simplecart_index.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

// Fetch products server-side for initial render
$productHandler = simplecart_getHandler('product');
$criteria = new icms_db_criteria_Compo();
$criteria->add(new icms_db_criteria_Item('active', 1));
$criteria->setSort('name');
$criteria->setOrder('ASC');
$productObjects = $productHandler->getObjects($criteria, false, true);

$products = array();
foreach ($productObjects as $p) {
    $price = (float)$p->getVar('price');
    $products[] = array(
        'id' => (int)$p->getVar('product_id'),
        'name' => $p->getVar('name'),
        'price' => $price,
        'price_formatted' => '$' . number_format($price, 2),
        'description' => $p->getVar('description'),
    );
}

$icmsTpl->assign('products', $products);
$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_ajax_url', SIMPLECART_URL . 'ajax.php');
$icmsTpl->assign('simplecart_version', SIMPLECART_VERSION);

include ICMS_ROOT_PATH . '/footer.php';

