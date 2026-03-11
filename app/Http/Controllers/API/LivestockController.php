<?php

namespace App\Http\Controllers\API;

use App\Models\Livestock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\LivestockRequest;
use App\Http\Requests\RecordWeightRequest;
use App\Http\Resources\LivestockResource;
use App\Http\Resources\WeightRecordResource;
use Illuminate\Http\JsonResponse;

class LivestockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Livestock::with('pen');

        if ($request->has('pen_id') && $request->pen_id) {
            $query->where('pen_id', $request->pen_id);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->boolean('status'));
        }

        if ($request->has('breed_type') && $request->breed_type) {
            $query->where('breed_type', $request->breed_type);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ear_tag', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $livestocks = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'livestocks' => LivestockResource::collection($livestocks),
                'pagination' => [
                    'current_page' => $livestocks->currentPage(),
                    'per_page' => $livestocks->perPage(),
                    'total' => $livestocks->total(),
                    'last_page' => $livestocks->lastPage(),
                ]
            ]
        ]);
    }

    public function store(LivestockRequest $request): JsonResponse
    {
        $livestock = Livestock::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ternak berhasil ditambahkan.',
            'data' => new LivestockResource($livestock->load('pen'))
        ], 201);
    }

    public function show(Livestock $livestock): JsonResponse
    {
        $livestock->load('pen', 'weightRecords');
        return response()->json([
            'success' => true,
            'data' => new LivestockResource($livestock)
        ]);
    }

    public function update(LivestockRequest $request, Livestock $livestock): JsonResponse
    {
        $livestock->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Ternak berhasil diperbarui.',
            'data' => new LivestockResource($livestock->load('pen'))
        ]);
    }

    public function destroy(Livestock $livestock): JsonResponse
    {
        $livestock->update(['status' => false]);
        return response()->json([
            'success' => true,
            'message' => 'Ternak berhasil dinonaktifkan.'
        ]);
    }

    public function recordWeight(RecordWeightRequest $request, Livestock $livestock): JsonResponse
    {
        $weightRecord = $livestock->weightRecords()->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Berat badan berhasil dicatat.',
            'data' => new WeightRecordResource($weightRecord)
        ], 201);
    }

    public function weightHistory(Livestock $livestock, Request $request): JsonResponse
    {
        $records = $livestock->weightRecords()
            ->orderBy('record_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => WeightRecordResource::collection($records),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
            ]
        ]);
    }
}
