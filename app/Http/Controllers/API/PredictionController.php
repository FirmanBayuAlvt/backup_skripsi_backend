<?php

namespace App\Http\Controllers\API;

use App\Models\Livestock;
use App\Models\Prediction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PredictionRequest;
use App\Http\Resources\PredictionResource;
use App\Services\MLService;
use Illuminate\Http\JsonResponse;

class PredictionController extends Controller
{
    protected $mlService;

    public function __construct(MLService $mlService)
    {
        $this->mlService = $mlService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Prediction::with('livestock');

        if ($request->has('livestock_id')) {
            $query->where('livestock_id', $request->livestock_id);
        }

        $predictions = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'predictions' => PredictionResource::collection($predictions),
                'pagination' => [
                    'current_page' => $predictions->currentPage(),
                    'per_page' => $predictions->perPage(),
                    'total' => $predictions->total(),
                    'last_page' => $predictions->lastPage(),
                ]
            ]
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 5);
        $predictions = Prediction::with('livestock')
            ->latest()
            ->limit($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'predictions' => PredictionResource::collection($predictions)
            ]
        ]);
    }

    public function predict(PredictionRequest $request): JsonResponse
    {
        $livestock = Livestock::with('pen')->findOrFail($request->livestock_id);

        $features = [
            'current_weight' => $livestock->current_weight,
            'age_days' => $livestock->age_days,
            'breed_type' => $livestock->breed_type,
            'health_status' => $livestock->health_status,
            // Data pakan bisa diambil dari feeding records jika ada
            'feed_silase_kg' => 2.5,
            'feed_cf_jember_kg' => 1.2,
            'feed_jagung_halus_kg' => 0.8,
        ];

        $mlResult = $this->mlService->predict($features);

        if (!$mlResult) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan prediksi tidak tersedia. Silakan coba lagi nanti.'
            ], 503);
        }

        $prediction = Prediction::create([
            'livestock_id' => $livestock->id,
            'prediction_days' => $request->prediction_days,
            'predicted_gain' => $mlResult['predicted_gain'],
            'confidence' => $mlResult['confidence'] ?? 0.85,
            'interval_lower' => $mlResult['interval']['lower'] ?? $mlResult['predicted_gain'] * 0.9,
            'interval_upper' => $mlResult['interval']['upper'] ?? $mlResult['predicted_gain'] * 1.1,
            'recommendations' => $this->generateRecommendations($mlResult, $livestock),
            'input_features' => $features,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prediksi berhasil dijalankan.',
            'data' => new PredictionResource($prediction->load('livestock'))
        ]);
    }

    public function correlation(): JsonResponse
    {
        // Data dummy, bisa diganti dengan analisis nyata
        return response()->json([
            'success' => true,
            'data' => [
                'feed_weight_correlation' => 0.85,
                'factors' => [
                    'Jenis Pakan' => 0.78,
                    'Kondisi Kandang' => 0.65,
                    'Kesehatan' => 0.72,
                    'Umur' => 0.68,
                ],
                'analysis_period' => '6_bulan_terakhir',
            ]
        ]);
    }

    protected function generateRecommendations(array $mlResult, Livestock $livestock): array
    {
        $recs = [];

        if ($mlResult['predicted_gain'] < 0.1) {
            $recs[] = 'Pertumbuhan lambat. Pertimbangkan untuk meningkatkan kualitas pakan.';
        } elseif ($mlResult['predicted_gain'] > 0.25) {
            $recs[] = 'Pertumbuhan sangat baik. Pertahankan manajemen pakan.';
        }

        if (($mlResult['confidence'] ?? 0) < 0.7) {
            $recs[] = 'Tingkat kepercayaan prediksi rendah. Periksa kelengkapan data.';
        }

        if (empty($recs)) {
            $recs[] = 'Tidak ada rekomendasi khusus.';
        }

        return $recs;
    }
}
