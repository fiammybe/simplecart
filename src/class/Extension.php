<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * Extension points for simplecart add-ons
 *
 * An add-on is an ImpressCMS module that ships a file `simplecart_extension.php` in its module root.
 * Simplecart includes that file once per request (for active modules only) and the file registers
 * its listeners with SimplecartExtension::listen(). See docs/EXTENSION_POINTS.md for the contract.
 *
 * Without add-ons nothing is registered and every dispatch is a no-op, so the shop behaves as before.
 */

if (!defined('SIMPLECART_EXTENSION_API')) {
    define('SIMPLECART_EXTENSION_API', 1);
}

/**
 * Event handed to listeners: read the payload, add results
 */
class SimplecartExtensionEvent
{
    /** @var string */
    private $name;

    /** @var array<string, mixed> */
    private $payload;

    /** @var array<int, array<string, mixed>> */
    private $results = [];

    /** @param array<string, mixed> $payload */
    public function __construct(string $name, array $payload = [])
    {
        $this->name = $name;
        $this->payload = $payload;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->payload) ? $this->payload[$key] : $default;
    }

    /** @param array<string, mixed> $result */
    public function addResult(array $result): void
    {
        $this->results[] = $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function getResults(): array
    {
        return $this->results;
    }
}

class SimplecartExtension
{
    public const API_VERSION = 1;

    /**
     * Rises with every additive change, so an add-on can check what the core offers:
     * 2 adds `language` to the mail payload of order.render;
     * 3 adds `method`/`action` to scan.resolve, scan actions with a CSRF token, and `allow_close` in order.render (context scan)
     */
    public const API_REVISION = 3;

    public const EVENT_CART_SUMMARY = 'cart.summary';
    public const EVENT_CART_EMPTIED = 'cart.emptied';
    public const EVENT_COLLECT_LINES = 'order.collectLines';
    public const EVENT_BEFORE_PLACE = 'order.beforePlace';
    public const EVENT_AFTER_PLACE = 'order.afterPlace';
    public const EVENT_CANCELLED = 'order.cancelled';
    public const EVENT_RENDER = 'order.render';
    public const EVENT_SCAN_RESOLVE = 'scan.resolve';

    /**
     * Known events. true: an exception from a listener propagates to the caller (the caller aborts);
     * false: the exception is logged and ignored so a broken add-on cannot break the shop.
     *
     * @var array<string, bool>
     */
    private const EVENTS = [
        self::EVENT_CART_SUMMARY => false,
        self::EVENT_CART_EMPTIED => false,
        self::EVENT_COLLECT_LINES => true,
        self::EVENT_BEFORE_PLACE => true,
        self::EVENT_AFTER_PLACE => true,
        self::EVENT_CANCELLED => false,
        self::EVENT_RENDER => false,
        self::EVENT_SCAN_RESOLVE => false,
    ];

    private const MAX_QUANTITY = 1000;
    private const MAX_NAME_LENGTH = 255;

    /** Action names an add-on may use on the scan page */
    private const SCAN_ACTION_PATTERN = '/^[a-z_]{1,32}$/';
    private const SCAN_ACTION_STYLES = ['primary', 'warning'];
    private const SCAN_STATUSES = ['success', 'warning', 'error'];

    /** @var array<string, array<int, callable>> */
    private static $listeners = [];

    /** @var bool */
    private static $booted = false;

    /** @var callable|null */
    private static $fileProvider = null;

    public static function getApiVersion(): int
    {
        return self::API_VERSION;
    }

    /** @return array<int, string> */
    public static function getEventNames(): array
    {
        return array_keys(self::EVENTS);
    }

    /**
     * Registers a listener. Listeners run in registration order and receive a SimplecartExtensionEvent
     */
    public static function listen(string $event, callable $listener): void
    {
        if (! isset(self::EVENTS[$event])) {
            throw new InvalidArgumentException("Unknown simplecart extension event '{$event}'");
        }

        self::$listeners[$event][] = $listener;
    }

