# High-Level Design (HLD) --- SortedLinkedList (PHP)

## 1. Overview

This library provides a `SortedLinkedList` data structure that maintains
values in ascending order at all times.\
Each list instance stores **exactly one value type**: `int` **or**
`string`. Mixing types is not allowed.

Key goals: - Always sorted after every modifying operation - Strict type
enforcement (no coercion) - Clean, predictable API aligned with PHP
conventions - High code quality verified by CI (tests, static analysis,
formatting, mutation testing)

------------------------------------------------------------------------

## 2. Public API (High-Level)

### 2.1 Main Class

`SortedLinkedList`

Core operations: - `add(int|string $value): void` -
`addAll(iterable $values): void` - `remove(int|string $value): bool` -
`contains(int|string $value): bool` - `get(int $index): int|string` -
`first(): int|string` - `last(): int|string` - `clear(): void` -
`isEmpty(): bool` - `toArray(): array`

PHP integration: - Implements `Countable` (`count(): int`) - Implements
`IteratorAggregate` (`getIterator(): Traversable`)

### 2.2 Construction / Factories

To ensure type safety, the instance type is chosen explicitly: -
`SortedLinkedList::forInt(?callable $comparator = null): self` -
`SortedLinkedList::forString(?callable $comparator = null): self`

Comparator signature (C++-like): - `callable($a, $b): int` where `<0`,
`0`, `>0`

------------------------------------------------------------------------

## 3. Architecture & Components

### 3.1 Package Layout (Proposed)

    src/
      SortedLinkedList.php
      Internal/
        Node.php
        Comparators.php
      Exception/
        TypeMismatchException.php
        EmptyListException.php
        IndexOutOfBoundsException.php
    tests/
      ...

Design rule: everything under `Internal/` is not part of the public API.

------------------------------------------------------------------------

## 4. Data Model

### 4.1 Node (Internal)

Represents a single linked list element.

Fields: - `public int|string $value` - `public ?Node $next`

### 4.2 SortedLinkedList Internal State

Fields: - `private ?Node $head` - `private ?Node $tail` -
`private int $size` - `private string $type` (INT or STRING) -
`private callable $compare`

------------------------------------------------------------------------

## 5. Core Algorithms (High-Level)

### 5.1 Insertion (`add`)

1.  Validate type of `$value`.
2.  If list empty → set head and tail.
3.  If value should be before head → insert at head.
4.  Traverse until correct insertion point.
5.  Insert node.
6.  Update tail if needed.
7.  Increment size.

Time complexity: O(n)

### 5.2 Removal (`remove`)

1.  Validate type.
2.  If head matches → remove head.
3.  Otherwise traverse with previous pointer.
4.  Relink nodes.
5.  Update tail if needed.
6.  Decrement size.

Time complexity: O(n)

### 5.3 Access by Index (`get`)

Traverse from head until reaching the given index.\
Throw `IndexOutOfBoundsException` if invalid.

Time complexity: O(n)

------------------------------------------------------------------------

## 6. Type System & Validation Strategy

-   All files use `declare(strict_types=1);`
-   Runtime type validation using `is_int()` / `is_string()`
-   `null` values are rejected
-   On mismatch → throw `TypeMismatchException`

------------------------------------------------------------------------

## 7. Comparator Design

### 7.1 Default Comparators

-   Integers → numeric comparison
-   Strings → lexicographic comparison

### 7.2 Custom Comparator

If provided, it replaces the default comparator.

Requirements: - Must return `< 0`, `0`, or `> 0` - Must be consistent
and transitive

------------------------------------------------------------------------

## 8. Iteration & Export

-   Implements `IteratorAggregate`
-   Uses generator (`yield`) to iterate from head to tail
-   `toArray()` returns a copy of list values

------------------------------------------------------------------------

## 9. Error Handling & Exceptions

Recommended custom exceptions: - `TypeMismatchException` -
`IndexOutOfBoundsException` - `EmptyListException`

Rules: - No silent failures - `remove(value)` returns `false` if value
not found

------------------------------------------------------------------------

## 10. Quality Gates (CI-Level)

CI must enforce: - Unit tests passing - PHPStan (max level) passing -
Psalm (strict mode) passing - PHP-CS-Fixer passing - Mutation testing
threshold met

------------------------------------------------------------------------

## 11. Key Design Decisions

1.  Explicit factory methods for type safety.
2.  Stable duplicate insertion.
3.  Internal node encapsulation.
4.  Comparator abstraction.
5.  Integration with PHP interfaces (`Countable`, `IteratorAggregate`).

------------------------------------------------------------------------

## 12. Non-Goals

-   O(1) random access
-   Automatic type coercion
-   Multi-type list instances
-   Persistent storage guarantees
