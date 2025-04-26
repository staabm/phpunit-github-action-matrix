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
    $output = Exec::cmd(sprintf('php %s --list-suites', $phpunitPath), $stderr, $exitCode);
} elseif ($segmentationStrategy === 'groups') {
    $output = Exec::cmd(sprintf('php %s --list-groups', $phpunitPath), $stderr, $exitCode);
} else {
    throw new RuntimeException('Invalid SEGMENTATION_STRATEGY value');
}

if ($exitCode !== 0) {
    echo $stderr;
    echo $output;

    throw new RuntimeException('Command failed with exit code %d', $exitCode));
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


final class Exec
{
    /**
     * @param string $stderrOutput
     * @param int $exitCode
     * @param-out string $stderrOutput
     * @param-out int $exitCode
     *
     * @return string
     */
    public static function cmd($cmd, &$stderrOutput, &$exitCode)
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],   // stderr
        ];

        $stderrOutput = '';
        $output = '';

        if (!function_exists('proc_open')) {
            throw new Exception('Function proc_open() is not available');
        }

        $process = proc_open($cmd, $descriptorspec, $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stderrOutput = stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[2]);

            $status = proc_get_status($process);
            while ($status['running']) {
                // sleep half a second
                usleep(500000);
                $status = proc_get_status($process);
            }
            $exitCode = $status['exitcode'];

            proc_close($process);
        }

        return $output === false ? '' : $output;
    }

}
