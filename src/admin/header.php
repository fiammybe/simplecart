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
 * A confirmed deletion goes back to the page the confirmation was opened from, which is the Referer of the
 * confirmation. Without a Referer the redirect has no target and the browser shows a half built page.
 */
if (!empty($_POST['confirm']) && empty($_POST['redirect_page'])) {
    $_POST['redirect_page'] = basename((string)$_SERVER['SCRIPT_NAME']);
}
