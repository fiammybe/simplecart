<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';

$xoopsOption['template_main'] = 'simplecart_index.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

$cartMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    switch ($action) {
        case 'add_to_cart':
            if ($productId > 0 && simplecart_addProductToCart($productId, $quantity)) {
                $cartMessage = _MD_SIMPLECART_ADD_TO_CART;
            }
            break;
        case 'update_quantity':
            if ($productId > 0) {
                simplecart_updateCartItemQuantity($productId, $quantity);
                $cartMessage = _MD_SIMPLECART_TOTAL;
            }
            break;
        case 'remove_from_cart':
            if ($productId > 0) {
                simplecart_removeFromCart($productId);
                $cartMessage = _MD_SIMPLECART_REMOVE;
            }
            break;
        case 'empty_cart':
            simplecart_emptyCart();
            $cartMessage = _MD_SIMPLECART_EMPTY_CART;
            break;
    }
}

$products = simplecart_getProductList(true);
$cartSummary = simplecart_getCartSummary();
$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_message', $cartMessage);
$icmsTpl->assign('simplecart_products', $products);
$icmsTpl->assign('simplecart_cart_items', $cartSummary['items']);
$icmsTpl->assign('simplecart_cart_total', $cartSummary['total']);
$icmsTpl->assign('simplecart_cart_total_formatted', $cartSummary['total_formatted']);
$icmsTpl->assign('simplecart_cart_count', $cartSummary['count']);

include ICMS_ROOT_PATH . '/footer.php';

