<?php

namespace App\Services\SmartSeeder;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PixabayClient
{
    private const SEARCH_URL = 'https://pixabay.com/api/';

    /**
     * Finds a horizontal photo for $query that is not in $usedIds and downloads it
     * (Pixabay does not allow hotlinking, images must be stored locally).
     *
     * @return array{id: int, contents: string, extension: string, photographer: string, photographer_url: string, url: string}|null
     */
    public function photo(string $apiKey, string $query, array $usedIds = []): ?array
    {
        try {
            $response = Http::timeout(30)->get(self::SEARCH_URL, [
                'key' => $apiKey,
                'q' => mb_substr($query, 0, 100),
                'image_type' => 'photo',
                'orientation' => 'horizontal',
                'safesearch' => 'true',
                'per_page' => 20,
            ]);
        } catch (ConnectionException $e) {
            throw new SmartSeederException('Could not reach the Pixabay API: ' . $e->getMessage());
        }

        if ($response->status() === 429) {
            throw new SmartSeederException('Pixabay rate limit reached. Wait a minute and try again.');
        }

        if ($response->status() === 400 && str_contains($response->body(), 'API key')) {
            throw new SmartSeederException('The Pixabay API key was rejected.');
        }

        if ($response->failed()) {
            throw new SmartSeederException("Pixabay API error ({$response->status()}).");
        }

        foreach ($response->json('hits', []) as $hit) {
            if (in_array($hit['id'] ?? null, $usedIds, true) || empty($hit['largeImageURL'])) {
                continue;
            }

            $image = Http::timeout(60)->get($hit['largeImageURL']);
            $type = (string) $image->header('Content-Type');

            if ($image->failed() || !in_array($type, ['image/jpeg', 'image/png'], true)) {
                continue;
            }

            $user = (string) ($hit['user'] ?? '');

            return [
                'id' => $hit['id'],
                'contents' => $image->body(),
                'extension' => $type === 'image/png' ? 'png' : 'jpg',
                'photographer' => $user,
                'photographer_url' => $user !== '' ? "https://pixabay.com/users/{$user}-{$hit['user_id']}/" : '',
                'url' => (string) ($hit['pageURL'] ?? ''),
            ];
        }

        return null;
    }
}
