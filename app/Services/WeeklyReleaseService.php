<?php

namespace App\Services;

use Illuminate\Http\Request;

class WeeklyReleaseService
{
    private array $filters;
    private bool $weekly;
    private string $startDay;

    public function __construct(
        private readonly Request $request,
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
        if (!$this->weekly) {
            return;
        }

        $range = $this->getDateRange();

        $filter = array_merge($this->filters, [
            'from' => $range['from'],
            'to'   => $range['to'],
        ]);

        $this->applyFilters($filter);
    }

    private function getDateRange(): array
    {
        if (!empty($this->filters['from'])) {
            return DateRangeService::resolveWeeklyRange($this->filters['from'], $this->startDay);
        }

        // Sinon on utilise weeks + startDay
        $weeks = (int) ($this->filters['weeks'] ?? 0);

        return DateRangeService::resolveWeekWithOffset($weeks, $this->startDay);
    }

    private function applyFilters(array $filter): void
    {
        $data = [ 'filter' => array_merge($this->filters, $filter)];

        $this->request->merge($data);

        request()->merge($data);
        // request()->replace($this->request->all());
    }
}
