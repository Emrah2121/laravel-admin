<?php

namespace App\Services\SmartSeeder;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PexelsClient
{
    private const SEARCH_URL = 'https://api.pexels.com/v1/search';

    /**
     * Finds a landscape photo for $query that is not in $usedIds and downloads it.
     *
     * @return array{id: int, contents: string, photographer: string, photographer_url: string, url: string}|null
     */
    public function photo(string $apiKey, string $query, array $usedIds = []): ?array
    {
        try {
            $response = Http::withHeaders(['Authorization' => $apiKey])
                ->timeout(30)
                ->get(self::SEARCH_URL, ['query' => $query, 'orientation' => 'landscape', 'per_page' => 15]);
        } catch (ConnectionException $e) {
            throw new SmartSeederException('Could not reach the Pexels API: ' . $e->getMessage());
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new SmartSeederException('The Pexels API key was rejected.');
        }

        if ($response->status() === 429) {
            throw new SmartSeederException('Pexels rate limit reached. Try again later.');
        }

        if ($response->failed()) {
            throw new SmartSeederException("Pexels API error ({$response->status()}).");
        }

        foreach ($response->json('photos', []) as $photo) {
            if (in_array($photo['id'] ?? null, $usedIds, true) || empty($photo['src']['large'])) {
                continue;
            }

            $image = Http::timeout(60)->get($photo['src']['large']);

            if ($image->failed() || !str_starts_with((string) $image->header('Content-Type'), 'image/')) {
                continue;
            }

            return [
                'id' => $photo['id'],
                'contents' => $image->body(),
                'photographer' => (string) ($photo['photographer'] ?? ''),
                'photographer_url' => (string) ($photo['photographer_url'] ?? ''),
                'url' => (string) ($photo['url'] ?? ''),
            ];
        }

        return null;
    }
}
