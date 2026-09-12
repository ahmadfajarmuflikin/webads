<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'ad_account_id',
        'meta_adset_id',
        'name',
        'status',
        'daily_budget',
        'optimization_goal',
        'targeting',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'targeting' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(AdInsightDaily::class, 'meta_entity_id', 'meta_adset_id')
            ->where('meta_entity_type', 'ADSET');
    }
}
