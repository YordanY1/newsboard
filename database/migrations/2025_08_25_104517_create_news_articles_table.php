<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_search_id')->constrained()->cascadeOnDelete();
            $table->string('source_name')->nullable()->index();
            $table->string('title');
            $table->text('url');
            $table->timestamp('published_at')->nullable()->index();
            $table->string('author')->nullable();
            $table->string('language', 8)->nullable();
            $table->timestamps();

            $table->index(['news_search_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
