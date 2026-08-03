<?php

namespace App\Http\Controllers;

use App\Models\TrainEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainEngineController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'train_engines' => TrainEngine::query()->with(['train', 'engine'])->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'train_id' => ['required', 'integer', 'exists:trains,id'],
            'engine_id' => ['required', 'integer', 'exists:engines,id'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $userId = $request->user()?->id;

        $trainEngine = TrainEngine::create([
            ...$validated,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Train engine link created successfully.',
            'train_engine' => $trainEngine->load(['train', 'engine']),
        ], 201);
    }

    public function show(TrainEngine $trainEngine): JsonResponse
    {
        return response()->json(['train_engine' => $trainEngine->load(['train', 'engine'])]);
    }

    public function update(Request $request, TrainEngine $trainEngine): JsonResponse
    {
        $validated = $request->validate([
            'train_id' => ['sometimes', 'integer', 'exists:trains,id'],
            'engine_id' => ['sometimes', 'integer', 'exists:engines,id'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ]);

        $trainEngine->fill($validated);
        $trainEngine->updated_by = $request->user()?->id;
        $trainEngine->save();

        return response()->json([
            'message' => 'Train engine link updated successfully.',
            'train_engine' => $trainEngine->fresh()->load(['train', 'engine']),
        ]);
    }

    public function destroy(Request $request, TrainEngine $trainEngine): JsonResponse
    {
        $trainEngine->deleted_by = $request->user()?->id;
        $trainEngine->save();
        $trainEngine->delete();

        return response()->json(['message' => 'Train engine link deleted successfully.']);
    }
}
