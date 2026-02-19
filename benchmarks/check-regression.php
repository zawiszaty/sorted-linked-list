<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php benchmarks/check-regression.php <current.json> <baseline.json> [threshold]\n");
    exit(2);
}

$currentPath = $argv[1];
$baselinePath = $argv[2];
$threshold = isset($argv[3]) ? (float) $argv[3] : 0.30;

/**
 * @return array<string, mixed>
 */
function loadJson(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("File not found: {$path}");
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        throw new RuntimeException("Invalid JSON in: {$path}");
    }

    return $decoded;
}

function extractTimeMsValue(mixed $value): ?float
{
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    if (!is_array($value)) {
        return null;
    }

    if (isset($value['median']) && (is_int($value['median']) || is_float($value['median']))) {
        return (float) $value['median'];
    }

    if (isset($value['avg']) && (is_int($value['avg']) || is_float($value['avg']))) {
        return (float) $value['avg'];
    }

    return null;
}

/**
 * @return array<string, float>
 */
function flattenAddAllTimes(array $data): array
{
    $result = [];

    foreach (['int', 'string'] as $kind) {
        if (!isset($data[$kind]) || !is_array($data[$kind])) {
            continue;
        }

        foreach ($data[$kind] as $size => $metrics) {
            if (!is_array($metrics) || !isset($metrics['addAll']['time_ms'])) {
                continue;
            }

            $timeValue = extractTimeMsValue($metrics['addAll']['time_ms']);
            if ($timeValue !== null) {
                $result["{$kind}.{$size}.addAll"] = $timeValue;
            }
        }
    }

    return $result;
}

try {
    $current = flattenAddAllTimes(loadJson($currentPath));
    $baseline = flattenAddAllTimes(loadJson($baselinePath));
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(2);
}

$failed = false;

foreach ($baseline as $key => $baselineMs) {
    if (!isset($current[$key])) {
        continue;
    }

    if ($baselineMs <= 0.0) {
        continue;
    }

    $currentMs = $current[$key];
    $diff = ($currentMs - $baselineMs) / $baselineMs;

    printf(
        "%s baseline=%.2fms current=%.2fms diff=%+.2f%%\n",
        $key,
        $baselineMs,
        $currentMs,
        $diff * 100
    );

    if ($diff > $threshold) {
        $failed = true;
    }
}

if ($failed) {
    fwrite(STDERR, "Performance regression detected above threshold " . ($threshold * 100) . "%\n");
    exit(1);
}

echo "No performance regression above threshold " . ($threshold * 100) . "%\n";
