<?php

namespace App\Services;

use Illuminate\Http\Request;

class WeeklyReleaseService
{
    private array $filters;
    private bool $weekly;
    private string $startDay;

    public function __construct(
        private Request $request,
        private readonly string $key = 'filter',
    )
    {
        $this->filters = $this->request->input($this->key, []);
        $this->weekly = $this->request->boolean("$this->key.weekly");
        $this->startDay = config('music.weekly_start_day', 'friday');
    }

    public static function fromRequest(Request $request, string $key = 'filter'): self
    {
        return new self($request, $key);
    }

    public function handle(): void
    {
        if ($this->weekly && !empty($this->filters['from'])) {
            $range = DateRangeService::resolveWeeklyRange($this->filters['from'], $this->startDay);

            $filter = array_merge($this->filters, [
                'from' => $range['from'],
                'to'   => $range['to'],
            ]);

            $this->applyFilters($filter);
        }
    }

    private function applyFilters(array $filter): void
    {
        request()->merge([
             'filter' => array_merge($this->filters, $filter),
        ]);

        $this->request->merge([
            'filter' => array_merge($this->filters, $filter),
        ]);
    }
}
