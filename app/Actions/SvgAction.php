<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;

class SvgAction
{
    public function generate(array $data): array
    {
        $iconPath = $this->getIconPath($data['icon']);
        $fontPath = storage_path('app/public/' . $data['font']);
        $color1 = $data['color1'];
        $color2 = $data['color2'] ?? null;

        $logoSvg = $this->createSvg($iconPath, $fontPath, $data['name'], $color1, $color2);
        $logoFooterSvg = $this->createSvg($iconPath, $fontPath, $data['name'], $color1, $color2, true);
        $favicon = $this->createFavicon($logoSvg, Str::slug($data['name']));

        return [
            'logo' => $logoSvg,
            'logo_footer' => $logoFooterSvg,
            'favicon' => $favicon,
        ];
    }

    public function save(array $data): void
    {
        $folderName = Str::slug($data['name']);
        $generatedFiles = $this->generate($data);

        $destinationPath = storage_path("app/public/site-logos/$folderName");
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        foreach ($generatedFiles as $key => $fileContent) {
            $fileName = ($key === 'favicon') ? 'favicon.ico' : "$key.svg";
            file_put_contents("$destinationPath/$fileName", $fileContent);
        }
    }

    private function getIconPath(?string $icon): string
    {
        if (!$icon) {
            $iconFiles = Storage::disk('public')->files('icons/auto');
            if (count($iconFiles) > 0) {
                return storage_path('app/public/' . $iconFiles[array_rand($iconFiles)]);
            }

            throw new \Exception("No icons available in the directory");
        }

        return storage_path('app/public/' . $icon);
    }

    private function createSvg($iconPath, $fontPath, $name, $color1, $color2, $isFooter = false): string
    {
        if (!file_exists($iconPath)) {
            throw new \Exception("Icon file not found: $iconPath");
        }

        $iconContents = file_get_contents($iconPath);

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="200" height="100" viewBox="0 0 200 100">
            <style>
                @font-face {
                    font-family: 'CustomFont';
                    src: url('$fontPath');
                }
                .text {
                    fill: $color1;
                    font-family: 'CustomFont';
                    font-size: 24px;
                }
            </style>
            <rect width="100%" height="100%" fill="$color2"/>
            <g>
                $iconContents
                <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" class="text">$name</text>
            </g>
        </svg>
        SVG;

        return $svg;
    }

    private function createFavicon($svgCode, $folderName): string {
        if (empty($svgCode)) {
            throw new \Exception('SVG код пуст');
        }

        $svgFilePath = storage_path("app/public/temp/{$folderName}.svg");
        file_put_contents($svgFilePath, $svgCode);

        $image = new Imagick();
        try {
            $image->readImageBlob($svgCode);
            $image->setImageFormat('ico');
            $image->resizeImage(16, 16, Imagick::FILTER_LANCZOS, 1);
        } catch (\Exception $e) {
            throw new \Exception('Ошибка обработки SVG: ' . $e->getMessage());
        }

        return $image->getImageBlob();
    }
}
