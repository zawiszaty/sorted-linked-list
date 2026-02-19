<?php

declare(strict_types=1);

namespace Zawiszaty\SortedLinkedList\Internal;

final class Node
{
    public function __construct(
        public int|string $value,
        public ?self $next = null
    ) {
    }
}
