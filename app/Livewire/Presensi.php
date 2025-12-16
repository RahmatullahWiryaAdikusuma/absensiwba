<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Schedule;
use App\Models\Leave;
use App\Models\LeaveCuti;
use App\Models\Attendance;
use App\Models\Shift;
use App\Events\AttendanceRecorded;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class Presensi extends Component
{
    use WithFileUploads;

    public $latitude;
    public $longitude;
    public $photo;
    public $shift_id;
    public $shifts;
    public $locationHistory; 
    public $userStatus = []; 

    public function mount()
    {
        $user = Auth::user();
        $schedule = Schedule::with(['shift', 'office'])->where('user_id', $user->id)->first();
        
        $this->shifts = Shift::all();
        
        if ($schedule) {
            $this->shift_id = $schedule->shift_id;
            
            $this->userStatus = [
                'is_wfa' => (bool) $schedule->is_wfa,
                'is_banned' => (bool) $schedule->is_banned,
                'office_lat' => $schedule->office ? $schedule->office->latitude : 0,
                'office_lng' => $schedule->office ? $schedule->office->longitude : 0,
                'office_radius' => $schedule->office ? ($schedule->office->radius ?? 50) : 50,
            ];
        } else {
            $this->userStatus = [
                'is_wfa' => false,
                'is_banned' => false,
                'office_lat' => 0,
                'office_lng' => 0,
                'office_radius' => 50,
            ];

            if ($this->shifts->isNotEmpty()) {
                $this->shift_id = $this->shifts->first()->id;
            }
        }
    }

    // Tidak perlu cek radius di setiap update koordinat JS, cukup saat store saja untuk efisiensi
    public function updatedLatitude() { }
    public function updatedLongitude() { }

    public function render()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
                            ->whereNull('end_time')->latest()->first();
                            
        if (!$attendance) {
            $attendance = Attendance::where('user_id', $user->id)->latest()->first();
            if ($attendance && $attendance->end_time && !$attendance->created_at->isToday()) {
                $attendance = null;
            }
        }

        $schedule = Schedule::with('office')->where('user_id', $user->id)->first();

        return view('livewire.presensi', [
            'schedule' => $schedule,
            'attendance' => $attendance,
        ]);
    }

    public function distance($lat1, $lon1, $lat2, $lon2)
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) return 0;
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        return ($dist * 60 * 1.1515 * 1.609344);
    }

    public function store()
    {
        $this->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'photo' => 'required|image|max:10240',
            'shift_id' => 'required|exists:shifts,id', 
        ]);

        $user = Auth::user();
        $schedule = Schedule::with(['office', 'shift'])->where('user_id', $user->id)->first();

        if ($schedule && $schedule->is_banned) {
            session()->flash('error', 'Akun Anda dibekukan (Banned). Tidak bisa absen.');
            return;
        }

        // Cek Radius (Server Side)
        $insideRadius = false;
        if ($schedule) {
            if ($schedule->is_wfa) {
                $insideRadius = true;
            } elseif ($schedule->office) {
                $dist = $this->distance($this->latitude, $this->longitude, $schedule->office->latitude, $schedule->office->longitude);
                $radiusKm = ($schedule->office->radius ?? 50) / 1000;
                $insideRadius = $dist <= $radiusKm;
            }
        }

        if (!$insideRadius) {
            session()->flash('error', 'Validasi Gagal: Anda terdeteksi di luar radius kantor.');
            return;
        }

        $today = Carbon::today()->format('Y-m-d');
        
        $isOnLeave = Leave::where('user_id', $user->id)->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->exists();
        $isOnCuti = LeaveCuti::where('user_id', $user->id)->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->exists();

        if ($isOnLeave || $isOnCuti) {
            session()->flash('error', 'Anda sedang Cuti/Izin.');
            return;
        }

        if ($schedule) {
            $activeAttendance = Attendance::where('user_id', $user->id)
                            ->whereNull('end_time')->latest()->first();
            
            $now = Carbon::now();
            $photoPath = $this->photo->store('attendance-photos', 'public');
            $selectedShift = Shift::find($this->shift_id);

            $refLat = $schedule->is_wfa ? $this->latitude : $schedule->office->latitude;
            $refLng = $schedule->is_wfa ? $this->longitude : $schedule->office->longitude;

            if ($activeAttendance) {
                $activeAttendance->update([
                    'end_latitude' => $this->latitude,
                    'end_longitude' => $this->longitude,
                    'end_time' => $now->toDateTimeString(),
                    'end_image' => $photoPath,
                ]);
                event(new AttendanceRecorded($activeAttendance, 'check_out'));
                session()->flash('message', 'Absen Pulang Berhasil.');
            } else {
                $newAttendance = Attendance::create([
                    'user_id' => $user->id,
                    'schedule_latitude' => $refLat,
                    'schedule_longitude' => $refLng,
                    'schedule_start_time' => $selectedShift->start_time,
                    'schedule_end_time' => $selectedShift->end_time,
                    'start_latitude' => $this->latitude,
                    'start_longitude' => $this->longitude,
                    'start_time' => $now->toDateTimeString(),
                    'start_image' => $photoPath,
                ]);
                event(new AttendanceRecorded($newAttendance, 'check_in'));
                session()->flash('message', 'Absen Masuk Berhasil.');
            }
            return redirect()->route('presensi');
        } else {
            session()->flash('error', 'Jadwal belum diatur.');
        }
    }
}