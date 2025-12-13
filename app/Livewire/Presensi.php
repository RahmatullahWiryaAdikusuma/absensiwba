<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Schedule;
use App\Models\Leave;
use App\Models\Attendance;
use App\Models\Shift; // <--- Import Model Shift
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
    
    // TAMBAHAN: Variable untuk pilihan Shift
    public $shift_id;
    public $shifts; 

    public function mount()
    {
        $user = Auth::user();
        $schedule = Schedule::where('user_id', $user->id)->first();

        // Ambil semua data shift untuk dropdown
        $this->shifts = Shift::all();

        // Set default shift sesuai jadwal user (jika ada)
        if ($schedule) {
            $this->shift_id = $schedule->shift_id;
        } elseif ($this->shifts->isNotEmpty()) {
            $this->shift_id = $this->shifts->first()->id;
        }
    }

    public function updatedLatitude() {
        $this->checkRadius();
    }
    public function updatedLongitude() {
        $this->checkRadius();
    }

    public function render()
    {
        $user = Auth::user();
        $schedule = Schedule::where('user_id', $user->id)->first();

        // Cari data absen terakhir yang BELUM dipulangin (Masih Open)
        $attendance = Attendance::where('user_id', $user->id)
                            ->whereNull('end_time')
                            ->latest()
                            ->first();

        // Jika tidak ada yang gantung, cari history hari ini untuk tampilan
        if (!$attendance) {
            $attendance = Attendance::where('user_id', $user->id)
                                ->latest()
                                ->first();
            
            if ($attendance && $attendance->end_time && !$attendance->created_at->isToday()) {
                $attendance = null;
            }
        }

        return view('livewire.presensi', [
            'schedule' => $schedule,
            'attendance' => $attendance
        ]);
    }

    public function checkRadius()
    {
        $schedule = Schedule::where('user_id', Auth::user()->id)->first();

        if ($schedule && $schedule->officeLocation) {
            $officeLat = $schedule->officeLocation->latitude;
            $officeLng = $schedule->officeLocation->longitude;
            $radiusKm = ($schedule->officeLocation->radius ?? 50) / 1000;

            $distance = $this->distance($this->latitude, $this->longitude, $officeLat, $officeLng);
            $this->insideRadius = $distance <= $radiusKm;
        }
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
            'shift_id' => 'required|exists:shifts,id', // Validasi Shift harus dipilih
        ]);

        $user = Auth::user();
        $schedule = Schedule::where('user_id', $user->id)->first();
        $this->checkRadius();

        if (!$this->insideRadius) {
            session()->flash('error', 'Anda berada di luar radius kantor!');
            return;
        }

        // Cek Cuti
        $today = Carbon::today()->format('Y-m-d');
        $isOnLeave = Leave::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($isOnLeave) {
            session()->flash('error', 'Anda sedang cuti, tidak bisa absen.');
            return;
        }

        if ($schedule) {
            // Cek absen gantung
            $activeAttendance = Attendance::where('user_id', $user->id)
                            ->whereNull('end_time')
                            ->latest()
                            ->first();
            
            $now = Carbon::now();
            $photoPath = $this->photo->store('attendance-photos', 'public');

            // Ambil Data Shift yang DIPILIH (Bukan default schedule)
            // Ini kunci agar Satpam A bisa pakai jam Shift 2
            $selectedShift = Shift::find($this->shift_id);

            if ($activeAttendance) {
                // === ABSEN PULANG ===
                $activeAttendance->update([
                    'end_latitude' => $this->latitude,
                    'end_longitude' => $this->longitude,
                    'end_time' => $now->toDateTimeString(),
                    'end_image' => $photoPath,
                ]);

                event(new AttendanceRecorded($activeAttendance, 'check_out'));

            } else {
                // === ABSEN MASUK ===
                // Simpan jam shift sesuai pilihan di dropdown
                $newAttendance = Attendance::create([
                    'user_id' => $user->id,
                    'schedule_latitude' => $schedule->officeLocation->latitude,
                    'schedule_longitude' => $schedule->officeLocation->longitude,
                    
                    // PENTING: Gunakan data dari Shift yang dipilih user
                    'schedule_start_time' => $selectedShift->start_time,
                    'schedule_end_time' => $selectedShift->end_time,
                    
                    'start_latitude' => $this->latitude,
                    'start_longitude' => $this->longitude,
                    'start_time' => $now->toDateTimeString(),
                    'start_image' => $photoPath,
                ]);

                event(new AttendanceRecorded($newAttendance, 'check_in'));
            }

            return redirect()->route('presensi');
        }
    }
}