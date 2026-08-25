<?php

namespace App\Services;

class TaxCalculator
{
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($netAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return intdiv($netAmount * $rateBasisPoints, 10000);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return $grossAmount - intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);
    }
}
