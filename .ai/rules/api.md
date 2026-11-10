---
paths:
  - 'app/Http/Controllers/Api/**/*.php'
---

# Api

## Document API fields with @example
On each key of an API response array, add a PHPDoc `@example` with a realistic sample so Scramble does not invent placeholders such as "string" or 0. Match the field type: quoted strings for strings, an unquoted integer for integers. See ServerTimeController.
