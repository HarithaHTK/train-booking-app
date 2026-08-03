<?php

namespace App\Http\Controllers;

use App\Models\Train;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainController extends Controller
{
    public function index(): JsonResponse
    {
        $trains = Train::query()
            ->with(['route', 'engines', 'coaches'])
            ->latest()
            ->get();

        return response()->json(['trains' => $trains]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'train_number' => ['required', 'string', 'max:255', 'unique:trains,train_number'],
            'train_name' => ['required', 'string', 'max:255'],
            'route_id' => ['sometimes', 'nullable', 'integer', 'exists:routes,id'],
            'is_active' => ['sometimes', 'boolean'],
            'engine_ids' => ['sometimes', 'array'],
            'engine_ids.*' => ['integer', 'exists:engines,id'],
            'coach_ids' => ['sometimes', 'array'],
            'coach_ids.*' => ['integer', 'exists:coaches,id'],
        ]);

        $userId = $request->user()?->id;

        $train = Train::create([
            'train_number' => $validated['train_number'],
            'train_name' => $validated['train_name'],
            'route_id' => $validated['route_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->syncEngines($train, $validated['engine_ids'] ?? [], $userId);
        $this->syncCoaches($train, $validated['coach_ids'] ?? [], $userId);

        return response()->json([
            'message' => 'Train created successfully.',
            'train' => $this->loadTrain($train),
        ], 201);
    }

    public function show(Train $train): JsonResponse
    {
        return response()->json(['train' => $this->loadTrain($train)]);
    }

    public function update(Request $request, Train $train): JsonResponse
    {
        $validated = $request->validate([
            'train_number' => ['sometimes', 'string', 'max:255', Rule::unique('trains', 'train_number')->ignore($train->id)],
            'train_name' => ['sometimes', 'string', 'max:255'],
            'route_id' => ['sometimes', 'nullable', 'integer', 'exists:routes,id'],
            'is_active' => ['sometimes', 'boolean'],
            'engine_ids' => ['sometimes', 'array'],
            'engine_ids.*' => ['integer', 'exists:engines,id'],
            'coach_ids' => ['sometimes', 'array'],
            'coach_ids.*' => ['integer', 'exists:coaches,id'],
        ]);

        $userId = $request->user()?->id;

        $train->fill(collect($validated)->only(['train_number', 'train_name', 'route_id', 'is_active'])->all());
        $train->updated_by = $userId;
        $train->save();

        if (array_key_exists('engine_ids', $validated)) {
            $this->syncEngines($train, $validated['engine_ids'] ?? [], $userId);
        }

        if (array_key_exists('coach_ids', $validated)) {
            $this->syncCoaches($train, $validated['coach_ids'] ?? [], $userId);
        }

        return response()->json([
            'message' => 'Train updated successfully.',
            'train' => $this->loadTrain($train->fresh()),
        ]);
    }

    public function destroy(Request $request, Train $train): JsonResponse
    {
        $userId = $request->user()?->id;

        foreach ($train->trainEngines()->get() as $trainEngine) {
            $trainEngine->deleted_by = $userId;
            $trainEngine->save();
            $trainEngine->delete();
        }

        foreach ($train->trainCoaches()->get() as $trainCoach) {
            $trainCoach->deleted_by = $userId;
            $trainCoach->save();
            $trainCoach->delete();
        }

        $train->deleted_by = $userId;
        $train->save();
        $train->delete();

        return response()->json(['message' => 'Train deleted successfully.']);
    }

    private function loadTrain(Train $train): Train
    {
        return $train->load([
            'route',
            'engines',
            'coaches',
            'trainEngines.engine',
            'trainCoaches.coach',
            'coaches.seats',
        ]);
    }

    private function syncEngines(Train $train, array $engineIds, ?int $userId): void
    {
        $train->trainEngines()->delete();

        foreach (array_values($engineIds) as $index => $engineId) {
            $train->trainEngines()->create([
                'engine_id' => $engineId,
                'position' => $index + 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    private function syncCoaches(Train $train, array $coachIds, ?int $userId): void
    {
        $train->trainCoaches()->delete();

        foreach (array_values($coachIds) as $index => $coachId) {
            $train->trainCoaches()->create([
                'coach_id' => $coachId,
                'position' => $index + 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }
}
