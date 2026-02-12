<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'work_date', 'clock_in', 'clock_out', 'note'];

    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function breakMinutes(): int
    {
        return (int) $this->breakTimes()
            ->whereNotNull('end_time')
            ->get()
            ->sum(fn ($b) => $b->start_time->diffInMinutes($b->end_time));
    }

    public function workMinutes(): int
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        $work = $this->clock_in->diffInMinutes($this->clock_out);
        return max(0, $work - $this->breakMinutes());
    }

    public function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;
        return sprintf('%d:%02d', $hours, $mins);
    }
}
