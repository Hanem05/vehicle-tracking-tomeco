<?php

namespace App\Services;

use App\Models\Violation;
use Carbon\CarbonImmutable;

class TrendService
{
    public function weekly(): array
    {
        $start = CarbonImmutable::now()->startOfWeek()->subWeeks(11);
        $dates = Violation::where('occurred_at', '>=', $start)->pluck('occurred_at');
        $counts = array_fill(0, 12, 0);
        foreach ($dates as $date) {
            $week = (int) $start->diffInWeeks(CarbonImmutable::parse($date)->startOfWeek());
            if ($week >= 0 && $week < 12) $counts[$week]++;
        }
        $labels = [];
        for ($i = 0; $i < 12; $i++) $labels[] = $start->addWeeks($i)->format('M j');

        $forecast = null;
        if (array_sum($counts) >= 20 && count(array_filter($counts)) >= 8) {
            $recent = array_sum(array_slice($counts, -4)) / 4;
            $prior = array_sum(array_slice($counts, -8, 4)) / 4;
            $slope = ($recent - $prior) / 4;
            $forecast = [];
            for ($i = 1; $i <= 4; $i++) $forecast[] = max(0, (int) round($recent + $slope * $i));
        }

        $futureLabels = [];
        for ($i = 1; $i <= 4; $i++) $futureLabels[] = CarbonImmutable::now()->startOfWeek()->addWeeks($i)->format('M j');
        return compact('labels', 'counts', 'forecast', 'futureLabels');
    }
}
