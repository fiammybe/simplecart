<?php
include_once __DIR__ . '/header.php';
icms_cp_header();
echo '<h1>' . _MI_SIMPLECART_NAME . '</h1>';
echo '<ul>';
echo '<li><a href="product.php">' . _MI_SIMPLECART_MENU_PRODUCTS . '</a></li>';
echo '<li><a href="order.php">' . _MI_SIMPLECART_MENU_ORDERS . '</a></li>';
echo '</ul>';
icms_cp_footer();
?>
