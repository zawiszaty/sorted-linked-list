<?php

declare(strict_types=1);

namespace Zawiszaty\SortedLinkedList;

use Closure;
use Countable;
use IteratorAggregate;
use Zawiszaty\SortedLinkedList\Exception\EmptyListException;
use Zawiszaty\SortedLinkedList\Exception\IndexOutOfBoundsException;
use Zawiszaty\SortedLinkedList\Exception\TypeMismatchException;
use Zawiszaty\SortedLinkedList\Internal\Node;
use Traversable;
use InvalidArgumentException;

/**
 * Sorted singly linked list that maintains ascending order after every mutation.
 *
 * Each instance stores exactly one value type (`int` or `string`) selected
 * through factory methods.
 *
 * @author Zawiszaty
 * @package Zawiszaty\SortedLinkedList
 * @implements IteratorAggregate<int, int|string>
 */
final class SortedLinkedList implements Countable, IteratorAggregate
{
    private const TYPE_INT = 'int';
    private const TYPE_STRING = 'string';

    private ?Node $head = null;
    private ?Node $tail = null;
    private int $size = 0;
    private Closure $compare;

    private function __construct(private string $type, ?callable $comparator = null)
    {
        $this->compare = $comparator !== null
            ? Closure::fromCallable($comparator)
            : $this->defaultComparator($this->type);
    }

    /**
     * Creates a list instance that accepts only integers.
     *
     * @param callable(int, int): int|null $comparator Optional comparator that returns <0, 0 or >0.
     */
    public static function forInt(?callable $comparator = null): self
    {
        return new self(self::TYPE_INT, $comparator);
    }

    /**
     * Creates a list instance that accepts only strings.
     *
     * @param callable(string, string): int|null $comparator Optional comparator that returns <0, 0 or >0.
     */
    public static function forString(?callable $comparator = null): self
    {
        return new self(self::TYPE_STRING, $comparator);
    }

    /**
     * Inserts a value while preserving sorted order.
     *
     * Duplicate values are appended after existing equal values.
     *
     * @throws TypeMismatchException When the value type does not match the list type.
     * @throws InvalidArgumentException When comparator returns a non-int value.
     */
    public function add(int|string $value): void
    {
        $this->assertValueType($value);
        $node = new Node($value);

        if ($this->head === null) {
            $this->head = $node;
            $this->tail = $node;
            $this->size++;
            return;
        }

        if ($this->compareValues($value, $this->head->value) < 0) {
            $node->next = $this->head;
            $this->head = $node;
            $this->size++;
            return;
        }

        $current = $this->head;
        while ($current->next !== null && $this->compareValues($value, $current->next->value) >= 0) {
            $current = $current->next;
        }

        $node->next = $current->next;
        $current->next = $node;

        if ($node->next === null) {
            $this->tail = $node;
        }

        $this->size++;
    }

    /**
     * Inserts multiple values while preserving sorted order.
     *
     * All values are validated first; if any value is invalid, nothing is inserted.
     *
     * @param iterable<int|string> $values Values to insert.
     *
     * @throws TypeMismatchException When at least one value type does not match the list type.
     * @throws InvalidArgumentException When comparator returns a non-int value.
     */
    public function addAll(iterable $values): void
    {
        $validated = [];
        foreach ($values as $value) {
            $validated[] = $this->normalizeValueForList($value);
        }

        foreach ($validated as $value) {
            $this->add($value);
        }
    }

    /**
     * Removes the first occurrence of a value.
     *
     * @return bool True when an element was removed, false when value was not found.
     *
     * @throws TypeMismatchException When the value type does not match the list type.
     * @throws InvalidArgumentException When comparator returns a non-int value.
     */
    public function remove(int|string $value): bool
    {
        $this->assertValueType($value);
        if ($this->head === null) {
            return false;
        }

        if ($this->compareValues($this->head->value, $value) === 0) {
            $this->head = $this->head->next;
            if ($this->head === null) {
                $this->tail = null;
            }
            $this->size--;
            return true;
        }

        $previous = $this->head;
        $current = $this->head->next;

        while ($current !== null) {
            if ($this->compareValues($current->value, $value) === 0) {
                $previous->next = $current->next;
                if ($current->next === null) {
                    $this->tail = $previous;
                }
                $this->size--;
                return true;
            }
            $previous = $current;
            $current = $current->next;
        }

        return false;
    }

    /**
     * Removes all occurrences of a value.
     *
     * @return int Number of removed elements.
     *
     * @throws TypeMismatchException When the value type does not match the list type.
     * @throws InvalidArgumentException When comparator returns a non-int value.
     */
    public function removeAll(int|string $value): int
    {
        $this->assertValueType($value);
        if ($this->head === null) {
            return 0;
        }

        $removed = 0;

        while ($this->head !== null && $this->compareValues($this->head->value, $value) === 0) {
            $this->head = $this->head->next;
            $this->size--;
            $removed++;
        }

        if ($this->head === null) {
            $this->tail = null;
            return $removed;
        }

        $previous = $this->head;
        $current = $this->head->next;

        while ($current !== null) {
            $comparison = $this->compareValues($current->value, $value);

            if ($comparison === 0) {
                $previous->next = $current->next;
                $this->size--;
                $removed++;
                $current = $previous->next;
                continue;
            }

            // List is sorted, so once we pass searched value we can stop.
            if ($comparison > 0) {
                break;
            }

            $previous = $current;
            $current = $current->next;
        }

        if ($previous->next === null) {
            $this->tail = $previous;
        }

        return $removed;
    }

