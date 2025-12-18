<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'series_slug' => ['required','string'],
            'book_slug' => ['required','string'],
            'season_no' => ['required','integer','min:1'],
            'book_token' => ['nullable','string'],
            'device_agent_id' => ['nullable','string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $hasBook = (bool)$this->input('book_token');
            $hasDevice = (bool)$this->input('device_agent_id');

            if (!$hasBook && !$hasDevice) {
                $v->errors()->add('pairing', 'book_token or device_agent_id required');
            }
            if ($hasBook && $hasDevice) {
                $v->errors()->add('pairing', 'Provide only one of book_token or device_agent_id');
            }
        });
    }
}
