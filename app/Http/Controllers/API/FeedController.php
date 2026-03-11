<?php

namespace App\Http\Controllers\API;

use App\Models\Feed;
use App\Models\Livestock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\FeedRequest;
use App\Http\Resources\FeedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Feed::query();

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $feeds = $query->paginate($request->input('per_page', 15));

        // Perbaikan: hitung total nilai stok dengan DB::raw
        $totalStockValue = Feed::select(DB::raw('SUM(current_stock * price_per_kg) as total'))->value('total') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'feed_types' => FeedResource::collection($feeds),
                'total_types' => Feed::count(),
                'low_stock_count' => Feed::all()->filter->is_stock_low->count(),
                'stock_summary' => [
                    'total_stock_kg' => Feed::sum('current_stock'),
                    'total_value' => $totalStockValue,
                ],
                'pagination' => [
                    'current_page' => $feeds->currentPage(),
                    'per_page' => $feeds->perPage(),
                    'total' => $feeds->total(),
                    'last_page' => $feeds->lastPage(),
                ]
            ]
        ]);
    }
    public function store(FeedRequest $request): JsonResponse
    {
        $feed = Feed::create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Pakan berhasil ditambahkan.',
            'data' => new FeedResource($feed)
        ], 201);
    }

    public function show(Feed $feed): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new FeedResource($feed)
        ]);
    }

    public function update(FeedRequest $request, Feed $feed): JsonResponse
    {
        $feed->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Pakan berhasil diperbarui.',
            'data' => new FeedResource($feed)
        ]);
    }

    public function destroy(Feed $feed): JsonResponse
    {
        $feed->update(['is_active' => false]);
        return response()->json([
            'success' => true,
            'message' => 'Pakan berhasil dinonaktifkan.'
        ]);
    }

      public function stockSummary(): JsonResponse
    {
        $feeds = Feed::where('is_active', true)->get();

        $totalStockValue = Feed::where('is_active', true)
            ->select(DB::raw('SUM(current_stock * price_per_kg) as total'))
            ->value('total') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'feed_types' => FeedResource::collection($feeds),
                'low_stock_alerts' => $feeds->filter->is_stock_low->values()->map(fn($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'current_stock' => $f->current_stock,
                    'category' => $f->category,
                ]),
                'stock_summary' => [
                    'total_stock_kg' => $feeds->sum('current_stock'),
                    'total_value' => $totalStockValue,
                    'low_stock_count' => $feeds->filter->is_stock_low->count(),
                ],
            ]
        ]);
    }

    public function requirements(): JsonResponse
    {
        $totalWeight = Livestock::where('status', true)->sum('current_weight');
        $dailyRequirement = $totalWeight * 0.03; // 3% dari berat badan

        $composition = [
            'silase' => $dailyRequirement * 0.5,
            'cf_jember' => $dailyRequirement * 0.3,
            'jagung_halus' => $dailyRequirement * 0.2,
        ];

        $costPerDay = 0;
        $feeds = Feed::where('is_active', true)->get();
        foreach ($composition as $category => $kg) {
            $feed = $feeds->firstWhere('category', $category);
            if ($feed) {
                $costPerDay += $kg * ($feed->price_per_kg ?? 0);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'requirements' => [
                    'daily' => [
                        'total_kg' => round($dailyRequirement, 2),
                        'composition' => array_map('round', $composition),
                        'cost' => round($costPerDay, 2),
                    ],
                    'weekly' => [
                        'total_kg' => round($dailyRequirement * 7, 2),
                        'cost' => round($costPerDay * 7, 2),
                    ],
                    'monthly' => [
                        'total_kg' => round($dailyRequirement * 30, 2),
                        'cost' => round($costPerDay * 30, 2),
                    ],
                ],
                'current_stock' => FeedResource::collection($feeds),
                'stock_coverage' => [
                    'silase' => $composition['silase'] > 0 ? round(($feeds->firstWhere('category', 'silase')?->current_stock ?? 0) / $composition['silase'], 1) : 0,
                    'cf_jember' => $composition['cf_jember'] > 0 ? round(($feeds->firstWhere('category', 'cf_jember')?->current_stock ?? 0) / $composition['cf_jember'], 1) : 0,
                    'jagung_halus' => $composition['jagung_halus'] > 0 ? round(($feeds->firstWhere('category', 'jagung_halus')?->current_stock ?? 0) / $composition['jagung_halus'], 1) : 0,
                ],
            ]
        ]);
    }

    public function recordFeeding(Request $request): JsonResponse
    {
        $request->validate([
            'feed_id' => 'required|exists:feeds,id',
            'quantity_kg' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $feed = Feed::findOrFail($request->feed_id);
        $feed->decrement('current_stock', $request->quantity_kg);

        return response()->json([
            'success' => true,
            'message' => 'Pemberian pakan berhasil dicatat.',
            'data' => new FeedResource($feed)
        ]);
    }

    public function updateStock(Request $request): JsonResponse
    {
        $request->validate([
            'feed_id' => 'required|exists:feeds,id',
            'add_stock_kg' => 'required|numeric|min:0',
            'price_per_kg' => 'nullable|numeric|min:0',
        ]);

        $feed = Feed::findOrFail($request->feed_id);
        $feed->increment('current_stock', $request->add_stock_kg);

        if ($request->has('price_per_kg')) {
            $feed->update(['price_per_kg' => $request->price_per_kg]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stok pakan berhasil diperbarui.',
            'data' => new FeedResource($feed)
        ]);
    }
}
