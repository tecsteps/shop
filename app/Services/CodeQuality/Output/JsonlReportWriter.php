<?php

namespace App\Services\CodeQuality\Output;

use App\Services\CodeQuality\Findings\Report;

class JsonlReportWriter
{
    public function write(Report $report): string
    {
        $lines = [
            json_encode([
                'type' => 'summary',
                'report' => array_diff_key($report->toArray(), ['findings' => true, 'check_results' => true]),
            ], JSON_UNESCAPED_SLASHES),
        ];

        foreach ($report->checkResults as $result) {
            $lines[] = json_encode([
                'type' => 'check_result',
                'check_result' => $result->toArray(),
            ], JSON_UNESCAPED_SLASHES);
        }

        foreach ($report->findings as $finding) {
            $lines[] = json_encode([
                'type' => 'finding',
                'finding' => $finding->toArray(),
            ], JSON_UNESCAPED_SLASHES);
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
