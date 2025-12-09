<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Schedule extends Model
{
    use HasFactory, LogsActivity;

    protected $casts = [
        'is_wfa' => 'boolean',
        'is_banned' => 'boolean'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'shift_id', 'office_id', 'is_wfa', 'is_banned'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Jadwal kerja telah di-{$eventName}");
    }

    protected $fillable = [
        'user_id',
        'shift_id',
        'office_id',
        'office_location_id',
        'is_wfa',
        'is_banned'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function officeLocation()  
    {
        return $this->belongsTo(OfficeLocation::class);
    }

}
