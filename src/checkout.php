<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';
$xoopsOption['template_main'] = 'simplecart_checkout.html';
include ICMS_ROOT_PATH . '/header.php';
$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_ajax_url', SIMPLECART_URL . 'ajax.php');
// Get SEPA currency configuration
$sepaConfig = simplecart_getSepaConfig();
$currency = !empty($sepaConfig['currency']) ? $sepaConfig['currency'] : 'EUR';
$icmsTpl->assign('simplecart_currency', $currency);
error_log('Checkout: simplecart_currency assigned = ' . $currency);
include ICMS_ROOT_PATH . '/footer.php';

