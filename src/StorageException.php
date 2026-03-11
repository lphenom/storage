<?php

declare(strict_types=1);

namespace LPhenom\Storage;

/**
 * StorageException — base exception for all storage errors.
 *
 * Thrown by all StorageInterface implementations on failure.
 * Inherits from RuntimeException for easy catching at higher levels.
 */
final class StorageException extends \RuntimeException
{
}

