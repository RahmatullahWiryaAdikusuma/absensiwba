<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedule;

class EmployeeTodayStatus extends Widget
{
    protected static string $view = 'filament.widgets.employee-today-status';
    protected static ?int $sort = 0; // Paling atas
    
    // Agar lebar widget penuh (full width)
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return !Auth::user()->hasRole('super_admin');
    }

    // Kirim data ke view blade
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        // Ambil Jadwal User
        $schedule = Schedule::with(['shift', 'office'])->where('user_id', $user->id)->first();
        
        // Ambil Penempatan Aktif (Dari kode Placement yang kita buat sebelumnya)
        // Pastikan relasi activePlacement() ada di User.php
        $placement = $user->activePlacement()->with('office')->first();

        return [
            'user' => $user,
            'schedule' => $schedule,
            'placement' => $placement,
        ];
    }
}