<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoachController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'coaches' => Coach::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coach_number' => ['required', 'string', 'max:255', 'unique:coaches,coach_number'],
            'coach_type' => ['required', 'string', Rule::in(['reserved', 'unreserved'])],
            'total_seats' => ['required', 'integer', 'min:1'],
        ]);

        $userId = $request->user()?->id;

        $coach = Coach::create([
            ...$validated,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Coach created successfully.',
            'coach' => $coach,
        ], 201);
    }

    public function show(Coach $coach): JsonResponse
    {
        return response()->json(['coach' => $coach]);
    }

    public function update(Request $request, Coach $coach): JsonResponse
    {
        $validated = $request->validate([
            'coach_number' => ['sometimes', 'string', 'max:255', Rule::unique('coaches', 'coach_number')->ignore($coach->id)],
            'coach_type' => ['sometimes', 'string', Rule::in(['reserved', 'unreserved'])],
            'total_seats' => ['sometimes', 'integer', 'min:1'],
        ]);

        $coach->fill($validated);
        $coach->updated_by = $request->user()?->id;
        $coach->save();

        return response()->json([
            'message' => 'Coach updated successfully.',
            'coach' => $coach->fresh(),
        ]);
    }

    public function destroy(Request $request, Coach $coach): JsonResponse
    {
        $coach->deleted_by = $request->user()?->id;
        $coach->save();
        $coach->delete();

        return response()->json(['message' => 'Coach deleted successfully.']);
    }
}
