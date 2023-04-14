<?php

require __DIR__ . '/../Tail/Tail.php';
require __DIR__ . '/../Tail/TailException.php';

use drhino\Tail\Tail;
use drhino\Tail\TailException;

# http_response_code(200);
header('Content-Type: text/plain');
header('Cache-Control: no-store');
# header('Retry-After: 10');
# header('Location: server2.php');

try {
    $file = __DIR__ . '/test.log';
    $tail = new Tail($file);

    // Emits new contents that are appended to $file
    $tail->stream();
}
catch (InvalidArgumentException $e) {
    // Path cannot be empty
    echo 'InvalidArgumentException: ' . $e->getMessage() . PHP_EOL;
}
catch (TailException $e) {
    echo 'TailException: ' . $e->getMessage() . PHP_EOL;
}
