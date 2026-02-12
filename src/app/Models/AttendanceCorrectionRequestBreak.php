<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceCorrectionRequest;

class AttendanceCorrectionRequestBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'requested_start_time',
        'requested_end_time',
    ];

    protected $casts = [
        'requested_start_time' => 'datetime',
        'requested_end_time'   => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'request_id');
    }
}
