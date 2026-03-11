# Contributing to lphenom/storage

Thank you for considering contributing to `lphenom/storage`! This document outlines the process for contributing.

## Getting Started

1. Fork the repository.
2. Clone your fork: `git clone git@github.com:<your-username>/storage.git`
3. Start the development environment: `make up`
4. Install dependencies (already done in Docker): `make shell` → `composer install`

## Development Environment

All tooling runs **inside Docker** — you do not need PHP, Composer, or any other tool installed on your machine.

```bash
make up      # Start containers
make down    # Stop containers
make test    # Run PHPUnit tests
make lint    # Run PHPStan static analysis
make shell   # Open shell in PHP container
```

## Code Standards

### PHP Version

- Minimum PHP 8.1
- `declare(strict_types=1);` must be the first line of every PHP file

### KPHP Compatibility

This package must compile with KPHP. The following are **forbidden**:

- `new $className()` — dynamic class instantiation
- `ReflectionClass`, `ReflectionMethod` and all Reflection API
- `eval(...)`
- `$$varName` — variable variables
- Constructor property promotion (`public function __construct(private string $x)`)
- `readonly` properties
- `str_starts_with()`, `str_ends_with()`, `str_contains()` — use `substr()`/`strpos()` instead
- `callable` stored in typed arrays — use an interface instead
- `try/finally` without at least one `catch`
- `file()` with flags — use only one argument

### Code Style

- All classes must have `declare(strict_types=1);`
- All properties must be explicitly declared with type hints
- All public API methods must have PHPDoc comments
- Arrays must have typed PHPDoc: `@var array<string, SomeType>`

## Testing

Before submitting a PR:

```bash
make test   # All tests must pass
make lint   # PHPStan at level 9, no errors
```

For KPHP compatibility check:

```bash
make kphp-check   # Must produce exit code 0
```

## Commit Guidelines

We use [Conventional Commits](https://www.conventionalcommits.org/):

- `feat(storage): add S3 driver`
- `fix(storage): handle empty files in get()`
- `test(storage): add path traversal edge cases`
- `docs(storage): update storage.md`
- `chore: update dependencies`

## Pull Request Process

1. Create a branch from `main`: `git checkout -b feat/my-feature`
2. Make your changes with small, focused commits
3. Run `make test lint` — all checks must pass
4. Push and open a PR against `main`
5. A maintainer will review within a few days

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).

