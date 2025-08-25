<div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">News Search</h1>

    <form wire:submit.prevent="search" class="flex gap-2 mb-6">
        <input type="text" wire:model.defer="keyword" placeholder="Try: Laravel, AI, SpaceX..."
            class="w-full border rounded px-3 py-2">
        <button type="submit" class="px-4 py-2 bg-black text-white rounded cursor-pointer" wire:loading.attr="disabled">
            <span wire:loading.remove>Search</span>
            <span wire:loading>Searching…</span>
        </button>
    </form>

    @if ($errorMessage)
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 mb-4">
            {{ $errorMessage }}
        </div>
    @endif

    @if ($hasSearched && empty($errorMessage))
        <x-chart.articles-per-day :series="$chartSeries" />

        {{-- Articles --}}
        <div class="bg-white border rounded divide-y">
            @forelse($articles as $a)
                <article class="p-4">
                    <h3 class="font-semibold">
                        <a href="{{ $a->url }}" target="_blank" rel="noopener noreferrer"
                            class="underline hover:no-underline">{{ $a->title }}</a>
                    </h3>
                    <div class="text-sm text-gray-600 mt-1">
                        <span>Source: {{ $a->source_name ?? '—' }}</span>
                        <span class="mx-2">•</span>
                        <span>
                            Published:
                            {{ optional($a->published_at)->timezone('Europe/Sofia')->format('Y-m-d H:i') ?? '—' }}
                        </span>
                    </div>
                </article>
            @empty
                <p class="p-4 text-gray-600">No articles.</p>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $articles->onEachSide(1)->links() }}
        </div>
    @endif
</div>
