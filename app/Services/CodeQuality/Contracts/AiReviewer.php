<?php

namespace App\Services\CodeQuality\Contracts;

use App\Services\CodeQuality\AiReviewRequest;
use App\Services\CodeQuality\AiReviewResult;

interface AiReviewer
{
    public function review(AiReviewRequest $request): AiReviewResult;
}
