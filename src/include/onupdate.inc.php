<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

simplecart_prepareLegacyOrderTable();

/**
 * The IPF upgrade drops every column that no object declares, so the legacy customer_info
 * data has to be copied to the customer columns before that upgrade runs
 */
function simplecart_prepareLegacyOrderTable(): void
{
    $db = icms::$xoopsDB;
    $table = $db->prefix('simplecart_order');

    if (!simplecart_tableExists($table)) {
        return;
    }

    $customerColumns = [
        'customer_name' => "varchar(100) NOT NULL DEFAULT ''",
        'customer_email' => "varchar(255) NOT NULL DEFAULT ''",
        'customer_phone' => "varchar(50) NOT NULL DEFAULT ''",
        'customer_address' => 'text',
    ];

    simplecart_addMissingColumns($table, $customerColumns);

    if (simplecart_columnExists($table, 'customer_info') && ! simplecart_migrateCustomerInfo($table)) {
        throw new RuntimeException("Could not migrate customer_info in {$table}; the update was stopped before the column could be dropped");
    }

    simplecart_replaceNullsWithEmptyStrings($table, ['customer_address', 'payment_ref', 'helpende_hand']);
    simplecart_replaceNullsWithEmptyStrings($db->prefix('simplecart_product'), ['description']);
}

/**
 * The IPF upgrade makes every column NOT NULL, which fails while a row still holds NULL
 *
 * @param array<int, string> $columns
 */
function simplecart_replaceNullsWithEmptyStrings(string $table, array $columns): void
{
    $db = icms::$xoopsDB;

    foreach ($columns as $column) {
        if (! simplecart_columnExists($table, $column)) {
            continue;
        }

        $db->queryF("UPDATE `{$table}` SET `{$column}` = '' WHERE `{$column}` IS NULL");
    }
}

function simplecart_tableExists(string $table): bool
{
    $db = icms::$xoopsDB;
    $result = $db->queryF("SHOW TABLES LIKE '{$table}'");

    return $result && $db->getRowsNum($result) > 0;
}

/**
 * @param array<string, string> $columns
 */
function simplecart_addMissingColumns(string $table, array $columns): void
{
    $db = icms::$xoopsDB;

    foreach ($columns as $column => $definition) {
        if (simplecart_columnExists($table, $column)) {
            continue;
        }

        if (! $db->queryF("ALTER TABLE `{$table}` ADD `{$column}` {$definition}")) {
            throw new RuntimeException("Could not add column {$column} to {$table}");
        }
    }
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
