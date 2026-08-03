<?php

namespace App\Http\Controllers;

use App\Models\TrainCoach;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainCoachController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'train_coaches' => TrainCoach::query()->with(['train', 'coach'])->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'train_id' => ['required', 'integer', 'exists:trains,id'],
            'coach_id' => ['required', 'integer', 'exists:coaches,id'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $userId = $request->user()?->id;

        $trainCoach = TrainCoach::create([
            ...$validated,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Train coach link created successfully.',
            'train_coach' => $trainCoach->load(['train', 'coach']),
        ], 201);
    }

    public function show(TrainCoach $trainCoach): JsonResponse
    {
        return response()->json(['train_coach' => $trainCoach->load(['train', 'coach'])]);
    }

    public function update(Request $request, TrainCoach $trainCoach): JsonResponse
    {
        $validated = $request->validate([
            'train_id' => ['sometimes', 'integer', 'exists:trains,id'],
            'coach_id' => ['sometimes', 'integer', 'exists:coaches,id'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ]);

        $trainCoach->fill($validated);
        $trainCoach->updated_by = $request->user()?->id;
        $trainCoach->save();

        return response()->json([
            'message' => 'Train coach link updated successfully.',
            'train_coach' => $trainCoach->fresh()->load(['train', 'coach']),
        ]);
    }

    public function destroy(Request $request, TrainCoach $trainCoach): JsonResponse
    {
        $trainCoach->deleted_by = $request->user()?->id;
        $trainCoach->save();
        $trainCoach->delete();

        return response()->json(['message' => 'Train coach link deleted successfully.']);
    }
}