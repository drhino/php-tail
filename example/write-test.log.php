<?php

// Continuously write to $log
// Truncates half the file when it reaches 100 lines

$log = __DIR__ . '/test.log';

clearstatcache(true, $log);
file_put_contents($log, '');

/**
 * Appends the $data to the file.
 * @param string $path to file.
 * @param string $data to append.
 * @return void
 */
function append(string $path, string $data): void {
    $x = fopen($path, 'ab');
    flock($x, LOCK_EX);
    fwrite($x, $data);
    fclose($x);
}

/**
 * Truncates the number of $bytes from the beginning.
 * @param string $path to file.
 * @param integer $bytes to remove.
 * @param string $delimiter to expect between chunks of data.
 * @throws Exception
 * @return integer $written number of bytes.
 */
function truncate(string $path, int $bytes, string $delimiter = null): int {
    // First, we write the last part of the file to the beginning
    // After, we truncate that file to the number of bytes written
    try {
        $write = fopen($path, 'cb');
        $read = fopen($path, 'rb');

        // Acquires the lock
        flock($write, LOCK_EX);

        if (isset($delimiter)) {
            // We start the search for the $delimiter at
            // 1 character before the requested position
            // This ensures the exact position matches
            fseek($read, $bytes - 1);

            // ... @TODO: maximum number of buffers to read?
            // ... so we wouldn't be reading the whole file
            // ... if the delimiter does not exist in the file
            while (!feof($read)) {
                if (($buffer = fread($read, 8192)) === false) {
                    throw new Exception('Failed to fread()');
                }

                if (($index = strpos($buffer, $delimiter)) !== false) {
                    $bytes = ftell($read) - $index;
                    break;
                }
            }
        }

        // Sets the file pointer to the number of bytes to remove
        fseek($read, $bytes);

        // The expected number of bytes to be written
        $expected = filesize($path) - $bytes;
        $written = 0;

        // Writes the contents, starting from $bytes, to the beginning of the file
        while (!feof($read)) {
            if (($buffer = fread($read, 8192)) === false) {
                throw new Exception('Failed to fread()');
            }

            fwrite($write, $buffer);
            $written += strlen($buffer);
        }

        if ($expected !== $written) {
            throw new Exception("Wrote: $written bytes of $expected bytes");
        }

        // We duplicated the contents to the beginning, now we remove
        // the original contents, which will shrink the file size
        ftruncate($write, $written);
    }
    catch (Throwable $e) {}

    // Closes the file handle, optional, but best practice
    is_resource($read) && fclose($read);

    // Release the lock and close the file handle
    is_resource($write) && fclose($write);

    if (isset($e)) {
        throw $e;
    }

    return $written;
}

$i = 0;

while (connection_status() === CONNECTION_NORMAL) {
    $i++;

    clearstatcache(true, $log);

    if (filesize($log) === 1500) {
        // Keeps the last half of the file
        truncate($log, 750);
    }

    // Single character
	if ($i > 9) {
		$i = 1;
	}

    // Appends 15 bytes
    append($log, time() . '---' . $i . PHP_EOL);

    usleep(100000); // 100 ms
}
