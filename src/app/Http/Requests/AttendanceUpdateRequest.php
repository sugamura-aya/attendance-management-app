<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        //基本のルール(出勤・退勤・備考)
        $rules = [
            'clock_in'  => ['required'],
            'clock_out' => ['required', 'after:clock_in'],
            'remarks'   => ['required'],
        ];

        // 2. 休憩時間のループバリデーション
        // HTMLの name="break_start[0]" などに対応
        $breakStarts = $this->input('break_start', []);
        $breakEnds   = $this->input('break_end', []);

        foreach ($breakStarts as $key => $val) {
            // 開始か終了、どちらか一方でも入力があればチェック
            if (!empty($breakStarts[$key]) || !empty($breakEnds[$key])) {
                
                // --- 休憩開始のルール ---
                $rules["break_start.{$key}"] = [
                    'required',
                    function ($attribute, $value, $fail) {
                        // 出勤より前、または退勤より後の場合はエラー
                        if ($value < $this->clock_in || $value > $this->clock_out) {
                            $fail('休憩時間が不適切な値です');
                        }
                    },
                ];

                // --- 休憩終了のルール ---
                $rules["break_end.{$key}"] = [
                    'required',
                    function ($attribute, $value, $fail) use ($key, $breakStarts) {
                        $bStart = $breakStarts[$key] ?? null;
                        // 休憩開始より前、または退勤より後の場合はエラー
                        if ($value < $bStart || $value > $this->clock_out) {
                            $fail('休憩時間もしくは退勤時間が不適切な値です');
                        }
                    },
                ];
            }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            // 出勤・退勤の不備
            'clock_in.required'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after'    => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.required' => '出勤時間もしくは退勤時間が不適切な値です',

            // 休憩の「必須入力」エラー用（.* で全インデックスをカバー）
            'break_start.*.required' => '休憩時間が不適切な値です',
            'break_end.*.required'   => '休憩時間もしくは退勤時間が不適切な値です',

            // 備考の不備
            'remarks.required'   => '備考を記入してください',
        ];
    }

}
