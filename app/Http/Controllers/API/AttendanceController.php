<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Leave;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
// === WAJIB ADA BARIS INI UNTUK NOTIFIKASI ===
use Filament\Notifications\Notification; 

class AttendanceController extends Controller
{
    public function getAttendanceToday()
    {
        $userId = auth()->user()->id;
        $today = now()->toDateString();
        $currentMonth = now()->month;

        $attendanceToday = Attendance::select('start_time', 'end_time')
                                ->where('user_id', $userId)
                                ->whereDate('created_at', $today)
                                ->first();

        $attendanceThisMonth = Attendance::select('start_time', 'end_time', 'created_at')
                                ->where('user_id', $userId)
                                ->whereMonth('created_at', $currentMonth)
                                ->get()
                                ->map(function ($attendance) {
                                    return [
                                        'start_time' => $attendance->start_time,
                                        'end_time' => $attendance->end_time,
                                        'date' => $attendance->created_at->toDateString()
                                    ];
                                });

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $attendanceToday,
                'this_month' => $attendanceThisMonth
            ],
            'message' => 'Success get attendance today'
        ]);
    }

    public function getSchedule()
    {
        $schedule = Schedule::with(['office', 'shift'])
                        ->where('user_id', auth()->user()->id)
                        ->first();
        
        $today = Carbon::today()->format('Y-m-d');
        
        $approvedLeave = Leave::where('user_id', Auth::user()->id)
                            ->where('status', 'approved')
                            ->whereDate('start_date', '<=', $today)
                            ->whereDate('end_date', '>=', $today)
                            ->exists();

        if ($approvedLeave) {
            return response()->json([
                'success' => true,
                'message' => 'Anda tidak dapat melakukan presensi karena sedang cuti',
                'data' => null
            ]);
        }

        if ($schedule && $schedule->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'You are banned',
                'data' => null
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Success get schedule',
                'data' => $schedule
            ]);
        }
    }

    public function store(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $user = Auth::user(); // Ambil data user
        $userId = $user->id;
        $schedule = Schedule::with(['office', 'shift'])->where('user_id', $userId)->first();
        $today = Carbon::today()->format('Y-m-d');

        $approvedLeave = Leave::where('user_id', $userId)
                            ->where('status', 'approved')
                            ->whereDate('start_date', '<=', $today)
                            ->whereDate('end_date', '>=', $today)
                            ->exists();

        if ($approvedLeave) {
            return response()->json([
                'success' => true,
                'message' => 'Anda tidak dapat melakukan presensi karena sedang cuti',
                'data' => null
            ]);
        }

        if ($schedule) {
            $attendance = Attendance::where('user_id', $userId)
                            ->whereDate('created_at', $today)
                            ->first();

            // Ambil semua Admin untuk dikirimi notifikasi
            $admins = User::role('super_admin')->get();

            if (!$attendance) {
                // === ABSEN MASUK ===
                $attendance = Attendance::create([
                    'user_id' => $userId,
                    'schedule_latitude' => $schedule->office->latitude,
                    'schedule_longitude' => $schedule->office->longitude,
                    'schedule_start_time' => $schedule->shift->start_time,
                    'schedule_end_time' => $schedule->shift->end_time,
                    'start_latitude' => $request->latitude,
                    'start_longitude' => $request->longitude,
                    'start_time' => now(),
                    'end_time' => null,
                    'end_latitude' => null,
                    'end_longitude' => null,
                ]);

                // 1. KIRIM NOTIF KE KARYAWAN (KONFIRMASI)
                Notification::make()
                    ->title('Absen Masuk Berhasil')
                    ->body("Selamat bekerja! Absen tercatat pukul " . now()->format('H:i'))
                    ->success()
                    ->sendToDatabase($user);

                // 2. KIRIM NOTIF KE ADMIN (MONITORING)
                Notification::make()
                    ->title('Karyawan Masuk')
                    ->body("{$user->name} baru saja absen masuk.")
                    ->info()
                    ->sendToDatabase($admins);

                return response()->json([
                    'success' => true,
                    'message' => 'Absen masuk berhasil',
                    'data' => $attendance
                ]);

            } else { 
                // === ABSEN PULANG ===
                if ($attendance->end_time !== null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah melakukan absen pulang hari ini',
                        'data' => null
                    ], 400);
                }

                $attendance->update([
                    'end_latitude' => $request->latitude,
                    'end_longitude' => $request->longitude,
                    'end_time' => now(),
                ]);

                // 1. KIRIM NOTIF KE KARYAWAN
                Notification::make()
                    ->title('Absen Pulang Berhasil')
                    ->body("Hati-hati di jalan! Absen pulang tercatat pukul " . now()->format('H:i'))
                    ->success()
                    ->sendToDatabase($user);

                // 2. KIRIM NOTIF KE ADMIN
                Notification::make()
                    ->title('Karyawan Pulang')
                    ->body("{$user->name} baru saja absen pulang.")
                    ->warning()
                    ->sendToDatabase($admins);

                return response()->json([
                    'success' => true,
                    'message' => 'Absen pulang berhasil',
                    'data' => $attendance
                ]);
            }

        } else {
            return response()->json([
                'success' => false,
                'message' => 'No schedule found',
                'data' => null
            ]);
        }
    }

    public function getAttendanceByMonthYear($month, $year)
    {
        $validator = Validator::make(['month' => $month, 'year' => $year], [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2023|max:'.date('Y')
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $userId = auth()->user()->id;
        $attendanceList = Attendance::select('start_time', 'end_time', 'created_at')
                                ->where('user_id', $userId)
                                ->whereMonth('created_at', $month)
                                ->whereYear('created_at', $year)
                                ->get()
                                ->map(function ($attendance) {
                                    return [
                                        'start_time' => $attendance->start_time,
                                        'end_time' => $attendance->end_time,
                                        'date' => $attendance->created_at->toDateString()
                                    ];
                                });

        return response()->json([
            'success' => true,
            'data' => $attendanceList,
            'message' => 'Success get attendance by month and year'
        ]);
    }

    public function banned()
    {
        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
        if ($schedule) {
            $schedule->update([
                'is_banned' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Success banned schedule',
            'data' => $schedule
        ]);
    }

    public function getImage()
    {
        $user = auth()->user();
        return response()->json([
            'success' => true,
            'message' => 'Success get image',
            'data' => $user->image_url
        ]);
    }
}