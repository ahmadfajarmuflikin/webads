<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_set_id',
        'meta_ad_id',
        'name',
        'status',
        'creative_payload',
    ];

    protected $casts = [
        'creative_payload' => 'array',
    ];

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(AdSet::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(AdInsightDaily::class, 'meta_entity_id', 'meta_ad_id')
            ->where('meta_entity_type', 'AD');
    }
}
