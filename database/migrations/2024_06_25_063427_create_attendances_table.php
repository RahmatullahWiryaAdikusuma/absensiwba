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

        
        $table->double('schedule_latitude');
        $table->double('schedule_longitude');
        $table->time('schedule_start_time');
        $table->time('schedule_end_time');
 
        $table->double('start_latitude');
        $table->double('start_longitude');
        $table->datetime('start_time');

        
        $table->double('end_latitude')->nullable();   
        $table->double('end_longitude')->nullable();  
        $table->datetime('end_time')->nullable();        

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
