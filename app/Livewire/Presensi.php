<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads; 
use App\Models\Schedule;
use App\Models\Leave;
use App\Models\Attendance;
use App\Events\AttendanceRecorded; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class Presensi extends Component
{
    use WithFileUploads; // Gunakan trait ini

    public $latitude;
    public $longitude;
    public $insideRadius = false;
    public $photo; // Variabel foto

    public function updatedLatitude() {
        $this->checkRadius();
    }
    public function updatedLongitude() {
        $this->checkRadius();
    }

    public function render()
    {
        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
        $attendance = Attendance::where('user_id', Auth::user()->id)
                            ->whereDate('created_at', Carbon::today())
                            ->first();

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
            'photo' => 'required|image|max:10240', // Validasi foto
        ]);

        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
        $this->checkRadius();

        if (!$this->insideRadius) {
            session()->flash('error', 'Anda berada di luar radius kantor!');
            return;
        }

        // Cek Cuti
        $today = Carbon::today()->format('Y-m-d');
        $isOnLeave = Leave::where('user_id', Auth::user()->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($isOnLeave) {
            session()->flash('error', 'Anda sedang cuti, tidak bisa absen.');
            return;
        }

        if ($schedule) {
            $attendance = Attendance::where('user_id', Auth::user()->id)
                            ->whereDate('created_at', Carbon::today())
                            ->first();
            $now = Carbon::now();

            // Simpan Foto
            $photoPath = $this->photo->store('attendance-photos', 'public');

            if (!$attendance) {
                // --- ABSEN MASUK ---
                $newAttendance = Attendance::create([
                    'user_id' => Auth::user()->id,
                    'schedule_latitude' => $schedule->officeLocation->latitude,
                    'schedule_longitude' => $schedule->officeLocation->longitude,
                    'schedule_start_time' => $schedule->shift->start_time,
                    'schedule_end_time' => $schedule->shift->end_time,
                    'start_latitude' => $this->latitude,
                    'start_longitude' => $this->longitude,
                    'start_time' => $now->toDateTimeString(),
                    'start_image' => $photoPath,
                ]);

                event(new AttendanceRecorded($newAttendance, 'check_in'));

            } elseif (!$attendance->end_time) {
                // --- ABSEN PULANG ---
                $attendance->update([
                    'end_latitude' => $this->latitude,
                    'end_longitude' => $this->longitude,
                    'end_time' => $now->toDateTimeString(),
                    'end_image' => $photoPath,
                ]);

                // PEMICU NOTIFIKASI PULANG (PENTING!)
                event(new AttendanceRecorded($attendance, 'check_out'));
            }

            return redirect()->route('presensi');
        }
    }
}