<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20|unique:pens,code,' . $this->route('pen'),
            'category' => 'required|in:A,B,C,D',
            'capacity' => 'required|integer|min:1|max:100',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