    /**
     * Removes an element at the given sorted index.
     *
     * @throws IndexOutOfBoundsException When index is outside list bounds.
     */
    public function removeAt(int $index): void
    {
        $this->assertValidIndex($index);

        if ($index === 0) {
            $this->head = $this->head?->next;
            if ($this->head === null) {
                $this->tail = null;
            }
            $this->size--;
            return;
        }

        $previous = $this->nodeAt($index - 1);
        $target = $previous->next;
        $previous->next = $target?->next;

        if ($previous->next === null) {
            $this->tail = $previous;
        }

        $this->size--;
    }

    /**
     * Checks whether the list contains a value.
     *
     * @throws TypeMismatchException When the value type does not match the list type.
     * @throws InvalidArgumentException When comparator returns a non-int value.
     */
    public function contains(int|string $value): bool
    {
        $this->assertValueType($value);
        foreach ($this as $existing) {
            if ($this->compareValues($existing, $value) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the number of elements in the list.
     *
     * @return int<0, max>
     */
    public function count(): int
    {
        return max(0, $this->size);
    }

    /**
     * Returns whether the list is empty.
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Returns an element at the given sorted index.
     *
     * @throws IndexOutOfBoundsException When index is outside list bounds.
     */
    public function get(int $index): int|string
    {
        return $this->nodeAt($index)->value;
    }

    /**
     * Returns the first (smallest) element.
     *
     * @throws EmptyListException When the list is empty.
     */
    public function first(): int|string
    {
        if ($this->head === null) {
            throw new EmptyListException('Cannot read first element from an empty list.');
        }
        return $this->head->value;
    }

    /**
     * Returns the last (greatest) element.
     *
     * @throws EmptyListException When the list is empty.
     */
    public function last(): int|string
    {
        if ($this->tail === null) {
            throw new EmptyListException('Cannot read last element from an empty list.');
        }
        return $this->tail->value;
    }

    /**
     * Removes all elements from the list.
     */
    public function clear(): void
    {
        $this->head = null;
        $this->tail = null;
        $this->size = 0;
    }

    /**
     * Exports list values to a plain PHP array in sorted order.
     *
     * @return array<int, int|string>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this as $value) {
            $result[] = $value;
        }
        return $result;
    }

    /**
     * Returns a readable string representation, e.g. "[1, 2, 3]".
     */
    public function __toString(): string
    {
        return '[' . implode(', ', array_map(static fn (int|string $value): string => (string) $value, $this->toArray())) . ']';
    }

    /**
     * Returns an iterator that yields values in sorted order.
     *
     * @return Traversable<int, int|string>
     */
    public function getIterator(): Traversable
    {
        $current = $this->head;
        while ($current !== null) {
            yield $current->value;
            $current = $current->next;
        }
    }

    private function defaultComparator(string $type): Closure
    {
        if ($type === self::TYPE_INT) {
            return static function (int $a, int $b): int {
                return $a <=> $b;
            };
        }

        return static function (string $a, string $b): int {
            return strcmp($a, $b);
        };
    }

    private function compareValues(int|string $left, int|string $right): int
    {
        $result = ($this->compare)($left, $right);
        if (!is_int($result)) {
            throw new InvalidArgumentException('Comparator must return int.');
        }
        return $result;
    }

    private function assertValueType(int|string $value): void
    {
        if ($this->type === self::TYPE_INT) {
            if (!is_int($value)) {
                throw new TypeMismatchException(sprintf('Expected value of type int, got %s.', get_debug_type($value)));
            }
            return;
        }

        if (!is_string($value)) {
            throw new TypeMismatchException(sprintf('Expected value of type string, got %s.', get_debug_type($value)));
        }
    }

    private function normalizeValueForList(mixed $value): int|string
    {
        if (!is_int($value) && !is_string($value)) {
            throw new TypeMismatchException(sprintf('Expected value of type int|string, got %s.', get_debug_type($value)));
        }

        $this->assertValueType($value);

        return $value;
    }

    private function assertValidIndex(int $index): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new IndexOutOfBoundsException(sprintf('Index %d is out of bounds.', $index));
        }
    }

    private function nodeAt(int $index): Node
    {
        $this->assertValidIndex($index);
        $current = $this->head;
        for ($i = 0; $i < $index; $i++) {
            $current = $current?->next;
        }

        if ($current === null) {
            throw new IndexOutOfBoundsException(sprintf('Index %d is out of bounds.', $index));
        }

        return $current;
    }
}
