<?php

namespace App\Http\Controllers;

use App\Models\Engine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EngineController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'engines' => Engine::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'engine_number' => ['required', 'string', 'max:255', 'unique:engines,engine_number'],
            'engine_type' => ['required', 'string', 'max:255'],
            'fuel_type' => ['required', 'string', 'max:255'],
            'capacity' => ['sometimes', 'integer'],
            'condition' => ['sometimes', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id;

        $engine = Engine::create([
            ...$validated,
            'condition' => $validated['condition'] ?? 'active',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Engine created successfully.',
            'engine' => $engine,
        ], 201);
    }

    public function show(Engine $engine): JsonResponse
    {
        return response()->json(['engine' => $engine]);
    }

    public function update(Request $request, Engine $engine): JsonResponse
    {
        $validated = $request->validate([
            'engine_number' => ['sometimes', 'string', 'max:255', Rule::unique('engines', 'engine_number')->ignore($engine->id)],
            'engine_type' => ['sometimes', 'string', 'max:255'],
            'fuel_type' => ['sometimes', 'string', 'max:255'],
            'capacity' => ['sometimes', 'integer'],
            'condition' => ['sometimes', 'string', 'max:255'],
        ]);

        $engine->fill($validated);
        $engine->updated_by = $request->user()?->id;
        $engine->save();

        return response()->json([
            'message' => 'Engine updated successfully.',
            'engine' => $engine->fresh(),
        ]);
    }

    public function destroy(Request $request, Engine $engine): JsonResponse
    {
        $engine->deleted_by = $request->user()?->id;
        $engine->save();
        $engine->delete();

        return response()->json(['message' => 'Engine deleted successfully.']);
    }
}
