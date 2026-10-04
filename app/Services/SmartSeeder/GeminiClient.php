<?php

namespace App\Services\SmartSeeder;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Gemini generateContent with a JSON schema constrained response.
 * Kept separate so the provider can be swapped or mocked in tests.
 */
class GeminiClient
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    // Used when the chosen model stays overloaded; lighter models are busy less often.
    public const FALLBACK_MODEL = 'gemini-3.5-flash-lite';

    private const RETRY_DELAYS_MS = [2000, 5000];

    private const OVERLOADED_STATUSES = [500, 502, 503, 504];

    /**
     * The model that produced the last successful response.
     */
    public ?string $lastModel = null;

    public function generateJson(string $apiKey, string $model, string $system, string $prompt, array $schema): array
    {
        if (!preg_match('/^[a-z0-9.\-]+$/', $model)) {
            throw new SmartSeederException("Invalid Gemini model name [{$model}].");
        }

        $models = array_values(array_unique([$model, self::FALLBACK_MODEL]));

        foreach ($models as $candidate) {
            $response = $this->request($apiKey, $candidate, $system, $prompt, $schema);

            if (!in_array($response->status(), self::OVERLOADED_STATUSES, true)) {
                $this->lastModel = $candidate;

                return $this->parse($response);
            }
        }

        throw new SmartSeederException(
            "Gemini is overloaded right now ({$response->status()}), also on " . self::FALLBACK_MODEL . '. Try again in a few minutes.'
        );
    }

    private function request(string $apiKey, string $model, string $system, string $prompt, array $schema): Response
    {
        try {
            return Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->timeout(240)
                ->retry(self::RETRY_DELAYS_MS, 0, fn ($exception) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && in_array($exception->response->status(), self::OVERLOADED_STATUSES, true)), throw: false)
                ->post(sprintf(self::ENDPOINT, $model), [
                    'systemInstruction' => ['parts' => [['text' => $system]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new SmartSeederException('Could not reach the Gemini API: ' . $e->getMessage());
        }
    }

    private function parse(Response $response): array
    {
        if ($response->status() === 429) {
            throw new SmartSeederException('Gemini rate limit reached (free tier). Wait a minute and try again.');
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();

            throw new SmartSeederException("Gemini API error ({$response->status()}): {$message}");
        }

        if ($blockReason = $response->json('promptFeedback.blockReason')) {
            throw new SmartSeederException("Gemini blocked the request ({$blockReason}). Rephrase the instruction.");
        }

        $finishReason = $response->json('candidates.0.finishReason');

        if ($finishReason === 'MAX_TOKENS') {
            throw new SmartSeederException('The response was cut off (MAX_TOKENS). Ask for fewer records.');
        }

        $text = collect($response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');
        $data = json_decode($text, true);

        if (!is_array($data)) {
            throw new SmartSeederException('Gemini did not return valid JSON' . ($finishReason ? " ({$finishReason})." : '.'));
        }

        return $data;
    }
}
