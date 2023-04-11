<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use drhino\Tail\Tail;
use drhino\Tail\Exception\TailException;

/**
 * Test case
 * @psalm-api
 */
final class TailTest extends TestCase
{
    // psalm.dev/074 | php7.4
    // comment out for php8.1
    protected $backupStaticAttributes = false;
    protected $runTestInSeparateProcess = false;
  
    // Ensures the class can be created
    // @psalm-api
    public function testCanBeCreated(): void
    {
        $this->assertInstanceOf(Tail::class, new Tail(__DIR__ . '/test.log');
    }
}
