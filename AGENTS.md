# AGENTS

## Minimal Project Description
PHP `SortedLinkedList` library:
- singly linked list always kept in ascending order,
- each instance stores exactly one type: `int` or `string`,
- quality-focused: tests, static analysis, code style, mutation testing.

## Context (Most Important Files)
- Requirements: `requirements.md`
- High-level design: `HLD.md`

## Starter Structure
- Code: `src/`
- Tests: `tests/`
- Tool configs:
  - PHPUnit: `phpunit.xml`
  - PHPStan: `phpstan.neon`
  - Psalm: `psalm.xml`
  - CS Fixer: `.php-cs-fixer.dist.php`
  - Infection: `infection.json5`

## Quick Start
1. Install dependencies:
   - `composer install`
2. Run tests:
   - `composer test`
3. Run quality checks:
   - `composer stan`
   - `composer psalm`
   - `composer cs:check`
   - `composer infection`

## Working Rules
- Treat `requirements.md` as the source of truth.
- Align API and architecture with `HLD.md`.
- Prefer updating/adding tests before implementation changes.
