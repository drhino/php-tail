<?php declare(strict_types=1);

namespace drhino\Tail;

use InvalidArgumentException;

use const SEEK_END;
use const CONNECTION_NORMAL;

use function usleep;
use function is_dir;
use function clearstatcache;
use function filesize;
use function fileinode;
use function fopen;
use function fseek;
use function ftell;
use function fclose;
use function fpassthru;
use function connection_status;
use function ob_end_flush;

/**
 * Follows the end of a file and emits new lines as they are added.
 */
class Tail
{
    /** @var ?resource|closed-resource */
    private $filehandle;
    private string $path;
    private string $realpath = '';
    private int $path_inode = -1;
    private int $realpath_inode = -1;

    /**
     * @param string $path The path to the file.
     * 
     * @throws InvalidArgumentException
     */
    public function __construct(string $path)
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty');
        }

        $this->path = $path;
    }

    /**
     * Follows the end of a file and emits new lines as they are added.
     *
     * @throws TailException
     *
     * @return void
     */
    public function stream(): void
    {
        // Disables output_buffering
        while (@ob_end_flush());

        while (connection_status() === CONNECTION_NORMAL) {
            if ($this->filehandle()) {
                /** @var resource */
                $filehandle = $this->filehandle;

                if (($size = filesize($this->realpath)) === false) {
                    throw new TailException('Unable to retrieve size of file: ' . $this->realpath);
                }

                if (($position = ftell($filehandle)) === false) {
                    throw new TailException('Unable to get position of file: ' . $this->realpath);
                }

                if ($size < $position) {
                    // The file was truncated
                    $this->close();
                }
                elseif ($size > $position) {
                    // Emits new data
                    if (fpassthru($filehandle) !== ($size - $position)) {
                        throw new TailException('Unable to passthru stream of file: ' . $this->realpath);
                    }
                }
            } else {
                $this->close();
            }

            // Avoids high CPU usage
            usleep(500000);
        }
    }

    /**
     * Closes a file handle and reset the class variables.
     *
     * @return void
     */
    public function close(): void
    {
        if (isset($this->filehandle)) {
            # echo '---CLOSE' . PHP_EOL;
            fclose($this->filehandle);
        }

        $this->filehandle = null;
        $this->realpath = '';
        $this->path_inode = -1;
        $this->realpath_inode = -1;
    }

    /**
     * Tries to open a file handle.
     *
     * @throws TailException
     *
     * @return boolean Whether `private $this->filehandle` can be used.
     *         false indicates that `$this->close()` should be called.
     */
    private function filehandle()
    {
        // The path was recreated, removed or does not exist
        if ($this->inode($this->path, $this->path_inode)) {
            return false;
        }

        // The function `realpath()` is not properly resolving
        if ($this->realpath === '') {
            if (is_link($this->path)) {
                // The destination does not exist
                if (false === ($this->realpath = readlink($this->path))) {
                    return false;
                }
            } else {
                // Not a symlink
                $this->realpath = $this->path;
                $this->realpath_inode = $this->path_inode;
            }
        }

        // Checks the destination of a symlink
        if ($this->path_inode !== $this->realpath_inode) {
            // The destination path changed
            if ($this->inode($this->realpath, $this->realpath_inode)) {
                return false;
            }
        }

        // The destination is a directory
        if (is_dir($this->realpath)) {
            # echo 'tail: test.log: Is a directory' . PHP_EOL;
            return false;
        }

        // Opens the file handle
        if (!isset($this->filehandle)) {

            # echo '---OPEN' . PHP_EOL;
            # var_dump($this->realpath);

            if (($this->filehandle = fopen($this->realpath, 'rb')) === false) {
                throw new TailException('Unable to open file: ' . $this->realpath);
            }

            if (fseek($this->filehandle, 0, SEEK_END) === -1) {
                throw new TailException('Unable to seek to end of file: '. $this->realpath);
            }
        }

        return true;
    }

    /**
     * Finds the inode for $path and compares it with $ref.
     * When the inode has changed, the value of $ref is updated.
     *
     * @param string $path The path to the file.
     * @param integer $ref The variable that holds the inode.
     *
     * @return boolean Whether `$this->filehandle()` should break.
     */
    private function inode(string $path, &$ref): bool
    {
        clearstatcache(true, $path);

        $inode = @fileinode($path);

        if (false === $inode) {
            // The file does not exist
            // When a file handle was open, close it, retry after half a second
            return true;
        }

        if ($ref !== $inode) {
            // Sets the initial value or updates the existing one
            // $ref equals to `-1` when the file handle is not open
            $ref = $inode;

            // When the inode changed, indicates that the file handle should
            // be closed, the state reset, wait for half a second, then reopen
            // Unless there is no file handle open, then we can open right away
            return isset($this->filehandle);
        }

        // The file handle can be opened or re-used
        return false;
    }
}
