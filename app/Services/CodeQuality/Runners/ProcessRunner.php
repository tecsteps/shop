<?php

namespace App\Services\CodeQuality\Runners;

use App\Services\CodeQuality\CodeQualityException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ProcessRunner
{
    /**
     * @param  list<string>  $command
     * @param  array<string, string|false>  $environment
     */
    public function run(array $command, string $workingDirectory, int $timeoutSeconds, array $environment = []): ProcessResult
    {
        $started = hrtime(true);
        $process = new Process($command, $workingDirectory, $environment, null, $timeoutSeconds);

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw CodeQualityException::timedOut('Process timed out: '.implode(' ', $command));
        }

        return new ProcessResult(
            command: $command,
            exitCode: $process->getExitCode() ?? 1,
            output: $process->getOutput(),
            errorOutput: $process->getErrorOutput(),
            durationMs: (int) ((hrtime(true) - $started) / 1_000_000),
        );
    }
}
