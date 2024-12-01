<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogoGenerateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string', // Если не указана, берётся случайная
            'font' => 'required|string',
            'color1' => 'required|string',
            'color2' => 'nullable|string',
        ];
    }

    public function prepareForValidation(): void
    {
        if (!$this->has('icon') || empty($this->input('icon'))) {
            $icons = glob(storage_path('app/public/icons/auto/*.svg'));
            $randomIcon = $icons[array_rand($icons)] ?? null;

            if ($randomIcon) {
                $this->merge(['icon' => $randomIcon]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }
}
