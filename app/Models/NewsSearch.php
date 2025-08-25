<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsSearch extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'raw_json' => 'array',
        'fetched_at' => 'datetime',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class);
    }
}
