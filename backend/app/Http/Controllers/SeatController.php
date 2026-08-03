<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeatController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'seats' => Seat::query()->with('coach')->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coach_id' => ['required', 'integer', 'exists:coaches,id'],
            'seat_number' => ['required', 'string', 'max:255'],
            'seat_class' => ['sometimes', 'string', 'max:255'],
            'is_reserved' => ['sometimes', 'boolean'],
        ]);

        $userId = $request->user()?->id;

        $seat = Seat::create([
            ...$validated,
            'is_reserved' => $validated['is_reserved'] ?? false,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Seat created successfully.',
            'seat' => $seat,
        ], 201);
    }

    public function show(Seat $seat): JsonResponse
    {
        return response()->json(['seat' => $seat->load('coach')]);
    }

    public function update(Request $request, Seat $seat): JsonResponse
    {
        $validated = $request->validate([
            'coach_id' => ['sometimes', 'integer', 'exists:coaches,id'],
            'seat_number' => ['sometimes', 'string', 'max:255'],
            'seat_class' => ['sometimes', 'string', 'max:255'],
            'is_reserved' => ['sometimes', 'boolean'],
        ]);

        $seat->fill($validated);
        $seat->updated_by = $request->user()?->id;
        $seat->save();

        return response()->json([
            'message' => 'Seat updated successfully.',
            'seat' => $seat->fresh()->load('coach'),
        ]);
    }

    public function destroy(Request $request, Seat $seat): JsonResponse
    {
        $seat->deleted_by = $request->user()?->id;
        $seat->save();
        $seat->delete();

        return response()->json(['message' => 'Seat deleted successfully.']);
    }
}
