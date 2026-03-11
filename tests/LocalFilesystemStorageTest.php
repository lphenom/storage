<?php

declare(strict_types=1);

namespace LPhenom\Storage\Tests;

use LPhenom\Storage\LocalFilesystemStorage;
use LPhenom\Storage\StorageException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LPhenom\Storage\LocalFilesystemStorage
 */
final class LocalFilesystemStorageTest extends TestCase
{
    /** @var string */
    private string $tmpDir;

    /** @var LocalFilesystemStorage */
    private LocalFilesystemStorage $storage;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir() . '/lphenom-storage-test-' . uniqid('', true);
        $this->storage = new LocalFilesystemStorage($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    public function testPutAndGet(): void
    {
        $this->storage->put('file.txt', 'Hello, World!');
        $content = $this->storage->get('file.txt');

        self::assertSame('Hello, World!', $content);
    }

    public function testPutCreatesSubdirectory(): void
    {
        $this->storage->put('subdir/nested/file.txt', 'nested content');
        $content = $this->storage->get('subdir/nested/file.txt');

        self::assertSame('nested content', $content);
    }

    public function testPutOverwritesExistingFile(): void
    {
        $this->storage->put('file.txt', 'original');
        $this->storage->put('file.txt', 'updated');

        self::assertSame('updated', $this->storage->get('file.txt'));
    }

    public function testPutEmptyBytes(): void
    {
        $this->storage->put('empty.txt', '');

        self::assertSame('', $this->storage->get('empty.txt'));
    }

    public function testExistsReturnsTrueForExistingFile(): void
    {
        $this->storage->put('exists.txt', 'data');

        self::assertTrue($this->storage->exists('exists.txt'));
    }

    public function testExistsReturnsFalseForMissingFile(): void
    {
        self::assertFalse($this->storage->exists('missing.txt'));
    }

    public function testExistsReturnsFalseForPathTraversal(): void
    {
        // Path traversal should return false (path is invalid, not throw in exists())
        self::assertFalse($this->storage->exists('../etc/passwd'));
    }

    public function testDeleteRemovesFile(): void
    {
        $this->storage->put('to-delete.txt', 'bye');
        $this->storage->delete('to-delete.txt');

        self::assertFalse($this->storage->exists('to-delete.txt'));
    }

    public function testDeleteThrowsOnMissingFile(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->delete('nonexistent.txt');
    }

    public function testGetThrowsOnMissingFile(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->get('nonexistent.txt');
    }

    public function testStreamReturnsReadableResource(): void
    {
        $this->storage->put('stream.txt', 'stream content');
        $handle = $this->storage->stream('stream.txt');

        self::assertIsResource($handle);

        $content = stream_get_contents($handle);
        fclose($handle);

        self::assertSame('stream content', $content);
    }

    public function testStreamThrowsOnMissingFile(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->stream('nonexistent.txt');
    }

    public function testPathTraversalInPutThrowsException(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->put('../etc/passwd', 'malicious');
    }

    public function testPathTraversalInGetThrowsException(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->get('../etc/passwd');
    }

    public function testPathTraversalInDeleteThrowsException(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->delete('../etc/passwd');
    }

    public function testPathTraversalInStreamThrowsException(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->stream('../etc/passwd');
    }

    public function testNestedPathTraversalIsBlocked(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->put('a/b/../../etc/passwd', 'evil');
    }

    public function testAtomicWriteNoTempFilesRemainOnSuccess(): void
    {
        $this->storage->put('atomic.txt', 'atomic data');

        $tmpFiles = glob($this->tmpDir . '/atomic.txt.tmp.*');
        if ($tmpFiles === false) {
            $tmpFiles = [];
        }

        self::assertCount(0, $tmpFiles, 'No temporary files should remain after successful put()');
        self::assertSame('atomic data', $this->storage->get('atomic.txt'));
    }

    public function testEmptyPathThrowsException(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->put('', 'data');
    }

    public function testPutWithBinaryContent(): void
    {
        $binary = "\x00\x01\x02\x03\xFF\xFE";
        $this->storage->put('binary.bin', $binary);

        self::assertSame($binary, $this->storage->get('binary.bin'));
    }

    public function testPutWithLargeContent(): void
    {
        $large = str_repeat('x', 1024 * 1024); // 1 MB
        $this->storage->put('large.dat', $large);

        self::assertSame(strlen($large), strlen($this->storage->get('large.dat')));
    }

    public function testStorageRootIsCreatedIfNotExists(): void
    {
        $newRoot = $this->tmpDir . '/new-root-' . uniqid('', true);
        self::assertDirectoryDoesNotExist($newRoot);

        $storage = new LocalFilesystemStorage($newRoot);
        self::assertDirectoryExists($newRoot);

        $storage->put('test.txt', 'hello');
        self::assertSame('hello', $storage->get('test.txt'));
    }

    /**
     * Recursively remove a directory and all its contents.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}

