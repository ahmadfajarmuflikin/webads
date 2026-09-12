<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_account_id',
        'meta_campaign_id',
        'name',
        'objective',
        'status',
        'buying_type',
        'daily_budget',
        'lifetime_budget',
        'bid_strategy',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function adSets(): HasMany
    {
        return $this->hasMany(AdSet::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(AdInsightDaily::class, 'meta_entity_id', 'meta_campaign_id')
            ->where('meta_entity_type', 'CAMPAIGN');
    }
}
