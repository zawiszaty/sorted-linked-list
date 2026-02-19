# SortedLinkedList Library
## Project Specification

---

# 1. Introduction

## 1.1 Purpose

The purpose of this project is to design and implement a reusable PHP library that provides a `SortedLinkedList` data structure.

The structure must:

- Maintain elements in ascending order at all times.
- Use a linked list implementation (not array-based).
- Support either `int` or `string` values per instance.
- Enforce strict type safety.
- Follow modern quality assurance standards (static analysis, mutation testing, CI).

---

## 1.2 Scope

The library is intended to be:

- Production-quality
- Fully tested
- Compatible with all currently supported PHP versions
- Verified through strict static analysis and mutation testing

---

# 2. Functional Requirements

## 2.1 Type Enforcement

1. Each list instance must store values of exactly one type:
   - `int`
   - or `string`
2. The type must be defined at instantiation.
3. Mixing types within a single list instance is forbidden.
4. Implicit type coercion is not allowed.
5. Inserting a value of an invalid type must result in an exception.

---

## 2.2 Sorting Behavior

1. The list must always maintain ascending order.
2. Sorting must be preserved incrementally during insertion.
3. Sorting rules:
   - Integers: numeric comparison.
   - Strings: lexicographical comparison.
4. The sorting invariant must never be violated by any public method.

---

## 2.3 Duplicate Handling

1. Duplicate values must be allowed.
2. The list must preserve stable insertion order for equal elements  
   (new duplicates appear after existing equal values).

---

## 2.4 Insertion Operations

The library must provide:

- `add(value): void`  
  Inserts a value while preserving sorted order.

- `addAll(iterable values): void`  
  Inserts multiple values.

For `addAll`:

- All elements must be validated before insertion.
- If any element is invalid, the operation must fail.
- Transactional behavior is recommended (no partial insertion).

---

## 2.5 Removal Operations

The library must provide:

- `remove(value): bool`  
  Removes the first occurrence and returns whether removal succeeded.

- `removeAll(value): int`  
  Removes all occurrences and returns the number removed.

- `removeAt(index): void`  
  Removes the element at the specified sorted index.  
  Must throw an exception if the index is out of bounds.

---

## 2.6 Query Operations

The library must provide:

- `contains(value): bool`
- `count(): int`
- `isEmpty(): bool`
- `get(index)`
- `first()`
- `last()`

Behavior for invalid index access must result in an exception.

Behavior of `first()` and `last()` on an empty list must be clearly defined  
(recommended: throw an exception).

---

## 2.7 Iteration and Export

1. The list must be iterable in ascending order.
2. It must implement appropriate PHP iteration interfaces.
3. The library must provide:
   - `toArray(): array`
4. A readable string representation must be available (e.g., `[1, 2, 3]`).

---

## 2.8 Custom Comparator (Optional Feature)

1. The library may allow passing a custom comparator.
2. The comparator must behave similarly to C++ comparison functions:
   - Return `< 0`, `0`, or `> 0`.
3. The comparator must be consistent and transitive.
4. If provided, the custom comparator overrides default comparison logic.

---

## 2.9 Encapsulation

1. Internal node structures must not be publicly accessible.
2. Direct manipulation of internal links must not be possible.
3. All state changes must occur through the public API.
4. The sorting invariant must remain guaranteed at all times.

---

## 2.10 Complexity Requirements

The implementation must reflect linked list characteristics:

- `add(value)` – O(n)
- `remove(value)` – O(n)
- `contains(value)` – O(n)
- `get(index)` – O(n)
- Memory complexity – O(n)

The implementation must not internally rely on array-based sorting.

---

# 3. Non-Functional Requirements

## 3.1 PHP Compatibility

1. The library must be compatible with all currently supported PHP versions.
2. CI must test the project on all supported versions.
3. The codebase must not use features unavailable in the lowest supported version.
4. The minimum supported version must be defined in `composer.json`.

---

## 3.2 Continuous Integration

The project must include automated CI pipelines triggered on:

- `push`
- `pull_request`

CI must perform:

1. Dependency installation
2. Unit tests execution
3. Static analysis
4. Code style validation
5. Mutation testing

The pipeline must fail if any step fails.

---

## 3.3 Static Analysis and Type Safety

### PHPStan

- Must run at the highest strictness level.
- Zero errors allowed.
- Public APIs must be fully typed.
- Avoid implicit `mixed`.

### Psalm

- Must run in strict mode.
- Zero errors allowed.
- Proper use of annotations (including generics if applicable).

### Type Inspections

- The project must support IDE/static inspections.
- No type-related warnings should be present.

Type correctness must be validated by at least two independent analyzers.

---

## 3.4 Code Style

- PHP-CS-Fixer must enforce coding standards.
- Formatting must be validated in CI.
- Formatting violations must fail CI.
- Rules must be version-controlled.

---

## 3.5 Unit Testing

1. Unit tests must cover:
   - Sorting correctness
   - Duplicate handling
   - Type validation
   - Exceptions
   - Edge cases
   - Custom comparator (if implemented)
2. Minimum code coverage threshold must be defined (e.g., ≥ 80%).

---

## 3.6 Mutation Testing

1. The project must include mutation testing.
2. Mutation testing must be executed in CI.
3. A minimum mutation score threshold must be defined.
4. CI must fail if the mutation score falls below the threshold.

---

## 3.7 API Quality and Maintainability

1. API must follow PHP conventions (e.g., `Countable`, `IteratorAggregate`).
2. Edge-case behavior must be documented.
3. Exceptions must be consistent and well-defined.
4. No silent type coercion allowed.
5. Internal structure must remain encapsulated.
6. The sorting invariant must always be preserved.

---

# 4. Definition of Done

The project is considered complete only if:

- All supported PHP versions pass CI.
- All unit tests pass.
- Code coverage threshold is met.
- Mutation score threshold is met.
- PHPStan passes at maximum level.
- Psalm passes in strict mode.
- Code style validation passes.
- No type inspection warnings remain.
