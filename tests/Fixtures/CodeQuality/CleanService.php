<?php

namespace Tests\Fixtures\CodeQuality;

class CleanService
{
    public function value(): string
    {
        return config('app.name');
    }
}
