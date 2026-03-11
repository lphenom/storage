<?php

declare(strict_types=1);

namespace LPhenom\Storage;

/**
 * StorageInterface — contract for all storage drivers.
 *
 * All implementations must throw StorageException on failure.
 * This interface is KPHP-compatible: no reflection, no dynamic dispatch.
 */
interface StorageInterface
{
    /**
     * Write bytes to the given path.
     *
     * @param string               $path  Relative path within the storage root.
     * @param string               $bytes Raw content to write.
     * @param array<string, mixed> $meta  Optional metadata (driver-specific).
     *
     * @throws StorageException On write failure.
     */
    public function put(string $path, string $bytes, array $meta = []): void;

    /**
     * Read and return the content of a file.
     *
     * @param string $path Relative path within the storage root.
     *
     * @throws StorageException If the file does not exist or cannot be read.
     */
    public function get(string $path): string;

    /**
     * Open a readable stream for the given path.
     *
     * @param string $path Relative path within the storage root.
     *
     * @return resource An open readable file handle.
     *
     * @throws StorageException If the file does not exist or cannot be opened.
     */
    public function stream(string $path): mixed;

    /**
     * Delete a file at the given path.
     *
     * @param string $path Relative path within the storage root.
     *
     * @throws StorageException If the file cannot be deleted.
     */
    public function delete(string $path): void;

    /**
     * Check whether a file exists at the given path.
     *
     * @param string $path Relative path within the storage root.
     */
    public function exists(string $path): bool;
}

