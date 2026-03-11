<?php

namespace App\Http\Controllers\API;

use App\Models\Livestock;
use App\Models\Pen;
use App\Models\Feed;
use App\Models\Prediction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        $totalLivestock = Livestock::where('status', true)->count();
        $totalPens = Pen::where('status', 'active')->count();
        $totalCapacity = Pen::sum('capacity');
        $totalOccupancy = Livestock::where('status', true)->count();
        $occupancyRate = $totalCapacity > 0 ? round(($totalOccupancy / $totalCapacity) * 100, 2) : 0;

        $totalFeedTypes = Feed::where('is_active', true)->count();
        $totalFeedStock = Feed::sum('current_stock');
        $lowStockFeeds = Feed::all()->filter->is_stock_low->count();

        // Perbaikan: gunakan DB::raw dengan SELECT SUM(...)
        $totalFeedValue = Feed::select(DB::raw('SUM(current_stock * price_per_kg) as total'))->value('total') ?? 0;

        $recentGains = Livestock::where('status', true)
            ->with('weightRecords')
            ->get()
            ->map(fn($l) => $l->average_daily_gain)
            ->filter();
        $avgDailyGain = $recentGains->count() > 0 ? round($recentGains->avg(), 3) : 0;

        $recentPredictions = Prediction::with('livestock')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'livestock_ear_tag' => $p->livestock?->ear_tag,
                'predicted_gain' => $p->predicted_gain,
                'confidence' => $p->confidence,
                'created_at' => $p->created_at->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_livestock' => $totalLivestock,
                    'total_pens' => $totalPens,
                    'total_feed_types' => $totalFeedTypes,
                    'total_feed_stock_kg' => round($totalFeedStock, 2),
                    'total_feed_value' => round($totalFeedValue, 2),
                    'low_stock_feeds' => $lowStockFeeds,
                    'average_daily_gain' => $avgDailyGain,
                    'occupancy_rate' => $occupancyRate,
                ],
                'alerts' => $this->getAlerts(),
                'recent_predictions' => $recentPredictions,
                'recent_activity' => $this->getRecentActivity(),
            ]
        ]);
    }

    public function penAnalytics(): JsonResponse
    {
        $pens = Pen::withCount(['livestocks as occupancy' => fn($q) => $q->where('status', true)])->get();

        $chartData = [
            'labels' => $pens->pluck('name'),
            'data' => $pens->pluck('occupancy'),
            'capacity' => $pens->pluck('capacity'),
        ];

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }

    protected function getAlerts(): array
    {
        $alerts = [];

        $lowStockFeeds = Feed::all()->filter->is_stock_low;
        foreach ($lowStockFeeds as $feed) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => "Stok {$feed->name} tersisa {$feed->current_stock} kg",
                'suggestion' => 'Segera lakukan restok.',
            ];
        }

        $pens = Pen::withCount(['livestocks as occupancy' => fn($q) => $q->where('status', true)])->get();
        foreach ($pens as $pen) {
            if ($pen->capacity > 0 && ($pen->occupancy / $pen->capacity) >= 0.9) {
                $alerts[] = [
                    'severity' => 'info',
                    'message' => "Kandang {$pen->name} hampir penuh ({$pen->occupancy}/{$pen->capacity})",
                    'suggestion' => 'Pertimbangkan untuk menambah kandang baru atau memindahkan ternak.',
                ];
            }
        }

        return $alerts;
    }

    protected function getRecentActivity(): array
    {
        $recentLivestocks = Livestock::latest()->limit(5)->get()->map(fn($l) => [
            'type' => 'livestock_added',
            'description' => "Ternak {$l->ear_tag} ditambahkan",
            'time' => $l->created_at->diffForHumans(),
            'icon' => 'plus',
            'color' => 'green',
        ]);

        $recentWeights = \App\Models\WeightRecord::with('livestock')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($w) => [
                'type' => 'weight_recorded',
                'description' => "Berat {$w->livestock?->ear_tag}: {$w->weight_kg} kg",
                'time' => $w->created_at->diffForHumans(),
                'icon' => 'weight',
                'color' => 'blue',
            ]);

        $activities = $recentLivestocks->concat($recentWeights)->sortByDesc('time')->values()->take(10);
        return $activities->toArray();
    }
}
