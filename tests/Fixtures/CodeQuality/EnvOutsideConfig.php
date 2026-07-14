<?php

namespace Tests\Fixtures\CodeQuality;

class EnvOutsideConfig
{
    public function value(): ?string
    {
        return env('CODE_QUALITY_TEST_VALUE');
    }
}
