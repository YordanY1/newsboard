# NewsBoard – Laravel External API Integration Task

This project integrates with the [NewsAPI](https://newsapi.org/) service to search for news articles by keyword and display them with a simple chart.

---

## Features

-   Search articles by keyword (via NewsAPI `/v2/everything`).
-   Store raw JSON response in the database (keep only the latest 20 results).
-   Show article list with:
    -   Title (link to full article)
    -   Source
    -   Published date
-   Chart: number of articles per day (last 7 days).
-   Pagination (10 articles per page).
-   Caching to avoid redundant API calls.
-   Feature tests for service and component.

---

## Setup

### 1. Clone and install

```bash
git clone https://github.com/<your-username>/newsboard.git
cd newsboard
composer install
npm install
```

### 2. Environment

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

### 3. NewsAPI key

Get a free API key from [newsapi.org/register](https://newsapi.org/register)  
and add it to `.env`:

```
NEWSAPI_KEY=your_api_key_here
```

### 4. Database

Run migrations:

```bash
php artisan migrate
```

Default is SQLite (works out of the box). You can switch to MySQL/Postgres in `.env`.

### 5. Frontend build

```bash
npm run dev     # for local dev
npm run build   # for production
```

### 6. Run

```bash
php artisan serve
```

Visit: [http://localhost:8000](http://localhost:8000)

---

## Tests

Run the test suite:

```bash
php artisan test
```

Covers:

-   `NewsApiService` (fetch & error handling)
-   Livewire `Search` component (validation, rendering, chart)

---

## Project Structure

```
app/Livewire/News/Search.php        # Search component
app/Services/NewsApiService.php     # API service
app/Models/NewsSearch.php           # Search model
app/Models/NewsArticle.php          # Article model
resources/views/livewire/news/...   # Views
resources/js/chart/newsChart.js     # Chart.js logic
tests/Feature/...                   # Feature tests
```

---

## Notes

-   Free plan on NewsAPI has request limits → caching is used.
-   Keeps only the latest 20 searches per keyword.
-   Errors are handled gracefully and shown to the user.

---

## Author

Built using Laravel 12 + Livewire 3 + TailwindCSS.
