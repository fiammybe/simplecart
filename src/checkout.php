<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';
$xoopsOption['template_main'] = 'simplecart_checkout.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

// Generate CSRF token server-side
$csrfToken = icms::$security->createToken(0, 'simplecart');

// Get order handler and create a temporary order object to get field definitions
$orderHandler = simplecart_getHandler('order');
$orderObj = $orderHandler->create();
$checkoutFields = $orderObj->getCheckoutFields();

// Convert fields to JSON-friendly format for template
$fieldsForTemplate = array();
foreach ($checkoutFields as $fieldName => $fieldDef) {
    $field = array(
        'name' => $fieldName,
        'label' => $fieldDef['label'],
        'required' => $fieldDef['required'],
        'type' => 'text', // default
    );
    
    // Determine field type from control
    if (isset($fieldDef['control']) && is_array($fieldDef['control'])) {
        if (isset($fieldDef['control'][1]['name']) && $fieldDef['control'][1]['name'] === 'radio') {
            $field['type'] = 'radio';
            $field['options'] = $fieldDef['control'][1]['options'] ?? array();
        }
    } elseif ($fieldDef['type'] === XOBJ_DTYPE_TXTAREA) {
        $field['type'] = 'textarea';
    }
    
    $fieldsForTemplate[] = $field;
}

$icmsTpl->assign('csrf_token', $csrfToken);
$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_ajax_url', SIMPLECART_URL . 'ajax.php');
$icmsTpl->assign('checkout_fields', $fieldsForTemplate);
$icmsTpl->assign('checkout_fields_json', json_encode($fieldsForTemplate));

include ICMS_ROOT_PATH . '/footer.php';

