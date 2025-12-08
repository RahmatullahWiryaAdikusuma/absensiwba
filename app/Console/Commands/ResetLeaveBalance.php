<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ResetLeaveBalance extends Command
{
    protected $signature = 'app:reset-leave-balance';
    protected $description = 'Reset jatah cuti semua karyawan menjadi 12 hari setiap awal tahun';

    public function handle()
    {
        $this->info('Memulai proses reset cuti...');
        User::query()->update(['leave_balance' => 12]);
        $this->info('Berhasil! Semua karyawan sekarang memiliki 12 hari cuti.');
        Log::info('Jatah cuti tahunan telah di-reset otomatis menjadi 12 hari.');
    }
}