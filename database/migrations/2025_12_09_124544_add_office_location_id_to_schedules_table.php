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
    Schema::table('schedules', function (Blueprint $table) { 
        $table->foreignId('office_location_id')
            ->nullable()  
            ->constrained('office_locations')
            ->nullOnDelete();
    });
    }

    public function down(): void
{
    Schema::table('schedules', function (Blueprint $table) {
        $table->dropForeign(['office_location_id']);
        $table->dropColumn('office_location_id');
    });
}
};
