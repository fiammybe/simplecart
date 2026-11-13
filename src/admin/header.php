<?php
include_once __DIR__ . '/../../../include/cp_header.php';
include_once __DIR__ . '/../include/common.php';

if (!icms::$user || !icms::$user->isAdmin()) {
    redirect_header(ICMS_URL, 3, 'Access denied');
    exit;
}

if (!is_object(icms::$module) || icms::$module->getVar('dirname') !== 'simplecart') {
    $module_handler = icms::handler('icms_module');
    $icmsModule = $module_handler->getByDirname('simplecart');
}

icms_loadLanguageFile('simplecart', 'admin');

/*
 * Saving and confirmed deletion change data, so they need the security token that the secure form and the
 * confirmation page carry. Without it a foreign page could submit them on behalf of a logged in admin.
 */
$simplecartOp = isset($_REQUEST['op']) ? preg_replace('/[^a-z_]/', '', (string)$_REQUEST['op']) : '';
$simplecartChangesData = strpos($simplecartOp, 'add') === 0 || ($simplecartOp === 'del' && !empty($_POST['confirm']));
if ($simplecartChangesData && ($_SERVER['REQUEST_METHOD'] !== 'POST' || !icms::$security->check())) {
    redirect_header(basename((string)$_SERVER['SCRIPT_NAME']), 3, _AM_SIMPLECART_CSRF_FAIL);
    exit;
}

/*
 * A confirmed deletion goes back to the page the confirmation was opened from, which is the Referer of the
 * confirmation. Without a Referer the redirect has no target and the browser shows a half built page.
 */
if (!empty($_POST['confirm']) && empty($_POST['redirect_page'])) {
    $_POST['redirect_page'] = basename((string)$_SERVER['SCRIPT_NAME']);
}
