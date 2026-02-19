<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Zawiszaty\SortedLinkedList\SortedLinkedList;

/**
 * @return array{time_ms: float, mem_mb: float}
 */
function bench(callable $fn): array
{
    gc_collect_cycles();
    $memBefore = memory_get_usage(true);
    $start = hrtime(true);
    $fn();
    $end = hrtime(true);
    $memAfter = memory_get_usage(true);

    return [
        'time_ms' => ($end - $start) / 1_000_000,
        'mem_mb' => max(0, ($memAfter - $memBefore) / 1024 / 1024),
    ];
}

/**
 * @param list<float> $values
 */
function stats(array $values): array
{
    sort($values);
    $count = count($values);
    $sum = array_sum($values);
    $avg = $count > 0 ? $sum / $count : 0.0;
    $median = $count === 0
        ? 0.0
        : ($count % 2 === 0
            ? ($values[(int) ($count / 2) - 1] + $values[(int) ($count / 2)]) / 2
            : $values[(int) floor($count / 2)]);

    return [
        'runs' => $values,
        'min' => $count > 0 ? $values[0] : 0.0,
        'max' => $count > 0 ? $values[$count - 1] : 0.0,
        'avg' => $avg,
        'median' => $median,
    ];
}

/**
 * @return array{
 *   time_ms: array{runs: list<float>, min: float, max: float, avg: float, median: float},
 *   mem_mb: array{runs: list<float>, min: float, max: float, avg: float, median: float}
 * }
 */
function benchStats(callable $fn, int $repeats, int $warmup): array
{
    for ($i = 0; $i < $warmup; $i++) {
        $fn();
    }

    $times = [];
    $memories = [];
    for ($i = 0; $i < $repeats; $i++) {
        $result = bench($fn);
        $times[] = $result['time_ms'];
        $memories[] = $result['mem_mb'];
    }

    return [
        'time_ms' => stats($times),
        'mem_mb' => stats($memories),
    ];
}

/**
 * @return array<int>
 */
function makeShuffledInts(int $size): array
{
    $data = range(1, $size);
    shuffle($data);

    return $data;
}

/**
 * @return array<string>
 */
function makeShuffledStrings(int $size): array
{
    $data = [];
    for ($i = 1; $i <= $size; $i++) {
        $data[] = sprintf('item-%06d', $i);
    }
    shuffle($data);

    return $data;
}

/**
 * @return array{
 *   addAll: array{time_ms: float, mem_mb: float},
 *   contains4: array{time_ms: float, mem_mb: float},
 *   get3: array{time_ms: float, mem_mb: float},
 *   remove3: array{time_ms: float, mem_mb: float}
 * }
 */
function runForIntSize(int $size, int $repeats, int $warmup): array
{
    $values = makeShuffledInts($size);

    $list = SortedLinkedList::forInt();
    $add = benchStats(static function () use ($list, $values): void {
        $list->clear();
        $list->addAll($values);
    }, $repeats, $warmup);

    $contains = benchStats(static function () use ($list, $size): void {
        $list->contains(1);
        $list->contains(intdiv($size, 2));
        $list->contains($size);
        $list->contains($size + 1);
    }, $repeats, $warmup);

    $get = benchStats(static function () use ($list, $size): void {
        $list->get(0);
        $list->get(intdiv($size, 2));
        $list->get($size - 1);
    }, $repeats, $warmup);

    $remove = benchStats(static function () use ($list, $size): void {
        $first = 1;
        $middle = intdiv($size, 2);
        $last = $size;

        $list->remove($first);
        $list->remove($middle);
        $list->remove($last);

        // Restore removed values to keep each run comparable.
        $list->add($first);
        $list->add($middle);
        $list->add($last);
    }, $repeats, $warmup);

    return [
        'addAll' => $add,
        'contains4' => $contains,
        'get3' => $get,
        'remove3' => $remove,
    ];
}

/**
 * @return array{
 *   addAll: array{time_ms: float, mem_mb: float},
 *   contains4: array{time_ms: float, mem_mb: float}
 * }
 */
function runForStringSize(int $size, int $repeats, int $warmup): array
{
    $values = makeShuffledStrings($size);

    $list = SortedLinkedList::forString();
    $add = benchStats(static function () use ($list, $values): void {
        $list->clear();
        $list->addAll($values);
    }, $repeats, $warmup);

    $contains = benchStats(static function () use ($list, $size): void {
        $list->contains('item-000001');
        $list->contains(sprintf('item-%06d', intdiv($size, 2)));
        $list->contains(sprintf('item-%06d', $size));
        $list->contains('item-999999');
    }, $repeats, $warmup);

    return [
        'addAll' => $add,
        'contains4' => $contains,
    ];
}

$jsonMode = in_array('--json', $argv, true);
$repeats = 5;
$warmup = 1;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--repeats=')) {
        $repeats = max(1, (int) substr($arg, strlen('--repeats=')));
    }

    if (str_starts_with($arg, '--warmup=')) {
        $warmup = max(0, (int) substr($arg, strlen('--warmup=')));
    }
}

mt_srand(12345);

$result = [
    'generated_at' => date(DATE_ATOM),
    'php_version' => PHP_VERSION,
    'repeats' => $repeats,
    'warmup' => $warmup,
    'int' => [],
    'string' => [],
];

foreach ([1000, 5000, 10000] as $n) {
    $result['int'][(string) $n] = runForIntSize($n, $repeats, $warmup);
}

foreach ([1000, 5000] as $n) {
    $result['string'][(string) $n] = runForStringSize($n, $repeats, $warmup);
}

if ($jsonMode) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

echo "SortedLinkedList quick performance benchmark\n";
echo "PHP " . $result['php_version'] . PHP_EOL;
echo "Repeats: " . $repeats . " | Warmup: " . $warmup . PHP_EOL;

foreach ($result['int'] as $size => $metrics) {
    echo PHP_EOL . "INT benchmark (n={$size})" . PHP_EOL;
    printf(
        "  addAll     : median %8.2f ms | avg %8.2f ms\n",
        $metrics['addAll']['time_ms']['median'],
        $metrics['addAll']['time_ms']['avg']
    );
    printf(
        "  contains x4: median %8.2f ms | avg %8.2f ms\n",
        $metrics['contains4']['time_ms']['median'],
        $metrics['contains4']['time_ms']['avg']
    );
    printf(
        "  get x3     : median %8.2f ms | avg %8.2f ms\n",
        $metrics['get3']['time_ms']['median'],
        $metrics['get3']['time_ms']['avg']
    );
    printf(
        "  remove x3  : median %8.2f ms | avg %8.2f ms\n",
        $metrics['remove3']['time_ms']['median'],
        $metrics['remove3']['time_ms']['avg']
    );
}

foreach ($result['string'] as $size => $metrics) {
    echo PHP_EOL . "STRING benchmark (n={$size})" . PHP_EOL;
    printf(
        "  addAll     : median %8.2f ms | avg %8.2f ms\n",
        $metrics['addAll']['time_ms']['median'],
        $metrics['addAll']['time_ms']['avg']
    );
    printf(
        "  contains x4: median %8.2f ms | avg %8.2f ms\n",
        $metrics['contains4']['time_ms']['median'],
        $metrics['contains4']['time_ms']['avg']
    );
}
