<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogoGenerateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'font' => 'required|string',
            'color1' => 'required|string',
            'color2' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
