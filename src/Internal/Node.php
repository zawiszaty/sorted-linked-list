<?php

declare(strict_types=1);

namespace Zawiszaty\SortedLinkedList\Internal;

final class Node
{
    public int|string $value;

    public ?self $next;

    public function __construct(int|string $value, ?self $next = null)
    {
        $this->value = $value;
        $this->next = $next;
    }
}
