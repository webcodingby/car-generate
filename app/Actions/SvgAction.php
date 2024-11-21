<?php

namespace App\Actions;

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
        $color2 = $data['color2'] ?? null
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

    private function createSvg
    (
        string $iconPath,
        string $fontPath,
        string $name,
        string $color1,
        string $color2,
        bool $isFooter = false
    ): string
    {
        $svgContent = $this->readSvgFile($iconPath);
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

    private function readSvgFile(string $iconPath): string
    {
        $content = file_get_contents($iconPath);
        if ($content === false) {
            throw new \Exception("Не удалось прочитать SVG файл: {$iconPath}");
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
        dd($svgCode);
        if (empty($svgCode)) {
            throw new \Exception('SVG код пуст');
        }

        $svgFilePath = storage_path("app/public/site-logos/{$folderName}.svg");
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
