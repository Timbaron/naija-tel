---
name: Bug Report
about: Create a report to help us improve NaijaTel
title: '[BUG] '
labels: bug
assignees: ''
---

### Describe the Bug
A clear and concise description of what the bug is.

### Phone Number or Prefix (Masked)
If applicable, provide the phone number prefix or masked number (e.g. `0803 *** *567` or prefix `0803`). **DO NOT include full private phone numbers.**

### Expected Behavior
A clear and concise description of what you expected to happen.

### Actual Behavior
What actually happened (include exception traces or return values).

### Context & Environment
- NaijaTel Version: [e.g. 0.1.0]
- Laravel Version: [e.g. 11.x, 12.x]
- PHP Version: [e.g. 8.2, 8.3, 8.4]
- Cache Driver: [e.g. redis, array, file]
- MNP Provider (if applicable): [e.g. fake, icn, custom]

### Steps to Reproduce
Steps to reproduce the behavior:
```php
use NaijaTel\Facades\NaijaTel;

$result = NaijaTel::detect('...');
```
