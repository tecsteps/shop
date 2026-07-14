<?php

namespace Tests\Fixtures\CodeQuality;

use App\Services\Llm\LlmClient;

class HeartbeatAiReference
{
    public function client(): string
    {
        return LlmClient::class;
    }
}
