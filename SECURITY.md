# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 0.1.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in `lphenom/storage`, please **do not** open a public GitHub issue.

Instead, please report it by email to: **popkovd.o@yandex.ru**

Please include:
- A description of the vulnerability
- Steps to reproduce it
- Potential impact
- Suggested fix (optional)

We will respond within 72 hours and aim to release a patch within 14 days.

## Security Considerations

### Path Traversal Protection

`LocalFilesystemStorage` guards against path traversal attacks by normalising all paths and ensuring they remain within the configured root directory. Any attempt to access files outside the root (e.g., via `../`) will throw a `StorageException`.

### Atomic Writes

`LocalFilesystemStorage::put()` uses atomic writes (write to a temp file, then rename) to prevent partial file reads during concurrent access.

