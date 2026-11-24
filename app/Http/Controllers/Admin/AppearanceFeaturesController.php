<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FeatureSettings;
use App\Services\ThemeSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppearanceFeaturesController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'features' => FeatureSettings::all(),
            'definitions' => FeatureSettings::definitions(),
            'presets' => collect(ThemeSettings::presets())->map(fn (array $preset, string $key) => [
                'key' => $key,
                'label' => $preset['label'],
                'colors' => $preset['colors'],
            ])->values(),
            'theme' => ThemeSettings::currentKey(),
        ]);
    }

    public function updateFeatures(Request $request): JsonResponse
    {
        $data = $request->validate([
            'features' => ['required', 'array'],
            'features.*' => ['boolean'],
        ]);

        return response()->json(['features' => FeatureSettings::set($data['features'])]);
    }

    public function updateTheme(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'string', Rule::in(array_keys(ThemeSettings::presets()))],
        ]);

        ThemeSettings::set($data['theme']);

        return response()->json(['theme' => ThemeSettings::currentKey()]);
    }
}
