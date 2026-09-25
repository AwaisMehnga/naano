<?php

namespace App\Services\LinkedIn;

class AudienceMixBuilder
{
    /**
     * Turn engagers bucket lists into percentage maps brands expect.
     *
     * @param  array{people_count?: int, reply_rate?: float|null, seniority?: list<array{label: string, count: int}>, job_title?: list<array{label: string, count: int}>, locations?: list<array{label: string, count: int}>, top?: list<array<string, mixed>>}|null  $engagers
     * @return array{seniority?: array<string, int>, job_title?: array<string, int>, geo?: array<string, int>}
     */
    public function fromEngagers(?array $engagers): array
    {
        if ($engagers === null || $engagers === []) {
            return [];
        }

        $mix = [];

        $seniority = $this->percentMap($engagers['seniority'] ?? []);
        if ($seniority !== []) {
            $mix['seniority'] = $seniority;
        }

        $jobTitle = $this->percentMap($engagers['job_title'] ?? []);
        if ($jobTitle !== []) {
            $mix['job_title'] = $jobTitle;
        }

        $geo = $this->percentMap($engagers['locations'] ?? []);
        if ($geo !== []) {
            $mix['geo'] = $geo;
        }

        return $mix;
    }

    /**
     * @param  list<array{label: string, count: int}>|mixed  $buckets
     * @return array<string, int>
     */
    private function percentMap(mixed $buckets): array
    {
        if (! is_array($buckets) || $buckets === []) {
            return [];
        }

        $total = 0;
        $counts = [];

        foreach ($buckets as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $count = (int) ($row['count'] ?? 0);

            if ($label === '' || $count < 1) {
                continue;
            }

            $counts[$label] = ($counts[$label] ?? 0) + $count;
            $total += $count;
        }

        if ($total < 1) {
            return [];
        }

        $map = [];
        $assigned = 0;
        $labels = array_keys($counts);
        $last = array_key_last($labels);

        foreach ($labels as $index => $label) {
            if ($index === $last) {
                $map[$label] = max(0, 100 - $assigned);

                continue;
            }

            $share = (int) round(($counts[$label] / $total) * 100);
            $map[$label] = $share;
            $assigned += $share;
        }

        return $map;
    }
}
