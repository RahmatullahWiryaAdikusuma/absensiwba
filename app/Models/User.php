<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Carbon\Carbon;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, logsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'position_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getSisaCutiAttribute(): int
    {
        $year = Carbon::now()->year;
        $jatahTahunan = 12;
 
        $cutiTerpakai = $this->leaveCutis()
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->get()
            ->sum(function ($leave) { 
                return Carbon::parse($leave->start_date)
                    ->diffInDays(Carbon::parse($leave->end_date)) + 1;
            });

        return max(0, $jatahTahunan - $cutiTerpakai);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'position_id',])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "User ini telah di-{$eventName}");
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function position()
    {
    return $this->belongsTo(Position::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? url('storage/'. $this->image) : null;
    }

    

    public function canAccessPanel(Panel $panel): bool
    { 
        return $this->is_active;
    }
 
    public function leave(): HasMany
    {
        return $this->hasMany(Leave::class);
    } 

    public function leaveCutis(): HasMany
    {
        return $this->hasMany(LeaveCuti::class);
    }
}
