<?php

namespace Tests\Feature;

use App\Services\NewsApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class NewsApiServiceTest extends TestCase
{
    #[Test]
    public function it_fetches_and_normalizes_articles_from_newsapi()
    {
        // Fake response from NewsAPI
        $fakeResponse = [
            'status' => 'ok',
            'totalResults' => 2,
            'articles' => [
                [
                    'source' => ['name' => 'TechCrunch'],
                    'author' => 'John Doe',
                    'title' => 'Laravel 12 released',
                    'description' => 'The next big thing in PHP...',
                    'url' => 'https://example.com/laravel12',
                    'publishedAt' => now()->subDay()->toIso8601String(),
                ],
                [
                    'source' => ['name' => 'The Verge'],
                    'author' => 'Jane Smith',
                    'title' => 'AI is taking over',
                    'description' => 'AI dominates headlines again...',
                    'url' => 'https://example.com/ai',
                    'publishedAt' => now()->toIso8601String(),
                ],
            ],
        ];

        Http::fake([
            'newsapi.org/*' => Http::response($fakeResponse, 200),
        ]);

        $service = new NewsApiService();
        $result = $service->searchEverything('Laravel');

        // Assert the shape of the result
        $this->assertEquals('ok', $result['status']);
        $this->assertEquals(2, $result['totalResults']);
        $this->assertCount(2, $result['articles']);

        // Assert normalization of publishedAt
        $this->assertTrue(Carbon::hasFormat($result['articles'][0]['publishedAt'], 'Y-m-d\TH:i:sP'));

        // Assert important fields
        $this->assertEquals('Laravel 12 released', $result['articles'][0]['title']);
        $this->assertEquals('TechCrunch', $result['articles'][0]['source']['name']);
    }

    #[Test]
    public function it_throws_an_exception_on_failed_response()
    {
        Http::fake([
            'newsapi.org/*' => Http::response(['message' => 'Invalid API key'], 401),
        ]);

        $this->expectException(\RuntimeException::class);

        $service = new NewsApiService();
        $service->searchEverything('Laravel');
    }
}
