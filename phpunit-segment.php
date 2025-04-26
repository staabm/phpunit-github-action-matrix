<?php declare(strict_types = 1);

require_once __DIR__ . '/lib/Exec.php';

$env = getenv();
if (!isset($env['SEGMENTATION_STRATEGY'])) {
    throw new RuntimeException('SEGMENTATION_STRATEGY environment variable is not set');
}
$segmentationStrategy = $env['SEGMENTATION_STRATEGY'];

if (!isset($env['PHPUNIT_PATH'])) {
    echo '::error:: PHPUNIT_PATH environment variable is not set';
    exit(1);
}
$phpunitPath = $env['PHPUNIT_PATH'];

if ($segmentationStrategy === 'suites') {
    $output = Exec::cmd(sprintf('php %s --list-suites', $phpunitPath), $stderr, $exitCode);
} elseif ($segmentationStrategy === 'groups') {
    $output = Exec::cmd(sprintf('php %s --list-groups', $phpunitPath), $stderr, $exitCode);
} else {
    echo '::error:: Invalid SEGMENTATION_STRATEGY value.';
    exit(1);
}

if ($exitCode !== 0) {
    echo $stderr;
    echo $output;

    printf('::error:: Command failed with exit code %d', $exitCode);
    exit(1);
}

$segments = [];
foreach(preg_split("/((\r?\n)|(\r\n?))/", $output) as $line){
    $line = trim($line);
    if (strpos($line, '-') !== 0) {
        continue;
    }

    $segments[] = ltrim($line, '- ');
}

if ($segments === []) {
    echo '::error:: No tests found';
    exit(1);
}

echo json_encode($segments);
