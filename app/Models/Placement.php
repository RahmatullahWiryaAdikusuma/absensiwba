<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Placement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'office_id',
        'start_date',
        'end_date',
        'placement_status',
        'daily_rate',
        'is_backup',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_backup' => 'boolean',
        'daily_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}