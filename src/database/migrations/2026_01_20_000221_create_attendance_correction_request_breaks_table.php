<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionRequestBreaksTable extends Migration
{
    public function up()
    {
        Schema::create('attendance_correction_request_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('attendance_correction_requests')
                ->cascadeOnDelete();

            $table->dateTime('requested_start_time')->nullable();
            $table->dateTime('requested_end_time')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_correction_request_breaks');
    }
}
