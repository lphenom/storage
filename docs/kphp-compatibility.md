# KPHP Compatibility Guide — lphenom/storage

This document describes all KPHP constraints applied in `lphenom/storage`
and the patterns used to satisfy them.

---

## How KPHP compilation works

KPHP (`vkcom/kphp`) compiles PHP source into a **static C++ binary**:

- KPHP does **not** use PHP runtime during compilation — it has its own PHP parser.
- The compiled binary does **not** depend on PHP.
- KPHP uses strict type inference and refuses to compile ambiguous or dynamic code.
- The Docker image `vkcom/kphp` is based on Ubuntu 20.04 focal with PHP 7.4 tooling.

> **Important:** The minimum PHP version for **development/runtime** is 8.1.  
> The PHP 7.4 inside the KPHP Docker image is the compiler toolchain only — not your runtime.

---

## Forbidden constructs

### 1. `str_starts_with` / `str_ends_with` / `str_contains`

These PHP 8.0+ functions are **not implemented in KPHP**.

```php
// ❌ FORBIDDEN
if (str_starts_with($path, '/')) { ... }

// ✅ CORRECT
if (substr($path, 0, 1) === '/') { ... }
if (strpos($path, 'needle') !== false) { ... }
```

In `LocalFilesystemStorage::resolvePath()` all string checks use `substr()` and `strpos()`.

---

### 2. Constructor property promotion

```php
// ❌ FORBIDDEN in KPHP
final class LocalFilesystemStorage {
    public function __construct(private string $root) {}
}

// ✅ CORRECT
final class LocalFilesystemStorage {
    private string $root;

    public function __construct(string $root) {
        $this->root = rtrim($root, '/\\');
    }
}
```

---

### 3. `readonly` properties

```php
// ❌ FORBIDDEN in KPHP
private readonly string $root;

// ✅ CORRECT
private string $root;
```

---

### 4. `try/finally` without `catch`

KPHP requires at least one `catch` block.

```php
// ❌ FORBIDDEN
try {
    $result = file_put_contents($tmp, $bytes);
} finally {
    if (is_file($tmp)) { unlink($tmp); }
}

// ✅ CORRECT — used in LocalFilesystemStorage::put()
$exception = null;
try {
    $result = file_put_contents($tmp, $bytes);
    if (!rename($tmp, $fullPath)) {
        throw new StorageException('rename failed');
    }
} catch (StorageException $e) {
    $exception = $e;
} catch (\Throwable $e) {
    $exception = new StorageException($e->getMessage(), 0, $e);
}
if (is_file($tmp)) { @unlink($tmp); }
if ($exception !== null) { throw $exception; }
```

---

### 5. `!isset() + throw` type narrowing

KPHP does not narrow types after `!isset() + throw`. Use explicit null assignment:

```php
// ❌ KPHP does not narrow $val after this
if (!isset($this->map[$key])) { throw new Exception(); }
$val = $this->map[$key]; // type still ?T in KPHP

// ✅ CORRECT — used throughout LocalFilesystemStorage
$val = $this->map[$key] ?? null;
if ($val === null) { throw new StorageException('...'); }
```

---

### 6. Reflection API

Not used anywhere in this package. All behaviour is explicit — no `ReflectionClass`,
no `get_class()`, no `class_exists()`.

---

### 7. `eval()` and dynamic class loading

Not used. No `new $className()`, no `call_user_func()` with string class names.

---

### 8. `file()` with flags

KPHP supports `file()` with only one argument.

This package does not use `file()` — content is read with `file_get_contents()` instead.

---

### 9. `stream()` return type

KPHP has limited support for `resource` as a return type in interfaces.
The method is declared as `mixed` in the interface signature with a `@return resource` PHPDoc:

```php
/**
 * @return resource
 */
public function stream(string $path): mixed;
```

The `stream()` method is included in the KPHP entrypoint but is marked as optional
for KPHP-mode callers who do not need streaming.

---

## Entrypoint for KPHP

KPHP does not support Composer PSR-4 autoloading.
The file `build/kphp-entrypoint.php` includes all source files explicitly,
in dependency order (exceptions and interfaces before classes):

```php
require_once __DIR__ . '/../src/StorageException.php';
require_once __DIR__ . '/../src/StorageInterface.php';
require_once __DIR__ . '/../src/LocalFilesystemStorage.php';
```

---

## Allowed constructs (KPHP-friendly)

| Construct | Status |
|-----------|--------|
| `declare(strict_types=1)` | ✅ |
| `final class` / `interface` | ✅ |
| `?Type` nullable, `int\|string` union | ✅ |
| `array<K, V>` in PHPDoc | ✅ |
| `new ClassName()` (explicit, not dynamic) | ✅ |
| `try/catch` with at least one `catch` | ✅ |
| `instanceof` | ✅ |
| `substr()`, `strpos()`, `strlen()` | ✅ |
| `file_get_contents()`, `file_put_contents()` | ✅ |
| `fopen()`, `fclose()` | ✅ |
| `is_file()`, `is_dir()`, `mkdir()`, `unlink()`, `rename()` | ✅ |
| `uniqid()`, `dirname()`, `explode()`, `implode()` | ✅ |
| `sys_get_temp_dir()` | ✅ |

---

## Verification

```bash
docker build -f Dockerfile.check -t lphenom-storage-check .
```

- **Stage `kphp-build`**: compiles with `vkcom/kphp`, runs the binary as a non-root user.
- **Stage `phar-build`**: builds a PHAR with PHP 8.1, runs the smoke test.

Both stages must exit with code 0.

---

## References

- [KPHP vs PHP differences](https://vkcom.github.io/kphp/kphp-language/kphp-vs-php/whats-the-difference.html)
- [KPHP Docker image](https://hub.docker.com/r/vkcom/kphp)
- [lphenom/storage — storage.md](./storage.md)

