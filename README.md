# lphenom/storage

[![CI](https://github.com/lphenom/storage/actions/workflows/ci.yml/badge.svg)](https://github.com/lphenom/storage/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

LPhenom Storage — абстракция файлового хранилища для PHP 8.1+ и KPHP.

Часть экосистемы фреймворка [LPhenom](https://github.com/lphenom).

## Возможности

- 📦 `StorageInterface` — чистый, минималистичный API
- 🗂 `LocalFilesystemStorage` — драйвер для локальной файловой системы с защитой от обхода путей и атомарной записью
- ⚡ Совместимость с KPHP — компилируется в статический бинарник с [vkcom/kphp](https://github.com/vkcom/kphp)
- 🛡 Строгая типизация везде
- 🧪 Полное покрытие тестами

## Требования

- PHP >= 8.1

## Установка

```bash
composer require lphenom/storage
```

## Быстрый старт

```php
use LPhenom\Storage\LocalFilesystemStorage;
use LPhenom\Storage\StorageException;

$storage = new LocalFilesystemStorage('/var/app/uploads');

// Сохранить файл
$storage->put('images/photo.jpg', file_get_contents('/tmp/photo.jpg'));

// Проверить существование
if ($storage->exists('images/photo.jpg')) {
    // Прочитать содержимое
    $bytes = $storage->get('images/photo.jpg');

    // Открыть как поток
    $stream = $storage->stream('images/photo.jpg');
    fpassthru($stream);
    fclose($stream);
}

// Удалить файл
$storage->delete('images/photo.jpg');
```

## Обработка ошибок

Все методы выбрасывают `StorageException` при ошибке:

```php
use LPhenom\Storage\StorageException;

try {
    $content = $storage->get('missing-file.txt');
} catch (StorageException $e) {
    echo 'Ошибка хранилища: ' . $e->getMessage();
}
```

## Документация

Полная документация — в [docs/storage.md](docs/storage.md).

## Разработка

```bash
make up      # Запустить Docker-окружение
make test    # Запустить тесты PHPUnit
make lint    # Запустить PHPStan (уровень 9)
make kphp-check  # Проверить совместимость с KPHP + сборку PHAR
make down    # Остановить контейнеры
```

## Участие в разработке

См. [CONTRIBUTING.md](CONTRIBUTING.md).

## Безопасность

См. [SECURITY.md](SECURITY.md).

## Лицензия

MIT — [LPhenom Contributors](LICENSE)
