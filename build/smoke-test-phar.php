#!/usr/bin/env php
<?php

/**
 * PHAR smoke-test: require the built PHAR and verify autoloading works.
 *
 * Usage: php build/smoke-test-phar.php /path/to/lphenom-storage.phar
 */

declare(strict_types=1);

$pharFile = $argv[1] ?? dirname(__DIR__) . '/lphenom-storage.phar';

if (!file_exists($pharFile)) {
    fwrite(STDERR, 'PHAR not found: ' . $pharFile . PHP_EOL);
    exit(1);
}

require $pharFile;

$tmpDir  = sys_get_temp_dir() . '/lphenom-storage-phar-smoke-' . (string) time();
$storage = new \LPhenom\Storage\LocalFilesystemStorage($tmpDir);

// put + exists + get
$storage->put('smoke/test.txt', 'phar-smoke-ok');

if (!$storage->exists('smoke/test.txt')) {
    fwrite(STDERR, 'smoke-test: exists() failed after put()' . PHP_EOL);
    exit(1);
}

$content = $storage->get('smoke/test.txt');
if ($content !== 'phar-smoke-ok') {
    fwrite(STDERR, 'smoke-test: get() returned unexpected: ' . $content . PHP_EOL);
    exit(1);
}

echo 'smoke-test: put/exists/get ok' . PHP_EOL;

// stream
$stream = $storage->stream('smoke/test.txt');
$read   = stream_get_contents($stream);
fclose($stream);

if ($read !== 'phar-smoke-ok') {
    fwrite(STDERR, 'smoke-test: stream() read unexpected: ' . $read . PHP_EOL);
    exit(1);
}

echo 'smoke-test: stream ok' . PHP_EOL;

// delete
$storage->delete('smoke/test.txt');

if ($storage->exists('smoke/test.txt')) {
    fwrite(STDERR, 'smoke-test: exists() returned true after delete()' . PHP_EOL);
    exit(1);
}

echo 'smoke-test: delete ok' . PHP_EOL;

echo '=== PHAR smoke-test: OK ===' . PHP_EOL;

