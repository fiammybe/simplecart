<?php
require __DIR__ . '/bootstrap.php';

foreach (glob(__DIR__ . '/*Test.php') as $file) {
    require $file;
}

$passed = 0;
foreach ($GLOBALS['__tests'] as $name => $fn) {
    SimplecartExtension::reset();
    SimplecartExtension::setFileProvider(function () { return []; });
    try {
        $fn();
        $passed++;
        echo "PASS  {$name}\n";
    } catch (Throwable $e) {
        $GLOBALS['__failures']++;
        echo "FAIL  {$name}\n      " . get_class($e) . ': ' . $e->getMessage() . "\n      at " . $e->getFile() . ':' . $e->getLine() . "\n";
    }
}

echo "\n{$passed} passed, {$GLOBALS['__failures']} failed\n";
exit($GLOBALS['__failures'] > 0 ? 1 : 0);
