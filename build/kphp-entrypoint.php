<?php

/**
 * KPHP entrypoint for lphenom/storage.
 *
 * This file is used by KPHP to compile the package into a static binary.
 * All source files must be required explicitly — KPHP does not support
 * Composer PSR-4 autoloading.
 *
 * Order matters: interfaces and exceptions must be loaded before classes
 * that use them.
 *
 * Usage:
 *   kphp -d /build/kphp-out -M cli /build/build/kphp-entrypoint.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/StorageException.php';
require_once __DIR__ . '/../src/StorageInterface.php';
require_once __DIR__ . '/../src/LocalFilesystemStorage.php';

// Smoke-test: verify basic operations work in compiled binary
$tmpDir = '/tmp/kphp-storage-test-' . (string) time();

$storage = new \LPhenom\Storage\LocalFilesystemStorage($tmpDir);

// put + exists + get
$storage->put('hello/world.txt', 'Hello, KPHP!');

if (!$storage->exists('hello/world.txt')) {
    echo 'ERROR: exists() returned false after put()' . PHP_EOL;
    exit(1);
}

$content = $storage->get('hello/world.txt');
if ($content !== 'Hello, KPHP!') {
    echo 'ERROR: get() returned unexpected content: ' . $content . PHP_EOL;
    exit(1);
}

// delete
$storage->delete('hello/world.txt');

if ($storage->exists('hello/world.txt')) {
    echo 'ERROR: exists() returned true after delete()' . PHP_EOL;
    exit(1);
}

echo '=== KPHP smoke-test: OK ===' . PHP_EOL;

