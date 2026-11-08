<?php
/**
 * Minimal stand-ins for the ImpressCMS/IPF classes the module code relies on, so the
 * extension points can be tested without an ImpressCMS installation: php tests/run.php
 */
error_reporting(E_ALL);
set_error_handler(function ($no, $str, $file, $line) {
    throw new ErrorException($str, 0, $no, $file, $line);
});

define('ICMS_ROOT_PATH', dirname(__DIR__) . '/src');
define('SIMPLECART_ROOT_PATH', ICMS_ROOT_PATH . '/');
define('SIMPLECART_URL', 'https://shop.test/modules/simplecart/');
// Same values as ImpressCMS (libraries/icms/core/Object.php)
foreach (['XOBJ_DTYPE_TXTBOX' => 1, 'XOBJ_DTYPE_TXTAREA' => 2, 'XOBJ_DTYPE_INT' => 3, 'XOBJ_DTYPE_URL' => 4, 'XOBJ_DTYPE_EMAIL' => 5, 'XOBJ_DTYPE_SOURCE' => 8, 'XOBJ_DTYPE_LTIME' => 11, 'XOBJ_DTYPE_FLOAT' => 201] as $c => $v) {
    define($c, $v);
}

define('_XOBJ_ERR_REQUIRED', '%s is required');
define('_XOBJ_ERR_SHORTERTHAN', '%s must be shorter than %d characters');
define('_CORE_DB_INVALIDEMAIL', 'Invalid Email');

// The site settings the multilanguage code reads
$GLOBALS['icmsConfig'] = ['language' => 'english'];
$GLOBALS['icmsConfigMultilang'] = ['ml_enable' => 1, 'ml_tags' => 'en,nl', 'ml_names' => 'english,nederlands'];

require_once ICMS_ROOT_PATH . '/language/english/main.php';
require_once ICMS_ROOT_PATH . '/language/english/modinfo.php';
require_once ICMS_ROOT_PATH . '/language/english/admin.php';

class icms_core_DataFilter
{
    public static function htmlSpecialChars($s) { return htmlspecialchars((string)$s); }
    public static function stripSlashesGPC($s) { return $s; }
    public static function censorString($s) { return $s; }

    /** Stand-in: ImpressCMS checks e-mail addresses with its own pattern, html with HTMLPurifier */
    public static function checkVar($data, $type, $options1 = '', $options2 = '')
    {
        return $type === 'email' ? filter_var($data, FILTER_VALIDATE_EMAIL) : $data;
    }
}

function icms_currency($value) { return (float)str_replace(',', '.', (string)$value); }

/** Stand-ins that only understand `=` conditions, enough to keep one order's rows apart from another's */
class icms_db_criteria_Compo
{
    private $items = [];

    public function __construct($item = null) { if ($item !== null) { $this->items[] = $item; } }
    public function add($item, $condition = 'AND') { $this->items[] = $item; }
    public function setSort($s) {}
    public function setOrder($o) {}

    public function matches($object): bool
    {
        foreach ($this->items as $item) {
            if (! $item->matches($object)) {
                return false;
            }
        }

        return true;
    }
}

class icms_db_criteria_Item
{
    private $column;
    private $value;
    private $operator;

    public function __construct($column = null, $value = null, $operator = '=')
    {
        $this->column = $column;
        $this->value = $value;
        $this->operator = $operator;
    }

    public function matches($object): bool
    {
        if ($this->operator !== '=') {
            return true;
        }

        return (string)$object->getVar($this->column) === (string)$this->value;
    }
}

/**
 * Stand-in for icms_ipf_Object. cleanVars() mirrors the branches of icms_core_Object::cleanVars()
 * (ImpressCMS 2.0.x) that the module relies on, quirks included: a changed but empty value skips
 * every check, integers and floats are cast without an error, and a URL only gets http:// prepended.
 */
class icms_ipf_Object
{
    public $handler;
    public $cleanVars = [];
    private $vars = [];
    private $new = true;
    private $errors = [];

    public function __construct(&$handler = null) { $this->handler = $handler; }

    public function initVar($name, $type, $default = null, $required = false, $maxlength = null)
    {
        $this->vars[$name] = ['value' => $default, 'data_type' => $type, 'required' => $required, 'maxlength' => $maxlength, 'changed' => false];
    }

    public function setVar($name, $value)
    {
        $this->vars[$name] = ($this->vars[$name] ?? ['data_type' => 0, 'required' => false, 'maxlength' => null]) + [];
        $this->vars[$name]['value'] = $value;
        $this->vars[$name]['changed'] = true;
    }

    public function getVar($name, $format = 's') { return $this->vars[$name]['value'] ?? null; }
    public function isNew() { return $this->new; }
    public function unsetNew() { $this->new = false; }
    public function setErrors($error) { $this->errors[] = $error; }
    public function getErrors() { return $this->errors; }
    public function __call($name, $args) { return null; }

    public function cleanVars()
    {
        $this->errors = [];

        foreach ($this->vars as $key => $var) {
            $clean = $var['value'];

            if ($var['changed'] && (is_int($clean) || !empty($clean))) {
                $clean = is_string($clean) ? trim($clean) : $clean;

                switch ($var['data_type']) {
                    case XOBJ_DTYPE_TXTBOX:
                        if ($var['required'] && $clean != '0' && $clean == '') {
                            $this->setErrors(sprintf(_XOBJ_ERR_REQUIRED, $key));
                            break;
                        }
                        if (isset($var['maxlength']) && strlen($clean) > (int)$var['maxlength']) {
                            $this->setErrors(sprintf(_XOBJ_ERR_SHORTERTHAN, $key, (int)$var['maxlength']));
                        }
                        break;
                    case XOBJ_DTYPE_INT:
                        $clean = (int)$clean;
                        break;
                    case XOBJ_DTYPE_FLOAT:
                        $clean = icms_currency($clean);
                        break;
                    case XOBJ_DTYPE_EMAIL:
                        if ($clean != '' && ! icms_core_DataFilter::checkVar($clean, 'email')) {
                            $this->setErrors(_CORE_DB_INVALIDEMAIL);
                        }
                        break;
                    case XOBJ_DTYPE_URL:
                        if ($clean != '' && ! preg_match('/^http[s]*:\/\//i', $clean)) {
                            $clean = 'http://' . $clean;
                        }
                        break;
                }
            }

            $this->cleanVars[$key] = $clean;
        }

        return count($this->errors) === 0;
    }
}

