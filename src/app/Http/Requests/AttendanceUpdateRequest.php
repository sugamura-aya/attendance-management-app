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
        return [
            // 1. 出勤・退勤の前後チェック
            // 出勤
            'clock_in'  => ['required'],
            // 退勤
            'clock_out' => ['required', 'after:clock_in'],

            // 2. 休憩開始は「出勤より後」かつ「退勤より前」
            'break_start.*' => ['required', 'after:clock_in', 'before:clock_out'],

            // 3. 休憩終了は「休憩開始より後」かつ「退勤より前」
            'break_end.*'   => ['required', 'after:break_start.*', 'before:clock_out'],

            // 4. 備考
            'remarks' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            // 1に対応：出勤・退勤の不備
            'clock_in.required'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after'    => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.required' => '出勤時間もしくは退勤時間が不適切な値です',

            // 2に対応：休憩開始の不備
            'break_start.*.required' => '休憩時間が不適切な値です',
            'break_start.*.after'  => '休憩時間が不適切な値です',
            'break_start.*.before' => '休憩時間が不適切な値です',

            // 3に対応：休憩終了の不備（退勤との比較）
            'break_end.*.required' => '休憩時間もしくは退勤時間が不適切な値です',
            'break_end.*.after'  => '休憩時間もしくは退勤時間が不適切な値です',
            'break_end.*.before' => '休憩時間もしくは退勤時間が不適切な値です',

            // 4に対応：備考
            'remarks.required'   => '備考を記入してください',
        ];
    }

}
