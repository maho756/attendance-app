<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AdminAttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end'   => ['nullable', 'date_format:H:i'],

            'note' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $in  = $this->input('clock_in');
            $out = $this->input('clock_out');

            if (!$in || !$out) {
                return;
            }

            $inT  = Carbon::createFromFormat('H:i', $in);
            $outT = Carbon::createFromFormat('H:i', $out);

            if ($inT->gt($outT)) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breaks = $this->input('breaks', []);

            foreach ($breaks as $i => $b) {
                $bs = $b['start'] ?? null;
                $be = $b['end'] ?? null;

                if ($bs) {
                    $bsT = Carbon::createFromFormat('H:i', $bs);

                    if ($bsT->lt($inT) || $bsT->gt($outT)) {
                        $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    }
                }

                if ($be) {
                    $beT = Carbon::createFromFormat('H:i', $be);

                    if ($beT->gt($outT)) {
                        $validator->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}