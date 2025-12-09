<?php
namespace App\Events;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRecorded
{
    use Dispatchable, SerializesModels;

    public $attendance;
    public $type;  

    public function __construct(Attendance $attendance, string $type)
    {
        $this->attendance = $attendance;
        $this->type = $type;
    }
}