    public static function hasListeners(string $event): bool
    {
        self::boot();

        return ! empty(self::$listeners[$event]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function dispatch(string $event, array $payload = []): SimplecartExtensionEvent
    {
        if (! isset(self::EVENTS[$event])) {
            throw new InvalidArgumentException("Unknown simplecart extension event '{$event}'");
        }

        self::boot();

        $extensionEvent = new SimplecartExtensionEvent($event, $payload);

        foreach (self::$listeners[$event] ?? [] as $listener) {
            if (self::EVENTS[$event]) {
                $listener($extensionEvent);
                continue;
            }

            try {
                $listener($extensionEvent);
            } catch (Throwable $e) {
                self::logFailure($event, $e);
            }
        }

        return $extensionEvent;
    }

    /**
     * Loads the extension file of every active add-on, once per request
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        try {
            foreach (self::findExtensionFiles() as $file) {
                try {
                    require_once $file;
                } catch (Throwable $e) {
                    self::logFailure('boot ' . basename(dirname($file)), $e);
                }
            }
        } catch (Throwable $e) {
            self::logFailure('boot', $e);
        }
    }

    /**
     * Forgets listeners and the boot state. For tests
     */
    public static function reset(): void
    {
        self::$listeners = [];
        self::$booted = false;
        self::$fileProvider = null;
    }

    /**
     * Replaces the discovery of extension files. For tests
     */
    public static function setFileProvider(?callable $provider): void
    {
        self::$fileProvider = $provider;
    }

    /**
     * Normalizes a line returned by an add-on, or null when it is unusable
     *
     * @param array<string, mixed> $line
     * @return array{name: string, price: float, quantity: int, meta: mixed, source: string, html: string}|null
     */
    public static function normalizeLine(array $line): ?array
    {
        $name = trim((string)($line['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $price = (float)($line['price'] ?? 0);
        if (! is_finite($price) || $price < 0) {
            return null;
        }

        $quantity = (int)($line['quantity'] ?? 1);
        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            return null;
        }

        if (function_exists('mb_substr')) {
            $name = mb_substr($name, 0, self::MAX_NAME_LENGTH);
        } else {
            $name = substr($name, 0, self::MAX_NAME_LENGTH);
        }

        return [
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'meta' => $line['meta'] ?? null,
            'source' => (string)($line['source'] ?? ''),
            'html' => (string)($line['html'] ?? ''),
        ];
    }

    /**
     * Decides an add-on scan (scan.php with ?addon=...). The checks run before the add-on is called:
     * without admin rights, or on a POST without a valid token or a valid action name, scan.resolve is not dispatched.
     *
     * @param array{params?: array, method?: string, action?: mixed, user?: mixed, is_admin?: bool, token_valid?: bool} $request
     * @return array{error: string|null, scan: array|null}
     *   error: null, 'no_access', 'token_invalid', 'action_invalid' or 'invalid' (no add-on answered)
     *   scan: title, message, status, html and the validated actions of the first result
     */
    public static function resolveScan(array $request): array
    {
        if (empty($request['is_admin'])) {
            return ['error' => 'no_access', 'scan' => null];
        }

        $params = (array)($request['params'] ?? []);
        $method = strtoupper((string)($request['method'] ?? 'GET')) === 'POST' ? 'POST' : 'GET';
        $payload = [
            'addon' => (string)($params['addon'] ?? ''),
            'params' => $params,
            'user' => $request['user'] ?? null,
            'method' => $method,
        ];

        if ($method === 'POST') {
            if (empty($request['token_valid'])) {
                return ['error' => 'token_invalid', 'scan' => null];
            }

            $action = $request['action'] ?? null;
            if (! is_string($action) || ! preg_match(self::SCAN_ACTION_PATTERN, $action)) {
                return ['error' => 'action_invalid', 'scan' => null];
            }
            $payload['action'] = $action;
        }

        $results = self::dispatch(self::EVENT_SCAN_RESOLVE, $payload)->getResults();
        if (empty($results) || ! is_array($results[0])) {
            return ['error' => 'invalid', 'scan' => null];
        }

        $result = $results[0];
        $status = $result['status'] ?? '';

        return [
            'error' => null,
            'scan' => [
                'title' => (string)($result['title'] ?? ''),
                'message' => (string)($result['message'] ?? ''),
                'status' => in_array($status, self::SCAN_STATUSES, true) ? $status : 'warning',
                'html' => (string)($result['html'] ?? ''),
                'actions' => self::normalizeScanActions($result['actions'] ?? []),
            ],
        ];
    }

    /**
     * Keeps the usable actions of a scan.resolve result: a name of [a-z_] up to 32 characters, a label,
     * an optional confirm text and a style (primary or warning). Later actions with a name already used are dropped.
     * Label and confirm are returned raw; the template escapes them.
     *
     * @param mixed $actions
     * @return array<int, array{name: string, label: string, confirm: string, style: string}>
     */
    public static function normalizeScanActions($actions): array
    {
        if (! is_array($actions)) {
            return [];
        }

        $normalized = [];
        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $name = $action['name'] ?? null;
            $label = trim((string)(is_scalar($action['label'] ?? null) ? $action['label'] : ''));
            if (! is_string($name) || ! preg_match(self::SCAN_ACTION_PATTERN, $name) || $label === '' || isset($normalized[$name])) {
                continue;
            }

            $confirm = $action['confirm'] ?? '';
            $style = $action['style'] ?? 'primary';

            $normalized[$name] = [
                'name' => $name,
                'label' => $label,
                'confirm' => is_scalar($confirm) ? trim((string)$confirm) : '',
                'style' => in_array($style, self::SCAN_ACTION_STYLES, true) ? $style : 'primary',
            ];
        }

        return array_values($normalized);
    }

    /**
     * Name of the CSRF token of the scan actions of an add-on
     */
    public static function scanTokenName(string $addon): string
    {
        return 'simplecart_scan_' . substr((string)preg_replace('/[^a-z0-9_]/', '', strtolower($addon)), 0, 32);
    }

    /**
     * False when a result of order.render (context scan) returns allow_close = false: the scan page then
     * hides the close button and refuses a POST that closes the order
     *
     * @param array<int, mixed> $renderResults
     */
    public static function scanAllowsClose(array $renderResults): bool
    {
        foreach ($renderResults as $result) {
            if (is_array($result) && array_key_exists('allow_close', $result) && $result['allow_close'] !== null && ! $result['allow_close']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private static function findExtensionFiles(): array
    {
        if (self::$fileProvider !== null) {
            return array_values((array)(self::$fileProvider)());
        }

        $files = [];
        $moduleHandler = icms::handler('icms_module');
        $criteria = new icms_db_criteria_Item('isactive', 1);

        foreach ($moduleHandler->getObjects($criteria) as $module) {
            $dirname = (string)$module->getVar('dirname');

            if ($dirname === '' || $dirname === (defined('SIMPLECART_DIRNAME') ? SIMPLECART_DIRNAME : 'simplecart') || ! preg_match('/^[A-Za-z0-9_-]+$/', $dirname)) {
                continue;
            }

            $file = ICMS_ROOT_PATH . '/modules/' . $dirname . '/simplecart_extension.php';
            if (is_file($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private static function logFailure(string $context, Throwable $e): void
    {
        $message = "SimpleCart extension failure ({$context}): " . get_class($e) . ': ' . $e->getMessage();

        if (function_exists('simplecart_debugLog')) {
            simplecart_debugLog($message);
        }

        error_log($message);
    }
}
