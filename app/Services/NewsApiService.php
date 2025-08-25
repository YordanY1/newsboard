<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service wrapper around NewsAPI /v2/everything.
 *
 * Goals:
 * - Keep the last 20 articles per query (via pageSize=20, sorted by publishedAt).
 * - Provide a small abstraction with input sanitization and error handling.
 * - Cache the response to avoid redundant calls.
 */
class NewsApiService
{
    /** Base endpoint, e.g. https://newsapi.org */
    private string $base;

    /** API key for NewsAPI */
    private string $key;

    /** Default language for queries (NewsAPI supports: ar, de, en, es, fr, he, it, nl, no, pt, ru, sv, ud, zh) */
    private string $defaultLanguage = 'en';

    /** Default cache duration in minutes */
    private int $defaultCacheMinutes = 10;

    public function __construct()
    {
        $this->base = rtrim(config('services.newsapi.base', (string) env('NEWSAPI_BASE', 'https://newsapi.org')), '/');
        $this->key  = (string) env('NEWSAPI_KEY', '');

        if ($this->key === '') {
            // Fail fast if API key is missing; easier to debug in dev & CI
            throw new InvalidArgumentException('Missing NEWSAPI_KEY. Please set it in your .env.');
        }
    }

    /**
     * Fetch up to 20 latest articles using /v2/everything for a given keyword.
     *
     * @param  string $keyword   User's search term (e.g., "Laravel", "AI", etc.)
     * @param  array  $options   Optional overrides:
     *                           - 'language' => 'en' (NewsAPI language filter)
     *                           - 'from'     => Carbon|string|int (ISO8601 / parseable / unix ts) – lower bound
     *                           - 'to'       => Carbon|string|int – upper bound
     *                           - 'cache'    => int minutes (override default cache window)
     * @return array             Normalized NewsAPI JSON (status, totalResults, articles[...])
     */
    public function searchEverything(string $keyword, array $options = []): array
    {
        $keyword = trim($keyword);

        // Return an empty, consistent payload when keyword is empty (good for UX).
        if ($keyword === '') {
            return ['status' => 'ok', 'totalResults' => 0, 'articles' => []];
        }

        // Extract and sanitize options
        $language     = $this->sanitizeLanguage((string)($options['language'] ?? $this->defaultLanguage));
        $from         = $this->normalizeDate($options['from'] ?? now()->subDays(7)); // default: last 7 days
        $to           = $this->normalizeDate($options['to']   ?? now());
        $cacheMinutes = (int)($options['cache'] ?? $this->defaultCacheMinutes);

        // Cache key MUST include all search-defining params to avoid cross-pollution
        $cacheKey = sprintf(
            'newsapi:everything:q=%s|lang=%s|from=%s|to=%s|bucket=%s',
            Str::lower($keyword),
            $language,
            $from,
            $to,
            now()->format('YmdH') // hour bucket to naturally rotate cache
        );

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($keyword, $language, $from, $to) {
            $endpoint = "{$this->base}/v2/everything";

            try {
                // Perform HTTP request with retries for transient errors.
                $response = Http::withHeaders([
                    'X-Api-Key' => $this->key,
                ])
                    ->connectTimeout(5)  // faster fail on DNS/TLS/connect issues
                    ->timeout(10)        // total request timeout
                    ->retry(2, 200)      // 2 retries, 200ms backoff
                    ->get($endpoint, [
                        // Core query
                        'q'         => $keyword,
                        // Filters
                        'language'  => $language,
                        'from'      => $from,  // ISO8601 lower bound
                        'to'        => $to,    // ISO8601 upper bound
                        // Shape & order
                        'pageSize'  => 20,     // only latest 20
                        'sortBy'    => 'publishedAt',
                        'searchIn'  => 'title,description,content',
                    ]);
            } catch (\Illuminate\Http\Client\RequestException $e) {
                // Wrap Laravel's RequestException into a generic RuntimeException
                $msg = $e->response?->json('message') ?? $e->getMessage();
                throw new \RuntimeException('NewsAPI request failed: ' . $msg, $e->getCode(), $e);
            }

            // Check manually if response is still marked as failed (non-2xx, etc.)
            if ($response->failed()) {
                // NewsAPI usually returns { status, code, message } on errors
                $body = $response->json();
                $msg  = $body['message'] ?? $response->body();

                // Surface rate-limit context if available
                $rateRemaining = $response->header('X-RateLimit-Remaining');
                $rateReset     = $response->header('X-RateLimit-Reset');

                $detail = $msg;
                if ($rateRemaining !== null || $rateReset !== null) {
                    $detail .= sprintf(
                        ' (rate-limit: remaining=%s, reset=%s)',
                        $rateRemaining ?? 'n/a',
                        $rateReset ?? 'n/a'
                    );
                }

                throw new \RuntimeException('NewsAPI request failed: ' . $detail, $response->status());
            }

            // Parse body as array
            $data = (array) $response->json();

            // Defensive normalization: ensure required keys exist
            $data['status']       = $data['status']        ?? 'ok';
            $data['totalResults'] = (int)($data['totalResults'] ?? 0);
            $data['articles']     = is_array($data['articles'] ?? null) ? $data['articles'] : [];

            // Safety: trim to 20 articles just in case
            $data['articles'] = array_slice($data['articles'], 0, 20);

            // Normalize article timestamps to ISO8601 strings for consistent DB storage
            foreach ($data['articles'] as &$article) {
                if (!empty($article['publishedAt'])) {
                    $article['publishedAt'] = Carbon::parse($article['publishedAt'])->toIso8601String();
                }
            }

            return $data;
        });
    }

    /**
     * Validate/sanitize language: fallback to default if the value looks wrong.
     * (NewsAPI uses a limited set; we don't hard-fail to keep UX smooth.)
     */
    private function sanitizeLanguage(string $language): string
    {
        $language = strtolower(trim($language));
        if ($language === '') {
            return $this->defaultLanguage;
        }

        // Lightweight allow-list; extend as needed
        $allowed = ['ar', 'de', 'en', 'es', 'fr', 'he', 'it', 'nl', 'no', 'pt', 'ru', 'sv', 'ud', 'zh', 'bg'];
        return in_array($language, $allowed, true) ? $language : $this->defaultLanguage;
    }

    /**
     * Normalize a date input to ISO8601 (Y-m-d\TH:i:sP).
     * Accepts Carbon, numeric timestamps, or parseable strings.
     */
    private function normalizeDate(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value)->toIso8601String();
        }

        // Let Carbon try to parse strings like '2025-08-20', 'yesterday', etc.
        return Carbon::parse((string) $value)->toIso8601String();
    }
}
