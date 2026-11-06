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
foreach (['XOBJ_DTYPE_INT', 'XOBJ_DTYPE_TXTBOX', 'XOBJ_DTYPE_FLOAT', 'XOBJ_DTYPE_TXTAREA', 'XOBJ_DTYPE_LTIME'] as $i => $c) {
    define($c, $i + 1);
}

require_once ICMS_ROOT_PATH . '/language/english/main.php';
require_once ICMS_ROOT_PATH . '/language/english/modinfo.php';
require_once ICMS_ROOT_PATH . '/language/english/admin.php';

class icms_core_DataFilter
{
    public static function htmlSpecialChars($s) { return htmlspecialchars((string)$s); }
}

class icms_db_criteria_Compo
{
    public function add($item) {}
    public function setSort($s) {}
    public function setOrder($o) {}
}

class icms_db_criteria_Item
{
    public function __construct($a = null, $b = null) {}
}

class icms_ipf_Object
{
    public $handler;
    private $vars = [];
    private $new = true;

    public function __construct(&$handler = null) { $this->handler = $handler; }
    public function initVar($name, $type, $default = null) { $this->vars[$name] = $default; }
    public function setVar($name, $value) { $this->vars[$name] = $value; }
    public function getVar($name, $format = 's') { return $this->vars[$name] ?? null; }
    public function isNew() { return $this->new; }
    public function unsetNew() { $this->new = false; }
    public function getErrors() { return []; }
    public function __call($name, $args) { return null; }
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

    public function getObjects($criteria = null, $idAsKey = false, $asObject = true) { return array_values($this->stored); }

    public function deleteAll($criteria = null) { $this->stored = []; }

    public function delete(&$obj)
    {
        $this->beforeDelete($obj);
        unset($this->stored[(int)$obj->getVar($this->keyName)]);
    }

    public function beforeInsert(&$obj) { return true; }
    public function beforeUpdate(&$obj) { return true; }
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
require_once ICMS_ROOT_PATH . '/class/order.php';

/** Order whose mail is not sent */
class TestOrder extends SimplecartOrder
{
    public function sendConfirmationEmail(): bool { return true; }
}

function simplecart_formatMoney($amount, $currency = 'EUR') { return number_format((float)$amount, 2); }

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
