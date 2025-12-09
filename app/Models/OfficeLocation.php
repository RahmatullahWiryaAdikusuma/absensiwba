<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeLocation extends Model
{
    protected $guarded = [];

    public function locations(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}