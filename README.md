# SortedLinkedList

Strictly typed, always-sorted singly linked list for PHP.

## Overview

This library provides a `SortedLinkedList` data structure that:
- keeps values in ascending order after every write operation,
- supports one type per instance: `int` or `string`,
- allows duplicates with stable order for equal values,
- exposes a small and predictable API.

Project specification:
- `requirements.md`
- `HLD.md`

## Requirements

- PHP `^8.2`
- Composer

## Installation

```bash
composer install
```

## Quick Start

```php
<?php

declare(strict_types=1);

use Zawiszaty\SortedLinkedList\SortedLinkedList;

$list = SortedLinkedList::forInt();
$list->addAll([3, 1, 2, 2]);

echo $list . PHP_EOL;         // [1, 2, 2, 3]
echo $list->first() . PHP_EOL; // 1
echo $list->last() . PHP_EOL;  // 3
```

String mode:

```php
$list = SortedLinkedList::forString();
$list->addAll(['pear', 'apple', 'banana']);
// ['apple', 'banana', 'pear']
```

Custom comparator:

```php
$list = SortedLinkedList::forInt(
    static fn (int $a, int $b): int => $b <=> $a
);

$list->addAll([1, 3, 2]); // [3, 2, 1]
```

## Public API

- `SortedLinkedList::forInt(?callable $comparator = null): self`
- `SortedLinkedList::forString(?callable $comparator = null): self`
- `add(int|string $value): void`
- `addAll(iterable<int|string> $values): void`
- `remove(int|string $value): bool`
- `removeAll(int|string $value): int`
- `removeAt(int $index): void`
- `contains(int|string $value): bool`
- `get(int $index): int|string`
- `first(): int|string`
- `last(): int|string`
- `clear(): void`
- `toArray(): array`
- `count(): int`
- `isEmpty(): bool`
- `getIterator(): Traversable`
- `__toString(): string`

## Exceptions

- `TypeMismatchException`
- `IndexOutOfBoundsException`
- `EmptyListException`

## Example Script

Run the example:

```bash
php examples/demo.php
```

## Performance Benchmarking

Run a quick terminal benchmark:

```bash
php benchmarks/performance.php
```

Generate machine-readable results:

```bash
php benchmarks/performance.php --json > benchmarks/current.json
```

Render a human-readable report from JSON:

```bash
php benchmarks/render-report.php benchmarks/current.json
```

Check performance regression against the baseline (`30%` threshold by default):

```bash
php benchmarks/check-regression.php benchmarks/current.json benchmarks/baseline.json
```

Use a custom regression threshold (example `20%`):

```bash
php benchmarks/check-regression.php benchmarks/current.json benchmarks/baseline.json 0.20
```

## Quality Gates

This repository is configured with:
- PHPUnit
- PHPStan (max level)
- Psalm
- PHP CS Fixer
- Infection (mutation testing)

Current mutation gate is configured in `infection.json5`:
- `minMsi: 80.0`
- `minCoveredMsi: 80.0`

## Development Commands

```bash
composer test
composer stan
composer psalm
composer cs:check
XDEBUG_MODE=coverage composer infection
```

## CI Pipeline

GitHub Actions workflow (`.github/workflows/ci.yml`) runs in this order:
1. `build`
2. static analysis in parallel: `phpstan`, `cs-check`, `psalm`
3. in parallel after static analysis: `tests` (PHP matrix) and `mutation-testing`

## Project Layout

- `src/` - library source code
- `tests/` - PHPUnit tests
- `examples/` - runnable usage demo
- `requirements.md` - requirements specification
- `HLD.md` - high-level design
