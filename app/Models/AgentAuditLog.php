<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'action',
        'target_type',
        'target_id',
        'reason',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
