<?php

declare(strict_types=1);

namespace LPhenom\Storage;

/**
 * LocalFilesystemStorage — a StorageInterface driver for the local filesystem.
 *
 * Features:
 *  - Path traversal protection: all paths are normalised and confined within root.
 *  - Atomic writes: data is written to a temp file, then renamed into place.
 *  - KPHP-compatible: no reflection, no dynamic class loading, no property promotion.
 */
final class LocalFilesystemStorage implements StorageInterface
{
    /** @var string Absolute path to the storage root directory */
    private string $root;

    /**
     * @param string $root Absolute path to the storage root directory.
     *
     * @throws StorageException If the root directory cannot be created or is not a directory.
     */
    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/\\');

        if (!is_dir($this->root)) {
            if (!mkdir($this->root, 0755, true)) {
                throw new StorageException('Cannot create storage root: ' . $this->root);
            }
        }
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function put(string $path, string $bytes, array $meta = []): void
    {
        $fullPath = $this->resolvePath($path);
        $dir      = dirname($fullPath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new StorageException('Cannot create directory: ' . $dir);
            }
        }

        $tmpPath = $fullPath . '.tmp.' . uniqid('', true);

        $exception = null;

        try {
            $result = file_put_contents($tmpPath, $bytes);
            if ($result === false) {
                throw new StorageException('Failed to write to temporary file: ' . $tmpPath);
            }
            if (!rename($tmpPath, $fullPath)) {
                throw new StorageException('Failed to rename temporary file to: ' . $fullPath);
            }
        } catch (StorageException $e) {
            $exception = $e;
        } catch (\Throwable $e) {
            $exception = new StorageException('Failed to write file: ' . $path . ' — ' . $e->getMessage());
        }

        // Cleanup temp file if rename failed
        if ($exception !== null) {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
            throw $exception;
        }
    }

    public function get(string $path): string
    {
        $fullPath = $this->resolvePath($path);

        if (!is_file($fullPath)) {
            throw new StorageException('File not found: ' . $path);
        }

        $exception = null;
        $content   = null;

        try {
            $result = file_get_contents($fullPath);
            if ($result === false) {
                throw new StorageException('Failed to read file: ' . $path);
            }
            $content = $result;
        } catch (StorageException $e) {
            $exception = $e;
        } catch (\Throwable $e) {
            $exception = new StorageException('Failed to read file: ' . $path . ' — ' . $e->getMessage());
        }

        if ($exception !== null) {
            throw $exception;
        }

        if ($content === null) {
            throw new StorageException('Failed to read file: ' . $path);
        }

        return $content;
    }

    /**
     * Returns an open readable file handle (resource).
     * In KPHP mode the return type is mixed — use fread()/fclose() directly.
     */
    public function stream(string $path): mixed
    {
        $fullPath = $this->resolvePath($path);

        if (!is_file($fullPath)) {
            throw new StorageException('File not found: ' . $path);
        }

        $exception = null;
        $handle    = null;

        try {
            $result = fopen($fullPath, 'rb');
            if ($result === false) {
                throw new StorageException('Failed to open stream for: ' . $path);
            }
            $handle = $result;
        } catch (StorageException $e) {
            $exception = $e;
        } catch (\Throwable $e) {
            $exception = new StorageException('Failed to open stream for: ' . $path . ' — ' . $e->getMessage());
        }

        if ($exception !== null) {
            throw $exception;
        }

        if ($handle === null) {
            throw new StorageException('Failed to open stream for: ' . $path);
        }

        return $handle;
    }

    public function delete(string $path): void
    {
        $fullPath = $this->resolvePath($path);

        if (!is_file($fullPath)) {
            throw new StorageException('File not found: ' . $path);
        }

        $exception = null;

        try {
            if (!unlink($fullPath)) {
                throw new StorageException('Failed to delete file: ' . $path);
            }
        } catch (StorageException $e) {
            $exception = $e;
        } catch (\Throwable $e) {
            $exception = new StorageException('Failed to delete file: ' . $path . ' — ' . $e->getMessage());
        }

        if ($exception !== null) {
            throw $exception;
        }
    }

    public function exists(string $path): bool
    {
        $exception = null;
        $resolved  = null;

        try {
            $resolved = $this->resolvePath($path);
        } catch (StorageException $e) {
            $exception = $e;
        } catch (\Throwable $e) {
            $exception = new StorageException($e->getMessage());
        }

        if ($exception !== null) {
            return false;
        }

        if ($resolved === null) {
            return false;
        }

        return is_file($resolved);
    }

    /**
     * Resolve a relative path against the storage root.
     *
     * Normalises the path by resolving ".." segments manually,
     * then ensures the result is within the root directory.
     *
     * @throws StorageException On path traversal attempt or empty path.
     */
    private function resolvePath(string $path): string
    {
        $path = ltrim($path, '/\\');

        if ($path === '') {
            throw new StorageException('Path must not be empty.');
        }

        // Normalise directory separators
        $path = str_replace('\\', '/', $path);

        // Resolve path segments manually to prevent traversal
        $segments  = explode('/', $path);
        $resolved  = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                throw new StorageException('Path traversal detected: ' . $path);
            }
            $resolved[] = $segment;
        }

        if (count($resolved) === 0) {
            throw new StorageException('Path must not be empty after normalisation.');
        }

        $fullPath = $this->root . '/' . implode('/', $resolved);

        // Double-check: ensure the resolved path starts with the root
        $rootPrefix = $this->root . '/';
        $fullPrefix = substr($fullPath, 0, strlen($rootPrefix));

        if ($fullPrefix !== $rootPrefix) {
            throw new StorageException('Path escapes storage root: ' . $path);
        }

        return $fullPath;
    }
}

