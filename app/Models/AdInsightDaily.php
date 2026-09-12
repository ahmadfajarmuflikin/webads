<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdInsightDaily extends Model
{
    use HasFactory;

    protected $table = 'ad_insights_daily';

    protected $fillable = [
        'meta_entity_type',
        'meta_entity_id',
        'date',
        'spend',
        'impressions',
        'clicks',
        'cpc',
        'ctr',
        'frequency',
        'conversions',
        'conversion_value',
        'roas',
        'cpa',
        'raw_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2',
        'cpc' => 'decimal:2',
        'ctr' => 'decimal:3',
        'frequency' => 'decimal:2',
        'conversion_value' => 'decimal:2',
        'roas' => 'decimal:2',
        'cpa' => 'decimal:2',
        'raw_metrics' => 'array',
    ];
}
