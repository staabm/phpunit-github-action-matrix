<?php declare(strict_types = 1);

require_once __DIR__ . '/lib/Exec.php';

$env = getenv();
if (!isset($env['SEGMENTATION_STRATEGY'])) {
    throw new RuntimeException('SEGMENTATION_STRATEGY environment variable is not set');
}
$segmentationStrategy = $env['SEGMENTATION_STRATEGY'];

if (!isset($env['PHPUNIT_PATH'])) {
    throw new RuntimeException('PHPUNIT_PATH environment variable is not set');
}
$phpunitPath = $env['PHPUNIT_PATH'];

if ($segmentationStrategy === 'suites') {
    $output = Exec::cmd(sprintf('php %s --list-suites', $phpunitPath), $stderr, $exitCode);
} elseif ($segmentationStrategy === 'groups') {
    $output = Exec::cmd(sprintf('php %s --list-groups', $phpunitPath), $stderr, $exitCode);
} else {
    throw new RuntimeException('Invalid SEGMENTATION_STRATEGY value');
}

if ($exitCode !== 0) {
    echo $stderr;
    echo $output;

    throw new RuntimeException('Command failed with exit code %d', $exitCode);
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
    throw new RuntimeException('No tests found');
}

$commands = [];
foreach ($segments as $segment) {
    if ($segmentationStrategy === 'suites') {
        $commands[] = sprintf('php %s --testsuite "%s"', $phpunitPath, $segment);
    } elseif ($segmentationStrategy === 'groups') {
        $commands[] = sprintf('php %s --group "%s"', $phpunitPath, $segment);
    }
}

echo json_encode($commands);
