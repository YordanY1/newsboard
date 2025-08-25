<?php

namespace Tests\Feature;

use App\Livewire\News\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsSearchComponentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_validation_error_when_keyword_is_empty()
    {
        Livewire::test(Search::class)
            ->call('search')
            ->assertSee('Please type a keyword.');
    }

    #[Test]
    public function it_fetches_and_displays_articles()
    {
        Http::fake([
            'newsapi.org/*' => Http::response([
                'status' => 'ok',
                'totalResults' => 1,
                'articles' => [[
                    'source' => ['name' => 'The Verge'],
                    'author' => 'Jane Smith',
                    'title' => 'Laravel testing made easy',
                    'url' => 'https://example.com/test',
                    'publishedAt' => now()->toIso8601String(),
                ]],
            ], 200),
        ]);

        Livewire::test(Search::class)
            ->set('keyword', 'Laravel')
            ->call('search')
            ->assertSee('Laravel testing made easy')
            ->assertSee('The Verge')
            ->assertSee('Published:');
    }

    #[Test]
    public function it_displays_chart_canvas_after_successful_search()
    {
        Http::fake([
            'newsapi.org/*' => Http::response([
                'status' => 'ok',
                'totalResults' => 1,
                'articles' => [[
                    'source' => ['name' => 'TechCrunch'],
                    'author' => 'John Doe',
                    'title' => 'AI takes over',
                    'url' => 'https://example.com/ai',
                    'publishedAt' => now()->toIso8601String(),
                ]],
            ], 200),
        ]);

        Livewire::test(Search::class)
            ->set('keyword', 'AI')
            ->call('search')
            ->assertSee('Articles per day (last 7 days)')
            ->assertSeeHtml('<canvas id="articlesChart"');
    }
}
