<?php

namespace App\Http\Controllers\API;

use App\Models\Pen;
use App\Models\Livestock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PenRequest;
use App\Http\Resources\PenResource;
use Illuminate\Http\JsonResponse;

class PenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Pen::query();

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        $pens = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'pens' => PenResource::collection($pens),
                'stats' => [
                    'total_pens' => Pen::count(),
                    'total_capacity' => Pen::sum('capacity'),
                    'total_occupancy' => Livestock::where('status', true)->count(),
                    'available_pens' => Pen::where('status', 'active')->get()->filter(fn($pen) => $pen->current_occupancy < $pen->capacity)->count(),
                ],
                'pagination' => [
                    'current_page' => $pens->currentPage(),
                    'per_page' => $pens->perPage(),
                    'total' => $pens->total(),
                    'last_page' => $pens->lastPage(),
                ]
            ]
        ]);
    }

    public function store(PenRequest $request): JsonResponse
    {
        $pen = Pen::create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Kandang berhasil ditambahkan.',
            'data' => new PenResource($pen)
        ], 201);
    }

    public function show(Pen $pen): JsonResponse
    {
        $pen->load('livestocks');
        return response()->json([
            'success' => true,
            'data' => new PenResource($pen)
        ]);
    }

    public function update(PenRequest $request, Pen $pen): JsonResponse
    {
        $pen->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Kandang berhasil diperbarui.',
            'data' => new PenResource($pen)
        ]);
    }

    public function destroy(Pen $pen): JsonResponse
    {
        if ($pen->livestocks()->where('status', true)->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang masih berisi ternak aktif, tidak dapat dihapus.'
            ], 422);
        }

        $pen->update(['status' => 'inactive']);
        return response()->json([
            'success' => true,
            'message' => 'Kandang berhasil dinonaktifkan.'
        ]);
    }

    public function analytics(Pen $pen): JsonResponse
    {
        $livestocks = $pen->livestocks()->with('weightRecords')->where('status', true)->get();

        $totalWeight = $livestocks->sum('current_weight');
        $averageWeight = $livestocks->avg('current_weight');
        $averageAge = $livestocks->avg(fn($l) => $l->age_days);

        $feedRequirements = [
            'daily_kg' => round($totalWeight * 0.03, 2),
            'weekly_kg' => round($totalWeight * 0.03 * 7, 2),
            'monthly_kg' => round($totalWeight * 0.03 * 30, 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'pen' => new PenResource($pen),
                'livestock_stats' => [
                    'total' => $livestocks->count(),
                    'average_weight' => round($averageWeight, 2),
                    'average_age_days' => round($averageAge, 0),
                    'total_weight' => round($totalWeight, 2),
                    'by_gender' => [
                        'male' => $livestocks->where('gender', 'male')->count(),
                        'female' => $livestocks->where('gender', 'female')->count(),
                    ],
                ],
                'feed_requirements' => $feedRequirements,
                'performance' => [
                    'occupancy_rate' => $pen->capacity > 0 ? round(($livestocks->count() / $pen->capacity) * 100, 2) : 0,
                ],
            ]
        ]);
    }
}
