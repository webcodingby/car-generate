<?php

namespace App\Actions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Spatie\Image\Image;

class SvgAction
{
    /**
     * @throws \Exception
     */
    public function generate(array $data, string $path): array
    {
        try {
            $iconPath = $this->getIconPath($data['icon']);
        } catch (\Exception $e) {
            throw new \Exception($data['icon'] . ' не найден: ' . $e->getMessage());
        }
        $fontPath = storage_path('app/public/' . $data['font']);
        $color1 = $data['color1'];
        $color2 = $data['color2'] ?? null;
        $logoSvg = $this->createSvg($iconPath, $fontPath, $data['name'], $color1, $color2);
        $logoFooterSvg = $this->createSvg($iconPath, $fontPath, $data['name'], $color1, $color2, true);
        try {
            $favicon = $this->createFavicon($logoSvg, Str::slug($data['name']));
        } catch (\ImagickException $e) {
            throw new \Exception($logoSvg . ' не найден: ' . $e->getMessage());
        }
        $logoPath = $path . '/logo.svg';
        $logoFooterPath = $path . '/logo_footer.svg';
        file_put_contents($logoPath, $logoSvg);
        file_put_contents($logoFooterPath, $logoFooterSvg);

        return [
            'logo' => $logoSvg,
            'logo_footer' => $logoFooterSvg,
            'favicon' => $favicon,
        ];
    }

    public function save(array $data): array
    {
        $folderName = Str::slug($data['name']);
        $destinationPath = storage_path("app/public/site-logos/$folderName");
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $generatedFiles = $this->generate($data, $destinationPath);
        foreach ($generatedFiles as $key => $fileContent) {
            $fileName = ($key === 'favicon') ? 'favicon.ico' : "$key.svg";
            file_put_contents("$destinationPath/$fileName", $fileContent);
        }
        return $generatedFiles;
    }

    /**
     * @throws \Exception
     */
    private function getIconPath(?string $icon): string
    {
        if (!$icon) {
            try {
                $iconFiles = Storage::disk('public')->files('icons/auto');
                if (count($iconFiles) > 0) {
                    return storage_path('app/public/' . $iconFiles[array_rand($iconFiles)]);
                }
            } catch (\Exception $e) {
                throw new \Exception('Ошибка: ' . $e->getMessage());
            }
        }

        return storage_path('app/public/' . $icon);
    }

    private function createSvg
    (
        string $iconPath,
        string $fontPath,
        string $name,
        string $color1,
        string $color2,
        bool   $isFooter = false
    ): string
    {
        try {
            $svgContent = $this->readSvgFile($iconPath);
        } catch (\Exception $e) {
            throw new \Exception('Ошибка: ' . $e->getMessage());
        }
        $svgInnerContent = $this->extractSvgContent($svgContent);

        if ($isFooter) {
            $color1 = $color2 = '#fff';
        }

        $gradientId = $this->generateGradientId();
        $gradient = $this->createGradient($gradientId, $color1, $color2);

        $svgInnerContent = $this->removeFillAttributes($svgInnerContent);

        $textContent = $this->prepareTextContent($name, $fontPath);

        return $this->generateSvg($gradient, $gradientId, $svgInnerContent, $textContent);
    }

    /**
     * @throws \Exception
     */
    private function readSvgFile(string $iconPath): string
    {

        try {
            $content = file_get_contents($iconPath);
        } catch (\Exception $e) {
            throw new \Exception('Ошибка: ' . $e->getMessage());
        }

        return mb_convert_encoding($content, 'UTF-8', 'auto');
    }

    private function extractSvgContent(string $svgContent): string
    {
        if (preg_match('/<svg[^>]*>(.*?)<\/svg>/s', $svgContent, $matches)) {
            return $matches[1];
        }
        throw new \Exception("Не удалось найти содержимое между тегами <svg> и </svg>");
    }

    private function generateGradientId(): string
    {
        return 'gradient-' . Str::random(5);
    }

    private function createGradient(string $gradientId, string $color1, string $color2): string
    {
        return <<<GRADIENT
<defs>
    <linearGradient id="{$gradientId}" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" style="stop-color: {$color1}; stop-opacity: 1" />
        <stop offset="100%" style="stop-color: {$color2}; stop-opacity: 1" />
    </linearGradient>
</defs>
GRADIENT;
    }

    private function removeFillAttributes(string $svgContent): string
    {
        return preg_replace('/<path[^>]*fill=["\'][^"\']*["\'][^>]*>/i', '<path$1>', $svgContent);
    }

    private function prepareTextContent(string $name, string $fontPath): string
    {
        $name = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $words = explode(' ', $name);
        $lineHeight = 12;
        $fontSize = 20;
        $yOffset = 70;

        $textLines = array_map(function ($word, $index) use ($yOffset, $lineHeight) {
            $y = $yOffset + $index * $lineHeight;
            return <<<LINE
<tspan x="50%" y="{$y}" text-anchor="middle" alignment-baseline="middle">{$word}</tspan>
LINE;
        }, $words, array_keys($words));

        $textStyle = "font-family: '{$fontPath}'; font-size: {$fontSize}px;";
        return sprintf('<style>#text { %s }</style><text id="text">%s</text>', $textStyle, implode("\n", $textLines));
    }

    private function generateSvg
    (
        string $gradient,
        string $gradientId,
        string $svgInnerContent,
        string $textContent
    ): string
    {
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
    <svg fill="url(#{$gradientId})" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100">
        {$gradient}
        <g transform="translate(0, 0) scale(1.5)">
            {$svgInnerContent}
        </g>
        {$textContent}
    </svg>
SVG;
    }

    private function createFavicon(string $svgCode, string $folderName): string
    {
        $svgFilePath = storage_path("app/public/site-logos/{$folderName}/logo-favicon.svg");
        $faviconPath = storage_path("app/public/site-logos/{$folderName}/favicon.ico");

        // Сохранение SVG
        file_put_contents($svgFilePath, $svgCode);

        // Конвертация SVG в PNG с использованием Spatie Image
        $tempPngPath = storage_path("app/public/site-logos/{$folderName}/favicon.png");
        Image::load($svgFilePath)
            ->width(16)
            ->height(16)
            ->save($tempPngPath);

        // Конвертация PNG в ICO
        $image = new Imagick();
        $image->readImage($tempPngPath);
        $image->setImageFormat('ico');
        $image->writeImage($faviconPath);

        unlink($tempPngPath); // Удалить временный PNG

        return $faviconPath;
    }
}
