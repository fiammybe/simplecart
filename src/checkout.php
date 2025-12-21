<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';
$xoopsOption['template_main'] = 'simplecart_checkout.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

// Generate CSRF token server-side
$csrfToken = icms::$security->createToken(0, 'simplecart');

$icmsTpl->assign('csrf_token', $csrfToken);
$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_ajax_url', SIMPLECART_URL . 'ajax.php');

include ICMS_ROOT_PATH . '/footer.php';

