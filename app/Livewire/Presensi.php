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

    public $photo;
    public $shift_id;
    public $shifts;
    
    // Variabel untuk menyimpan konfigurasi lokasi
    public $geoConfig = [];

    public function mount()
    {
        $user = Auth::user();
        $schedule = Schedule::with(['shift', 'office', 'officeLocation'])->where('user_id', $user->id)->first();
        
        // DEBUG: Log data schedule
        \Log::info('=== DEBUG MOUNT ===');
        \Log::info('User ID: ' . $user->id);
        \Log::info('Schedule exists: ' . ($schedule ? 'YES' : 'NO'));
        if ($schedule) {
            \Log::info('Schedule ID: ' . $schedule->id);
            \Log::info('Office exists: ' . ($schedule->office ? 'YES' : 'NO'));
            \Log::info('OfficeLocation exists: ' . ($schedule->officeLocation ? 'YES' : 'NO'));
            
            if ($schedule->officeLocation) {
                \Log::info('OfficeLocation ID: ' . $schedule->officeLocation->id);
                \Log::info('OfficeLocation Name: ' . $schedule->officeLocation->name);
                \Log::info('OfficeLocation Latitude: ' . $schedule->officeLocation->latitude);
                \Log::info('OfficeLocation Longitude: ' . $schedule->officeLocation->longitude);
                \Log::info('OfficeLocation Radius: ' . $schedule->officeLocation->radius);
            }
            \Log::info('is_wfa: ' . ($schedule->is_wfa ? 'true' : 'false'));
            \Log::info('is_banned: ' . ($schedule->is_banned ? 'true' : 'false'));
        }
        
        $this->shifts = Shift::all();
        
        if ($schedule) {
            $this->shift_id = $schedule->shift_id;
            
            // Siapkan data Geo Config untuk dikirim ke Frontend
            // PERBAIKAN: Gunakan officeLocation bukan office!
            $this->geoConfig = [
                'is_banned' => (bool)$schedule->is_banned,
                'is_wfa'    => (bool)$schedule->is_wfa,
                'office_lat' => $schedule->officeLocation ? (float)$schedule->officeLocation->latitude : 0,
                'office_lng' => $schedule->officeLocation ? (float)$schedule->officeLocation->longitude : 0,
                'radius_meter' => $schedule->officeLocation ? (int)$schedule->officeLocation->radius : 50,
            ];
            
            // DEBUG: Log geoConfig
            \Log::info('geoConfig: ' . json_encode($this->geoConfig));
        } else {
            // Default jika tidak ada jadwal
            $this->geoConfig = [
                'is_banned' => false,
                'is_wfa' => false,
                'office_lat' => 0,
                'office_lng' => 0,
                'radius_meter' => 50,
            ];
            
            if ($this->shifts->isNotEmpty()) {
                $this->shift_id = $this->shifts->first()->id;
            }
        }
        
        \Log::info('===================');
    }

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

        $schedule = Schedule::with(['office', 'officeLocation'])->where('user_id', $user->id)->first();

        return view('livewire.presensi', [
            'schedule' => $schedule,
            'attendance' => $attendance,
        ]);
    }

    // Hitung Jarak (Server Side Validation) - Returns KM
    public function distance($lat1, $lon1, $lat2, $lon2)
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) return 0;
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        return ($dist * 60 * 1.1515 * 1.609344);
    }

    // Method store() menerima koordinat dari JS secara langsung
    public function store($latitude, $longitude)
    {
        $this->validate([
            'shift_id' => 'required|exists:shifts,id', 
            'photo' => 'required|image|max:10240',
        ]);

        if(empty($latitude) || empty($longitude)) {
            session()->flash('error', 'Lokasi tidak terdeteksi.');
            return;
        }

        $user = Auth::user();
        $schedule = Schedule::with(['office', 'officeLocation', 'shift'])->where('user_id', $user->id)->first();

        // 1. Cek Banned
        if ($schedule && $schedule->is_banned) {
            session()->flash('error', 'Akun dibekukan.');
            return;
        }

        // 2. Cek Radius (Validasi Server)
        $validRadius = false;
        if($schedule) {
            if($schedule->is_wfa) {
                $validRadius = true; 
            } elseif ($schedule->officeLocation) {
                $distKm = $this->distance($latitude, $longitude, $schedule->officeLocation->latitude, $schedule->officeLocation->longitude);
                $radiusKm = ($schedule->officeLocation->radius ?? 50) / 1000;
                $validRadius = $distKm <= $radiusKm; // Toleransi server bisa ditambah jika perlu
            }
        }

        if (!$validRadius) {
            session()->flash('error', 'Validasi Server Gagal: Anda berada di luar radius.');
            return;
        }

        // 3. Cek Cuti
        $today = Carbon::today()->format('Y-m-d');
        if (Leave::where('user_id', $user->id)->where('status', 'approved')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->exists()) {
            session()->flash('error', 'Anda sedang Cuti.');
            return;
        }

        // 4. Simpan Data
        $activeAttendance = Attendance::where('user_id', $user->id)->whereNull('end_time')->latest()->first();
        $now = Carbon::now();
        $photoPath = $this->photo->store('attendance-photos', 'public');
        $selectedShift = Shift::find($this->shift_id);
        
        $refLat = $schedule->is_wfa ? $latitude : $schedule->officeLocation->latitude;
        $refLng = $schedule->is_wfa ? $longitude : $schedule->officeLocation->longitude;

        if ($activeAttendance) {
               $activeAttendance->update([
                'end_latitude' => $latitude,
                'end_longitude' => $longitude,
                'end_time' => $now,
                'end_image' => $photoPath,
            ]);
            event(new AttendanceRecorded($activeAttendance, 'check_out'));
            session()->flash('message', 'Absen Pulang Berhasil.');
        } else {
            $newAtt = Attendance::create([
                'user_id' => $user->id,
                'schedule_latitude' => $refLat,
                'schedule_longitude' => $refLng,
                'schedule_start_time' => $selectedShift->start_time,
                'schedule_end_time' => $selectedShift->end_time,
                'start_latitude' => $latitude,
                'start_longitude' => $longitude,
                'start_time' => $now,
                'start_image' => $photoPath,
            ]);
            event(new AttendanceRecorded($newAtt, 'check_in'));
            session()->flash('message', 'Absen Masuk Berhasil.');
        }
        
        return redirect()->route('presensi');
    }
}