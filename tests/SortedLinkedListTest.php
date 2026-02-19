<?php

declare(strict_types=1);

namespace Zawiszaty\SortedLinkedList\Tests;

use PHPUnit\Framework\TestCase;
use Zawiszaty\SortedLinkedList\Exception\EmptyListException;
use Zawiszaty\SortedLinkedList\Exception\IndexOutOfBoundsException;
use Zawiszaty\SortedLinkedList\Exception\TypeMismatchException;
use Zawiszaty\SortedLinkedList\SortedLinkedList;

final class SortedLinkedListTest extends TestCase
{
    public function testItKeepsElementsSortedAndStableForDuplicates(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([3, 1, 2, 2]);

        self::assertSame([1, 2, 2, 3], $list->toArray());
        self::assertSame(4, $list->count());
    }

    public function testStringListUsesLexicographicalOrder(): void
    {
        $list = SortedLinkedList::forString();
        $list->addAll(['pear', 'apple', 'banana', 'banana']);

        self::assertSame(['apple', 'banana', 'banana', 'pear'], $list->toArray());
        self::assertSame('apple', $list->first());
        self::assertSame('pear', $list->last());
    }

    public function testAddRejectsInvalidType(): void
    {
        $list = SortedLinkedList::forInt();

        $this->expectException(TypeMismatchException::class);
        $list->add('1');
    }

    public function testAddAllIsTransactionalWhenAnyValueHasInvalidType(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 3]);

        try {
            $list->addAll([2, 'x']);
            self::fail('TypeMismatchException was not thrown.');
        } catch (TypeMismatchException) {
            self::assertSame([1, 3], $list->toArray());
        }
    }

    public function testRemoveRemovesOnlyFirstOccurrence(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([2, 2, 2, 3]);

        self::assertTrue($list->remove(2));
        self::assertSame([2, 2, 3], $list->toArray());
        self::assertFalse($list->remove(99));
    }

    public function testRemoveAllReturnsNumberOfRemovedElements(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([2, 1, 2, 3, 2]);

        self::assertSame(3, $list->removeAll(2));
        self::assertSame([1, 3], $list->toArray());
        self::assertSame(0, $list->removeAll(99));
    }

    public function testRemoveAtRemovesElementBySortedIndex(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([3, 1, 2]);

        $list->removeAt(1);
        self::assertSame([1, 3], $list->toArray());
    }

    public function testRemoveAtThrowsWhenIndexIsOutOfBounds(): void
    {
        $list = SortedLinkedList::forInt();
        $list->add(1);

        $this->expectException(IndexOutOfBoundsException::class);
        $list->removeAt(1);
    }

    public function testGetReturnsValueByIndex(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([10, 7, 9]);

        self::assertSame(7, $list->get(0));
        self::assertSame(9, $list->get(1));
        self::assertSame(10, $list->get(2));
    }

    public function testGetThrowsForInvalidIndex(): void
    {
        $list = SortedLinkedList::forInt();

        $this->expectException(IndexOutOfBoundsException::class);
        $list->get(0);
    }

    public function testFirstAndLastThrowForEmptyList(): void
    {
        $list = SortedLinkedList::forInt();

        try {
            $list->first();
            self::fail('EmptyListException was not thrown for first().');
        } catch (EmptyListException) {
        }

        $this->expectException(EmptyListException::class);
        $list->last();
    }

    public function testContainsAndClearAndIsEmpty(): void
    {
        $list = SortedLinkedList::forInt();
        self::assertTrue($list->isEmpty());

        $list->addAll([4, 1]);
        self::assertTrue($list->contains(1));
        self::assertFalse($list->contains(9));
        self::assertFalse($list->isEmpty());

        $list->clear();
        self::assertSame([], $list->toArray());
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());
    }

    public function testContainsRejectsInvalidType(): void
    {
        $list = SortedLinkedList::forString();
        $list->add('a');

        $this->expectException(TypeMismatchException::class);
        $list->contains(1);
    }

    public function testListIsIterableInAscendingOrder(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([5, 1, 3]);

        $iterated = [];
        foreach ($list as $value) {
            $iterated[] = $value;
        }

        self::assertSame([1, 3, 5], $iterated);
    }

    public function testToStringIsReadable(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([2, 1, 3]);

        self::assertSame('[1, 2, 3]', (string) $list);
    }

    public function testCustomComparatorOverridesDefaultOrdering(): void
    {
        $list = SortedLinkedList::forInt(
            static fn (int $left, int $right): int => $right <=> $left
        );

        $list->addAll([1, 3, 2]);

        self::assertSame([3, 2, 1], $list->toArray());
    }

    public function testStableInsertionForComparatorEqualValues(): void
    {
        $list = SortedLinkedList::forInt(
            static fn (int $left, int $right): int => intdiv($left, 10) <=> intdiv($right, 10)
        );

        $list->add(12);
        $list->add(11);
        $list->add(5);
        $list->add(19);
        $list->add(18);

        self::assertSame([5, 12, 11, 19, 18], $list->toArray());
    }

    public function testRemoveRejectsInvalidType(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 2, 3]);

        $this->expectException(TypeMismatchException::class);
        $list->remove('x');
    }

    public function testRemoveAllRejectsInvalidType(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 2, 3]);

        $this->expectException(TypeMismatchException::class);
        $list->removeAll('x');
    }

    public function testRemoveHeadKeepsTailAndDecrementsCount(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 2]);

        self::assertTrue($list->remove(1));
        self::assertSame(1, $list->count());
        self::assertSame(2, $list->last());
    }

    public function testRemoveMiddleKeepsTailAndDecrementsCount(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 2, 3]);

        self::assertTrue($list->remove(2));
        self::assertSame(2, $list->count());
        self::assertSame(3, $list->last());
        self::assertSame([1, 3], $list->toArray());
    }

    public function testRemoveAtZeroRemovesHead(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 2, 3]);

        $list->removeAt(0);
        self::assertSame([2, 3], $list->toArray());
        self::assertSame(2, $list->count());
        self::assertSame(3, $list->last());
    }

    public function testRemoveAtLastUpdatesTailAndCount(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 2, 3]);

        $list->removeAt(2);
        self::assertSame([1, 2], $list->toArray());
        self::assertSame(2, $list->count());
        self::assertSame(2, $list->last());
    }

    public function testAddAllRejectsNonScalarTypeWithDomainException(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 2]);

        try {
            /** @psalm-suppress InvalidArgument */
            /** @phpstan-ignore-next-line */
            $list->addAll([3, true]);
            self::fail('TypeMismatchException was not thrown.');
        } catch (TypeMismatchException) {
            self::assertSame([1, 2], $list->toArray());
        }
    }

    public function testGetThrowsForNegativeIndex(): void
    {
        $list = SortedLinkedList::forInt();
        $list->addAll([1, 2]);

        $this->expectException(IndexOutOfBoundsException::class);
        $list->get(-1);
    }
}
