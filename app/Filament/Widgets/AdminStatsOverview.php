<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\LeaveCuti;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    protected function getStats(): array
    {
        $today = now()->format('Y-m-d');
         
        $attendanceTrend = Trend::model(Attendance::class)
            ->between(start: now()->subDays(6), end: now())
            ->perDay()
            ->count()
            ->map(fn (TrendValue $value) => $value->aggregate)
            ->toArray();
 
        $lateCount = Attendance::whereDate('created_at', today())
            ->get()
            ->filter(fn ($a) => $a->isLate())
            ->count();

        $absentCount = Leave::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count() 
            + LeaveCuti::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count();

        return [
            Stat::make('Total Karyawan', User::where('is_active', true)->count())
                ->description('Karyawan aktif')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Hadir Hari Ini', Attendance::whereDate('created_at', today())->count())
                ->description($lateCount . ' Terlambat')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-check-circle')
                ->color($lateCount > 0 ? 'warning' : 'success') ,

            Stat::make('Sedang Tidak Masuk', $absentCount)
                ->description("Cuti/Izin")
                ->icon('heroicon-o-document-text')
                ->color('danger'),
        ];
    }
}