<?php declare(strict_types = 1);

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
    $output = shell_exec(sprintf('php %s --list-suites', $phpunitPath));
} elseif ($segmentationStrategy === 'groups') {
    $output = shell_exec(sprintf('php %s --list-groups', $phpunitPath));
} else {
    throw new RuntimeException('Invalid SEGMENTATION_STRATEGY value');
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
