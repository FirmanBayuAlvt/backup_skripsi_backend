<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LivestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'ear_tag' => 'required|string|max:50|unique:livestocks,ear_tag,' . $this->route('livestock'),
            'breed_type' => 'required|in:domba_lokal,domba_ekor_gemuk,domba_garut',
            'gender' => 'required|in:male,female',
            'birth_date' => 'required|date|before:today',
            'initial_weight' => 'required|numeric|min:0|max:200',
            'health_status' => 'sometimes|in:excellent,good,fair,poor',
            'notes' => 'nullable|string|max:1000',
            'pen_id' => 'nullable|exists:pens,id',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['ear_tag'] = 'sometimes|required|string|max:50|unique:livestocks,ear_tag,' . $this->route('livestock');
            $rules['breed_type'] = 'sometimes|required|in:domba_lokal,domba_ekor_gemuk,domba_garut';
            $rules['gender'] = 'sometimes|required|in:male,female';
            $rules['birth_date'] = 'sometimes|required|date|before:today';
            $rules['initial_weight'] = 'sometimes|required|numeric|min:0|max:200';
        }

        return $rules;
    }
}
