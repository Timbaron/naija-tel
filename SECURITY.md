# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |

## Privacy and Data Handling

NaijaTel processes Nigerian mobile phone numbers. The package is built with security and privacy by design:
- Phone numbers are never stored in plain text cache keys; deterministic SHA-256 hashes are used.
- Logging masks sensitive digits (e.g., `+234 803 *** *567`) by default.
- External network requests to MNP services use encrypted HTTPS transports with strict request timeouts.

## Reporting a Vulnerability

If you discover a security vulnerability in NaijaTel, please do not open a public issue. Email security reports directly to `timothy@akiode.com`.
