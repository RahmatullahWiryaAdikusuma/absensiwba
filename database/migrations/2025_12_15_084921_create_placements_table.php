<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('office_id')
                ->constrained('offices')
                ->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable(); 
            $table->enum('placement_status', ['active', 'inactive'])->default('active');
            $table->decimal('daily_rate', 15, 2)->default(0);
            $table->enum('backup', ['reguler', 'backup'])->default('reguler');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placements');
    }
};
