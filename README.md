# lphenom/storage

[![CI](https://github.com/lphenom/storage/actions/workflows/ci.yml/badge.svg)](https://github.com/lphenom/storage/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

LPhenom Storage — filesystem storage abstraction for PHP 8.1+ and KPHP.

Part of the [LPhenom](https://github.com/lphenom) framework ecosystem.

## Features

- 📦 `StorageInterface` — clean, minimal API
- 🗂 `LocalFilesystemStorage` — local filesystem driver with path traversal protection and atomic writes
- ⚡ KPHP-compatible — compiles to a static binary with [vkcom/kphp](https://github.com/vkcom/kphp)
- 🛡 Strict types everywhere
- 🧪 Full test coverage

## Requirements

- PHP >= 8.1

## Installation

```bash
composer require lphenom/storage
```

## Quick Start

```php
use LPhenom\Storage\LocalFilesystemStorage;
use LPhenom\Storage\StorageException;

$storage = new LocalFilesystemStorage('/var/app/uploads');

// Store a file
$storage->put('images/photo.jpg', file_get_contents('/tmp/photo.jpg'));

// Check existence
if ($storage->exists('images/photo.jpg')) {
    // Read content
    $bytes = $storage->get('images/photo.jpg');

    // Open as stream
    $stream = $storage->stream('images/photo.jpg');
    fpassthru($stream);
    fclose($stream);
}

// Delete a file
$storage->delete('images/photo.jpg');
```

## Error Handling

All methods throw `StorageException` on failure:

```php
use LPhenom\Storage\StorageException;

try {
    $content = $storage->get('missing-file.txt');
} catch (StorageException $e) {
    echo 'Storage error: ' . $e->getMessage();
}
```

## Documentation

See [docs/storage.md](docs/storage.md) for full documentation.

## Development

```bash
make up      # Start Docker environment
make test    # Run PHPUnit tests
make lint    # Run PHPStan (level 9)
make kphp-check  # Verify KPHP compilation + PHAR build
make down    # Stop containers
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md).

## License

MIT © [LPhenom Contributors](LICENSE)
