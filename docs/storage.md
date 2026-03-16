# lphenom/storage — Документация

`LPhenom\Storage` — абстракция файлового хранилища для PHP 8.1+ и KPHP.

## Обзор

Пакет предоставляет минималистичный, KPHP-совместимый API для хранения, чтения и удаления файлов.  
В комплекте поставляется один встроенный драйвер: `LocalFilesystemStorage`.

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

### Методы

| Метод | Описание |
|-------|----------|
| `put(string $path, string $bytes, array $meta = []): void` | Записать байты по заданному пути. При необходимости создаёт поддиректории. |
| `get(string $path): string` | Прочитать и вернуть полное содержимое файла. |
| `stream(string $path): resource` | Открыть читаемый поток (файловый дескриптор) для заданного пути. |
| `delete(string $path): void` | Удалить файл. |
| `exists(string $path): bool` | Проверить существование файла по заданному пути. |

Все методы выбрасывают `StorageException` при ошибке.

---

## LocalFilesystemStorage

Драйвер хранилища на основе локальной файловой системы.

### Конструктор

```php
$storage = new LocalFilesystemStorage('/var/app/uploads');
```

- Корневая директория будет создана автоматически, если она не существует.
- Все пути указываются относительно корня.
- Выбрасывает `StorageException`, если корневую директорию не удалось создать.

### Защита от обхода путей

Все пути санируются перед использованием:

1. Ведущие слэши и обратные слэши удаляются.
2. Путь разбивается на сегменты.
3. Любой сегмент `..` немедленно вызывает `StorageException` — обход никогда не разрешается молча.
4. Итоговый путь дополнительно проверяется на наличие корневого префикса.

```php
// ❌ выбрасывает StorageException — обнаружен обход пути
$storage->put('../etc/passwd', 'evil');
$storage->get('a/b/../../etc/passwd');
```

### Атомарная запись

`put()` использует стратегию «запись во временный файл + переименование» для обеспечения атомарности:

1. Содержимое записывается во временный файл: `<path>.tmp.<uniqid>`.
2. Временный файл переименовывается в итоговый путь (атомарная операция в POSIX-системах).
3. При любой ошибке временный файл удаляется и выбрасывается `StorageException`.

Это гарантирует, что параллельный читатель никогда не увидит частично записанный файл.

### Примеры использования

```php
use LPhenom\Storage\LocalFilesystemStorage;
use LPhenom\Storage\StorageException;

$storage = new LocalFilesystemStorage('/var/app/uploads');

// Записать файл
$storage->put('invoices/2026-001.pdf', $pdfBytes);

// Прочитать файл
$bytes = $storage->get('invoices/2026-001.pdf');

// Проверить существование
if ($storage->exists('invoices/2026-001.pdf')) {
    // Передать файл потоком (удобно для больших файлов)
    $stream = $storage->stream('invoices/2026-001.pdf');
    while (!feof($stream)) {
        echo fread($stream, 8192);
    }
    fclose($stream);
}

// Удалить файл
$storage->delete('invoices/2026-001.pdf');
```

### Обработка ошибок

```php
use LPhenom\Storage\StorageException;

try {
    $bytes = $storage->get('missing.txt');
} catch (StorageException $e) {
    // Файл не найден, ошибка прав доступа и т.д.
    echo $e->getMessage();
}
```

---

## StorageException

Все ошибки сообщаются через `StorageException`, который расширяет `\RuntimeException`.

```php
use LPhenom\Storage\StorageException;

try {
    $storage->delete('important.txt');
} catch (StorageException $e) {
    // логировать или пробросить дальше
}
```

---

## Реализация собственного драйвера

Для добавления нового бэкенда (S3, GCS, FTP и т.д.) реализуйте `StorageInterface`:

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
        // загрузить в S3 ...
    }

    public function get(string $path): string
    {
        // скачать из S3 ...
        return '';
    }

    public function stream(string $path): mixed
    {
        // вернуть поток ...
    }

    public function delete(string $path): void
    {
        // удалить из S3 ...
    }

    public function exists(string $path): bool
    {
        // проверить наличие в S3 ...
        return false;
    }
}
```

---

## Совместимость с KPHP

| Возможность | Статус |
|-------------|--------|
| Нет Reflection API | ✅ |
| Нет `eval()` | ✅ |
| Нет динамической загрузки классов (`new $class()`) | ✅ |
| Нет `str_starts_with` / `str_ends_with` / `str_contains` | ✅ используются `substr` / `strpos` |
| Нет Constructor Property Promotion | ✅ свойства объявлены явно |
| Нет `readonly` свойств | ✅ |
| `try/catch` всегда с хотя бы одним `catch` | ✅ |
| `file()` вызывается только с 1 аргументом | ✅ (не используется; вместо этого используется `file_get_contents`) |
| `stream()` возвращает `mixed` (тип `resource` в PHPDoc) | ✅ KPHP-совместимая сигнатура |
| `declare(strict_types=1)` в каждом файле | ✅ |

> Полное руководство по совместимости с KPHP — в [kphp-compatibility.md](./kphp-compatibility.md).

### Точка входа для KPHP

KPHP не поддерживает автозагрузку Composer. Файл `build/kphp-entrypoint.php` явно подключает все исходные файлы в порядке зависимостей:

```php
require_once __DIR__ . '/../src/StorageException.php';
require_once __DIR__ . '/../src/StorageInterface.php';
require_once __DIR__ . '/../src/LocalFilesystemStorage.php';
```

### Проверка совместимости с KPHP

```bash
# Собрать бинарник KPHP + PHAR
make kphp-check
# или напрямую:
docker build -f Dockerfile.check -t lphenom-storage-check .
```

Обе стадии сборки должны завершиться с кодом 0:
- **Стадия 1 (`kphp-build`)** — компиляция с `vkcom/kphp`, запуск бинарника.
- **Стадия 2 (`phar-build`)** — сборка PHAR с PHP 8.1, запуск smoke-теста.

---

## Разработка

```bash
make up      # Запустить Docker-окружение (PHP 8.1-alpine)
make test    # Запустить PHPUnit (28 тестов)
make lint    # Запустить PHPStan (уровень 9)
make down    # Остановить контейнеры
```
