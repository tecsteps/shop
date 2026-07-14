<?php

namespace App\Services\CodeQuality\Output;

use App\Services\CodeQuality\Findings\Finding;
use App\Services\CodeQuality\Findings\Report;

class PrettyReportWriter
{
    public function write(Report $report): string
    {
        $summary = $report->summary();
        $lines = [
            "Code quality {$report->status}: ".count($report->scope->files).' file(s), '.count($report->checkResults).' check(s).',
            "Findings: {$summary['critical']} critical, {$summary['error']} error, {$summary['warning']} warning, {$summary['info']} info.",
            "Skipped checks: {$summary['skipped_checks']}; checker failures: {$summary['failed_checks']}.",
        ];

        foreach ($report->checkResults as $result) {
            if (! in_array($result->status, ['skipped', 'errored', 'timed_out'], true)) {
                continue;
            }

            $lines[] = '';
            $lines[] = strtoupper($result->status).' '.$result->checkId.($result->message === null ? '' : ': '.$result->message);
        }

        foreach ($report->findings as $finding) {
            $lines[] = '';
            $lines[] = $this->findingTitle($finding);
            $lines[] = $finding->description;

            if ($finding->file !== null) {
                $location = $finding->file.($finding->line === null ? '' : ':'.$finding->line);
                $lines[] = 'Location: '.$location;
            }

            if ($finding->recommendation !== null && $finding->recommendation !== '') {
                $lines[] = 'Recommendation: '.$finding->recommendation;
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function findingTitle(Finding $finding): string
    {
        $blocking = $finding->blocking ? ' blocking' : '';

        return strtoupper($finding->severity)."{$blocking} {$finding->checkId}: {$finding->title}";
    }
}
