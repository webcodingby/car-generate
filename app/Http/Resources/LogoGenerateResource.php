<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LogoGenerateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'logo' => $this->resource['logo'],
            'logo_footer' => $this->resource['logo_footer'],
            'favicon' => base64_encode($this->resource['favicon']),
        ];
    }
}
