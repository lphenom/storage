<?php

declare(strict_types=1);

namespace LPhenom\Storage\Tests;

use LPhenom\Storage\StorageException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LPhenom\Storage\StorageException
 */
final class StorageExceptionTest extends TestCase
{
    public function testIsRuntimeException(): void
    {
        $e = new StorageException('test error');

        self::assertInstanceOf(\RuntimeException::class, $e);
    }

    public function testMessageIsPreserved(): void
    {
        $e = new StorageException('something went wrong');

        self::assertSame('something went wrong', $e->getMessage());
    }

    public function testCodeIsPreserved(): void
    {
        $e = new StorageException('error', 42);

        self::assertSame(42, $e->getCode());
    }

    public function testPreviousExceptionIsPreserved(): void
    {
        $previous = new \RuntimeException('original');
        $e        = new StorageException('wrapped', 0, $previous);

        self::assertSame($previous, $e->getPrevious());
    }

    public function testCanBeCaught(): void
    {
        $caught = false;

        try {
            throw new StorageException('catch me');
        } catch (StorageException $e) {
            $caught = true;
            self::assertSame('catch me', $e->getMessage());
        }

        self::assertTrue($caught);
    }

    public function testCanBeCaughtAsRuntimeException(): void
    {
        $caught = false;

        try {
            throw new StorageException('as runtime');
        } catch (\RuntimeException $e) {
            $caught = true;
        }

        self::assertTrue($caught);
    }
}

