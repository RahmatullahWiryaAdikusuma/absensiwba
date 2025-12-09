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
use Filament\Notifications\Notification;  
use App\Events\AttendanceRecorded;

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
            return response()->json(['success' => false, 'message' => 'Validation error', 'data' => $validator->errors()], 422);
        }

        $user = Auth::user(); 
        $userId = $user->id;
        $schedule = Schedule::with(['office', 'shift'])->where('user_id', $userId)->first();
        $today = Carbon::today()->format('Y-m-d');

        // 1. Cek Cuti
        $approvedLeave = Leave::where('user_id', $userId)
                            ->where('status', 'approved')
                            ->whereDate('start_date', '<=', $today)
                            ->whereDate('end_date', '>=', $today)
                            ->exists();

        if ($approvedLeave) {
            return response()->json(['success' => true, 'message' => 'Anda sedang cuti', 'data' => null]);
        }

        // 2. Cek Jadwal & Proses Absen
        if ($schedule) {
            $attendance = Attendance::where('user_id', $userId)->whereDate('created_at', $today)->first();

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
                ]);

                // 🔥 TRIGGER EVENT: CHECK IN
                event(new AttendanceRecorded($attendance, 'check_in'));

                return response()->json(['success' => true, 'message' => 'Absen masuk berhasil', 'data' => $attendance]);

            } else { 
                // === ABSEN PULANG ===
                if ($attendance->end_time !== null) {
                    return response()->json(['success' => false, 'message' => 'Sudah absen pulang'], 400);
                }

                $attendance->update([
                    'end_latitude' => $request->latitude,
                    'end_longitude' => $request->longitude,
                    'end_time' => now(),
                ]);

                // 🔥 TRIGGER EVENT: CHECK OUT
                event(new AttendanceRecorded($attendance, 'check_out'));

                return response()->json(['success' => true, 'message' => 'Absen pulang berhasil', 'data' => $attendance]);
            }
        }
        
        return response()->json(['success' => false, 'message' => 'No schedule found']);
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