class icms_ipf_Handler
{
    public $db;
    public $objectClass;
    public $stored = [];
    public $_moduleUrl = '';
    private $itemName;
    private $keyName;
    private $nextId = 1;
    public $insertShouldFail = false;
    public $identifierName;
    public $summaryName;
    public $_page;
    public $_itemname;

    public function __construct(&$db, $itemname, $keyname)
    {
        $this->db = $db;
        $this->itemName = $itemname;
        $this->keyName = $keyname;
    }

    public function create()
    {
        $class = $this->objectClass ?: 'Simplecart' . ucfirst($this->itemName);
        $handler = $this;
        return new $class($handler);
    }

    public function insert(&$obj, $force = false)
    {
        if ($this->insertShouldFail) {
            return false;
        }
        $isNew = $obj->isNew();
        if ($isNew) {
            $this->beforeInsert($obj);
            $obj->setVar($this->keyName, $this->nextId++);
            $obj->unsetNew();
        } else {
            $this->beforeUpdate($obj);
        }
        $this->stored[(int)$obj->getVar($this->keyName)] = $obj;
        if (! $isNew && method_exists($this, 'afterUpdate')) {
            $this->afterUpdate($obj);
        }
        return true;
    }

    public function get($id) { return $this->stored[(int)$id] ?? null; }

    public function getObjects($criteria = null, $idAsKey = false, $asObject = true)
    {
        return array_values(array_filter($this->stored, function ($object) use ($criteria) {
            return ! ($criteria instanceof icms_db_criteria_Compo) || $criteria->matches($object);
        }));
    }

    public function deleteAll($criteria = null)
    {
        foreach ($this->getObjects($criteria) as $object) {
            unset($this->stored[(int)$object->getVar($this->keyName)]);
        }
    }

    public function delete(&$obj)
    {
        $this->beforeDelete($obj);
        unset($this->stored[(int)$obj->getVar($this->keyName)]);
    }

    public function beforeInsert(&$obj) { return true; }
    public function beforeUpdate(&$obj) { return true; }
    public function beforeDelete(&$obj) { return true; }
    public function updateAll($field, $value, $criteria = null, $force = false) {}
}

class FakeDb
{
    public $queries = [];
    public $engine = 'InnoDB';
    public $storedStatus = [];

    public function prefix($name) { return 'x_' . $name; }

    public function queryF($sql)
    {
        $this->queries[] = $sql;
        return $sql;
    }

    public function fetchArray($result)
    {
        if (strpos($result, 'SHOW TABLE STATUS') === 0) {
            return ['Engine' => $this->engine];
        }
        if (preg_match('/SELECT `status` .* `order_id` = (\d+)/', $result, $m)) {
            return isset($this->storedStatus[(int)$m[1]]) ? ['status' => $this->storedStatus[(int)$m[1]]] : false;
        }
        return false;
    }
}

function simplecart_debugLog($message) {}

function simplecart_getHandler($name)
{
    return $GLOBALS['__handlers'][strtolower($name)];
}

require_once ICMS_ROOT_PATH . '/class/Extension.php';
require_once ICMS_ROOT_PATH . '/class/product.php';
require_once ICMS_ROOT_PATH . '/class/orderitem.php';
require_once ICMS_ROOT_PATH . '/class/campaign.php';
require_once ICMS_ROOT_PATH . '/class/question.php';
require_once ICMS_ROOT_PATH . '/class/orderanswer.php';
require_once ICMS_ROOT_PATH . '/class/order.php';
require_once ICMS_ROOT_PATH . '/class/OrderEmailTrait.php';
require_once ICMS_ROOT_PATH . '/class/OrderConfirmationEmail.php';
require_once ICMS_ROOT_PATH . '/class/PaymentReceivedEmail.php';
require_once ICMS_ROOT_PATH . '/class/SimplecartLegacyHelpMigration.php';
require_once ICMS_ROOT_PATH . '/class/SimplecartLegacyContactMigration.php';

/** Order whose mail is not sent */
class TestOrder extends SimplecartOrder
{
    public function sendConfirmationEmail(): bool { return true; }
}

function simplecart_formatMoney($amount, $currency = 'EUR') { return number_format((float)$amount, 2); }

// ---- fixtures shared by the tests
$customer = ['name' => 'Jan', 'email' => 'jan@example.com'];
$cart = [1 => ['product_id' => 1, 'quantity' => 2]];

// ---- tiny test framework
$GLOBALS['__tests'] = [];
$GLOBALS['__failures'] = 0;

function test(string $name, callable $fn): void { $GLOBALS['__tests'][$name] = $fn; }

function assertSame($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new Exception(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function assertTrue($condition, string $message = ''): void { assertSame(true, (bool)$condition, $message); }

function assertThrows(callable $fn, string $messageContains = ''): Throwable
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($messageContains !== '' && strpos($e->getMessage(), $messageContains) === false) {
            throw new Exception("Exception message '{$e->getMessage()}' does not contain '{$messageContains}'");
        }
        return $e;
    }
    throw new Exception('Expected an exception');
}
