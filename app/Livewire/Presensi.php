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
    public $insideRadius = false;
    public $photo;
    public $shift_id;
    public $shifts;
    public $locationHistory; 

    public function mount()
    {
        $user = Auth::user();

        // 1. CEK BANNED SAAT PERTAMA LOAD
        if ($user->is_banned) {
            // Kita biarkan saja, nanti di View kita blokir tampilannya
        }

        $schedule = Schedule::with(['shift', 'office'])->where('user_id', $user->id)->first();
        $this->shifts = Shift::all();
        
        if ($schedule) {
            $this->shift_id = $schedule->shift_id;
        } elseif ($this->shifts->isNotEmpty()) {
            $this->shift_id = $this->shifts->first()->id;
        }
    }

    public function updatedLatitude() { $this->checkRadius(); }
    public function updatedLongitude() { $this->checkRadius(); }

    public function render()
    {
        $user = Auth::user();
        $schedule = Schedule::with('office')->where('user_id', $user->id)->first();

        $attendance = Attendance::where('user_id', $user->id)
                            ->whereNull('end_time')->latest()->first();
                            
        if (!$attendance) {
            $attendance = Attendance::where('user_id', $user->id)->latest()->first();
            if ($attendance && $attendance->end_time && !$attendance->created_at->isToday()) {
                $attendance = null;
            }
        }

        return view('livewire.presensi', [
            'schedule' => $schedule,
            'attendance' => $attendance,
            'user' => $user // PASSING DATA USER KE VIEW
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

    public function checkRadius()
    {
        $user = Auth::user();
        
        // --- LOGIKA 1: JIKA USER WFA, BYPASS RADIUS ---
        // Karyawan WFA boleh absen dari mana saja (rumah/klien)
        if ($user->is_wfa) {
            $this->insideRadius = true;
            return;
        }
        // ----------------------------------------------

        $schedule = Schedule::with('office')->where('user_id', $user->id)->first();

        if ($schedule && $schedule->office) {
            $officeLat = $schedule->office->latitude;
            $officeLng = $schedule->office->longitude;
            $radiusKm = ($schedule->office->radius ?? 50) / 1000; 

            $distance = $this->distance($this->latitude, $this->longitude, $officeLat, $officeLng);
            $this->insideRadius = $distance <= $radiusKm;
        } else {
            $this->insideRadius = false; 
        }
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

        // --- LOGIKA 2: CEK BANNED SAAT SUBMIT ---
        if ($user->is_banned) {
            session()->flash('error', 'Akun Anda dinonaktifkan. Anda tidak bisa absen.');
            return;
        }
        
        $schedule = Schedule::with(['office', 'shift'])->where('user_id', $user->id)->first();
        
        // Cek radius ulang (Server Side Protection)
        $this->checkRadius();

        // Jika BUKAN WFA, dan diluar radius -> Tolak
        if (!$this->insideRadius) {
            if (!$user->is_wfa) {
                session()->flash('error', 'Posisi Anda diluar radius kantor!');
                return;
            }
        }

        $today = Carbon::today()->format('Y-m-d');
        
        // Cek Cuti
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

            // Tentukan koordinat referensi jadwal
            // Jika WFA: Koordinat jadwal dianggap sama dengan posisi user saat ini (agar report map tetap bagus)
            // Jika WFO: Koordinat jadwal adalah koordinat kantor asli
            $refLat = $user->is_wfa ? $this->latitude : $schedule->office->latitude;
            $refLng = $user->is_wfa ? $this->longitude : $schedule->office->longitude;

            if ($activeAttendance) {
                // Absen Pulang
                $activeAttendance->update([
                    'end_latitude' => $this->latitude,
                    'end_longitude' => $this->longitude,
                    'end_time' => $now->toDateTimeString(),
                    'end_image' => $photoPath,
                ]);
                event(new AttendanceRecorded($activeAttendance, 'check_out'));
                session()->flash('message', 'Hati-hati di jalan! Absen pulang sukses.');
            } else {
                // Absen Masuk
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
                session()->flash('message', 'Selamat bekerja! Absen masuk sukses.');
            }
            return redirect()->route('presensi');
        } else {
            session()->flash('error', 'Jadwal belum diatur.');
        }
    }
}