<?php

namespace App\Services\LinkedIn;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LinkedInProfileNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *     public_identifier: string|null,
     *     first_name: string|null,
     *     last_name: string|null,
     *     headline: string|null,
     *     job_title: string|null,
     *     summary: string|null,
     *     location: string|null,
     *     country_code: string|null,
     *     follower_count: int|null,
     *     connections_count: int|null,
     *     picture_url: string|null,
     *     current_company: array{name: string|null, url: string|null}|null,
     *     positions: list<array{title: string|null, company: string|null, company_url: string|null, location: string|null, start: string|null, end: string|null, description: string|null}>,
     *     educations: list<array{school: string|null, degree: string|null, field: string|null, start: string|null, end: string|null}>,
     *     skills: list<string>,
     *     engagers: array{people_count: int, reply_rate: float|null, seniority: list<array{label: string, count: int}>, locations: list<array{label: string, count: int}>, top: list<array{name: string|null, headline: string|null, profile_url: string|null}>}|null,
     *     captured_at: string
     * }
     */
    public function normalize(array $raw, ?array $engagers = null): array
    {
        $headline = $this->stringOrNull(
            Arr::get($raw, 'basic_info.headline')
            ?? Arr::get($raw, 'headline')
        );

        $profile = [
            'public_identifier' => $this->stringOrNull(
                Arr::get($raw, 'basic_info.public_identifier')
                ?? Arr::get($raw, 'publicIdentifier')
                ?? Arr::get($raw, 'public_identifier')
            ),
            'first_name' => $this->stringOrNull(
                Arr::get($raw, 'basic_info.firstname')
                ?? Arr::get($raw, 'basic_info.firstName')
                ?? Arr::get($raw, 'firstName')
                ?? Arr::get($raw, 'first_name')
            ),
            'last_name' => $this->stringOrNull(
                Arr::get($raw, 'basic_info.lastname')
                ?? Arr::get($raw, 'basic_info.lastName')
                ?? Arr::get($raw, 'lastName')
                ?? Arr::get($raw, 'last_name')
            ),
            'headline' => $headline,
            'job_title' => $this->stringOrNull(Arr::get($raw, 'jobTitle') ?? Arr::get($raw, 'job_title')),
            'summary' => $this->stringOrNull(
                Arr::get($raw, 'basic_info.about')
                ?? Arr::get($raw, 'summary')
                ?? Arr::get($raw, 'about')
            ),
            'location' => $this->stringOrNull(
                Arr::get($raw, 'basic_info.location.full')
                ?? Arr::get($raw, 'geoLocationName')
                ?? Arr::get($raw, 'locationName')
                ?? Arr::get($raw, 'basic_info.location')
                ?? Arr::get($raw, 'location')
            ),
            'country_code' => $this->countryCode(
                Arr::get($raw, 'countryCode')
                ?? Arr::get($raw, 'basic_info.location.country_code')
                ?? Arr::get($raw, 'basic_info.location.countryCode')
                ?? Arr::get($raw, 'geoCountryCode')
                ?? Arr::get($raw, 'country_code')
            ),
            'follower_count' => $this->intOrNull(
                Arr::get($raw, 'followerCount')
                ?? Arr::get($raw, 'followersCount')
                ?? Arr::get($raw, 'basic_info.follower_count')
                ?? Arr::get($raw, 'follower_count')
                ?? Arr::get($raw, 'followers')
            ),
            'connections_count' => $this->intOrNull(
                Arr::get($raw, 'connectionsCount')
                ?? Arr::get($raw, 'connectionCount')
                ?? Arr::get($raw, 'basic_info.connection_count')
                ?? Arr::get($raw, 'connections_count')
                ?? Arr::get($raw, 'connections')
            ),
            'picture_url' => $this->pictureUrl(
                Arr::get($raw, 'pictureUrl')
                ?? Arr::get($raw, 'basic_info.profile_picture_url')
                ?? Arr::get($raw, 'profilePicture')
                ?? Arr::get($raw, 'picture_url')
            ),
            'current_company' => $this->currentCompany($raw),
            'positions' => $this->positions($raw),
            'educations' => $this->educations($raw),
            'skills' => $this->skills($raw),
            'engagers' => $engagers,
            'captured_at' => now()->toIso8601String(),
        ];

        $this->assertValid($profile);

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function assertValid(array $profile): void
    {
        if ($profile['headline'] === null && $profile['public_identifier'] === null && $profile['first_name'] === null) {
            throw ValidationException::withMessages([
                'linkedin' => 'LinkedIn profile scrape returned an empty profile.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{name: string|null, url: string|null}|null
     */
    private function currentCompany(array $raw): ?array
    {
        $name = $this->stringOrNull(
            Arr::get($raw, 'currentCompany.name')
            ?? Arr::get($raw, 'companyName')
            ?? Arr::get($raw, 'basic_info.current_company')
        );

        $url = $this->stringOrNull(
            Arr::get($raw, 'companyLinkedinUrl')
            ?? Arr::get($raw, 'currentCompany.url')
            ?? Arr::get($raw, 'companyPublicId')
        );

        if ($name === null) {
            $positions = $this->positions($raw);
            $first = $positions[0] ?? null;

            if ($first === null) {
                return null;
            }

            return [
                'name' => $first['company'],
                'url' => $first['company_url'],
            ];
        }

        if (is_string($url) && ! str_starts_with($url, 'http') && $url !== '') {
            $url = 'https://www.linkedin.com/company/'.$url;
        }

        return [
            'name' => $name,
            'url' => $url,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<array{title: string|null, company: string|null, company_url: string|null, location: string|null, start: string|null, end: string|null, description: string|null}>
     */
    private function positions(array $raw): array
    {
        $rows = Arr::get($raw, 'positions') ?? Arr::get($raw, 'experience') ?? [];

        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach (array_slice($rows, 0, 20) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $company = Arr::get($row, 'company');
            $companyName = null;
            $companyUrl = null;

            if (is_array($company)) {
                $companyName = $this->stringOrNull(Arr::get($company, 'name'));
                $companyUrl = $this->stringOrNull(Arr::get($company, 'url') ?? Arr::get($company, 'linkedinUrl'));
            } else {
                $companyName = $this->stringOrNull(
                    $company
                    ?? Arr::get($row, 'companyName')
                    ?? Arr::get($row, 'company_name')
                );
                $companyUrl = $this->stringOrNull(
                    Arr::get($row, 'company_linkedin_url')
                    ?? Arr::get($row, 'companyUrl')
                );
            }

            $normalized[] = [
                'title' => $this->stringOrNull(Arr::get($row, 'title') ?? Arr::get($row, 'position')),
                'company' => $companyName,
                'company_url' => $companyUrl,
                'location' => $this->stringOrNull(Arr::get($row, 'locationName') ?? Arr::get($row, 'location')),
                'start' => $this->dateOrNull(
                    Arr::get($row, 'timePeriod.startDate')
                    ?? Arr::get($row, 'start_date')
                    ?? Arr::get($row, 'starts_at')
                    ?? Arr::get($row, 'start')
                ),
                'end' => $this->dateOrNull(
                    Arr::get($row, 'timePeriod.endDate')
                    ?? Arr::get($row, 'end_date')
                    ?? Arr::get($row, 'ends_at')
                    ?? Arr::get($row, 'end')
                ),
                'description' => $this->stringOrNull(Arr::get($row, 'description')),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<array{school: string|null, degree: string|null, field: string|null, start: string|null, end: string|null}>
     */
    private function educations(array $raw): array
    {
        $rows = Arr::get($raw, 'educations') ?? Arr::get($raw, 'education') ?? [];

        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach (array_slice($rows, 0, 15) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized[] = [
                'school' => $this->stringOrNull(
                    Arr::get($row, 'schoolName')
                    ?? Arr::get($row, 'school')
                    ?? Arr::get($row, 'title')
                ),
                'degree' => $this->stringOrNull(
                    Arr::get($row, 'degreeName')
                    ?? Arr::get($row, 'degree')
                ),
                'field' => $this->stringOrNull(
                    Arr::get($row, 'fieldOfStudy')
                    ?? Arr::get($row, 'field_of_study')
                    ?? Arr::get($row, 'field')
                ),
                'start' => $this->dateOrNull(
                    Arr::get($row, 'timePeriod.startDate')
                    ?? Arr::get($row, 'start_date')
                    ?? Arr::get($row, 'starts_at')
                    ?? Arr::get($row, 'start')
                ),
                'end' => $this->dateOrNull(
                    Arr::get($row, 'timePeriod.endDate')
                    ?? Arr::get($row, 'end_date')
                    ?? Arr::get($row, 'ends_at')
                    ?? Arr::get($row, 'end')
                ),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<string>
     */
    private function skills(array $raw): array
    {
        $rows = Arr::get($raw, 'skills') ?? [];

        if (! is_array($rows)) {
            return [];
        }

        $skills = [];

        foreach (array_slice($rows, 0, 60) as $row) {
            if (is_string($row) && $row !== '') {
                $skills[] = $row;

                continue;
            }

            if (is_array($row)) {
                $name = $this->stringOrNull(Arr::get($row, 'name') ?? Arr::get($row, 'title') ?? Arr::get($row, 'skill'));

                if ($name !== null) {
                    $skills[] = $name;
                }
            }
        }

        return array_values(array_unique($skills));
    }

    private function pictureUrl(mixed $value): ?string
    {
        if (is_string($value)) {
            return $this->stringOrNull($value);
        }

        if (! is_array($value)) {
            return null;
        }

        foreach (['800x800', '400x400', '200x200', '100x100'] as $size) {
            $candidate = $this->stringOrNull($value[$size] ?? null);

            if ($candidate !== null) {
                return $candidate;
            }
        }

        foreach ($value as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        return null;
    }

    private function countryCode(mixed $value): ?string
    {
        $code = $this->stringOrNull($value);

        if ($code === null) {
            return null;
        }

        return strtoupper(substr($code, 0, 2));
    }

    private function dateOrNull(mixed $value): ?string
    {
        if (is_array($value)) {
            $year = Arr::get($value, 'year');
            $month = Arr::get($value, 'month');

            if (is_numeric($year)) {
                $monthPart = is_numeric($month) ? str_pad((string) (int) $month, 2, '0', STR_PAD_LEFT) : '01';

                return sprintf('%04d-%s', (int) $year, $monthPart);
            }

            return null;
        }

        $asString = $this->stringOrNull($value);

        if ($asString === null) {
            return null;
        }

        try {
            return Carbon::parse($asString)->toDateString();
        } catch (\Throwable) {
            return $asString;
        }
    }
}
