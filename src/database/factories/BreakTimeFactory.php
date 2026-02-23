<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BreakTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attendance_id' => 1, // シーダーで上書きされるから仮でOK
            'start_time'    => '12:00:00',
            'end_time'      => '13:00:00',
        ];
    }
}
