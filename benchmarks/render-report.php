<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php benchmarks/render-report.php <benchmark.json>\n");
    exit(2);
}

$path = $argv[1];
if (!is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(2);
}

$data = json_decode((string) file_get_contents($path), true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON in: {$path}\n");
    exit(2);
}

echo "SortedLinkedList benchmark report\n";
echo "Generated: " . ($data['generated_at'] ?? 'n/a') . PHP_EOL;
echo "PHP: " . ($data['php_version'] ?? 'n/a') . PHP_EOL;
echo "Repeats: " . ($data['repeats'] ?? 'n/a') . " | Warmup: " . ($data['warmup'] ?? 'n/a') . PHP_EOL;

foreach (['int' => ['addAll', 'contains4', 'get3', 'remove3'], 'string' => ['addAll', 'contains4']] as $kind => $ops) {
    if (!isset($data[$kind]) || !is_array($data[$kind])) {
        continue;
    }

    foreach ($data[$kind] as $size => $metrics) {
        if (!is_array($metrics)) {
            continue;
        }

        echo PHP_EOL . strtoupper($kind) . " benchmark (n={$size})" . PHP_EOL;
        foreach ($ops as $op) {
            if (!isset($metrics[$op]['time_ms']['median'], $metrics[$op]['time_ms']['avg'])) {
                continue;
            }

            printf(
                "  %-10s : median %8.2f ms | avg %8.2f ms\n",
                $op,
                (float) $metrics[$op]['time_ms']['median'],
                (float) $metrics[$op]['time_ms']['avg']
            );
        }
    }
}
