<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('news_searches', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->index();
            $table->unsignedInteger('total_results')->default(0);
            $table->json('raw_json'); 
            $table->timestamp('fetched_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('news_searches');
    }
};
