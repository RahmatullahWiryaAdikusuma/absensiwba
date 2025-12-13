<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCuti extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $table = 'leave_cutis';

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'note',
        'surat_cuti',  
        'sisa_cuti',   
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}