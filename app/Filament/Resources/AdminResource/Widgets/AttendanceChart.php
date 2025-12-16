<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Auth;

class AttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Kehadiran (7 Hari Terakhir)';
    protected static ?int $sort = 2;

    // Hanya tampil untuk Super Admin
    public static function canView(): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    protected function getData(): array
    {
        $data = Trend::model(Attendance::class)
            ->between(
                start: now()->subDays(6),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Kehadiran',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6', // Warna Biru
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => \Carbon\Carbon::parse($value->date)->format('d M')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}