<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_account_id',
        'name',
        'entity_level',
        'rule_type',
        'conditions',
        'action',
        'action_value',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'action_value' => 'decimal:2',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }
}
