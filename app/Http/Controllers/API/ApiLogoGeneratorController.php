<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LogoGenerateRequest;
use App\Http\Resources\LogoGenerateResource;
use App\Services\SvgAction;
use Illuminate\Http\JsonResponse;

class ApiLogoGeneratorController extends Controller
{
    private SvgAction $svgAction;

    public function __construct(SvgAction $svgAction)
    {
        $this->svgAction = $svgAction;
    }

    public function generate(LogoGenerateRequest $request): JsonResponse
    {
        $generatedFiles = $this->svgAction->generate($request->validated());

        return response()->json(
            new LogoGenerateResource($generatedFiles),
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function save(LogoGenerateRequest $request): JsonResponse
    {
        $this->svgAction->save($request->validated());

        return response()->json(['message' => 'Иконки успешно сохранены!'], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
