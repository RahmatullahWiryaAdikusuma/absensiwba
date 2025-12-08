<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Schedule;
use App\Models\Leave;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;  
use Illuminate\Support\Carbon;

class Presensi extends Component
{
    public $latitude;
    public $longitude;
    public $insideRadius = false;
 
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
                            ->orderBy('created_at', 'desc')
                            ->first();

        return view('livewire.presensi', [
            'schedule' => $schedule,
            'insideRadius' => $this->insideRadius,
            'attendance' => $attendance
        ]);
    }
 
    
    public function checkRadius()
    {
        $schedule = Schedule::where('user_id', Auth::user()->id)->first();

        if ($schedule && $schedule->office) {
            $officeLat = $schedule->office->latitude;
            $officeLng = $schedule->office->longitude;
             
            $radiusKm = ($schedule->office->radius ?? 50) / 1000; 

            $distance = $this->distance($this->latitude, $this->longitude, $officeLat, $officeLng);

            $this->insideRadius = $distance <= $radiusKm;
        }
    }

    public function distance($lat1, $lon1, $lat2, $lon2) 
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        }
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return ($miles * 1.609344);  
    } 

    public function store()
    {
        $this->validate([
            'latitude' => 'required',
            'longitude' => 'required'
        ]);

        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
 
        $this->checkRadius(); 
        
        if (!$this->insideRadius) {
            session()->flash('error', 'Anda berada di luar radius !');
            return;
        }

        $today = Carbon::today()->format('Y-m-d');
        $approvedLeave = Leave::where('user_id', Auth::user()->id)
                            ->where('status', 'approved')
                            ->whereDate('start_date', '<=', $today)
                            ->whereDate('end_date', '>=', $today)
                            ->exists();

        if ($approvedLeave) {
            session()->flash('error', 'Anda sedang cuti.');
            return;
        }

        if ($schedule) { 
           
            $attendance = Attendance::where('user_id', Auth::user()->id)
                            ->whereNull('end_time') 
                            ->first();

            $now = Carbon::now();  

            if (!$attendance) { 
           
                Attendance::create([
                    'user_id' => Auth::user()->id,
                    'schedule_latitude' => $schedule->office->latitude,
                    'schedule_longitude' => $schedule->office->longitude,
                    'schedule_start_time' => $schedule->shift->start_time,
                    'schedule_end_time' => $schedule->shift->end_time,
                    'start_latitude' => $this->latitude,
                    'start_longitude' => $this->longitude, 
                    'start_time' => $now->toDateTimeString(), 
                    'end_time' => null,
                ]);
            } else { 
                $attendance->update([

                    'end_latitude' => $this->latitude,
                    'end_longitude' => $this->longitude, 
                    'end_time' => $now->toDateTimeString(), 
                ]);
            }

            return redirect()->route('presensi');
        }
    }
}