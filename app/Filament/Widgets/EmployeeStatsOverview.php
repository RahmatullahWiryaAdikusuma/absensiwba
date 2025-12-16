<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EmployeeStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    // Tampil untuk SEMUA KECUALI Super Admin (Jadi khusus karyawan)
    public static function canView(): bool
    {
        return !Auth::user()->hasRole('super_admin');
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Hitung kehadiran bulan ini
        $attendanceMonth = Attendance::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        // Hitung keterlambatan bulan ini
        $lateMonth = Attendance::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get()
            ->filter(fn ($a) => $a->isLate())
            ->count();

        return [
            Stat::make('Sisa Cuti Tahunan', $user->sisa_cuti . ' Hari')
                ->description('Gunakan dengan bijak')
                ->icon('heroicon-o-briefcase')
                ->color($user->sisa_cuti < 3 ? 'danger' : 'success'),

            Stat::make('Kehadiran Bulan Ini', $attendanceMonth . ' Hari')
                ->icon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Terlambat Bulan Ini', $lateMonth . ' Kali')
                ->description($lateMonth > 0 ? 'Usahakan lebih tepat waktu' : 'Pertahankan!')
                ->icon('heroicon-o-clock')
                ->color($lateMonth > 0 ? 'danger' : 'success'),
        ];
    }
}