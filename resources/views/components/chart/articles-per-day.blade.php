@props([
    'series' => [],
    'title' => 'Articles per day (last 7 days)',
    'height' => '280px',
])

<div x-data x-init="window.NewsChart.mount($el.querySelector('canvas'), JSON.parse($el.querySelector('canvas').dataset.series || '[]'))" x-transition
    class="bg-white border rounded-2xl shadow-md p-6 mb-8 transform transition duration-500 ease-out">
    <h2 class="font-semibold text-lg mb-3 tracking-tight">{{ $title }}</h2>

    <div style="height: {{ $height }};" class="overflow-hidden">
        <canvas id="articlesChart" data-series='@json($series)' aria-label="Articles per day chart"
            role="img" class="w-full h-full"></canvas>
    </div>
</div>
