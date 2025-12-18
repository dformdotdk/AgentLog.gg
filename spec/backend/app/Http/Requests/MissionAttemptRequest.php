<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MissionAttemptRequest extends FormRequest
{
    public function rules(): array
    {
        return ['answer' => ['required','string','max:100']];
    }
}
