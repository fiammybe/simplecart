<?php
$adminmenu = array();
$adminmenu[0]['title'] = 'Dashboard';
$adminmenu[0]['link']  = 'admin/index.php';
$adminmenu[1]['title'] = 'Producten';
$adminmenu[1]['link']  = 'admin/product.php';
$adminmenu[2]['title'] = 'Bestellingen';
$adminmenu[2]['link']  = 'admin/order.php';

$headermenu = array();

if (isset(icms::$module)) {
    $headermenu[] = array(
        'title' => _PREFERENCES,
        'link' => ICMS_MODULES_URL . '/system/admin.php?fct=preferences&amp;op=showmod&amp;mod=' . icms::$module->getVar('mid'));
    $headermenu[] = array(
        'title' => _CO_ICMS_GOTOMODULE,
        'link' => ICMS_MODULES_URL . '/' . basename(dirname(__DIR__)));
    $headermenu[] = array(
        'title' => _CO_ICMS_UPDATE_MODULE,
        'link' => ICMS_MODULES_URL . '/system/admin.php?fct=modulesadmin&amp;op=update&amp;module=' . icms::$module->getVar('dirname'));
}
