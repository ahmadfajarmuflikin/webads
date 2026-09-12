<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_account_id',
        'name',
        'currency',
        'timezone_name',
        'access_token',
        'target_roas',
        'target_cpa',
        'status',
    ];

    protected $casts = [
        'target_roas' => 'decimal:2',
        'target_cpa' => 'decimal:2',
        'access_token' => 'encrypted',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function adSets(): HasMany
    {
        return $this->hasMany(AdSet::class);
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(AutomationRule::class);
    }
}
