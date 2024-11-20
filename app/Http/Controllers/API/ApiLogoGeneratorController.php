<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Imagick;

class ApiLogoGeneratorController extends Controller
{
    public function generate(Request $request) {
//
//        $request->validate([
//            'name' => 'required|string|max:255',
//            'icon' => 'required|string',
////            'font' => 'required|string',
//            'color1' => 'required|string',
//            'color2' => 'nullable|string',
//        ]);
        $name = $request->name;
        $iconPath = storage_path('app/public/' . $request->icon);
        $fontPath = storage_path('app/public/' . $request->font);
        $color1 = $request->color1;
        $color2 = $request->color2;

        $logoSvg = $this->createSvg($iconPath, $fontPath, $name, $color1, $color2);
        $logoFooterSvg = $this->createSvg($iconPath, $fontPath, $name, $color1, $color2, true);
        // Преобразуем фавикон в base64 для отображения в img
        $favicon = base64_encode($this->createFavicon($logoSvg));

        return response()->json([
            'logo' => $logoSvg,
            'logo_footer' => $logoFooterSvg,
            'favicon' => $favicon,
        ]);
    }

    private function createSvg($iconPath, $fontPath, $name, $color1, $color2, $isFooter = false)
    {
        $svg = new Imagick();
        // Генерация SVG с использованием Imagick и предоставленных параметров
        $svg->setBackgroundColor($isFooter ? 'transparent' : $color1);

        // Генерация кода SVG с текстом и другими элементами
        $svgCode = "<svg width='200px'>..."; // Пример кода SVG
        return $svgCode;
    }

    private function createFavicon($svgCode)
    {
        $image = new Imagick();
        $image->readImageBlob($svgCode);
        $image->setImageFormat('ico');
        $image->resizeImage(16, 16, Imagick::FILTER_LANCZOS, 1);

        return $image->getImageBlob();
    }

    public function save(Request $request)
    {
        $actionType = $request->input('actionType');
        if ($actionType === 'generate') {
            // Логика генерации иконок
            $generatedFiles = $this->generate($request);
            return response()->json([
                'message' => 'Иконки успешно сгенерированы!',
                'files' => $generatedFiles,
            ]);
        } elseif ($actionType === 'save') {
            // Логика сохранения иконок
            $this->saveIcons($request);
            return response()->json([
                'message' => 'Иконки успешно сохранены!',
            ]);
        }

        return response()->json(['error' => 'Неверное действие'], 400);
    }

    protected function saveIcons(Request $request)
    {
        $name = $request->input('name');
        $generatedFiles = $this->generate($request);

        // Транслитерация имени
        $folderName = Str::slug($name);
        $destinationPath = storage_path("app/public/site-logo/$folderName");

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        foreach ($generatedFiles as $key => $filePath) {
            $fileName = ($key === 'favicon') ? 'favicon.ico' : "$key.svg";
            copy($filePath, "$destinationPath/$fileName");
        }

        return [
            'message' => 'Файлы успешно сохранены',
            'path' => $destinationPath,
        ];
    }

}
