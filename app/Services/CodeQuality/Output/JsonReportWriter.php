<?php

namespace App\Services\CodeQuality\Output;

use App\Services\CodeQuality\Findings\Report;

class JsonReportWriter
{
    public function write(Report $report): string
    {
        return json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
}
