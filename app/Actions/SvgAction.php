<?php

namespace App\Actions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Spatie\Image\Image;

class SvgAction
{
    public function generate(array $data, string $path): array
    {
        $iconPath = $this->getIconPath($data['icon']);
        $fontPath = storage_path('app/public/' . $data['font']);
        $color1 = $data['color1'];
        $color2 = $data['color2'] ?? null;
        $name = $data['name'];
        $nameParts = explode(' ', $name);
        $nameLength = strlen(str_replace(' ', '', $name));
        $hasTwoUppercase = preg_match('/[A-Z].*[A-Z]/', $name);
        $isSingleWord = count($nameParts) === 1;
        $hasEightChars = $isSingleWord && $nameLength === 8;

        //$optionsArray = $this->optionsTextAndLogoData($hasTwoUppercase);
        // Настройка текста и макета
        if ($hasTwoUppercase) {
            $gradient = false;
            $textColor1 = $color1;
            $textColor2 = $color2;
            $iconColor = $this->pickRandomColor($color1, $color2);
        } else {
            $gradient = true;
            $textColor1 = $textColor2 = null;
            $iconColor = null;
        }

        if ($hasEightChars) {
            $fontSize = 24 - max(0, ($nameLength - 8) * 1.5);
            $layout = 'under';
            $iconTransform = 'translate(0, 0) scale(1.5)';
        } elseif (count($nameParts) >= 3) {
            $fontSize = max(16, 32 - (strlen($name) * 0.7));
            $layout = 'side';
            $iconTransform = 'translate(10, 25) scale(0.8)';
        } else {
            $fontSize = max(16, 32 - (strlen($name) * 0.5));
            $layout = 'default';
            $iconTransform = 'translate(10, 10) scale(1.0)';
        }

        // Создание SVG логотипов
        $logoSvg = $this->createSvg($iconPath, $fontPath, $name, $color1, $color2, false, [
            'layout' => $layout,
            'fontSize' => $fontSize,
            'gradient' => $gradient,
            'textColor1' => $textColor1,
            'textColor2' => $textColor2,
            'iconTransform' => $iconTransform,
            'iconColor' => $iconColor
        ]);

        $logoFooterSvg = $this->createSvg($iconPath, $fontPath, $name, $color1, $color2, true, [
            'layout' => $layout,
            'fontSize' => $fontSize,
            'gradient' => $gradient,
            'textColor1' => $textColor1,
            'textColor2' => $textColor2,
            'iconTransform' => $iconTransform,
            'iconColor' => $iconColor
        ]);

        $logoPath = "/storage/" . $path . '/logo.svg';
        $logoFooterPath = "/storage/" . $path . '/logo_footer.svg';

        $logoFullPath = storage_path("app/public/") . $path . '/logo.svg';
        $logoFullFooterPath = storage_path("app/public/") . $path . '/logo_footer.svg';
        file_put_contents($logoFullPath, $logoSvg);
        file_put_contents($logoFullFooterPath, $logoFooterSvg);

        // Создание фавиконов
        //$faviconColored = $this->createFavicon($logoSvg, Str::slug($name), $color1);
        $faviconColored = "/storage/" . $this->createFavicon($iconPath, Str::slug($name), $color1, $color2);



        return [
            'logo' => $logoPath,
            'logo_footer' => $logoFooterPath,
            'favicon_colored' => $faviconColored
        ];
    }

    public function save(array $data): array
    {
        $folderName = Str::slug($data['name']);
        $destinationPath = "site-logos/$folderName";

        Storage::disk('public')->makeDirectory($destinationPath);

        $generatedFiles = $this->generate($data, $destinationPath);

        return $generatedFiles;
    }

    private function getIconPath(?string $icon): string
    {
        if (!$icon) {
            $iconFiles = Storage::disk('public')->files('icons/auto');
            if (empty($iconFiles)) {
                throw new \Exception('Иконки отсутствуют в папке icons/auto');
            }
            return public_path('/storage/' . $iconFiles[array_rand($iconFiles)]);
        }

        return $icon;
    }

    private function createSvg(
        string $iconPath,
        string $fontPath,
        string $name,
        string $color1,
        string $color2,
        bool $isFooter,
        array $options
    ): string {
        $svgContent = $this->readSvgFile($iconPath);
        $svgInnerContent = $this->extractSvgContent($svgContent);

        if ($isFooter) {
            $color1 = $color2 = '#fff';
        }

        $gradientId = $this->generateGradientId();
        $gradient = $this->createGradient($gradientId, $color1, $color2);

        $textContent = $this->prepareTextContent($name, $fontPath, $options['fontSize'], $options['layout']);

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg fill="url(#{$gradientId})" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100">
    {$gradient}
    <g transform="{$options['iconTransform']}">
        {$svgInnerContent}
    </g>
    {$textContent}
</svg>
SVG;
    }

    private function createFavicon(string $icon, string $folderName, string $color, string $color2): string
    {
        $faviconPath = "site-logos/{$folderName}/favicon.ico";
        $tempPngPath = storage_path("app/public/site-logos/{$folderName}/favicon.png");
                
        Image::load($icon)
            ->width(16)
            ->height(16)
            ->save($tempPngPath);

        $image = new Imagick();
        $image->readImage($tempPngPath);
        $image->setImageFormat('ico');
        $image->writeImage(storage_path("app/public/$faviconPath"));

        unlink($tempPngPath);

        return $faviconPath;
    }

    private function readSvgFile(string $iconPath): string
    {
        try {
            $content = file_get_contents($iconPath);
            return mb_convert_encoding($content, 'UTF-8', 'auto');
        } catch (\Exception $e) {
            $iconNewPath = public_path('/storage/' . $iconPath);
            $content = file_get_contents($iconNewPath);
            return mb_convert_encoding($content, 'UTF-8', 'auto');
        }
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

    private function prepareTextContent(
        string $name,
        string $fontPath,
        int $fontSize,
        string $layout
    ): string {
        $name = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $words = explode(' ', $name);
        $lineHeight = $layout === 'under' ? 20 : 14;
        $yOffset = $layout === 'under' ? 75 : 50;

        $textLines = array_map(function ($word, $index) use ($yOffset, $lineHeight) {
            $y = $yOffset + $index * $lineHeight;
            return <<<LINE
<tspan x="50%" y="{$y}" text-anchor="middle" alignment-baseline="middle">{$word}</tspan>
LINE;
        }, $words, array_keys($words));

        $textStyle = "font-family: '{$fontPath}'; font-size: {$fontSize}px;";
        return sprintf('<style>#text { %s }</style><text id="text">%s</text>', $textStyle, implode("\n", $textLines));
    }

    private function pickRandomColor(string $color1, string $color2): string
    {
        return rand(0, 1) ? $color1 : $color2;
    }

}
