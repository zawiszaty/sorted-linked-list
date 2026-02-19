<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Zawiszaty\SortedLinkedList\SortedLinkedList;

$list = SortedLinkedList::forInt();
$list->add(5);
$list->add(2);
$list->add(4);
$list->add(2);

echo 'After insert: ' . $list . PHP_EOL;   // [2, 2, 4, 5]
echo 'Element #2: ' . $list->get(2) . PHP_EOL; // 4

$list->remove(2);
echo 'After remove(2): ' . $list . PHP_EOL; // [2, 4, 5]
