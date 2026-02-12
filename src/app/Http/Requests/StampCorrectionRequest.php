<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StampCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'requested_clock_in' => ['required', 'date_format:H:i'],
            'requested_clock_out' => ['required', 'date_format:H:i'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],

            'requested_note' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'requested_note.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $in = $this->input('requested_clock_in');
            $out = $this->input('requested_clock_out');

            if (!$in || !$out) {
                return;
            }

            $clockIn = Carbon::createFromFormat('H:i', $in);
            $clockOut = Carbon::createFromFormat('H:i', $out);

            if ($clockIn->gt($clockOut)) {
                $validator->errors()->add('requested_clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                $validator->errors()->add('requested_clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breaks = $this->input('breaks', []);

            foreach ($breaks as $i => $b) {
                $start = $b['start'] ?? null;
                $end = $b['end'] ?? null;

                if (!$start && !$end) {
                    continue;
                }

                if ($start) {
                    $breakStart = Carbon::createFromFormat('H:i', $start);

                    if ($breakStart->lt($clockIn) || $breakStart->gt($clockOut)) {
                        $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    }
                }

                if ($end) {
                    $breakEnd = Carbon::createFromFormat('H:i', $end);

                    if ($breakEnd->gt($clockOut)) {
                        $validator->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}