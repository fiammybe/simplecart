<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * The tables are created from the IPF objects listed in object_items before this hook runs
 */
function icms_module_install_simplecart($module): bool
{
    return true;
}
