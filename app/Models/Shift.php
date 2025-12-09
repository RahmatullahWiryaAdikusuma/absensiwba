<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Shift extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'position_id', 'leave_balance'])  
            ->logOnlyDirty()  
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "User ini telah di-{$eventName}");  
    }
}
