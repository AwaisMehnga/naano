<?php

namespace App\Services\Apify;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApifyClient
{
    /**
     * Start an actor run, wait until finished, and return dataset items.
     *
     * @param  array<string, mixed>  $input
     * @return list<array<string, mixed>>
     */
    public function runActor(string $actorId, array $input): array
    {
        $token = $this->token();
        $baseUrl = rtrim((string) config('services.apify.base_url'), '/');

        $start = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->withQueryParameters(['token' => $token])
            ->post("{$baseUrl}/acts/{$actorId}/runs", $input);

        if ($start->failed()) {
            throw new RuntimeException('Apify actor could not be started: '.$start->body());
        }

        $runId = data_get($start->json(), 'data.id');
        $datasetId = data_get($start->json(), 'data.defaultDatasetId');

        if (! is_string($runId) || $runId === '') {
            throw new RuntimeException('Apify did not return a run id.');
        }

        $status = $this->waitForRun($baseUrl, $token, $runId);

        if ($status !== 'SUCCEEDED') {
            throw new RuntimeException("Apify actor run ended with status {$status}.");
        }

        if (! is_string($datasetId) || $datasetId === '') {
            $datasetId = data_get($this->fetchRun($baseUrl, $token, $runId), 'data.defaultDatasetId');
        }

        if (! is_string($datasetId) || $datasetId === '') {
            throw new RuntimeException('Apify did not return a dataset id.');
        }

        return $this->datasetItems($baseUrl, $token, $datasetId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function datasetItems(string $baseUrl, string $token, string $datasetId): array
    {
        $response = Http::timeout(60)
            ->acceptJson()
            ->get("{$baseUrl}/datasets/{$datasetId}/items", [
                'token' => $token,
                'format' => 'json',
                'clean' => 1,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Apify dataset could not be fetched: '.$response->body());
        }

        $items = $response->json();

        if (! is_array($items)) {
            return [];
        }

        /** @var list<array<string, mixed>> */
        return array_values(array_filter($items, is_array(...)));
    }

    private function waitForRun(string $baseUrl, string $token, string $runId): string
    {
        $timeout = max(10, (int) config('services.apify.poll_timeout_seconds', 120));
        $intervalMs = max(500, (int) config('services.apify.poll_interval_ms', 2000));
        $deadline = microtime(true) + $timeout;

        do {
            try {
                $payload = $this->fetchRun($baseUrl, $token, $runId);
            } catch (ConnectionException $e) {
                throw new RuntimeException('Apify run status could not be fetched.', 0, $e);
            }

            $status = (string) data_get($payload, 'data.status', '');

            if (in_array($status, ['SUCCEEDED', 'FAILED', 'ABORTED', 'TIMED-OUT'], true)) {
                return $status;
            }

            usleep($intervalMs * 1000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException('Apify actor run timed out while waiting for completion.');
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRun(string $baseUrl, string $token, string $runId): array
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->get("{$baseUrl}/actor-runs/{$runId}", [
                'token' => $token,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Apify run status could not be fetched: '.$response->body());
        }

        /** @var array<string, mixed> */
        return $response->json() ?? [];
    }

    private function token(): string
    {
        $token = config('services.apify.token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('APIFY_API_TOKEN is not configured.');
        }

        return $token;
    }
}
