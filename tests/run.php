<?php

declare(strict_types=1);

ini_set('assert.active', '1');
ini_set('assert.exception', '1');

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Huynt57\\LaravelSingletonize\\Tests\\' => __DIR__.'/',
        'Huynt57\\LaravelSingletonize\\' => __DIR__.'/../src/',
        'Illuminate\\' => __DIR__.'/Support/Illuminate/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir.str_replace('\\', '/', $relative).'.php';

            if (is_file($file)) {
                require_once $file;
            }

            return;
        }
    }
});

$factory = require __DIR__.'/SingletonizerTest.php';
$tests = $factory();

$passed = 0;
$total = count($tests);
$failures = [];

foreach ($tests as $name => $test) {
    try {
        $test();
        $passed++;
        echo "[PASS] $name\n";
    } catch (Throwable $e) {
        $failures[$name] = $e->getMessage();
        echo "[FAIL] $name - {$e->getMessage()}\n";
    }
}

if ($failures) {
    echo "\n".count($failures)." test(s) failed out of $total.\n";
    exit(1);
}

echo "\nAll $total tests passed.\n";
