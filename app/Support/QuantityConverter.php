<?php

namespace App\Support;

class QuantityConverter
{
    public static function bagsToPounds(int $bags, int $looseLb, int $bagWeightLb = 108): int
    {
        return ($bags * $bagWeightLb) + $looseLb;
    }

    public static function poundsToBags(int $totalLb, int $bagWeightLb = 108): array
    {
        $bags = intdiv($totalLb, $bagWeightLb);
        $looseLb = $totalLb % $bagWeightLb;

        return [
            'bags' => $bags,
            'loose_lb' => $looseLb,
        ];
    }
}
