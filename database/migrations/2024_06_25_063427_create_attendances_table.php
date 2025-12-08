<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        // Data Jadwal (Tetap wajib ada)
        $table->double('schedule_latitude');
        $table->double('schedule_longitude');
        $table->time('schedule_start_time');
        $table->time('schedule_end_time');

        // Data Absen Masuk (Wajib ada)
        $table->double('start_latitude');
        $table->double('start_longitude');
        $table->datetime('start_time');

        // Data Absen Pulang (HARUS NULLABLE / Boleh Kosong)
        // KARENA SAAT ABSEN MASUK, DATA INI BELUM ADA
        $table->double('end_latitude')->nullable();  // <--- Tambah ->nullable()
        $table->double('end_longitude')->nullable(); // <--- Tambah ->nullable()
        $table->datetime('end_time')->nullable();        // <--- Tambah ->nullable()

        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
