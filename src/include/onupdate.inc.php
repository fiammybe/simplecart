<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * Brings older installations up to the current product and order table layout
 */
function icms_module_update_simplecart($module): bool
{
    $db = icms::$xoopsDB;

    $productColumns = [
        'image' => "varchar(255) NOT NULL DEFAULT ''",
    ];

    if (!simplecart_addMissingColumns($module, $db->prefix('simplecart_product'), $productColumns)) {
        return false;
    }

    $table = $db->prefix('simplecart_order');

    $orderColumns = [
        'scan_token' => "varchar(64) NOT NULL DEFAULT ''",
        'scanned_at' => "int(10) unsigned NOT NULL DEFAULT '0'",
        'scanned_by' => "int(10) unsigned NOT NULL DEFAULT '0'",
        'customer_name' => "varchar(100) NOT NULL DEFAULT ''",
        'customer_email' => "varchar(255) NOT NULL DEFAULT ''",
        'customer_phone' => "varchar(50) NOT NULL DEFAULT ''",
        'customer_address' => 'text',
    ];

    if (!simplecart_addMissingColumns($module, $table, $orderColumns)) {
        return false;
    }

    if (!simplecart_columnExists($table, 'customer_info')) {
        return true;
    }

    if (!simplecart_migrateCustomerInfo($table)) {
        $module->setErrors("Could not migrate customer_info in {$table}; the column was kept");

        return false;
    }

    if (!$db->queryF("ALTER TABLE `{$table}` DROP COLUMN `customer_info`")) {
        $module->setErrors("Could not drop column customer_info from {$table}");

        return false;
    }

    return true;
}

/**
 * @param array<string, string> $columns
 */
function simplecart_addMissingColumns($module, string $table, array $columns): bool
{
    $db = icms::$xoopsDB;

    foreach ($columns as $column => $definition) {
        if (simplecart_columnExists($table, $column)) {
            continue;
        }

        if (!$db->queryF("ALTER TABLE `{$table}` ADD `{$column}` {$definition}")) {
            $module->setErrors("Could not add column {$column} to {$table}");

            return false;
        }
    }

    return true;
}

function simplecart_columnExists(string $table, string $column): bool
{
    $db = icms::$xoopsDB;
    $result = $db->queryF("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

    if (!$result) {
        return false;
    }

    return $db->getRowsNum($result) > 0;
}

/**
 * Copies the JSON in customer_info into the separate customer columns
 */
function simplecart_migrateCustomerInfo(string $table): bool
{
    $db = icms::$xoopsDB;
    $result = $db->queryF("SELECT `order_id`, `customer_info` FROM `{$table}`");

    if (!$result) {
        return false;
    }

    while ($row = $db->fetchArray($result)) {
        $customer = simplecart_decodeLegacyCustomerInfo((string)$row['customer_info']);
        $orderId = (int)$row['order_id'];
        $name = $db->quoteString(mb_substr($customer['name'], 0, 100));
        $email = $db->quoteString(mb_substr($customer['email'], 0, 255));
        $phone = $db->quoteString(mb_substr($customer['phone'], 0, 50));
        $address = $db->quoteString($customer['address']);

        $updated = $db->queryF(
            "UPDATE `{$table}` SET `customer_name` = {$name}, `customer_email` = {$email}, `customer_phone` = {$phone}, `customer_address` = {$address} WHERE `order_id` = {$orderId}"
        );

        if (!$updated) {
            return false;
        }
    }

    return true;
}

/**
 * @return array{
 *     name: string,
 *     email: string,
 *     phone: string,
 *     address: string
 * }
 */
function simplecart_decodeLegacyCustomerInfo(string $json): array
{
    $customer = json_decode($json, true);

    if (!is_array($customer)) {
        $customer = json_decode(html_entity_decode($json, ENT_QUOTES, 'UTF-8'), true);
    }

    $customer = is_array($customer) ? $customer : [];

    return [
        'name' => trim((string)($customer['name'] ?? '')),
        'email' => trim((string)($customer['email'] ?? '')),
        'phone' => trim((string)($customer['phone'] ?? '')),
        'address' => trim((string)($customer['address'] ?? '')),
    ];
}
