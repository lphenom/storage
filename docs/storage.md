# Storage

`LPhenom\Storage` — filesystem storage abstraction for PHP 8.1+ and KPHP.

## Overview

The package provides a minimal, KPHP-compatible API for storing, reading, and deleting files.  
It ships with one built-in driver: `LocalFilesystemStorage`.

---

## StorageInterface

```php
interface StorageInterface
{
    public function put(string $path, string $bytes, array $meta = []): void;
    public function get(string $path): string;
    public function stream(string $path): mixed;   // resource
    public function delete(string $path): void;
    public function exists(string $path): bool;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `put(string $path, string $bytes, array $meta = []): void` | Write bytes to the given path. Creates subdirectories as needed. |
| `get(string $path): string` | Read and return the full content of a file. |
| `stream(string $path): resource` | Open a readable stream (file handle) for the given path. |
| `delete(string $path): void` | Delete a file. |
| `exists(string $path): bool` | Check whether a file exists at the given path. |

All methods throw `StorageException` on failure.

---

## LocalFilesystemStorage

A storage driver backed by the local filesystem.

### Constructor

```php
$storage = new LocalFilesystemStorage('/var/app/uploads');
```

- The root directory will be created automatically if it does not exist.
- All paths are relative to the root.
- Throws `StorageException` if the root cannot be created.

### Path Traversal Protection

All paths are sanitised before use:

1. Leading slashes and backslashes are stripped.
2. The path is split into segments.
3. Any `..` segment causes an immediate `StorageException` — traversal is never resolved silently.
4. The final resolved path is verified to start with the root prefix as an additional safety check.

```php
// ❌ throws StorageException — path traversal detected
$storage->put('../etc/passwd', 'evil');
$storage->get('a/b/../../etc/passwd');
```

### Atomic Writes

`put()` uses a write-then-rename strategy to ensure atomicity:

1. Content is written to a temporary file: `<path>.tmp.<uniqid>`.
2. The temporary file is renamed to the final path (atomic on POSIX systems).
3. If any step fails, the temporary file is cleaned up and a `StorageException` is thrown.

This guarantees that a concurrent reader never sees a partially written file.

### Usage Examples

```php
use LPhenom\Storage\LocalFilesystemStorage;
use LPhenom\Storage\StorageException;

$storage = new LocalFilesystemStorage('/var/app/uploads');

// Write a file
$storage->put('invoices/2026-001.pdf', $pdfBytes);

// Read a file
$bytes = $storage->get('invoices/2026-001.pdf');

// Check existence
if ($storage->exists('invoices/2026-001.pdf')) {
    // Stream a file (useful for large files)
    $stream = $storage->stream('invoices/2026-001.pdf');
    while (!feof($stream)) {
        echo fread($stream, 8192);
    }
    fclose($stream);
}

// Delete a file
$storage->delete('invoices/2026-001.pdf');
```

### Error Handling

```php
use LPhenom\Storage\StorageException;

try {
    $bytes = $storage->get('missing.txt');
} catch (StorageException $e) {
    // File not found, permission error, etc.
    echo $e->getMessage();
}
```

---

## StorageException

All errors are reported via `StorageException`, which extends `\RuntimeException`.

```php
use LPhenom\Storage\StorageException;

try {
    $storage->delete('important.txt');
} catch (StorageException $e) {
    // log or re-throw
}
```

---

## Implementing a Custom Driver

To add a new backend (S3, GCS, FTP, etc.), implement `StorageInterface`:

```php
use LPhenom\Storage\StorageInterface;
use LPhenom\Storage\StorageException;

final class S3Storage implements StorageInterface
{
    private string $bucket;

    public function __construct(string $bucket)
    {
        $this->bucket = $bucket;
    }

    public function put(string $path, string $bytes, array $meta = []): void
    {
        // upload to S3 ...
    }

    public function get(string $path): string
    {
        // download from S3 ...
        return '';
    }

    public function stream(string $path): mixed
    {
        // return a stream ...
    }

    public function delete(string $path): void
    {
        // delete from S3 ...
    }

    public function exists(string $path): bool
    {
        // check S3 ...
        return false;
    }
}
```

---

## KPHP Compatibility

| Feature | Status |
|---------|--------|
| No `Reflection` API | ✅ |
| No `eval()` | ✅ |
| No dynamic class loading (`new $class()`) | ✅ |
| No `str_starts_with` / `str_ends_with` / `str_contains` | ✅ uses `substr` / `strpos` |
| No constructor property promotion | ✅ properties declared explicitly |
| No `readonly` properties | ✅ |
| `try/catch` with at least one `catch` | ✅ |
| `file()` called with 1 argument only | ✅ (not used; `file_get_contents` used instead) |
| `stream()` returns `mixed` (typed as `resource` in PHPDoc) | ✅ KPHP-compatible signature |
| `declare(strict_types=1)` in every file | ✅ |

> See [kphp-compatibility.md](./kphp-compatibility.md) for the full KPHP compatibility guide.

### KPHP Entrypoint

KPHP does not support Composer autoloading. The file `build/kphp-entrypoint.php` loads all source files with explicit `require_once` in dependency order:

```php
require_once __DIR__ . '/../src/StorageException.php';
require_once __DIR__ . '/../src/StorageInterface.php';
require_once __DIR__ . '/../src/LocalFilesystemStorage.php';
```

### Verifying KPHP Compatibility

```bash
# Build KPHP binary + PHAR
make kphp-check
# or directly:
docker build -f Dockerfile.check -t lphenom-storage-check .
```

Both build stages must exit with code 0:
- **Stage 1 (`kphp-build`)** — compiles with `vkcom/kphp`, runs the binary.
- **Stage 2 (`phar-build`)** — builds a PHAR with PHP 8.1, runs the smoke test.

---

## Development

```bash
make up      # Start Docker environment (PHP 8.1-alpine)
make test    # Run PHPUnit (28 tests)
make lint    # Run PHPStan (level 9)
make down    # Stop containers
```

