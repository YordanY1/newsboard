<?php

namespace App\Livewire\News;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\NewsApiService;
use App\Models\NewsSearch;
use App\Models\NewsArticle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class Search extends Component
{
    use WithPagination;

    // prettier pagination (Tailwind)
    protected string $paginationTheme = 'tailwind';

    public string $keyword = '';
    public string $errorMessage = '';
    public array $chartSeries = []; // [{date, count}]
    public bool $hasSearched = false;

    // Optional filters (match service options)
    public string $language = 'en';

    protected $queryString = [
        'keyword'  => ['except' => ''],
        'language' => ['except' => 'en'],
        'page'     => ['except' => 1],
    ];

    public function mount(): void
    {
        if ($this->keyword !== '') {
            $this->search();
        }
    }

    public function search(): void
    {
        $this->resetPage();
        $this->errorMessage = '';
        $this->hasSearched = true;

        if (trim($this->keyword) === '') {
            $this->errorMessage = 'Please type a keyword.';
            $this->chartSeries = [];
            return;
        }

        try {
            $service = app(NewsApiService::class);

            // keep API window aligned with the chart: last 7 days
            $from = now()->startOfDay()->subDays(6);
            $to   = now()->endOfDay();

            $data = $service->searchEverything($this->keyword, [
                'language' => $this->language,
                'from'     => $from,
                'to'       => $to,
            ]);

            DB::transaction(function () use ($data) {
                $search = NewsSearch::create([
                    'keyword'       => $this->keyword,
                    'total_results' => (int) $data['totalResults'],
                    'raw_json'      => $data,
                    'fetched_at'    => now(),
                ]);

                // Build bulk insert for denormalized articles
                $articles = [];
                foreach ($data['articles'] as $a) {
                    $articles[] = [
                        'news_search_id' => $search->id,
                        'source_name'    => $a['source']['name'] ?? null,
                        'title'          => $a['title'] ?? '[no title]',
                        'url'            => $a['url'] ?? '',
                        'published_at'   => !empty($a['publishedAt']) ? Carbon::parse($a['publishedAt']) : null,
                        'author'         => $a['author'] ?? null,
                        // store the requested language (NewsAPI doesn’t return it in /everything)
                        'language'       => $this->language,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
                if ($articles) {
                    NewsArticle::insert($articles);
                }

                // Keep only last 20 searches for this keyword
                $toKeep = 20;
                $old = NewsSearch::where('keyword', $this->keyword)
                    ->orderByDesc('id')
                    ->skip($toKeep)
                    ->take(PHP_INT_MAX)
                    ->pluck('id');

                if ($old->isNotEmpty()) {
                    NewsSearch::whereIn('id', $old)->delete();
                }
            });

            $this->buildChart();
        } catch (\Throwable $e) {
            // nicer message for typical API/key/rate cases
            $this->errorMessage = 'Error while fetcing the API: ' . $e->getMessage();
            $this->chartSeries = [];
        }
    }

    public function buildChart(): void
    {
        $from = now()->startOfDay()->subDays(6);
        $to   = now()->endOfDay();

        $latestSearch = NewsSearch::where('keyword', $this->keyword)
            ->latest('fetched_at')
            ->first();

        if (!$latestSearch) {
            $this->chartSeries = [];
            return;
        }

        $rows = NewsArticle::where('news_search_id', $latestSearch->id)
            ->whereBetween('published_at', [$from, $to])
            ->selectRaw('DATE(published_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $series = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $found = $rows->firstWhere('d', $day);
            $series[] = [
                'date'  => $day,
                'count' => $found ? (int) $found->c : 0,
            ];
        }

        $this->chartSeries = $series;
    }

    public function getArticlesProperty()
    {
        $latestSearch = NewsSearch::where('keyword', $this->keyword)
            ->latest('fetched_at')
            ->first();

        if (!$latestSearch) {
            // empty paginator so the view can still call ->links()
            return new LengthAwarePaginator([], 0, 10, 1, [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]);
        }

        return NewsArticle::where('news_search_id', $latestSearch->id)
            ->orderByDesc('published_at')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.news.search', [
            'articles'    => $this->articles, // WithPagination
            'chartSeries' => $this->chartSeries,
        ])->layout('layouts.app', [
            'title' => $this->keyword ? "News Search: {$this->keyword}" : 'News Search',
        ]);
    }
}
