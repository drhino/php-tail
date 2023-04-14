<?php

// Continuously write to $log
// Truncates half the file when it reaches 100 lines

$log = __DIR__ . '/test.log';

clearstatcache(true, $log);
file_put_contents($log, '');

$i = 0;

while (connection_status() === CONNECTION_NORMAL) {
    $i++;

    clearstatcache(true, $log);

    if (filesize($log) === 1500) {
        $write = fopen($log, 'cb');
        flock($write, LOCK_EX);
        $read = fopen($log, 'rb');

        // The last half of the file
        fseek($read, 750);

        if (!feof($read)) {
            if (($buffer = fread($read, 8192)) === false) {
                die('Failed to fread()');
            }

            fwrite($write, $buffer);
        }

        fclose($read);
        ftruncate($write, 750);
        fclose($write);
    }

    // Single character
	if ($i > 9) {
		$i = 1;
	}

    // Length of 15 bytes including newline
    $append = time() . '---' . $i . PHP_EOL;

    file_put_contents($log, $append, FILE_APPEND);

    usleep(100000); // 100 ms
}
