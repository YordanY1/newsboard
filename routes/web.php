<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\News\Search;

Route::get('/', Search::class)->name('news.search');
