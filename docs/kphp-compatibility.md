# Руководство по совместимости с KPHP — lphenom/storage

Данный документ описывает все ограничения KPHP, применённые в `lphenom/storage`,
и паттерны, используемые для их соблюдения.

---

## Как работает компиляция KPHP

KPHP (`vkcom/kphp`) компилирует PHP-исходники в **статический C++ бинарник**:

- KPHP **не использует** PHP runtime при компиляции — у него есть собственный парсер PHP.
- Скомпилированный бинарник **не зависит** от PHP.
- KPHP использует строгий вывод типов и отказывается компилировать неоднозначный или динамический код.
- Docker-образ `vkcom/kphp` основан на Ubuntu 20.04 focal с инструментарием PHP 7.4.

> **Важно:** Минимальная версия PHP для **разработки/runtime** — 8.1.  
> PHP 7.4 внутри Docker-образа KPHP — это только инструментарий компилятора, а не ваш runtime.

---

## Запрещённые конструкции

### 1. `str_starts_with` / `str_ends_with` / `str_contains`

Эти функции PHP 8.0+ **не реализованы в KPHP**.

```php
// ❌ ЗАПРЕЩЕНО
if (str_starts_with($path, '/')) { ... }

// ✅ ПРАВИЛЬНО
if (substr($path, 0, 1) === '/') { ... }
if (strpos($path, 'needle') !== false) { ... }
```

В `LocalFilesystemStorage::resolvePath()` все проверки строк используют `substr()` и `strpos()`.

---

### 2. Constructor Property Promotion

```php
// ❌ ЗАПРЕЩЕНО в KPHP
final class LocalFilesystemStorage {
    public function __construct(private string $root) {}
}

// ✅ ПРАВИЛЬНО
final class LocalFilesystemStorage {
    private string $root;

    public function __construct(string $root) {
        $this->root = rtrim($root, '/\\');
    }
}
```

---

### 3. `readonly` свойства

```php
// ❌ ЗАПРЕЩЕНО в KPHP
private readonly string $root;

// ✅ ПРАВИЛЬНО
private string $root;
```

---

### 4. Конструктор исключения — без аргумента `$previous`

Конструктор `\Exception` в KPHP поддерживает только 2 аргумента: `(string $message, int $code)`.
Третий аргумент `$previous` (цепочка предыдущих исключений) **не поддерживается**.

```php
// ❌ ЗАПРЕЩЕНО в KPHP
throw new StorageException('error', 0, $e);

// ✅ ПРАВИЛЬНО — включить причину в строку сообщения
throw new StorageException('error: ' . $e->getMessage());
```

---

### 5. `try/finally` без `catch`

KPHP требует наличия хотя бы одного блока `catch`.

```php
// ❌ ЗАПРЕЩЕНО
try {
    $result = file_put_contents($tmp, $bytes);
} finally {
    if (is_file($tmp)) { unlink($tmp); }
}

// ✅ ПРАВИЛЬНО — используется в LocalFilesystemStorage::put()
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

### 6. Сужение типов через `!isset() + throw`

KPHP не сужает типы после `!isset() + throw`. Используйте явное присваивание null:

```php
// ❌ KPHP не сужает тип $val после этого
if (!isset($this->map[$key])) { throw new Exception(); }
$val = $this->map[$key]; // тип всё ещё ?T в KPHP

// ✅ ПРАВИЛЬНО — используется повсеместно в LocalFilesystemStorage
$val = $this->map[$key] ?? null;
if ($val === null) { throw new StorageException('...'); }
```

---

### 7. Reflection API

Не используется нигде в этом пакете. Всё поведение явное — нет `ReflectionClass`,
нет `get_class()`, нет `class_exists()`.

---

### 8. `eval()` и динамическая загрузка классов

Не используется. Нет `new $className()`, нет `call_user_func()` со строковыми именами классов.

---

### 9. `file()` с флагами

KPHP поддерживает `file()` только с одним аргументом.

В этом пакете `file()` не используется — содержимое читается через `file_get_contents()`.

---

### 10. Возвращаемый тип `stream()`

KPHP имеет ограниченную поддержку `resource` как возвращаемого типа в интерфейсах.
Метод объявлен как `mixed` в сигнатуре интерфейса с `@return resource` в PHPDoc:

```php
/**
 * @return resource
 */
public function stream(string $path): mixed;
```

Метод `stream()` включён в точку входа KPHP, но помечен как опциональный
для KPHP-вызывающих, которым не нужна потоковая передача.

---

## Точка входа для KPHP

KPHP не поддерживает PSR-4 автозагрузку Composer.
Файл `build/kphp-entrypoint.php` явно подключает все исходные файлы
в порядке зависимостей (исключения и интерфейсы — перед классами):

```php
require_once __DIR__ . '/../src/StorageException.php';
require_once __DIR__ . '/../src/StorageInterface.php';
require_once __DIR__ . '/../src/LocalFilesystemStorage.php';
```

---

## Допустимые конструкции (совместимые с KPHP)

| Конструкция | Статус |
|-------------|--------|
| `declare(strict_types=1)` | ✅ |
| `final class` / `interface` | ✅ |
| `?Type` nullable, `int\|string` union | ✅ |
| `array<K, V>` в PHPDoc | ✅ |
| `new ClassName()` (явное, не динамическое) | ✅ |
| `try/catch` с хотя бы одним `catch` | ✅ |
| `instanceof` | ✅ |
| `substr()`, `strpos()`, `strlen()` | ✅ |
| `file_get_contents()`, `file_put_contents()` | ✅ |
| `fopen()`, `fclose()` | ✅ |
| `is_file()`, `is_dir()`, `mkdir()`, `unlink()`, `rename()` | ✅ |
| `uniqid()`, `dirname()`, `explode()`, `implode()` | ✅ |
| `sys_get_temp_dir()` | ✅ |

---

## Проверка

```bash
docker build -f Dockerfile.check -t lphenom-storage-check .
```

- **Стадия `kphp-build`**: компиляция с `vkcom/kphp`, запуск бинарника от имени непривилегированного пользователя.
- **Стадия `phar-build`**: сборка PHAR с PHP 8.1, запуск smoke-теста.

Обе стадии должны завершиться с кодом 0.

---

## Ссылки

- [Отличия KPHP от PHP](https://vkcom.github.io/kphp/kphp-language/kphp-vs-php/whats-the-difference.html)
- [Docker-образ KPHP](https://hub.docker.com/r/vkcom/kphp)
- [lphenom/storage — storage.md](./storage.md)

