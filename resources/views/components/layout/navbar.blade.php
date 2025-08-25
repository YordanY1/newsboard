<header class="bg-white border-b shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <a href="{{ route('news.search') }}" class="text-lg font-bold text-indigo-600">
            {{ config('app.name', 'NewsBoard') }}
        </a>
        <nav class="flex items-center gap-4">
            <a href="{{ route('news.search') }}" class="text-sm font-medium hover:text-indigo-600 cursor-pointer">
                Search
            </a>
        </nav>
    </div>
</header>
